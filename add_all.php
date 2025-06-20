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

        $count = is_array($data) ? count($data) : 0;
        echo "Number of elements in data: $count<br>";

        if (is_array($data)) {
            foreach ($data as $item)
            {
                $title = $conn->real_escape_string($item['title']);
                $link = $conn->real_escape_string($item['url']);
                $description = $conn->real_escape_string($item['description']);

                $sql = "INSERT INTO search_results (title, url, description) VALUES ('$title', '$link', '$description')";
                $conn->query($sql);
            }
            echo "All links added successfully!";
        }
        else
        {
            echo "Failed to fetch or decode links.";
        }
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
        <h2>Add all internal links</h2>
        <label for="url">URL:</label>

        <input type="url" id="url" name="url" value="https://" required>

        <div class="captcha">
            <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
        </div>

        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" required>

        <button type="submit" style="font-weight: bold;">Add to Database</button>
        <button type="button" onclick="window.location.href='..'">Back</button>

    </form>
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
