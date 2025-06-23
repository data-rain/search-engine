<?php
// Include database credentials
require_once __DIR__ ."/../dbpass.php";

// Main function to fetch and normalize links from a URL
function getAllLinks($url) {
    // Fetch the HTML content using cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html === false) {
        return [];
    }

    // Load HTML into DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $links = [];

    // 1. Extract links from <a href="">
    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!empty($href) && strpos($href, 'http') === 0) {
            // Extract only protocol and domain
            if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org|ru))\b/i', $href, $matches)) {
                $links[] = $matches[1];
            }
        }
    }

    // 2. Extract plain text URLs from the HTML body
    $body = '';
    $bodyNodes = $dom->getElementsByTagName('body');
    if ($bodyNodes->length > 0) {
        $body = $dom->saveHTML($bodyNodes->item(0));
    } else {
        $body = $html;
    }

    // Match URLs with http(s), www, or just domain in plain text
    if (preg_match_all('/((https?:\/\/)?(www\.)?([a-zA-Z0-9\-]+\.)+(com|ir|org|ru)\b[^\s"<>()]*)/i', $body, $matches)) {
        foreach ($matches[0] as $foundUrl) {
            $links[] = $foundUrl;
        }
    }

    // Remove duplicate links
    $links = array_unique($links);

    // Normalize links: ensure https:// and keep only protocol+domain
    $normalizedLinks = [];
    foreach ($links as $link) {
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . ltrim($link, '/');
        }
        // Extract only protocol and domain (ending with .com, .ir, .org, .ru)
        if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org|ru))\b/i', $link, $m)) {
            $normalizedLinks[] = $m[1];
        }
    }

    // Return unique, normalized links
    return array_unique($normalizedLinks);
}

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

$page = $tasks[0]['done'] + 1;
if($page>$tasks[0]['count']) {
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

    // Only update 'done' if response and insertions were successful
    $conn->query("UPDATE tasks SET done = $page WHERE name = 'enamad'");
}

$conn->query("UPDATE tasks SET run = run + 1 WHERE name = 'enamad'");

$conn->close();
?>