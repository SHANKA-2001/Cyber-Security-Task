<?php
session_start();
require 'vendor/autoload.php';
require 'aws-config.php';

use Aws\S3\S3Client;

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check if file ID is provided
if (!isset($_POST['fileId'])) {
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

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get S3 key before deleting from database
        $stmt = $conn->prepare("SELECT s3_key FROM files WHERE file_id = ? AND user_email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $_POST['fileId'], $_SESSION['user']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("File not found or access denied");
        }

        $file = $result->fetch_assoc();
        $s3Key = $file['s3_key'];

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM files WHERE file_id = ? AND user_email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $_POST['fileId'], $_SESSION['user']);
        $stmt->execute();

        // Delete from S3
        $s3->deleteObject([
            'Bucket' => AWS_BUCKET_NAME,
            'Key'    => $s3Key
        ]);

        // Commit transaction
        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

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