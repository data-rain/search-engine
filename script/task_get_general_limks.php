<?php
// Include database credentials
require_once __DIR__ ."/../dbpass.php";

// ====== CONFIGURATION ======
$task_name = ''; //
$table_name = ''; //
// ==========================

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create table if not exists (use variable)
$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    cruel ENUM('yes','no') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    state VARCHAR(50) NOT NULL DEFAULT 'stop',
    count INT NOT NULL DEFAULT 0,
    done INT NOT NULL DEFAULT 0,
    run INT NOT NULL DEFAULT 0,
    debug TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($sql);

// Use variable for task name
$sql = "SELECT * FROM tasks WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $task_name);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($tasks)) {
    $stmt = $conn->prepare("INSERT INTO tasks (name, url, state, count) VALUES (?, '', 'stop', 0)");
    $stmt->bind_param("s", $task_name);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    exit;
}

if($tasks[0]['state'] == 'stop') {
    $conn->close();
    exit;
}

$page = $tasks[0]['done'] + 1;
if($page > $tasks[0]['count']) {
    $conn->close();
    exit;
}

$queryArr = [
    'url'=> $tasks[0]['url'].$page
];

$query = http_build_query($queryArr);
$response = @file_get_contents('https://datarain.ir/get_links.php?' . $query);
$data = json_decode($response, true);

if (isset($data['links']) && is_array($data['links']) && count($data['links']) > 0) {
    // There are links to process
    $stmt = $conn->prepare("SELECT id FROM `$table_name` WHERE url = ?");
    $insertStmt = $conn->prepare("INSERT INTO `$table_name` (url) VALUES (?)");
    foreach ($data['links'] as $url) {
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $insertStmt->bind_param("s", $url);
            $insertStmt->execute();
        }
    }
    $stmt->close();
    $insertStmt->close();

    // Only update 'done' if response and insertions were successful
    $conn->query("UPDATE tasks SET done = $page WHERE name = '$task_name'");
} else {
    // Log or handle error: response invalid or no links
    // $conn->query("UPDATE tasks SET debug = 'No valid links or bad response on page $page' WHERE name = '$task_name'");
}

$conn->query("UPDATE tasks SET run = run + 1 WHERE name = '$task_name'");

$conn->close();
?>