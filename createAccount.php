<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the beginning
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "secure_cloud_storage";

// Validate form submission
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['error'] = "Invalid request method";
    header("Location: registration.php");
    exit();
}

// Validate required fields
$required_fields = ['firstName', 'lastName', 'email', 'newPassword', 'contactNumber'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "All fields are required";
        header("Location: registration.php");
        exit();
    }
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("Location: registration.php");
    exit();
}

// Validate password length
if (strlen($_POST['newPassword']) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters long";
    header("Location: registration.php");
    exit();
}

try {
    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Sanitize and get form data
    $firstName = $conn->real_escape_string(trim($_POST['firstName']));
    $lastName = $conn->real_escape_string(trim($_POST['lastName']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['newPassword'];
    $contactNumber = $conn->real_escape_string(trim($_POST['contactNumber']));

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already registered";
        header("Location: registration.php");
        exit();
    }
    $check_email->close();

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, contact_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $contactNumber);

    // Execute the statement
    if ($stmt->execute()) {
        // Send email notification
        $to = $email;
        $subject = "Your Secure Cloud Storage Password Has Been Reset";
        $message = "Hello,\n\nYour password has been successfully reset. If you did not request this change, please contact support immediately.\n\nThank you,\nSecure Cloud Storage Team";
        $headers = "From: no-reply@yourdomain.com\r\n";

        @mail($to, $subject, $message, $headers);

        $_SESSION['message'] = "Registration successful! You can now log in.";
        header("Location: registration.php");
        exit();
    } else {
        throw new Exception("Registration failed: " . $stmt->error);
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: registration.php");
    exit();
} finally {
    // Close statement and connection if they exist
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 