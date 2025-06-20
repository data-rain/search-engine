<?php
session_start();
require 'dbpass.php';

header('Content-Type: application/json');

// Get JSON data and CAPTCHA from POST
$data = json_decode(file_get_contents('php://input'), true);
$captcha = '';
if (isset($_SERVER['HTTP_X_CAPTCHA'])) {
    $captcha = strtoupper(trim($_SERVER['HTTP_X_CAPTCHA']));
} elseif (isset($_POST['captcha'])) {
    $captcha = strtoupper(trim($_POST['captcha']));
}

// Validate CAPTCHA
if ($captcha === '' || $captcha !== ($_SESSION['captcha_code'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CAPTCHA']);
    exit;
}

if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO search_results (url, title, description) VALUES (?, ?, ?)");
foreach ($data as $row) {
    $stmt->bind_param("sss", $row['url'], $row['title'], $row['description']);
    $stmt->execute();
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
?>