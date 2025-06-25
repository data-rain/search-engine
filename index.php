<?php
require 'dbpass.php';

$conn = @new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$results_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $results_per_page;

$search_time_start = microtime(true);

if (isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);
    $results = [];
    $total_results = 0;

    $count_sql = "SELECT COUNT(*) as total FROM search_results WHERE MATCH(title, url, description) AGAINST ('$query' IN NATURAL LANGUAGE MODE)";
    $count_result = $conn->query($count_sql);
    if ($count_result && $row = $count_result->fetch_assoc()) {
        $total_results = intval($row['total']);
    }

    $sql = "SELECT ID, title, url, description FROM search_results WHERE MATCH(title, url, description) AGAINST ('$query' IN NATURAL LANGUAGE MODE) ORDER BY clicks DESC LIMIT $results_per_page OFFSET $offset";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    } else {
        $sql_fallback = "SELECT ID, title, url, description FROM search_results";
        $fallback_result = $conn->query($sql_fallback);
        if ($fallback_result) {
            while ($row = $fallback_result->fetch_assoc()) {
                similar_text(strtolower($query), strtolower($row['title'] . ' ' . $row['description']), $percent);
                if ($percent > 60) {
                    $row['score'] = $percent;
                    $results[] = $row;
                }
            }
            usort($results, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            $total_results = count($results);
            $results = array_slice($results, $offset, $results_per_page);
        }
    }

    $search_time = microtime(true) - $search_time_start;
    $conn->close();
} elseif (isset($_GET['visit'])) {
    $visit_id = intval($_GET['visit']);
    session_start();

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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="search-header">
        <a href="./" class="logo">
            <span style="font-size:5rem; vertical-align:-6px;">&#9730;</span>
            DataRain
        </a>
        <a href="./add.php" class="add-link-btn" title="Add Link"><span>+</span></a>
    </div>
    <div class="search-container">
        <form class="search-form" action="" method="GET" autocomplete="off">
            <input style="padding-left: 20px;" type="text" name="query" placeholder="What are you thinking about?" required <?php if (!isset($results)) echo "autofocus" ?> value="<?php echo isset($query) ? htmlspecialchars($query) : ''; ?>">
            <input type="submit" value="Search">
        </form>
    </div>
    <?php
    if (isset($results)) {
        echo '<div class="search-results">';
        echo '<h2>' . $total_results . ' results for <b>"' . htmlspecialchars($query) . '"</b>';
        if (isset($search_time)) {
            printf(' (%.2f seconds)', $search_time);
        }
        echo '</h2>';
        foreach ($results as $result) {
            echo '<div class="result-item">';
            echo '<a href="./?visit=' . $result['ID'] . '" target="_blank" class="result-title">' . htmlspecialchars($result['title']) . '</a>';
            echo '<div class="result-desc">' . htmlspecialchars($result['description']) . '</div>';
            echo '<a href="'. htmlspecialchars($result['url']) .'" class="result-url" target="_blank">' . htmlspecialchars($result['url']) . '</a>';
            echo '</div>';
        }

        $total_pages = ceil($total_results / $results_per_page);
        if ($total_pages > 1) {
            echo '<div class="pagination">';
            $max_links = 9;
            $start = max(1, $page - floor($max_links / 2));
            $end = min($total_pages, $start + $max_links - 1);
            if ($end - $start < $max_links - 1) {
                $start = max(1, $end - $max_links + 1);
            }
            if ($page > 1) {
                echo "<a href='?page=" . ($page - 1) . "&query=" . urlencode($query) . "'>&laquo;</a>";
            }
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    echo "<span class='active'>$i</span>";
                } else {
                    echo "<a href='?page=$i&query=" . urlencode($query) . "'>$i</a>";
                }
            }
            if ($page < $total_pages) {
                echo "<a href='?page=" . ($page + 1) . "&query=" . urlencode($query) . "'>&raquo;</a>";
            }
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.querySelector('.search-form input[type="text"]');
    if (!input) return;

    function setDirection() {
        const rtlPattern = /[\u0591-\u07FF\uFB1D-\uFDFD\uFE70-\uFEFC]/;
        if (rtlPattern.test(input.value)) {
            input.style.direction = 'rtl';
            input.style.textAlign = 'right';
        } else {
            input.style.direction = 'ltr';
            input.style.textAlign = 'left';
        }
    }

    input.addEventListener('input', setDirection);
    setDirection();
});
</script>
</html>
