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

        $response = @file_get_contents('https://datarain.ir/get_links.php?url=' . urlencode($url));
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
        #saveForm input[type="text"]:focus {
            border-color: #007BFF;
            outline: none;
            background: #eef6ff;
        }
        #saveAllBtn:hover {
            background: #0056b3;
        }
        #saveCaptchaImg:hover {
            box-shadow: 0 0 4px #007BFF;
        }
        /* Toast notification styles */
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
    <form id="searchForm" method="POST" action="" autocomplete="off">
        <h2>Add all links</h2>
        <label for="url">URL:</label>

        <input type="url" id="url" name="url" value="https://" required>

        <div class="captcha">
            <img src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;">
        </div>

        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" required>

        <button type="button" onclick="window.location.href='..'">← Back</button>
        <button type="submit" style="font-weight: bold; width:50%;"> Search </button>
    </form>

    <div id="toast"></div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data) && is_array($data)) {
        $urls = [];
        foreach ($data as $item) {
            if (!empty($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                $urls[] = trim($item);
            }
        }
        $jsUrls = json_encode($urls);

        //Hide form
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var searchForm = document.getElementById('searchForm');
                if (searchForm) searchForm.style.display = 'none';
            });
            </script>";

        echo "<h3>Results : " . count($urls) . " </h3>";
        echo '<div style="overflow-x:auto; max-width:100vw; margin:auto;">'; // Add this wrapper div
        echo '<table id="resultsTable" border="1" cellpadding="8" style="margin:auto; background:#fff; border-radius:8px; min-width:600px;">';
        echo '<tr><th>#</th><th>URL</th><th>Title</th><th>Description</th></tr>';
        echo '</table>';
        echo '</div>';
        echo '<form id="saveForm" style="max-width:400px;margin:32px auto 0 auto;padding:24px;border:2px solid #007BFF;border-radius:12px;background:#f9f9f9;box-shadow:0 2px 8px rgba(0,0,0,0.07);display:flex;flex-direction:column;align-items:center;">';
        echo '<div style="display:flex;align-items:center;width:100%;margin-bottom:16px;">';
        echo '<img id="saveCaptchaImg" src="captcha_image.php" alt="CAPTCHA" style="vertical-align:middle;cursor:pointer;margin-right:12px;border-radius:6px;border:1px solid #ccc;">';
        echo '<input type="text" id="saveCaptchaInput" placeholder="Enter CAPTCHA" style="flex:1;padding:8px;border-radius:6px;border:1px solid #ccc;" required>';
        echo '</div>';
        echo '<div style="display:flex;justify-content:space-between;width:100%;gap:12px;">';
        echo '<button type="button" onclick="window.location.href=\'..\'" style="flex:1;padding:14px 0;font-size:1.1em;font-weight:bold;background:linear-gradient(90deg,#6c757d 60%,#495057 100%);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(108,117,125,0.10);transition:background 0.2s,transform 0.1s;letter-spacing:0.5px;cursor:pointer;outline:none;display:flex;align-items:center;justify-content:center;gap:8px;">
            <svg width="20" height="20" fill="none" style="margin-right:6px;"><path d="M15 5l-6 5 6 5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Cancel
            </button>';
        echo '<button id="saveAllBtn" type="submit" style="flex:1;padding:14px 0;font-size:1.1em;font-weight:bold;background:linear-gradient(90deg,#007BFF 60%,#0056b3 100%);color:#fff;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(0,123,255,0.10);transition:background 0.2s,transform 0.1s;letter-spacing:0.5px;cursor:pointer;outline:none;display:flex;align-items:center;justify-content:center;gap:8px;">
            <svg width="20" height="20" fill="none" style="margin-right:6px;"><path d="M10 2v12m0 0l-4-4m4 4l4-4M4 16h12" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Save All
            </button>';
        echo '</div>';
        echo '</form>';
        echo "<script>window.resultUrls = $jsUrls;</script>";
    }
    ?>
</body>
</html>

<script type="text/javascript" charset="UTF-8">
    // Refresh CAPTCHA image when clicked
    document.addEventListener('DOMContentLoaded', function() {
        // Refresh main form CAPTCHA
        const captchaImg = document.querySelector('.captcha img');
        if (captchaImg) {
            captchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        }

        const saveForm = document.getElementById('saveForm');
        const saveBtn = document.getElementById('saveAllBtn');
        const saveCaptchaImg = document.getElementById('saveCaptchaImg');
        const saveCaptchaInput = document.getElementById('saveCaptchaInput');

        if (saveCaptchaImg) {
            saveCaptchaImg.addEventListener('click', function() {
                this.src = 'captcha_image.php?' + Date.now();
            });
        }

        if (saveForm) {
            saveForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                saveBtn.disabled = true; // Disable button to prevent multiple submits

                const table = document.getElementById('resultsTable');
                const data = [];
                for (let i = 1; i < table.rows.length; i++) { // skip header row
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
                    // Always refresh CAPTCHA and clear input after submit
                    if (saveCaptchaImg) saveCaptchaImg.src = 'captcha_image.php?' + Date.now();
                    if (saveCaptchaInput) saveCaptchaInput.value = '';
                    saveBtn.disabled = false; // Re-enable button
                })
                .catch(() => {
                    showToast('Failed to save data!', 'red');
                    if (saveCaptchaImg) saveCaptchaImg.src = 'captcha_image.php?' + Date.now();
                    if (saveCaptchaInput) saveCaptchaInput.value = '';
                    saveBtn.disabled = false; // Re-enable button
                });
            });
        }

        // Fetch title/description for each URL and update table
        if (window.resultUrls && Array.isArray(window.resultUrls)) {
            const table = document.getElementById('resultsTable');
            var idc=0;
            window.resultUrls.forEach((url) => {
                // Add row with loading placeholders
                const row = table.insertRow(-1);
                row.insertCell(0).textContent = ++idc;
                row.insertCell(1).textContent = url;
                row.insertCell(2).textContent = '~';
                row.insertCell(3).textContent = '~';

                // Fetch info via AJAX
                fetch('https://datarain.ir/get_title.php?url=' + encodeURIComponent(url))
                    .then(res => {
                        console.log('Fetching:', url, 'Status:', res.status);
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        row.cells[2].textContent = data.title || '';
                        row.cells[3].textContent = data.description || '';
                    })
                    .catch((err) => {
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
