<?php
require 'dbpass.php';
session_start();

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['captcha'] = strtoupper($_POST['captcha']);

    // Check CAPTCHA
    if ($_POST['captcha'] === $_SESSION['captcha_code']) {
        $url = $conn->real_escape_string($_POST['url']);

        // Only use start_page and end_page if multi_page is checked
        $queryArr = ['url' => $url];
        if (isset($_POST['multi_page'])) {
            $start_page = isset($_POST['start_page']) ? intval($_POST['start_page']) : 1;
            $end_page = isset($_POST['end_page']) ? intval($_POST['end_page']) : 1;
            if ($start_page && $end_page && $end_page >= $start_page) {

                //limit the end_page to 10 pages
                $dif=$end_page - $start_page;
                if ($dif > 10) {
                    $end_page = $start_page + 10;
                }

                $queryArr['start_page'] = $start_page;
                $queryArr['end_page'] = $end_page;
            }
        }
        $query = http_build_query($queryArr);

        // Fetch links from remote service
        $response = @file_get_contents('https://datarain.ir/get_links.php?' . $query);
        $data = json_decode($response, true);
    } else {
        echo "Invalid CAPTCHA!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Add Links</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: #232946;
        }
        .container {
            margin: 40px auto;
            padding: 0 12px;
        }
        form {
            max-width: 440px;
            margin: 32px auto;
            padding: 32px 28px 28px 28px;
            border: 1px solid #b7d6ff;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,123,255,0.08), 0 1.5px 6px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        label {
            font-weight: 500;
            margin-bottom: 4px;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 10px;
            border: 1.5px solid #d0e3fa;
            border-radius: 8px;
            font-size: 1em;
            background: #f7fbff;
            color: #232946;
            transition: border-color 0.2s;
            box-sizing: border-box;
            text-align: left;
        }
        input:focus, textarea:focus {
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
        .btn-blue {
            background: linear-gradient(90deg,#007BFF 60%,#0056b3 100%);
            color: #fff;
        }
        .btn-blue:hover {
            background: linear-gradient(90deg,#0056b3 60%,#003974 100%);
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
        table {
            width: 98vw;
            max-width: 1400px;
            min-width: 900px;
            background: #fff;
            color: #232946;
            border-radius: 18px;
            margin: 24px auto 0 auto;
            border-collapse: separate;
            border-spacing: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            font-size: 0.92em;
        }
        th, td {
            padding: 12px 14px;
            border: 1px solid #d0e3fa;
            text-align: left;
            word-break: break-all;
        }
        /* Set fixed min-width for # and URL columns */
        th:nth-child(1), td:nth-child(1) {
            min-width: 24px;
            /* width: 16px; */
            /* max-width: 60px; */
        }
        th:nth-child(2), td:nth-child(2) {
            min-width: 200px;
            /* width: 200px; */
            /* max-width: 600px; */
        }
        th {
            background: #f1f7ff;
            color: #007BFF;
        }
        table tr:first-child th:first-child { border-top-left-radius: 18px; }
        table tr:first-child th:last-child { border-top-right-radius: 18px; }
        table tr:last-child td:first-child { border-bottom-left-radius: 18px; }
        table tr:last-child td:last-child { border-bottom-right-radius: 18px; }
        #toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #28a745;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 40px;
            font-size: 1.1em;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transition: opacity 0.4s, bottom 0.4s;
        }
        #toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 60px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="toast"></div>
        <?php
        // If links were fetched, display them in a table and show save form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['links']) && is_array($data['links'])) {
            $urls = [];
            foreach ($data['links'] as $item) {
                if (!empty($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                    $urls[] = trim($item);
                }
            }
            $jsUrls = json_encode($urls);

            echo "<h3>Results : " . count($urls) . " for ".htmlspecialchars($url);
            if (isset($_POST['multi_page'])) {
                echo " (Pages: " . htmlspecialchars($start_page) . " - " . htmlspecialchars($end_page) . ")";
            }
            echo "</h3>";

            // Table for displaying fetched URLs
            echo '<div style="overflow-x:auto; max-width:100vw; margin:auto;">';
            echo '<table id="resultsTable">';
            echo '<tr><th>#</th><th>URL</th><th>Title</th><th>Description</th></tr>';
            echo '</table>';
            echo '</div>';
            // Save form with CAPTCHA
            echo '<form id="saveForm" style="max-width:400px;margin:32px auto 0 auto;padding:24px;border:2px solid #b7d6ff;border-radius:12px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.07);display:flex;flex-direction:column;align-items:center;">';
            echo '<div style="display:flex;align-items:center;width:100%;margin-bottom:16px;">';
            echo '<img id="saveCaptchaImg" src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;cursor:pointer;margin-right:12px;border-radius:6px;border:1px solid #cce0ff;">';
            echo '<input type="text" id="saveCaptchaInput" placeholder="Enter CAPTCHA" style="flex:1;padding:8px;border-radius:6px;border:1.5px solid #d0e3fa;background:#f7fbff;color:#232946;" required>';
            echo '</div>';
            echo '<div style="display:flex;justify-content:space-between;width:100%;gap:12px;">';
            echo '<button type="button" onclick="window.location.href=\'add.php\'" class="btn-back" style="flex:1;padding:14px 0;font-size:1.1em;font-weight:bold;border-radius:8px;letter-spacing:0.5px;cursor:pointer;">';
            echo '← Back</button>';
            echo '<button id="saveAllBtn" type="submit" class="btn-blue" style="flex:1;padding:14px 0;font-size:1.1em;font-weight:bold;border-radius:8px;letter-spacing:0.5px;cursor:pointer;">';
            echo 'Save All</button>';
            echo '</div>';
            echo '</form>';
            // Pass URLs to JS
            echo "<script>window.resultUrls = $jsUrls;</script>";
        }
        ?>
<script type="text/javascript">
    // Enable/disable page inputs based on checkbox
    function togglePageInputs() {
        var checked = document.getElementById('multiPageCheckbox').checked;
        document.getElementById('pageInputs').style.display = checked ? 'flex' : 'none';
        if (!checked) {
            document.getElementById('start_page').value = '';
            document.getElementById('end_page').value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.captcha img').forEach(function(captchaImg) {
            captchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        });
        togglePageInputs();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const saveCaptchaImg = document.getElementById('saveCaptchaImg');
        const saveCaptchaInput = document.getElementById('saveCaptchaInput');
        const saveForm = document.getElementById('saveForm');
        const saveBtn = document.getElementById('saveAllBtn');

        if (saveCaptchaImg) {
            saveCaptchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        }

        if (saveForm) {
            saveForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveBtn.disabled = true;

                const table = document.getElementById('resultsTable');
                const data = [];
                for (let i = 1; i < table.rows.length; i++) {
                    const row = table.rows[i];
                    if(row.cells[2].textContent!="Error" && row.cells[2].textContent!="~" && row.cells[2].textContent!="") {
                        data.push({
                            url: row.cells[1].textContent,
                            title: row.cells[2].textContent,
                            description: row.cells[3].textContent
                        });
                    }
                }
                fetch('save_links.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CAPTCHA': saveCaptchaInput.value.trim()
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showToast('All links saved to database!');
                    } else if (res.error && res.error.toLowerCase().includes('captcha')) {
                        showToast('Invalid CAPTCHA!', "#dc3545");
                    } else {
                        showToast('Error: ' + (res.error || 'Unknown error'), "#dc3545");
                    }
                    if (saveCaptchaImg) saveCaptchaImg.src = 'captcha_image.php?' + Date.now();
                    if (saveCaptchaInput) saveCaptchaInput.value = '';
                    saveBtn.disabled = false;
                })
                .catch(() => {
                    showToast('Failed to save data!', 'red');
                    if (saveCaptchaImg) saveCaptchaImg.src = 'captcha_image.php?' + Date.now();
                    if (saveCaptchaInput) saveCaptchaInput.value = '';
                    saveBtn.disabled = false;
                });
            });
        }

        if (window.resultUrls && Array.isArray(window.resultUrls)) {
            const table = document.getElementById('resultsTable');
            var idc = 0;
            window.resultUrls.forEach((url) => {
                const row = table.insertRow(-1);
                row.insertCell(0).textContent = ++idc;
                row.insertCell(1).textContent = url;
                row.insertCell(2).textContent = '~';
                row.insertCell(3).textContent = '~';

                fetch('https://datarain.ir/get_title.php?url=' + encodeURIComponent(url))
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        row.cells[2].textContent = data.title || '';
                        row.cells[3].textContent = data.description || '';
                    })
                    .catch(() => {
                        row.cells[2].textContent = 'Error';
                        row.cells[3].textContent = 'Error';
                    });
            });
        }
    });

    function showToast(message, color = "#28a745") {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.style.backgroundColor = color;
        toast.className = "show";
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
    }
</script>
</html>
