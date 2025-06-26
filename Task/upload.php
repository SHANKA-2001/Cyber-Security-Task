<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>File Upload - Secure Cloud Storage</title>
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap"
    rel="stylesheet"
  />
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    /* Basic Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Open Sans', sans-serif;
    }

    body {
      min-height: 100vh;
      background: #f1f1f1;
      display: flex;
      flex-direction: column;
    }

    /* Header / Navbar */
    header {
      background-color: #222;
      padding: 0.75rem 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header .logo {
      color: #fff;
      font-weight: 600;
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

    /* Main container with background image */
    main {
      flex: 1;
      background: url('https://c4.wallpaperflare.com/wallpaper/77/154/473/server-room-lights-dark-wallpaper-preview.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    /* Upload Form Card */
    .upload-container {
      background-color: #fff;
      width: 400px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      padding: 2rem;
      text-align: center;
    }
    .upload-container .profile-icon {
      font-size: 60px;
      color: #fff;
      background-color: #e32b2b; /* Red circle behind icon */
      width: 80px;
      height: 80px;
      margin: 0 auto 1rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .upload-container h2 {
      margin-bottom: 1.5rem;
      color: #333;
      font-weight: 600;
    }
    .upload-container .form-group {
      text-align: left;
      margin-bottom: 1rem;
    }
    .upload-container .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.9rem;
      color: #333;
    }
    .upload-container .form-group input[type="email"],
    .upload-container .form-group input[type="password"],
    .upload-container .form-group input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      font-size: 0.9rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .upload-container .form-group input[type="file"] {
      display: block;
      margin: 0.5rem 0;
    }
    .upload-container .btn {
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      background-color: #e32b2b;
      border: none;
      border-radius: 4px;
      color: #fff;
      cursor: pointer;
      margin: 1rem 0;
    }
    .upload-container .btn:hover {
      background-color: #c12727;
    }
    .upload-container a {
      color: #e32b2b;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .upload-container a:hover {
      text-decoration: underline;
    }

    /* Footer */
    footer {
      background-color: #222;
      color: #fff;
      text-align: center;
      padding: 0.5rem;
      font-size: 0.9rem;
    }
    footer a {
      color: #e32b2b;
      text-decoration: none;
    }
    footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">SECURE CLOUD STORAGE</div>
    <nav>
      <a href="#">Home</a>
      <a href="#">Login</a>
      <a href="#">Token</a>
      <a href="#">FAQ</a>
      <a href="logout.php" class="btn btn-logout" style="background-color: #e32b2b; color: #fff; text-decoration: none; padding: 0.4rem 1rem; border-radius: 4px; font-weight: 600; margin-left: 1rem;">Logout</a>
    </nav>
  </header>

  <!-- Main Content -->
  <main>
    <div class="upload-container">
      <div class="profile-icon">
        <span class="material-icons">cloud_upload</span>
      </div>
      <h2>Upload</h2>
      <form id="uploadForm" enctype="multipart/form-data">
        <div class="form-group">
          <label for="email">E-Mail</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter Email"
            required
          />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter Password"
            required
          />
        </div>
        <div class="form-group">
          <label for="token">File Token</label>
          <input
            type="password"
            id="token"
            name="token"
            placeholder="Enter File Token"
            required
          />
        </div>
        <div class="form-group">
          <label for="file">Choose File</label>
          <input type="file" id="file" name="file" required />
        </div>
        <button type="submit" class="btn">Upload</button>
      </form>
      <div id="uploadStatus"></div>
      <div>
        <a href="filedirectory.php">Back to File Directory</a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    Email: <a href="securecloud@gmail.com">securecloud@gmail.com</a> <br />
    Call: +94-774885956 <br />
    &copy; 2022 SECURE CLOUD STORAGE. All Rights Reserved. Concept designed and developed by Shanka.
  </footer>

  <script src="encryption.js"></script>
  <script>
    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const statusDiv = document.getElementById('uploadStatus');
      const file = document.getElementById('file').files[0];
      const fileToken = document.getElementById('token').value;
      
      if (!file || !fileToken) {
        statusDiv.textContent = 'Please select a file and enter a file token';
        statusDiv.style.color = 'red';
        return;
      }

      try {
        statusDiv.textContent = 'Encrypting file...';
        statusDiv.style.color = 'blue';

        // Encrypt the file
        const encryptedData = await encryptFileForUpload(file, fileToken);
        
        // Create form data with encrypted content
        const formData = new FormData();
        formData.append('email', document.getElementById('email').value);
        formData.append('password', document.getElementById('password').value);
        // Safe base64 to Uint8Array conversion for large files
        function base64ToUint8Array(base64) {
          const binary_string = atob(base64);
          const len = binary_string.length;
          const bytes = new Uint8Array(len);
          for (let i = 0; i < len; i++) {
            bytes[i] = binary_string.charCodeAt(i);
          }
          return bytes;
        }
        formData.append('originalFilename', file.name);
        formData.append('encryptedFile', new Blob([base64ToUint8Array(encryptedData.encryptedFile)], { type: 'application/octet-stream' }));
        formData.append('encryptedAESKey', encryptedData.encryptedAESKey);
        formData.append('encryptedAESKeyIv', encryptedData.encryptedAESKeyIv);
        formData.append('ephemeralPublicKey', encryptedData.ephemeralPublicKey);
        formData.append('encryptedPrivateKey', encryptedData.encryptedPrivateKey);
        formData.append('fileIv', encryptedData.fileIv);
        formData.append('privateKeyIv', encryptedData.privateKeyIv);
        formData.append('salt', encryptedData.salt);

        statusDiv.textContent = 'Verifying credentials...';
        const response = await fetch('upload-handler.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Save private key data locally
          const privateKeyData = result.privateKeyData;
          const privateKeyBlob = new Blob([JSON.stringify(privateKeyData)], { type: 'application/json' });
          const privateKeyURL = URL.createObjectURL(privateKeyBlob);
          const a = document.createElement('a');
          // Use the original file name in the private key file name
          const originalFileName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
          a.href = privateKeyURL;
          a.download = `private_key_${originalFileName}_${privateKeyData.fileId}.json`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(privateKeyURL);

          statusDiv.textContent = result.message;
          statusDiv.style.color = 'green';
          this.reset();
        } else {
          statusDiv.textContent = result.message;
          statusDiv.style.color = 'red';
          if (result.message.includes('Invalid email or password') || 
              result.message.includes('account is not active')) {
            document.getElementById('password').value = '';
          }
        }
      } catch (error) {
        statusDiv.textContent = 'Error: ' + error.message;
        statusDiv.style.color = 'red';
      }
    });
  </script>
</body>
</html>
