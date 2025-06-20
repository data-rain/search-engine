<?php

require 'dbpass.php';

if(!isset($_SESSION))session_start();

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$responseMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['captcha'] = strtoupper($_POST['captcha']);
    if ($_POST['captcha'] === $_SESSION['captcha_code']) {
        $title = $conn->real_escape_string($_POST['title']);
        $url = $conn->real_escape_string($_POST['url']);
        $description = $conn->real_escape_string($_POST['description']);

        $sql = "INSERT INTO search_results (title, url, description) VALUES ('$title', '$url', '$description')";
        if ($conn->query($sql) === TRUE) {
            $responseMsg = '<div class="alert success">✅ Record added successfully!</div>';
        } else {
            $responseMsg = '<div class="alert error">❌ Error: ' . htmlspecialchars($conn->error) . '</div>';
        }
    } else {
        $responseMsg = '<div class="alert error">❌ Invalid CAPTCHA!</div>';
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
        .alert {
            padding: 14px 18px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 1.1em;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #b7e0c3;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <form method="POST" action="" autocomplete="off">
        <h2>Add more link</h2>
        <label for="url">URL:</label>
        <div style="display: flex; gap: 8px; align-items: center;">
            <input type="url" id="url" name="url" value="http://" required style="flex: 1;">
            <button type="button" onclick="fetchTitle()" style="padding: 10px 14px;">Get info</button>
        </div>

        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <div class="captcha">
            <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
        </div>

        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" required>

        <button type="submit" style="font-weight: bold;">Add to Database</button>
        <button type="button" onclick="window.location.href='..'">Back</button>

    </form>
    <div id="responseMsg" style="max-width:400px;margin:20px auto 0 auto;">
        <?php if (!empty($responseMsg)) echo $responseMsg; ?>
    </div>
</body>
</html>

<script type="text/javascript" charset="UTF-8">
    function fetchTitle() {
    const url = document.getElementById('url').value;
    if (!url) return;
    fetch('get_title.php?url=' + encodeURIComponent(url))
        .then(response => response.json())
        .then(data => {
        if (data.title) {
            document.getElementById('title').value = data.title;
        }
        if (data.description) {
            document.getElementById('description').value = data.description;
        }
        })
        .catch(() => alert('Error fetching data.'));
    }

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
