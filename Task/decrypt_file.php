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

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Support both JSON and form POST for fileId
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = $input['fileId'] ?? null;
} else {
    $fileId = $_POST['fileId'] ?? null;
}
if (!$fileId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File ID is required']);
    exit;
}

try {
    // Initialize S3 client
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ]
    ]);

    // Get file metadata from database
    $conn = new mysqli('localhost', 'root', '', 'secure_cloud_storage');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM files WHERE file_id = ? AND user_email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $fileId, $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("File not found or access denied");
    }

    $file = $result->fetch_assoc();

    // Get encrypted file from S3
    $encryptedFile = $s3->getObject([
        'Bucket' => AWS_BUCKET_NAME,
        'Key'    => $file['s3_key']
    ]);

    // Return the encrypted file and metadata for client-side decryption
    header('Content-Type: application/json');
    echo json_encode([
        'encryptedFile' => base64_encode($encryptedFile['Body']->getContents()),
        'encryptedAesKey' => $file['encrypted_aes_key'],
        'encryptedAESKeyIv' => $file['encrypted_aes_key_iv'],
        'ephemeralPublicKey' => $file['ephemeral_public_key'],
        'iv' => $file['file_iv'],
        'originalFilename' => $file['original_filename'],
        'mimeType' => $file['mime_type']
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
} 