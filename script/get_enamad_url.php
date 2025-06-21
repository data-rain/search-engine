<?php
// Include database credentials
require_once __DIR__ ."/../dbpass.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS enamad_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
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

$sql = "SELECT * FROM tasks WHERE name = 'enamad'";
$result = $conn->query($sql);
$tasks = $result->fetch_all(MYSQLI_ASSOC);
if (empty($tasks)) {
    $conn->query("INSERT INTO tasks (name, url, state, count) VALUES ('enamad', '', 'stop', 0)");
    $conn->close();
    exit;
}

if($tasks[0]['state'] == 'stop') {
    $conn->close();
    exit;
}

$start_page = $tasks[0]['done'] + 1;
if($start_page>$tasks[0]['count']) {
    $conn->close();
    exit;
}

$queryArr = [
    'url'=> $tasks[0]['url'],
    'start_page' => $start_page,
    'end_page' => $start_page
];

$query = http_build_query($queryArr);
$response = @file_get_contents('https://datarain.ir/get_links.php?' . $query);
$data = json_decode($response, true);


if (isset($data['links']) && is_array($data['links'])) {
    $stmt = $conn->prepare("SELECT id FROM enamad_urls WHERE url = ?");
    $insertStmt = $conn->prepare("INSERT INTO enamad_urls (url) VALUES (?)");
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
}

// $conn->query("UPDATE tasks SET debug = '{$data}' WHERE name = 'enamad'");

$conn->query("UPDATE tasks SET done = $start_page WHERE name = 'enamad'");
$conn->query("UPDATE tasks SET run = run + 1 WHERE name = 'enamad'");

$conn->close();
?>