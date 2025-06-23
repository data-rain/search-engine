<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

// دریافت URL از GET یا POST
$url = $_GET['url'] ?? $_POST['url'] ?? null;
if (!$url) {
    http_response_code(400);
    die("No URL provided.");
}

// بررسی امنیت اولیه
if (!preg_match('/^https?:\/\//i', $url)) {
    http_response_code(400);
    die("Invalid URL.");
}

// تابع ارسال درخواست curl با هدرهای استاندارد مرورگر
function curl_request($url, $returnHeaders = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($returnHeaders) {
        curl_setopt($ch, CURLOPT_HEADER, true);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    if(curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    $info = curl_getinfo($ch);
    curl_close($ch);

    return ['content' => $response, 'info' => $info];
}

// گرفتن هدر محتوا برای تشخیص نوع
$headerData = curl_request($url, true);
if ($headerData === false) {
    http_response_code(502);
    die("Error fetching the URL headers.");
}
$content = $headerData['content'];
$info = $headerData['info'];
$content_type = $info['content_type'] ?? 'application/octet-stream';

// اگر محتوا HTML بود، باید لینک‌ها رو بازنویسی کنیم
if (strpos($content_type, 'text/html') !== false) {
    // فقط محتوای بدنه رو جدا می‌کنیم (بدون هدرها)
    $header_size = $info['header_size'] ?? 0;
    $body = substr($content, $header_size);

    $base_url_parts = parse_url($url);
    $base_scheme = $base_url_parts['scheme'] ?? 'http';
    $base_host = $base_url_parts['host'] ?? '';
    $base_path = $base_url_parts['path'] ?? '/';

    $base_root = $base_scheme . '://' . $base_host;

    // تابع برای تبدیل لینک نسبی به مطلق
    function resolve_url($base, $rel) {
        global $base_root; // استفاده از base_root تعریف‌شده در بالا

        // اگر لینک از قبل مطلق است
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
        // اگر لینک با // شروع شده (schema-relative)
        if (substr($rel,0,2) == '//') {
            $scheme = parse_url($base, PHP_URL_SCHEME);
            return $scheme . ':' . $rel;
        }
        // اگر لینک با / شروع شده
        if ($rel[0] == '/') {
            $parts = parse_url($base);
            return $parts['scheme'] . '://' . $parts['host'] . $rel;
        }
        // لینک نسبی
        $path = parse_url($base, PHP_URL_PATH);
        $path = preg_replace('#/[^/]*$#', '/', $path);
        return $base_root . $path . $rel;
    }

    // بازنویسی src, href در HTML
    $body = preg_replace_callback('/(src|href)=["\']([^"\']+)["\']/', function($matches) use ($url) {
        $attr = $matches[1];
        $link = $matches[2];

        // تبدیل لینک نسبی به مطلق
        $abs_link = resolve_url($url, $link);

        // بازنویسی به پراکسی
        return $attr . '="proxy.php?url=' . urlencode($abs_link) . '"';
    }, $body);

    // بازنویسی url(...) داخل CSS (ساده)
    $body = preg_replace_callback('/url\((["\']?)(.*?)\1\)/i', function($matches) use ($url) {
        $link = $matches[2];
        $abs_link = resolve_url($url, $link);
        return 'url("proxy.php?url=' . urlencode($abs_link) . '")';
    }, $body);

    header("Content-Type: text/html; charset=utf-8");
    echo $body;
    exit;
}

// برای محتواهای غیر HTML، استریم مستقیم با curl

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // استریم مستقیم
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

// ارسال هدر نوع محتوا
header("Content-Type: " . $content_type);

// اجرای curl و خروجی مستقیم
curl_exec($ch);
curl_close($ch);
exit;
?>
