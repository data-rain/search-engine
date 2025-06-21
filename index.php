<?php
// Include database credentials
require 'dbpass.php';

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination setup
$results_per_page = 100; // Number of results per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $results_per_page;

// Search logic
if (isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);

    // Get total result count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM search_results WHERE title LIKE '%$query%' OR url LIKE '%$query%' OR description LIKE '%$query%'";
    $count_result = $conn->query($count_sql);
    $total_results = ($count_result && $row = $count_result->fetch_assoc()) ? intval($row['total']) : 0;

    // Fetch paginated results
    $sql = "SELECT ID, title, url, description FROM search_results WHERE title LIKE '%$query%' OR url LIKE '%$query%' OR description LIKE '%$query%' ORDER BY `clicks` DESC LIMIT $results_per_page OFFSET $offset";
    $result = $conn->query($sql);

    $results = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }

    $conn->close();
}
// Handle link visit (click counter and redirect)
else if (isset($_GET['visit'])) {
    $visit_id = intval($_GET['visit']);
    session_start();

    // Prevent multiple increments per session
    if (!isset($_SESSION['run_once_flags'])) {
        $_SESSION['run_once_flags'] = [];
    }
    if (empty($_SESSION['run_once_flags'][$visit_id])) {
        $conn->query("UPDATE search_results SET clicks = clicks + 1 WHERE ID = $visit_id");
        $_SESSION['run_once_flags'][$visit_id] = true;
    }
    $res = $conn->query("SELECT url FROM search_results WHERE ID = $visit_id");
    if ($res && $row = $res->fetch_assoc()) {
        $url = $row['url'];
        $conn->close();
        header("Location: $url");
        exit;
    }
    $conn->close();
    echo "Error updating click counter or fetching URL.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataRain Search Engine</title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            background-color: #fff;
            overflow: auto;
        }
        .search-container {
            text-align: center;
            position: relative;
            z-index: 10;
            color: black;
            margin-top: 50px;
        }
        .search-container h1 {
            font-size: 3rem;
            font-weight: bold;
            color: #007BFF;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }
        .search-container input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-container input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .rain {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        .drop {
            position: absolute;
            width: 1px;
            height: 5px;
            background-color: #007BFF;
            opacity: 0.5;
            animation: fall linear infinite;
        }
        @keyframes fall {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }
        .search-results {
            margin-top: 20px;
            text-align: left;
            width: 80%;
        }
        .search-results h2 {
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Rain animation background -->
    <div class="rain"></div>

    <!-- Add Link button -->
    <a href="./add.php" style="position: absolute; top: 10px; left: 10px; text-decoration: none;">
        <button style="border: none; background-color: #007BFF; color: white; border-radius: 5px; cursor: pointer;">
            <span style="font-size: 1.1rem; font-weight: bold;"> Add Link </span>
        </button>
    </a>

    <!-- Search form -->
    <div class="search-container">
        <a href="./index.php" style="text-decoration:none"><h1>DataRain</h1></a>
        <form action="" method="GET" style="display: inline-block;" autocomplete="off">
            <input type="text" name="query" placeholder="Enter your search term..." required autofocus>
            <input type="submit" style="margin-top: 10px; font-size: 0.9rem; font-weight: bold;" value="Search">
        </form>
    </div>

    <?php
    // Display search results if available
    if (isset($results)) {
        echo '<div class="search-results">';
        echo '<t4>Search: '.$total_results.' Results for "' . htmlspecialchars($query) . '"</t4>';
        echo '<ul>';
        foreach ($results as $result) {
            echo '<li style="margin-bottom: 20px; display: flex;">';
            echo '<div>';
            // Title link (click counter)
            echo '<a href="./?visit=' . $result['ID'] . '" target="_blank" style="font-size: 1.5rem; color: #007BFF; text-decoration: none;">' . htmlspecialchars($result['title']) . '</a>';
            // Description
            echo '<p style="margin: 5px 0; color: #222;">' . htmlspecialchars($result['description']) . '</p>';
            // Direct URL
            echo '<a href="'. htmlspecialchars($result['url']) .'" style="font-size: 0.8rem; margin: 5px 0; color: #999; text-decoration:none">' . htmlspecialchars($result['url']) .'</a>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';

        // Pagination links
        $total_pages = ceil($total_results / $results_per_page);
        if ($total_pages > 1) {
            echo '<div style="text-align:center;margin:20px 0;">';

            $max_links = 10; // Max page links to show
            $start = max(1, $page - floor($max_links / 2));
            $end = min($total_pages, $start + $max_links - 1);
            if ($end - $start < $max_links - 1) {
                $start = max(1, $end - $max_links + 1);
            }

            // Prev link
            if ($page > 1) {
                echo "<a href='?page=" . ($page - 1) . "&query=" . urlencode($query) . "' style='margin:0 6px;color:#007BFF;text-decoration:none;'>&laquo; Prev</a>";
            }

            // Page number links
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    echo "<span style='margin:0 6px;font-weight:bold;color:#007BFF;'>$i</span>";
                } else {
                    echo "<a href='?page=$i&query=" . urlencode($query) . "' style='margin:0 6px;color:#007BFF;text-decoration:none;'>$i</a>";
                }
            }

            // Next link
            if ($page < $total_pages) {
                echo "<a href='?page=" . ($page + 1) . "&query=" . urlencode($query) . "' style='margin:0 6px;color:#007BFF;text-decoration:none;'>Next &raquo;</a>";
            }

            echo '</div>';
        }
        echo '</div>';
    }
    ?>

    <!-- Rain animation script -->
    <script>
        // Simple rain effect
        const rainContainer = document.querySelector('.rain');
        const numberOfDrops = 7;
        for (let i = 0; i < numberOfDrops; i++) {
            const drop = document.createElement('div');
            drop.classList.add('drop');
            drop.style.left = Math.random() * 100 + '%';
            drop.style.animationDuration = Math.random() * 2 + 2 + 's';
            rainContainer.appendChild(drop);
        }
    </script>
</body>
</html>
