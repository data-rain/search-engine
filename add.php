<?php
// Database connection
$servername = "localhost";
$username = "esmaeill";
$password = "15031374";
$dbname = "datarain";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $_POST['captcha']=strtoupper($_POST['captcha'] );
    if ($_POST['captcha'] === $_SESSION['captcha_code']) {
        $title = $conn->real_escape_string($_POST['title']);
        $url = $conn->real_escape_string($_POST['url']);
        $description = $conn->real_escape_string($_POST['description']);

        $sql = "INSERT INTO search_results (title, url, description) VALUES ('$title', '$url', '$description')";
        if ($conn->query($sql) === TRUE) {
            echo "Record added successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Invalid CAPTCHA!";
    }
}

// Generate CAPTCHA code
if(!isset($_SESSION))session_start();
$captcha_code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6);
$_SESSION['captcha_code'] = $captcha_code;
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
    <form method="POST" action="">
        <h2>Submit Form</h2>
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="url">URL:</label>
        <input type="url" id="url" name="url" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <div class="captcha"><?php echo $captcha_code; ?></div>
        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" required>

        <button type="submit">Submit</button>
        <button type="button" onclick="window.location.href='..'">Back</button>

    </form>
</body>
</html>