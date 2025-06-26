<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// AWS S3 configuration
$bucketName = 'your-bucket-name';
$keyName = 'uploads/encryptedFile.dat'; // Path to the encrypted file on S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'your-region'
]);

try {
    // Retrieve the encrypted file from S3
    $result = $s3->getObject([
        'Bucket' => $bucketName,
        'Key'    => $keyName
    ]);

    // Fetch the encrypted AES key and IV from the database (or another storage)
    $db = new mysqli("localhost", "username", "password", "database_name");
    $resultDB = $db->query("SELECT aes_key, iv FROM encryption_keys WHERE id = 1");
    $row = $resultDB->fetch_assoc();

    // Return encrypted file, AES key, and IV as JSON response
    echo json_encode([
        'encryptedFile' => base64_encode($result['Body']),
        'encryptedAESKey' => $row['aes_key'],
        'iv' => $row['iv']
    ]);

} catch (AwsException $e) {
    echo json_encode(['message' => 'AWS retrieval failed: ' . $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>
