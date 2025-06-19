<?php
$mysqli = new mysqli("localhost", "esmaeill", "15031374", "datarain");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['query'])) {
        $query = $mysqli->real_escape_string($_POST['query']);

        $sql = "SELECT ID, title, url, description FROM search_results WHERE title LIKE '%$query%' AND description LIKE '%$query%' ORDER BY `ID` ASC";
        $result = $mysqli->query($sql);

        if ($result->num_rows > 0) {
            $results = [];
            while ($row = $result->fetch_assoc())
            {
                $results[] = $row;
            }
        }
        else
        {
            $sql = "SELECT ID, title, url, description FROM search_results WHERE url LIKE '%$query%' ORDER BY `ID` ASC";
            $result = $mysqli->query($sql);

            if ($result->num_rows > 0) {
                $results = [];
                while ($row = $result->fetch_assoc())
                {
                    $results[] = $row;
                }
            }
            else
            {
                $sql = "SELECT ID, title, url, description FROM search_results WHERE title LIKE '%$query%' OR url LIKE '%$query%' OR description LIKE '%$query%' ORDER BY `ID` ASC";
                $result = $mysqli->query($sql);

                if ($result->num_rows > 0) {
                    $results = [];
                    while ($row = $result->fetch_assoc())
                    {
                        $results[] = $row;
                    }
                }
                else
                {
                    $results = [];
                }
            }
        }
        $mysqli->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataRain Search Engine</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column; /* Stack elements vertically */
            align-items: center; /* Center elements horizontally */
            height: 100vh;
            background-color: #fff;
            overflow: auto;
        }
        .search-container {
            text-align: center;
            position: relative;
            z-index: 10;
            color: black;
            margin-top: 50px; /* Add margin to move content down slightly */
        }
        .search-container h1 {
            font-size: 3rem; /* Make the title bigger */
            font-weight: bold;
            color: #007BFF; /* Add a blue color for beauty */
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* Add a subtle shadow */
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
            0% {
                transform: translateY(-100%);
            }
            100% {
                transform: translateY(100vh);
            }
        }
        .search-results {
            margin-top: 20px; /* Add spacing between search container and results */
            text-align: left;
            width: 80%; /* Adjust width for better alignment */
        }
        .search-results h2 {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="rain"></div>

    <a href="./add.php" style="position: absolute; top: 10px; left: 10px; text-decoration: none;">
        <button style="padding-top: 1px; padding-bottom: 6px; padding-right: 9px; padding-left: 8px; border: none; background-color: #007BFF; color: white; border-radius: 5px; cursor: pointer;">
        <span style="font-size: 1.3rem; font-weight: bold;"> + </span>
        </button>
    </a>

    <div class="search-container">
        <a href="./index.php" style="text-decoration:none"><h1>DataRain</h1></a>
        <form action="index.php" method="POST" style="display: inline-block;">
            <input type="text" name="query" placeholder="Enter your search term..." required>
            <input type="submit" value="Search">
        </form>

    </div>
    <?php
    if (isset($results)) {
        echo '<div class="search-results">';
        echo '<t4>Search Results for "' . $query . '"</t4>';
        echo '<ul>';
        foreach ($results as $result) {
            echo '<li style="margin-bottom: 20px; display: flex; align-items: center;">';
            echo '<div>';
            echo '<a href="' . $result['url'] . '" target="_blank" style="font-size: 1.5rem; color: #007BFF; text-decoration: none;">' . $result['title'] . '</a>';
            echo '<p style="margin: 5px 0; color: #222;">' . $result['description'] . '</p>';
            echo '<p style="font-size: 0.8rem; margin: 5px 0; color: #999;">' . $result['url'] .'</p>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>
    <script>
        const rainContainer = document.querySelector('.rain');
        const numberOfDrops = 10;

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