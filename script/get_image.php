<?php
$url = $_GET['url'] ?? '';

if (filter_var($url, FILTER_VALIDATE_URL)) {
    // پاک‌سازی آدرس
    $cleanUrl = filter_var($url, FILTER_SANITIZE_URL);

    // ساخت URL نهایی برای thum.io
    $screenshotUrl = "https://image.thum.io/get/width/1200/crop/800/" . $cleanUrl;

    // راه‌اندازی cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $screenshotUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // در صورت مشکل SSL
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);

    $imageData = curl_exec($ch);

    if ($imageData === false) {
        $error = curl_error($ch);
        echo "خطا در cURL: " . $error;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 200) {
            header("Content-Type: image/png");
            echo $imageData;
        } else {
            echo "کد HTTP دریافتی از thum.io: $httpCode<br>";
            echo "URL فرستاده‌شده: <pre>$screenshotUrl</pre>";
        }
    }

    curl_close($ch);
} else {
    echo "آدرس معتبر وارد کنید.";
}
