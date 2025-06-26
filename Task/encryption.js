// Function to generate AES key
async function generateAESKey() {
    return await window.crypto.subtle.generateKey(
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );
}

// Function to generate ECC key pair
async function generateECCKeyPair() {
    return await window.crypto.subtle.generateKey(
        { name: "ECDH", namedCurve: "P-384" },
        true,
        ["deriveKey", "deriveBits"]
    );
}

// Function to derive PBKDF2 key
async function derivePBKDF2Key(password, salt) {
    const encoder = new TextEncoder();
    const passwordBuffer = encoder.encode(password);
    
    const keyMaterial = await window.crypto.subtle.importKey(
        "raw",
        passwordBuffer,
        { name: "PBKDF2" },
        false,
        ["deriveBits", "deriveKey"]
    );

    return await window.crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt: salt,
            iterations: 100000,
            hash: "SHA-256"
        },
        keyMaterial,
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );
}

// Function to encrypt file with AES
async function encryptFileWithAES(file, aesKey) {
    const fileBuffer = await file.arrayBuffer();
    const iv = window.crypto.getRandomValues(new Uint8Array(12));
    
    const encryptedData = await window.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        aesKey,
        fileBuffer
    );

    return {
        encryptedData: new Uint8Array(encryptedData),
        iv: iv
    };
}

// Function to encrypt AES key with ECC public key using ECDH key agreement
async function encryptAESKeyWithECC(aesKey, recipientPublicKey) {
    // Generate ephemeral ECDH key pair for sender
    const ephemeralKeyPair = await window.crypto.subtle.generateKey(
        { name: "ECDH", namedCurve: "P-384" },
        true,
        ["deriveKey", "deriveBits"]
    );

    // Derive shared secret using sender's ephemeral private key and recipient's public key
    const sharedSecret = await window.crypto.subtle.deriveKey(
        {
            name: "ECDH",
            public: recipientPublicKey
        },
        ephemeralKeyPair.privateKey,
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );

    // Export the AES key to raw format
    const exportedAESKey = await window.crypto.subtle.exportKey("raw", aesKey);
    // Encrypt the AES key with the shared secret
    const iv = window.crypto.getRandomValues(new Uint8Array(12));
    const encryptedAESKey = await window.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        sharedSecret,
        exportedAESKey
    );

    // Export the ephemeral public key to send to the recipient
    const exportedEphemeralPublicKey = await window.crypto.subtle.exportKey("spki", ephemeralKeyPair.publicKey);

    return {
        encryptedAESKey: new Uint8Array(encryptedAESKey),
        iv: iv,
        ephemeralPublicKey: new Uint8Array(exportedEphemeralPublicKey)
    };
}

// Function to encrypt ECC private key with PBKDF2 key
async function encryptECCPrivateKey(eccPrivateKey, pbkdf2Key) {
    const exportedPrivateKey = await window.crypto.subtle.exportKey("pkcs8", eccPrivateKey);
    
    const iv = window.crypto.getRandomValues(new Uint8Array(12));
    const encryptedPrivateKey = await window.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        pbkdf2Key,
        exportedPrivateKey
    );

    return {
        encryptedPrivateKey: new Uint8Array(encryptedPrivateKey),
        iv: iv
    };
}

function uint8ToBase64(uint8) {
    let binary = '';
    const len = uint8.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(uint8[i]);
    }
    return btoa(binary);
}

// Main encryption function
async function encryptFileForUpload(file, fileToken) {
    try {
        // Generate keys
        const aesKey = await generateAESKey();
        const eccKeyPair = await generateECCKeyPair();
        
        // Generate salt and derive PBKDF2 key
        const salt = window.crypto.getRandomValues(new Uint8Array(16));
        const pbkdf2Key = await derivePBKDF2Key(fileToken, salt);
        
        // Encrypt file with AES
        const { encryptedData, iv: fileIv } = await encryptFileWithAES(file, aesKey);
        
        // Encrypt AES key with ECC public key
        const encryptedAESKeyResult = await encryptAESKeyWithECC(aesKey, eccKeyPair.publicKey);
        
        // Encrypt ECC private key with PBKDF2 key
        const { encryptedPrivateKey, iv: privateKeyIv } = await encryptECCPrivateKey(eccKeyPair.privateKey, pbkdf2Key);
        
        // Convert everything to base64 for transmission
        return {
            encryptedFile: uint8ToBase64(encryptedData),
            encryptedAESKey: uint8ToBase64(encryptedAESKeyResult.encryptedAESKey),
            encryptedAESKeyIv: uint8ToBase64(encryptedAESKeyResult.iv),
            ephemeralPublicKey: uint8ToBase64(encryptedAESKeyResult.ephemeralPublicKey),
            encryptedPrivateKey: uint8ToBase64(encryptedPrivateKey),
            fileIv: uint8ToBase64(fileIv),
            privateKeyIv: uint8ToBase64(privateKeyIv),
            salt: uint8ToBase64(salt)
        };
    } catch (error) {
        console.error('Encryption failed:', error);
        throw error;
    }
}
