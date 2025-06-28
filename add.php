<?php
require 'dbpass.php';

// Start session if not already started
if (!isset($_SESSION)) session_start();

// Connect to the database
$conn = @new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection mysqli failed!");
}

$responseMsg = '';

// Handle form submission for adding a single link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $_POST['captcha'] = strtoupper($_POST['captcha']);
    // Check CAPTCHA
    if ($_POST['captcha'] === $_SESSION['captcha_code']) {
        if ($_POST['form_type'] === 'single') {
            $title = $conn->real_escape_string($_POST['title']);
            $url = $conn->real_escape_string($_POST['url']);
            $description = $conn->real_escape_string($_POST['description']);

            // Insert new record into the database
            $sql = "INSERT INTO search_results (title, url, description) VALUES ('$title', '$url', '$description')";
            if ($conn->query($sql) === TRUE) {
                $responseMsg = '<div class="alert success">✅ Record added successfully!</div>';
            } else {
                $responseMsg = '<div class="alert error">❌ Error: ' . htmlspecialchars($conn->error) . '</div>';
            }
        }
        // No DB insert for bulk, handled in add_all.php
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
    <title>Add Link - DataRain</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 440px;
            margin: 40px auto;
            padding: 0 12px;
        }
        .card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,123,255,0.08), 0 1.5px 6px rgba(0,0,0,0.03);
            padding: 32px 28px 28px 28px;
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        h2 {
            color: #007BFF;
            margin: 0 0 12px 0;
            font-size: 1.5em;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        label {
            font-weight: 500;
            margin-bottom: 4px;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px 16px; /* More space left and right */
            margin-bottom: 10px;
            border: 1.5px solid #d0e3fa;
            border-radius: 8px;
            font-size: 1em;
            background: #f7fbff;
            transition: border-color 0.2s;
            box-sizing: border-box; /* Ensures padding is included in width */
            text-align: left; /* Ensures LTR alignment */
        }
        input:focus, textarea:focus, select:focus {
            border-color: #007BFF;
            outline: none;
            background: #eef6ff;
        }
        .form-row {
            display: flex;
            gap: 12px;
        }
        .form-row > div {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .captcha {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f1f7ff;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .captcha img {
            cursor: pointer;
            border-radius: 6px;
            border: 1px solid #cce0ff;
            box-shadow: 0 1px 4px rgba(0,123,255,0.07);
        }
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 1.1em;
            text-align: center;
            font-weight: bold;
            margin-bottom: 18px;
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
        .form-section { display: none; }
        .form-section.active { display: block; }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .form-actions button {
            flex: 1;
            padding: 13px 0;
            font-size: 1.08em;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            letter-spacing: 0.5px;
        }
        .btn-back {
            background: linear-gradient(90deg,#6c757d 60%,#495057 100%);
            color: #fff;
        }
        .btn-back:hover {
            background: linear-gradient(90deg,#495057 60%,#343a40 100%);
        }
        .btn-green {
            background: linear-gradient(90deg,#28a745 60%,#218838 100%);
            color: #fff;
        }
        .btn-green:hover {
            background: linear-gradient(90deg,#218838 60%,#155724 100%);
        }
        .btn-blue {
            background: linear-gradient(90deg,#007BFF 60%,#0056b3 100%);
            color: #fff;
        }
        .btn-blue:hover {
            background: linear-gradient(90deg,#0056b3 60%,#003974 100%);
        }
        .form-type-select {
            margin: 0 auto 24px auto;
            max-width: 440px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff; /* Match card background */
            border-radius: 18px; /* Match card border radius */
            padding: 18px 24px;
            box-shadow: 0 4px 24px rgba(0,123,255,0.08), 0 1.5px 6px rgba(0,0,0,0.03); /* Match card shadow */
        }
        .form-type-select label {
            margin-bottom: 0;
            color: #007BFF;
            font-weight: 600;
        }
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0 12px 0;
            user-select: none;
        }
        .checkbox-row input[type="checkbox"] {
            accent-color: #007BFF;
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            font-weight: 500;
            color: #007BFF;
            font-size: 1.08em;
            cursor: pointer;
            gap: 8px;
        }
        .checkbox-label .custom-checkbox {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Display response message if set -->
        <?php if (!empty($responseMsg)) echo $responseMsg; ?>

        <!-- Form type selector -->
        <div class="form-type-select">
            <label for="formTypeSelect">Select form type:</label>
            <select id="formTypeSelect" onchange="switchFormType()">
                <option value="single">Add Single Link</option>
                <option value="bulk">Bulk Link Search</option>
            </select>
        </div>

        <!-- Single Link Form -->
        <form id="singleForm" class="card form-section active" method="POST" action="" autocomplete="off">
            <input type="hidden" name="form_type" value="single">
            <h2>Add link</h2>
            <div style="display: flex; gap: 8px; align-items: stretch; margin-bottom: 10px;">
                <input type="url" id="url" name="url" value="https://" required style="flex: 1 1 auto; min-width: 0;">
                <button type="button"
                    onclick="fetchTitle()"
                    style="padding: 0 18px; background: #e3f0ff; color: #007BFF; border: 1px solid #b7d6ff; border-radius: 7px; font-weight: 600; cursor: pointer; flex-shrink: 0; display: flex; align-items: center; height: 44px; min-width: 90px;">
                    Get info
                </button>
            </div>
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"></textarea>
            <div class="captcha">
                <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
                <span style="font-size: 0.95em; color: #888;">Click image to refresh</span>
            </div>
            <label for="captcha">Enter CAPTCHA:</label>
            <input type="text" id="captcha" name="captcha" required>
            <div class="form-actions">
                <button type="button" onclick="window.location.href='..'" class="btn-back">
                    ← Back
                </button>
                <button type="submit" class="btn-green">
                    Add to Database
                </button>
            </div>
        </form>

        <!-- Bulk Link Search Form -->
        <form id="bulkForm" class="card form-section" method="POST" action="add_all.php" autocomplete="off">
            <input type="hidden" name="form_type" value="bulk">
            <h2>Bulk Link Search</h2>
            <label for="bulk_url">URL:</label>
            <input type="url" id="bulk_url" name="url" value="https://" required>
            <div class="checkbox-row">
                <input type="checkbox" id="multiPageCheckbox" name="multi_page" onchange="togglePageInputs()">
                <label for="multiPageCheckbox" class="checkbox-label">
                    <span class="custom-checkbox"></span>
                    Enable multiple page support
                </label>
            </div>
            <div id="pageInputs" class="form-row" style="margin-bottom: 8px;">
                <div>
                    <label for="start_page">Start Page:</label>
                    <input type="number" id="start_page" name="start_page" min="1" value="1">
                </div>
                <div>
                    <label for="end_page">End Page:</label>
                    <input type="number" id="end_page" name="end_page" min="1" value="1">
                </div>
            </div>
            <div class="captcha">
                <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
                <span style="font-size: 0.95em; color: #888;">Click image to refresh</span>
            </div>
            <label for="bulk_captcha">Enter CAPTCHA:</label>
            <input type="text" id="bulk_captcha" name="captcha" required>
            <div class="form-actions">
                <button type="button" onclick="window.location.href='..'" class="btn-back">
                    ← Back
                </button>
                <button type="submit" class="btn-blue">
                    Search for Links
                </button>
            </div>
        </form>
    </div>
<script type="text/javascript" charset="UTF-8">
    // Switch between single and bulk forms
    function switchFormType() {
        var type = document.getElementById('formTypeSelect').value;
        document.getElementById('singleForm').classList.remove('active');
        document.getElementById('bulkForm').classList.remove('active');
        if (type === 'single') {
            document.getElementById('singleForm').classList.add('active');
        } else {
            document.getElementById('bulkForm').classList.add('active');
        }
    }

    // Enable/disable page inputs based on checkbox
    function togglePageInputs() {
        var checked = document.getElementById('multiPageCheckbox').checked;
        document.getElementById('pageInputs').style.display = checked ? 'flex' : 'none';
        // Optionally clear values if disabled
        if (!checked) {
            document.getElementById('start_page').value = '';
            document.getElementById('end_page').value = '';
        }
    }

    // Fetch title and description for the given URL using AJAX
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
        document.querySelectorAll('.captcha img').forEach(function(captchaImg) {
            captchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        });
        togglePageInputs(); // Set initial state for page inputs
    });
</script>
</body>
</html>
