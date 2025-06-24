<?php
// Include database credentials
require_once __DIR__ ."/../dbpass.php";

// ====== CONFIGURATION ======
$task_name = 'enamadv3'; //
$table_name = 'enamadv3_urls'; //
// ==========================

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
$conn = @new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `$table_name` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    cruel ENUM('yes','no') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    state VARCHAR(50) NOT NULL DEFAULT 'stop',
    count INT NOT NULL DEFAULT 0,
    done INT NOT NULL DEFAULT 0,
    run INT NOT NULL DEFAULT 0,
    debug TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Get task info
$sql = "SELECT * FROM tasks WHERE name = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $task_name);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($tasks)) {
    $stmt = $conn->prepare("INSERT INTO tasks (name, url, state, count) VALUES (?, 'https://www.enamad.ir/DomainListForMIMT/Index/', 'stop', 6667)");
    $stmt->bind_param("s", $task_name);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    exit("Task created, please configure and start it.");
}

if($tasks[0]['state'] == 'stop') {
    $conn->close();
    exit("Task is stopped.");
}

$conn->query("UPDATE tasks SET run = run + 1 WHERE name = '$task_name'");

$page = $tasks[0]['done'] + 1;
if($page > $tasks[0]['count']) {
    $conn->close();
    exit("All pages done.");
}

$data = getAllLinks($tasks[0]['url'].$page);

if ($data === false || empty($data)) {
    $conn->query("UPDATE tasks SET debug = 'Failed to fetch remote data for page $page' WHERE name = '$task_name'");
    $conn->close();
    exit("Failed to fetch remote data.");
}

if (is_array($data) && count($data) > 0) {
    foreach ($data as $url) {
        $conn->query("INSERT INTO $table_name (url) VALUE ('$url')");
    }
    $conn->query("UPDATE tasks SET done = $page WHERE name = '$task_name'");
} else {
    $conn->query("UPDATE tasks SET debug = 'No valid links or bad response on page $page' WHERE name = '$task_name'");
}

$conn->close();
echo 'Done page '.$page.' !';
exit;
?>
