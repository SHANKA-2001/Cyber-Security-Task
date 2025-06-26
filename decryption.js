async function decryptFile() {
    const password = document.getElementById('password').value;
    const fileToken = document.getElementById('file-token').value;

    if (!password || !fileToken) {
        alert('Password and File Token are required!');
        return;
    }

    try {
        // Step 1: Retrieve the encrypted file and AES key (from cloud storage or the server)
        const response = await fetch('getEncryptedData.php');
        const encryptedData = await response.json();

        const encryptedFile = encryptedData.encryptedFile; // Base64-encoded encrypted file data
        const encryptedAESKey = encryptedData.encryptedAESKey; // Base64-encoded encrypted AES key
        const iv = new Uint8Array(atob(encryptedData.iv).split('').map(c => c.charCodeAt(0))); // Base64-encoded IV

        // Step 2: Retrieve the encrypted ECC private key file (local file storage)
        const fileInput = document.getElementById('ecc-private-key-file').files[0];
        const reader = new FileReader();
        reader.onload = async function (event) {
            const encryptedECCPrivateKey = new Uint8Array(event.target.result);

            // Step 3: Decrypt the ECC private key using PBKDF2 with the file token and password
            const salt = encryptedECCPrivateKey.slice(0, 16); // Salt was stored at the beginning of the ECC private key file
            const pbkdf2Key = await window.crypto.subtle.importKey(
                "raw",
                new TextEncoder().encode(password),
                { name: "PBKDF2" },
                false,
                ["deriveBits", "deriveKey"]
            );

            const decryptionKey = await window.crypto.subtle.deriveKey(
                { name: "PBKDF2", salt: salt, iterations: 100000, hash: "SHA-256" },
                pbkdf2Key,
                { name: "AES-GCM", length: 256 },
                false,
                ["decrypt"]
            );

            // Decrypt the ECC private key
            const decryptedECCPrivateKey = await window.crypto.subtle.decrypt(
                { name: "AES-GCM", iv: salt },
                decryptionKey,
                encryptedECCPrivateKey.slice(16)
            );

            // Step 4: Use the decrypted ECC private key to decrypt the AES key
            const importedECCPrivateKey = await window.crypto.subtle.importKey(
                "pkcs8",
                decryptedECCPrivateKey,
                { name: "ECDH", namedCurve: "P-384" },
                true,
                ["deriveKey"]
            );

            const decryptedAESKeyBuffer = await window.crypto.subtle.decrypt(
                { name: "ECDH" },
                importedECCPrivateKey,
                atob(encryptedAESKey).split('').map(c => c.charCodeAt(0))
            );

            // Step 5: Decrypt the file using the decrypted AES key
            const decryptedFileBuffer = await window.crypto.subtle.decrypt(
                { name: "AES-GCM", iv },
                await window.crypto.subtle.importKey("raw", decryptedAESKeyBuffer, { name: "AES-GCM" }, false, ["decrypt"]),
                atob(encryptedFile).split('').map(c => c.charCodeAt(0))
            );

            // Convert decrypted file buffer to text or other formats as needed
            const decryptedText = new TextDecoder().decode(decryptedFileBuffer);
            console.log(decryptedText); // Display or process the decrypted file

            alert('File decrypted successfully!');
        };

        reader.readAsArrayBuffer(fileInput);

    } catch (e) {
        console.error('Decryption failed:', e);
    }
}

function promptForPrivateKeyFile() {
  return new Promise((resolve, reject) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json,.dat,.bin,.key,.txt,application/json,application/octet-stream';
    input.onchange = () => {
      const file = input.files[0];
      if (!file) return reject('No file selected');
      const reader = new FileReader();
      reader.onload = function(e) {
        try {
          // Try to parse as JSON, fallback to Uint8Array (from ArrayBuffer)
          let result;
          try {
            result = JSON.parse(e.target.result);
          } catch {
            // If not JSON, try as ArrayBuffer
            const reader2 = new FileReader();
            reader2.onload = function(ev) {
              resolve(new Uint8Array(ev.target.result));
            };
            reader2.onerror = () => reject('Failed to read private key file');
            reader2.readAsArrayBuffer(file);
            return;
          }
          resolve(result);
        } catch (err) {
          reject('Failed to read private key file');
        }
      };
      reader.onerror = () => reject('Failed to read private key file');
      reader.readAsText(file); // Try as text first
    };
    input.click();
  });
}
