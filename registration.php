<?php
session_start();
$isError = isset($_SESSION['error']);
$modalTitle = $isError ? "Error!" : "Successfully Created !";
$modalMessage = $isError ? $_SESSION['error'] : "You can now go to the login page.";
$icon = $isError
  ? '<svg width="60" height="60" viewBox="0 0 60 60"><circle cx="30" cy="30" r="28" fill="#ffebee" stroke="#e32b2b" stroke-width="3"/><line x1="20" y1="20" x2="40" y2="40" stroke="#e32b2b" stroke-width="4" stroke-linecap="round"/><line x1="40" y1="20" x2="20" y2="40" stroke="#e32b2b" stroke-width="4" stroke-linecap="round"/></svg>'
  : '<svg width="60" height="60" viewBox="0 0 60 60"><circle cx="30" cy="30" r="28" fill="#e8f5e9" stroke="#2ecc40" stroke-width="3"/><polyline points="18,32 28,42 44,22" fill="none" stroke="#2ecc40" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$btnClass = $isError ? "btn btn-error" : "btn btn-success";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - Secure Cloud Storage</title>
  <!-- Google Fonts (optional) -->
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap"
    rel="stylesheet"
  />
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

    /* Main container with background image of server racks */
    main {
      flex: 1;
      background: url('https://c4.wallpaperflare.com/wallpaper/77/154/473/server-room-lights-dark-wallpaper-preview.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    /* Registration Card */
    .register-container {
      background-color: #fff;
      width: 400px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      padding: 2rem;
      text-align: center;
    }
    .register-container .profile-icon {
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
    .register-container h2 {
      margin-bottom: 1.5rem;
      color: #333;
      font-weight: 600;
    }
    .register-container .form-group {
      text-align: left;
      margin-bottom: 1rem;
    }
    .register-container .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      font-size: 0.9rem;
      color: #333;
    }
    .register-container .form-group input {
      width: 100%;
      padding: 0.6rem;
      font-size: 0.9rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .register-container .btn {
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
    .register-container .btn:hover {
      background-color: #c12727;
    }
    .register-container a {
      color: #e01818;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .register-container a:hover {
      text-decoration: underline;
    }

    /* Footer (optional) */
    footer {
      background-color: #222;
      color: #fff;
      text-align: center;
      padding: 0.5rem;
    }

    /* Add styles for error and success messages */
    .alert {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
      text-align: left;
    }
    .alert-error {
      background-color: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }
    .alert-success {
      background-color: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 90%;
      max-width: 400px;
      border-radius: 8px;
      text-align: center;
      position: relative;
    }
    .close {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover,
    .close:focus {
      color: #e32b2b;
      text-decoration: none;
      cursor: pointer;
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
  </style>
</head>
<body>
  <!-- Modal Popup for Messages -->
  <?php if(isset($_SESSION['error']) || isset($_SESSION['message'])): ?>
    <div id="messageModal" class="modal" style="display:block;">
      <div class="modal-content custom-modal-content">
        <div class="modal-checkmark">
          <?php echo $icon; ?>
        </div>
        <h2 style="margin: 0 0 10px 0;"><?php echo $modalTitle; ?></h2>
        <p style="margin-bottom: 20px;">
          <?php echo $modalMessage; ?>
        </p>
        <button id="okModalBtn" class="<?php echo $btnClass; ?>" style="width: 100px; margin: 0 auto;">OK</button>
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
    <?php unset($_SESSION['error']); unset($_SESSION['message']); ?>
  <?php endif; ?>

  <!-- Header / Navigation -->
  <header>
    <div class="logo">SECURE CLOUD STORAGE</div>
    <nav>
      <a href="#">Home</a>
      <a href="#">User</a>
      <a href="#">FAQ</a>
    </nav>
  </header>

  <!-- Main Content -->
  <main>
    <div class="register-container">
      <div class="profile-icon">
        <!-- A user icon can be placed here, e.g., from Google Material Icons -->
        <span class="material-icons">person</span>
      </div>
      <h2>Register</h2>
      <form action="createAccount.php" method="POST">
        <div class="form-group">
          <label for="firstName">First Name<sup>*</sup></label>
          <input
            type="text"
            id="firstName"
            name="firstName"
            placeholder="Enter Your First Name"
            required
            minlength="2"
            maxlength="50"
          />
        </div>
        <div class="form-group">
          <label for="lastName">Last Name<sup>*</sup></label>
          <input
            type="text"
            id="lastName"
            name="lastName"
            placeholder="Enter Your Last Name"
            required
            minlength="2"
            maxlength="50"
          />
        </div>
        <div class="form-group">
          <label for="newPassword">New Password<sup>*</sup></label>
          <input
            type="password"
            id="newPassword"
            name="newPassword"
            placeholder="Ex: ABC@123"
            required
            minlength="6"
          />
        </div>
        <div class="form-group">
          <label for="email">E-Mail<sup>*</sup></label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter Your E-Mail (e.g., abcd@gmail.com)"
            required
          />
        </div>
        <div class="form-group">
          <label for="contactNumber">Contact Number<sup>*</sup></label>
          <input
            type="tel"
            id="contactNumber"
            name="contactNumber"
            placeholder="Enter Contact Number (e.g., +94xxxxxxxxx)"
            required
            pattern="[+]?[0-9]{10,15}"
          />
        </div>
        <button type="submit" class="btn">Sign Up</button>
      </form>
      <div>
        <a href="Login.php">Return to Login Page</a>
      </div>
    </div>
  </main>

  <!-- Footer (optional) -->
  <footer>
    &copy; 2025 SECURE CLOUD STORAGE
  </footer>
</body>
</html>
