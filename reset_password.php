<?php
session_start();
$modal = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
        $modal = [
            'type' => 'error',
            'message' => 'All fields are required.'
        ];
    } elseif ($newPassword !== $confirmPassword) {
        $modal = [
            'type' => 'error',
            'message' => 'Passwords do not match.'
        ];
    } elseif (strlen($newPassword) < 6) {
        $modal = [
            'type' => 'error',
            'message' => 'Password must be at least 6 characters.'
        ];
    } else {
        $conn = new mysqli('localhost', 'root', '', 'secure_cloud_storage');
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
            $stmt->bind_param('ss', $hashed, $email);
            if ($stmt->execute()) {
                $modal = [
                    'type' => 'success',
                    'message' => 'Password reset successful! You can now log in with your new password.'
                ];
            } else {
                $modal = [
                    'type' => 'error',
                    'message' => 'Failed to reset password. Please try again.'
                ];
            }
        } else {
            $modal = [
                'type' => 'error',
                'message' => 'No account found with that email.'
            ];
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password - Secure Cloud Storage</title>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet" />
  <style>
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
    main {
      flex: 1;
      background: url('https://c4.wallpaperflare.com/wallpaper/77/154/473/server-room-lights-dark-wallpaper-preview.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }
    .reset-container {
      background-color: #fff;
      width: 350px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      padding: 2rem;
      text-align: center;
    }
    .reset-container .profile-icon {
      font-size: 60px;
      color: #fff;
      background-color: #e32b2b;
      width: 80px;
      height: 80px;
      margin: 0 auto 1rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .reset-container h2 {
      margin-bottom: 1.5rem;
      color: #333;
      font-weight: 600;
    }
    .reset-container .form-group {
      text-align: left;
      margin-bottom: 1rem;
    }
    .reset-container .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.9rem;
      color: #333;
    }
    .reset-container .form-group input {
      width: 100%;
      padding: 0.6rem;
      font-size: 0.9rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .reset-container .btn {
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
    .reset-container .btn:hover {
      background-color: #c12727;
    }
    .reset-container a {
      color: #e32b2b;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .reset-container a:hover {
      text-decoration: underline;
    }
    footer {
      background-color: #222;
      color: #fff;
      text-align: center;
      padding: 0.5rem;
    }
    .btn-success {
      background: #2ecc40 !important;
    }
    .btn-success:hover {
      background: #27ae60 !important;
    }
    .btn-error {
      background: #e32b2b !important;
    }
    .btn-error:hover {
      background: #c12727 !important;
    }
    .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .custom-modal-content { background: #fff; border-radius: 12px; box-shadow: 0 8px 32px rgba(44, 62, 80, 0.2); padding: 32px 24px 24px 24px; max-width: 350px; margin: 10% auto; text-align: center; position: relative; }
    .modal-checkmark { margin-bottom: 16px; }
  </style>
</head>
<body>
  <!-- Header / Navigation -->
  <header>
    <div class="logo">SECURE CLOUD STORAGE</div>
    <nav>
      <a href="#">Home</a>
      <a href="Login.php">Login</a>
      <a href="#">Admin</a>
    </nav>
  </header>
  <!-- Main Content -->
  <main>
    <div class="reset-container">
      <div class="profile-icon">
        <span class="material-icons">lock</span>
      </div>
      <h2>Reset Password</h2>
      <form action="reset_password.php" method="POST">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter Email" required />
        </div>
        <div class="form-group">
          <label for="newPassword">New Password</label>
          <input type="password" id="newPassword" name="newPassword" placeholder="Enter New Password" required />
        </div>
        <div class="form-group">
          <label for="confirmPassword">Re-Enter Password</label>
          <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-Enter New Password" required />
        </div>
        <button type="submit" class="btn">Reset</button>
      </form>
      <div>
        <a href="Login.php">Return to Login</a>
      </div>
    </div>
  </main>
  <footer>
    &copy; 2025 SECURE CLOUD STORAGE
  </footer>
<?php if (!empty($modal)): ?>
  <div id="messageModal" class="modal" style="display:block;">
    <div class="modal-content custom-modal-content">
      <div class="modal-checkmark">
        <?php if ($modal['type'] === 'success'): ?>
          <svg width="60" height="60" viewBox="0 0 60 60">
            <circle cx="30" cy="30" r="28" fill="#e8f5e9" stroke="#2ecc40" stroke-width="3"/>
            <polyline points="18,32 28,42 44,22" fill="none" stroke="#2ecc40" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        <?php else: ?>
          <svg width="60" height="60" viewBox="0 0 60 60">
            <circle cx="30" cy="30" r="28" fill="#ffebee" stroke="#e32b2b" stroke-width="3"/>
            <line x1="20" y1="20" x2="40" y2="40" stroke="#e32b2b" stroke-width="4" stroke-linecap="round"/>
            <line x1="40" y1="20" x2="20" y2="40" stroke="#e32b2b" stroke-width="4" stroke-linecap="round"/>
          </svg>
        <?php endif; ?>
      </div>
      <h2 style="margin: 0 0 10px 0;">
        <?php echo $modal['type'] === 'success' ? 'Success!' : 'Error!'; ?>
      </h2>
      <p style="margin-bottom: 20px;">
        <?php echo htmlspecialchars($modal['message']); ?>
      </p>
      <button id="okModalBtn" class="btn <?php echo $modal['type'] === 'success' ? 'btn-success' : 'btn-error'; ?>" style="width: 100px; margin: 0 auto;">OK</button>
    </div>
  </div>
  <script>
    document.getElementById('okModalBtn').onclick = function() {
      document.getElementById('messageModal').style.display = 'none';
    };
    window.onclick = function(event) {
      var modal = document.getElementById('messageModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    };
  </script>
<?php endif; ?>
</body>
</html> 