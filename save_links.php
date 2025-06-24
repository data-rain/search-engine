<?php
require 'dbpass.php';

session_start();

// Set response type to JSON
header('Content-Type: application/json');

// Get JSON data from POST body
$data = json_decode(file_get_contents('php://input'), true);

// Get CAPTCHA from custom header or POST
$captcha = '';
if (isset($_SERVER['HTTP_X_CAPTCHA'])) {
    $captcha = strtoupper(trim($_SERVER['HTTP_X_CAPTCHA']));
} elseif (isset($_POST['captcha'])) {
    $captcha = strtoupper(trim($_POST['captcha']));
}

// Validate CAPTCHA
if ($captcha === '' ||$captcha !== ($_SESSION['captcha_code'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CAPTCHA']);
    exit;
}

// Validate input data
if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Connect to the database
$conn = @new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Prepare SQL statement for inserting links
$stmt = $conn->prepare("INSERT INTO search_results (url, title, description) VALUES (?, ?, ?)");

// Insert each link into the database
foreach ($data as $row) {
    $stmt->bind_param("sss", $row['url'], $row['title'], $row['description']);
    $stmt->execute();
}

// Clean up
$stmt->close();
$conn->close();

// Return success response
echo json_encode(['success' => true]);
?>