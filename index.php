<?php
// Include database credentials
require 'dbpass.php';

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination setup
$results_per_page = 10; // Google-like: fewer results per page
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
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            min-height: 100vh;
        }
        .search-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 0 0 0; /* Increased top padding for more space above logo */
            width: 100%;
            position: relative;
            background: #f8fafc;
        }
        .add-link-btn {
            background: #fff;              /* Visible white background */
            color: #4285f4;                /* Icon color */
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            font-size: 2.2rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(66,133,244,0.10); /* Soft shadow for visibility */
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 18px;
            right: 32px;
            text-decoration: none !important;
            line-height: 1;
            padding: 0;
        }
        .add-link-btn span {
            color: #4285f4;
            position: relative;
            top: -4px;
            font-size: 2rem;
            display: inline-block;
        }
        .add-link-btn:hover {
            background: #e3f0ff;
        }
        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Product Sans', Arial, sans-serif;
            font-size: 3.2rem;
            font-weight: bold;
            letter-spacing: -2px;
            text-shadow: 1px 2px 8px rgba(66,133,244,0.07);
            margin-bottom: 0.5rem;
            user-select: none;
            color:#4285f4;
        }
        .logo span {
            font-size: 3.6rem;
            vertical-align: -6px;
        }
        .search-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .search-form {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 600px;
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 2px 12px rgba(66,133,244,0.07);
            padding: 8px 18px;
            margin-top: 18px;
        }
        .search-form input[type="text"] {
            flex: 1;
            border: none;
            outline: none;
            font-size: 1.2rem;
            padding: 12px 10px;
            background: transparent;
        }
        .search-form input[type="submit"] {
            background: #4285f4;
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 8px 22px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            margin-left: 10px;
            transition: background 0.2s;
        }
        .search-form input[type="submit"]:hover {
            background: #1967d2;
        }
        .search-results {
            width: 98vw;
            max-width: 1400px;
            margin: 0 auto;
            margin-top: 18px;
            background: #f8fafc; /* Match body background */
            border-radius: 0; /* Remove rounded corners */
            box-shadow: none; /* Remove card shadow */
            padding: 0 0 0 0;
        }
        .search-results h2 {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 18px;
            margin-left: 32px;
        }
        .result-item {
            margin-bottom: 32px;
            border-bottom: 1px solid #f0f0f0;
            padding: 28px 32px 18px 32px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(66,133,244,0.07);
        }
        .result-title {
            font-size: 1.4rem;
            color: #1a0dab;
            text-decoration: none;
            font-weight: 500;
            line-height: 1.2;
        }
        .result-title:hover {
            text-decoration: underline;
        }
        .result-desc {
            color: #222;
            margin: 7px 0 4px 0;
            font-size: 1.04rem;
        }
        .result-url {
            font-size: 0.92rem;
            color: #006621;
            text-decoration: none;
            word-break: break-all;
        }
        .pagination {
            text-align: center;
            margin: 24px 0 8px 0; /* Add small space below pagination */
        }
        .pagination a, .pagination span {
            display: inline-block;
            margin: 0 6px;
            padding: 6px 12px;
            border-radius: 50%;
            color: #4285f4;
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1rem;
            transition: background 0.15s;
        }
        .pagination a:hover {
            background: #e3f0ff;
        }
        .pagination .active {
            background: #4285f4;
            color: #fff;
            font-weight: bold;
        }
        @media (max-width: 800px) {
            .search-results, .search-header { max-width: 98vw; padding: 10px; }
            .search-form { max-width: 98vw; }
            .result-item { padding: 18px 8px 12px 8px; }
        }
    </style>
</head>
<body>
    <div class="search-header">
        <div class="logo" style="color:#4285f4; font-family:'Product Sans',Arial,sans-serif; font-size:4.2rem; font-weight:bold; letter-spacing:-2px; text-shadow:1px 2px 8px rgba(66,133,244,0.10); margin-bottom:0.5rem; user-select:none;">
            <span style="font-size:8rem; vertical-align:-6px;">&#9730;</span> DataRain
        </div>
        <a href="./add.php" class="add-link-btn" title="Add Link"><span>+</span></a>
    </div>
    <div class="search-container">
        <form class="search-form" action="" method="GET" autocomplete="off">
            <input type="text" name="query" placeholder="Search DataRain..." required autofocus value="<?php echo isset($query) ? htmlspecialchars($query) : ''; ?>">
            <input type="submit" value="Search">
        </form>
    </div>
    <?php
    // Display search results if available
    if (isset($results)) {
        echo '<div class="search-results">';
        echo '<h2>' . $total_results . ' results for <b>"' . htmlspecialchars($query) . '"</b></h2>';
        foreach ($results as $result) {
            echo '<div class="result-item">';
            // Title link (click counter)
            echo '<a href="./?visit=' . $result['ID'] . '" target="_blank" class="result-title">' . htmlspecialchars($result['title']) . '</a>';
            // Description
            echo '<div class="result-desc">' . htmlspecialchars($result['description']) . '</div>';
            // Direct URL
            echo '<a href="'. htmlspecialchars($result['url']) .'" class="result-url" target="_blank">' . htmlspecialchars($result['url']) . '</a>';
            echo '</div>';
        }

        // Pagination links
        $total_pages = ceil($total_results / $results_per_page);
        if ($total_pages > 1) {
            echo '<div class="pagination">';
            $max_links = 9; // Max page links to show
            $start = max(1, $page - floor($max_links / 2));
            $end = min($total_pages, $start + $max_links - 1);
            if ($end - $start < $max_links - 1) {
                $start = max(1, $end - $max_links + 1);
            }
            // Prev link
            if ($page > 1) {
                echo "<a href='?page=" . ($page - 1) . "&query=" . urlencode($query) . "'>&laquo;</a>";
            }
            // Page number links
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    echo "<span class='active'>$i</span>";
                } else {
                    echo "<a href='?page=$i&query=" . urlencode($query) . "'>$i</a>";
                }
            }
            // Next link
            if ($page < $total_pages) {
                echo "<a href='?page=" . ($page + 1) . "&query=" . urlencode($query) . "'>&raquo;</a>";
            }
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</body>
</html>
