<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}
require 'vendor/autoload.php';
require 'aws-config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Initialize S3 client using constants
$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => AWS_REGION,
    'credentials' => [
        'key'    => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS_KEY,
    ]
]);
$bucket = AWS_BUCKET_NAME;

// Function to verify user credentials
function verifyUserCredentials($email, $password) {
    try {
        $conn = new mysqli('localhost', 'root', '', 'secure_cloud_storage');
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT password, is_active FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['is_active'] != 1) {
                return ['success' => false, 'message' => 'Your account is not active. Please check your email for activation instructions.'];
            }
            if (password_verify($password, $user['password'])) {
                return ['success' => true];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error verifying credentials: ' . $e->getMessage()];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
}

// Function to store encrypted file in S3 and metadata in MySQL
function storeEncryptedFile($s3, $bucket, $encryptedData, $email, $originalFilename, $fileSize, $mimeType) {
    try {
        $conn = new mysqli('localhost', 'root', '', 'secure_cloud_storage');
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Generate unique file ID
        $fileId = uniqid() . '_' . time();

        // Create the directory if it doesn't exist
        $s3Key = "files/{$email}/{$fileId}";

        // Store encrypted file in S3
        $s3->putObject([
            'Bucket'     => $bucket,
            'Key'        => $s3Key,
            'Body'       => file_get_contents($encryptedData['encryptedFile']),
            'ACL'        => 'private'
        ]);

        // Store metadata in MySQL
        $stmt = $conn->prepare("INSERT INTO files (file_id, user_email, original_filename, file_size, s3_key, encrypted_aes_key, encrypted_aes_key_iv, file_iv, ephemeral_public_key, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssissssss", 
            $fileId,
            $email,
            $originalFilename,
            $fileSize,
            $s3Key,
            $encryptedData['encryptedAESKey'],
            $encryptedData['encryptedAESKeyIv'],
            $encryptedData['fileIv'],
            $encryptedData['ephemeralPublicKey'],
            $mimeType
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to store file metadata: " . $stmt->error);
        }

        // Prepare private key data for local storage
        $privateKeyData = [
            'fileId' => $fileId,
            'encryptedPrivateKey' => $encryptedData['encryptedPrivateKey'],
            'privateKeyIv' => $encryptedData['privateKeyIv'],
            'salt' => $encryptedData['salt']
        ];

        return [
            'success' => true,
            'message' => 'File encrypted and uploaded successfully',
            'fileId' => $fileId,
            'privateKeyData' => $privateKeyData
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error storing encrypted file: ' . $e->getMessage()
        ];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
}

// Handle the upload request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First verify user credentials
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $authResult = verifyUserCredentials($email, $password);
    
    if (!$authResult['success']) {
        header('Content-Type: application/json');
        echo json_encode($authResult);
        exit;
    }
    
    // Prepare encrypted data
    $encryptedData = [
        'encryptedFile' => $_FILES['encryptedFile']['tmp_name'],
        'encryptedAESKey' => $_POST['encryptedAESKey'],
        'encryptedAESKeyIv' => $_POST['encryptedAESKeyIv'],
        'ephemeralPublicKey' => $_POST['ephemeralPublicKey'],
        'encryptedPrivateKey' => $_POST['encryptedPrivateKey'],
        'fileIv' => $_POST['fileIv'],
        'privateKeyIv' => $_POST['privateKeyIv'],
        'salt' => $_POST['salt']
    ];
    
    // Use the original file name sent from the client, with fallback
    if (isset($_POST['originalFilename']) && !empty($_POST['originalFilename'])) {
        $originalFilename = $_POST['originalFilename'];
    } else {
        // Log the error for debugging
        error_log('originalFilename not set in POST: ' . print_r($_POST, true));
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Upload failed: original file name not received from client."
        ]);
        exit;
    }
    $fileSize = $_FILES['encryptedFile']['size'];
    $mimeType = $_FILES['encryptedFile']['type'] ?? 'application/octet-stream';

    // Store encrypted file and metadata
    $response = storeEncryptedFile(
        $s3, 
        $bucket, 
        $encryptedData, 
        $email,
        $originalFilename,
        $fileSize,
        $mimeType
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 