<?php

require 'dbpass.php';

session_start();

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $_POST['captcha']=strtoupper($_POST['captcha'] );

    if ($_POST['captcha'] === $_SESSION['captcha_code'])
    {
        $url = $conn->real_escape_string($_POST['url']);

        $response = @file_get_contents('http://datarain.ir/get_links.php?url=' . urlencode($url));
        $data = json_decode($response, true);
    }
    else
    {
        echo "Invalid CAPTCHA!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #87CEEB;
        }
        form {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            padding-right: 40px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .captcha {
            font-weight: bold;
            font-size: 18px;
            color: #333;
            background-color: #e0e0e0;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 10px;
            width: 100%;
        }
    </style>
</head>
<body>
    <form method="POST" action="" autocomplete="off">
        <h2>Add all links</h2>
        <label for="url">URL:</label>

        <input type="url" id="url" name="url" value="https://" required>

        <div class="captcha">
            <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
        </div>

        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" required>

        <button type="button" onclick="window.location.href='..'">Back</button>
        <button type="submit" style="font-weight: bold; width:50%;"> Search </button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data) && is_array($data)) {

        $count = 0;
        if (is_array($data)) {
            foreach ($data as $item) {
            if (!empty($item)) {
                $count++;
            }
            }
        }
        echo '<h3>Results : '.$count.' </h3>';
        echo '<table border="1" cellpadding="8" style="margin:auto; background:#fff; border-radius:8px;">';
        echo '<tr><th>#</th><th>URL</th><th>Title</th><th>Description</th></tr>';
        $k=0;
        foreach ($data as $i => $itemUrl) {
            $itemUrl = trim($itemUrl);
            $title = '';
            $desc = '';
            if (filter_var($itemUrl, FILTER_VALIDATE_URL)) {
                $getTitleResponse = @file_get_contents('http://datarain.ir/get_title.php?url=' . urlencode($itemUrl));
                $titleData = json_decode($getTitleResponse, true);
                $title = isset($titleData['title']) ? htmlspecialchars($titleData['title']) : '';
                $desc = isset($titleData['description']) ? htmlspecialchars($titleData['description']) : '';
            }
            echo '<tr>';
            echo '<td>' . ++$k. '</td>';
            echo '<td>' . htmlspecialchars($itemUrl) . '</td>';
            echo '<td>' . $title . '</td>';
            echo '<td>' . $desc . '</td>';
            echo '</tr>';
            flush();
            ob_flush();
        }
        echo '</table>';
    }
    ?>
</body>
</html>

<script type="text/javascript" charset="UTF-8">
    // Refresh CAPTCHA image when clicked
    document.addEventListener('DOMContentLoaded', function() {
        const captchaImg = document.querySelector('.captcha img');
        if (captchaImg) {
            captchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        }
    });
</script>
