<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>File Directory - Secure Cloud Storage</title>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <!-- Styles -->
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Open Sans', sans-serif;
    }
    body {
      background-color: #f1f1f1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      background-color: #222;
      padding: 0.75rem 1rem;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header .logo {
      font-weight: bold;
      font-size: 1.2rem;
    }
    header nav a {
      color: #fff;
      margin-left: 1rem;
      text-decoration: none;
      font-weight: 500;
    }
    header nav a:hover {
      text-decoration: underline;
    }
    main {
        flex: 1;
      background: url('https://c4.wallpaperflare.com/wallpaper/77/154/473/server-room-lights-dark-wallpaper-preview.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      flex: 2;
      padding: 2rem;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    .container h2 {
      margin-bottom: 1.5rem;
      font-size: 1.5rem;
      text-align: center;
      color: #333;
    }
    .table-container {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1rem;
    }
    table th, table td {
      text-align: left;
      padding: 0.75rem;
      border: 1px solid #ddd;
    }
    table th {
      background-color: #f4f4f4;
    }
    table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 4px;
      color: #fff;
      cursor: pointer;
    }
    .btn-decrypt {
      background-color: #333;
    }
    .btn-decrypt:hover {
      background-color: #444;
    }
    .btn-download {
      background-color: #007bff;
    }
    .btn-download:hover {
      background-color: #0056b3;
    }
    .btn-delete {
      background-color: #e32b2b;
    }
    .btn-delete:hover {
      background-color: #c12727;
    }
    .pagination {
      display: flex;
      justify-content: center;
      gap: 1rem;
      font-size: 0.9rem;
    }
    .pagination a {
      text-decoration: none;
      color: #333;
      padding: 0.25rem 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .pagination a.active {
      background-color: #333;
      color: #fff;
    }
    footer {
      background-color: #222;
      color: #fff;
      text-align: center;
      padding: 0.5rem;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">SECURE CLOUD STORAGE</div>
    <nav>
      <a href="#">Home</a>
      <a href="#">User</a>
      <a href="#">Token</a>
      <a href="#">FAQ</a>
      <a href="logout.php" class="btn btn-logout" style="background-color: #e32b2b; color: #fff; text-decoration: none; padding: 0.4rem 1rem; border-radius: 4px; font-weight: 600; margin-left: 1rem;">Logout</a>
    </nav>
  </header>

  <!-- Main Content -->
  <main>
    <div class="container">
      <h2>All Files</h2>
      <div style="text-align: right; margin-bottom: 1rem;">
        <a href="upload.php" class="btn btn-upload" style="background-color: #28a745; color: #fff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; font-weight: 600;">Upload File</a>
      </div>
      <div class="table-container">
        <table id="filesTable">
          <thead>
            <tr>
              <th>User</th>
              <th>File Name</th>
              <th>File Size</th>
              <th>Uploaded Date</th>
              <th>Decrypt</th>
              <th>Download</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            <!-- Files will be loaded here dynamically -->
          </tbody>
        </table>
      </div>
      <div class="pagination" id="pagination">
        <!-- Pagination will be added here -->
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    Email: <a href="securecloud@gmail.com">securecloud@gmail.com</a> <br />
    Call: +94-774885956 <br />
    &copy; 2022 SECURE CLOUD STORAGE. All Rights Reserved. Concept designed and developed by Shanka.
  </footer>

  <script>
    // Function to format file size
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Function to format date
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString();
    }

    // Track decrypted files in session
    const decryptedFiles = new Set();
    let lastDecryptedFileId = null;
    let lastDecryptedBlob = null;

    // Function to load files
    async function loadFiles() {
      try {
        const response = await fetch('get_files.php');
        const data = await response.json();
        
        const tbody = document.querySelector('#filesTable tbody');
        tbody.innerHTML = '';
        
        data.forEach(file => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${file.user_email}</td>
            <td>${file.original_filename}</td>
            <td>${formatFileSize(file.file_size)}</td>
            <td>${formatDate(file.uploaded_at)}</td>
            <td>
              <button class="btn btn-decrypt" onclick="decryptFile('${file.file_id}', '${file.original_filename}')">Decrypt</button>
            </td>
            <td>
              <button class="btn btn-download" onclick="downloadFile('${file.file_id}', '${file.original_filename}')">Download</button>
            </td>
            <td>
              <button class="btn btn-delete" onclick="deleteFile('${file.file_id}')">Delete</button>
            </td>
          `;
          tbody.appendChild(row);
        });
      } catch (error) {
        console.error('Error loading files:', error);
      }
    }

    // Helper: prompt for private key file and return as Uint8Array or parsed JSON, with debug logs
    function promptForPrivateKeyFile() {
      return new Promise((resolve, reject) => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json,.dat,.bin,.key,.txt,application/json,application/octet-stream';
        input.onchange = () => {
          const file = input.files[0];
          if (!file) {
            alert('No private key file selected.');
            return reject('No file selected');
          }
          const reader = new FileReader();
          reader.onload = function(e) {
            try {
              // Try to parse as JSON, fallback to Uint8Array (from ArrayBuffer)
              let result;
              try {
                result = JSON.parse(e.target.result);
                console.log('Parsed as JSON:', result);
                resolve(result);
              } catch {
                // If not JSON, try as ArrayBuffer
                const reader2 = new FileReader();
                reader2.onload = function(ev) {
                  console.log('Read as ArrayBuffer');
                  resolve(new Uint8Array(ev.target.result));
                };
                reader2.onerror = () => {
                  alert('Failed to read private key file');
                  reject('Failed to read private key file');
                };
                reader2.readAsArrayBuffer(file);
                return;
              }
            } catch (err) {
              alert('Failed to read private key file');
              reject('Failed to read private key file');
            }
          };
          reader.onerror = () => {
            alert('Failed to read private key file');
            reject('Failed to read private key file');
          };
          reader.readAsText(file); // Try as text first
        };
        input.click();
      });
    }

    // Utility: base64 to Uint8Array
    function base64ToUint8Array(base64) {
      const binary_string = atob(base64);
      const len = binary_string.length;
      const bytes = new Uint8Array(len);
      for (let i = 0; i < len; i++) {
        bytes[i] = binary_string.charCodeAt(i);
      }
      return bytes;
    }

    // Full decryption process (modified: do not auto-download, just return the Blob)
    async function fullDecryptProcess({
      encryptedFileB64,
      encryptedAESKeyB64,
      encryptedAESKeyIvB64,
      ephemeralPublicKeyB64,
      fileIvB64,
      encryptedPrivateKeyB64,
      privateKeyIvB64,
      saltB64,
      fileToken,
      privateKeyFileData,
      originalFileName,
      mimeType = 'application/octet-stream'
    }) {
      // 1. Decrypt ECC private key with PBKDF2
      const encryptedPrivateKeyBytes = base64ToUint8Array(encryptedPrivateKeyB64);
      const privateKeyIvBytes = base64ToUint8Array(privateKeyIvB64);
      const saltBytes = base64ToUint8Array(saltB64);
      const encoder = new TextEncoder();
      const keyMaterial = await window.crypto.subtle.importKey(
        'raw',
        encoder.encode(fileToken),
        { name: 'PBKDF2' },
        false,
        ['deriveBits', 'deriveKey']
      );
      const pbkdf2Key = await window.crypto.subtle.deriveKey(
        {
          name: 'PBKDF2',
          salt: saltBytes,
          iterations: 100000,
          hash: 'SHA-256'
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        true,
        ['decrypt']
      );
      const decryptedPrivateKeyBuffer = await window.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: privateKeyIvBytes },
        pbkdf2Key,
        encryptedPrivateKeyBytes
      );
      const eccPrivateKey = await window.crypto.subtle.importKey(
        'pkcs8',
        decryptedPrivateKeyBuffer,
        { name: 'ECDH', namedCurve: 'P-384' },
        true,
        ['deriveKey', 'deriveBits']
      );
      // 2. Decrypt AES key with ECC private key (ECDH)
      const ephemeralPublicKeyBytes = base64ToUint8Array(ephemeralPublicKeyB64);
      const encryptedAESKeyBytes = base64ToUint8Array(encryptedAESKeyB64);
      const aesKeyIvBytes = base64ToUint8Array(encryptedAESKeyIvB64);
      const ephemeralPublicKey = await window.crypto.subtle.importKey(
        'spki',
        ephemeralPublicKeyBytes,
        { name: 'ECDH', namedCurve: 'P-384' },
        true,
        []
      );
      const sharedSecret = await window.crypto.subtle.deriveKey(
        {
          name: 'ECDH',
          public: ephemeralPublicKey
        },
        eccPrivateKey,
        { name: 'AES-GCM', length: 256 },
        true,
        ['decrypt']
      );
      const decryptedAesKeyRaw = await window.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: aesKeyIvBytes },
        sharedSecret,
        encryptedAESKeyBytes
      );
      const aesKey = await window.crypto.subtle.importKey(
        'raw',
        decryptedAesKeyRaw,
        { name: 'AES-GCM' },
        true,
        ['decrypt']
      );
      // 3. Decrypt the file with the AES key
      const encryptedFileBytes = base64ToUint8Array(encryptedFileB64);
      const fileIvBytes = base64ToUint8Array(fileIvB64);
      const decryptedFileBuffer = await window.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: fileIvBytes },
        aesKey,
        encryptedFileBytes
      );
      // 4. Return the Blob (do not auto-download)
      return new Blob([decryptedFileBuffer], { type: mimeType });
    }

    // Function to decrypt file
    async function decryptFile(fileId, fileName) {
      const token = prompt('Enter the file token:');
      if (!token) return;
      let privateKeyData;
      try {
        privateKeyData = await promptForPrivateKeyFile();
      } catch (err) {
        alert(err);
        return;
      }
      try {
        alert('Fetching encrypted file and metadata from server...');
        const response = await fetch('decrypt_file.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ fileId: fileId })
        });
        const result = await response.json();
        if (result.error) {
          alert(result.error);
          return;
        }
        alert('Decrypting file...');
        // Call the full decryption process and store the Blob for download
        const decryptedBlob = await fullDecryptProcess({
          encryptedFileB64: result.encryptedFile,
          encryptedAESKeyB64: result.encryptedAesKey,
          encryptedAESKeyIvB64: result.encryptedAESKeyIv,
          ephemeralPublicKeyB64: result.ephemeralPublicKey,
          fileIvB64: result.iv,
          encryptedPrivateKeyB64: privateKeyData.encryptedPrivateKey,
          privateKeyIvB64: privateKeyData.privateKeyIv,
          saltB64: privateKeyData.salt,
          fileToken: token,
          privateKeyFileData: privateKeyData,
          originalFileName: result.originalFilename || fileName,
          mimeType: result.mimeType || 'application/octet-stream'
        });
        lastDecryptedFileId = fileId;
        lastDecryptedBlob = decryptedBlob;
        decryptedFiles.add(fileId);
        alert('Decryption is completed, download the file');
      } catch (error) {
        alert('Decryption failed: ' + error);
      }
    }

    // Function to download file
    function downloadFile(fileId, fileName) {
      if (!decryptedFiles.has(fileId)) {
        alert('Please Decrypt the file first!');
        return;
      }
      if (lastDecryptedFileId === fileId && lastDecryptedBlob) {
        const url = URL.createObjectURL(lastDecryptedBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName || 'decrypted_file';
        document.body.appendChild(a);
        a.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        document.body.removeChild(a);
      } else {
        alert('Decrypted file not found in session. Please decrypt again.');
      }
    }

    // Function to delete file
    async function deleteFile(fileId) {
      if (!confirm('Are you sure you want to delete this file?')) return;

      try {
        const formData = new FormData();
        formData.append('fileId', fileId);

        const response = await fetch('delete_file.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        if (result.success) {
          alert('File deleted successfully!');
          loadFiles(); // Reload the file list
        } else {
          alert(result.message);
        }
      } catch (error) {
        console.error('Error deleting file:', error);
        alert('Error deleting file');
      }
    }

    // Load files when the page loads
    document.addEventListener('DOMContentLoaded', loadFiles);
  </script>
</body>
</html>
