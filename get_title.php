<?php
header('Content-Type: application/json; charset=utf-8');

// تنظیم دامنه‌های مجاز برای دسترسی (CORS)
$allowedOrigins = ['https://datarain.ir', 'http://localhost:8080'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// بررسی وجود پارامتر url
if (!isset($_GET['url'])) {
    echo json_encode(['error' => 'No URL provided']);
    exit;
}

// اعتبارسنجی اولیه آدرس
$url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
if (!$url) {
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

// جلوگیری از SSRF: بررسی IP مقصد
$host = parse_url($url, PHP_URL_HOST);
$ip = gethostbyname($host);
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    echo json_encode(['error' => 'Blocked private or reserved IP address']);
    exit;
}

// تنظیمات cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SafeBot/1.0)',
]);

$html = curl_exec($ch);

// بررسی خطای cURL
if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// بررسی نوع محتوا
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
if (stripos($contentType, 'text/html') === false) {
    echo json_encode(['error' => 'Unsupported content type']);
    exit;
}

// بارگذاری HTML
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

// حذف تگ‌های ناخواسته
$tagsToRemove = ['script', 'style', 'noscript', 'svg', 'iframe'];
foreach ($tagsToRemove as $tag) {
    while (($elements = $doc->getElementsByTagName($tag))->length > 0) {
        $element = $elements->item(0);
        $element->parentNode->removeChild($element);
    }
}

// استخراج اطلاعات
$title = '';
$description = '';
$keywords = '';
$fullText = '';

// عنوان
$titleTags = $doc->getElementsByTagName('title');
if ($titleTags->length > 0) {
    $title = trim($titleTags->item(0)->textContent);
}

// متا
$metaTags = $doc->getElementsByTagName('meta');
foreach ($metaTags as $meta) {
    $nameAttr = strtolower($meta->getAttribute('name'));
    $propertyAttr = strtolower($meta->getAttribute('property'));

    if ($nameAttr === 'description' || $propertyAttr === 'og:description') {
        $description = trim(strip_tags($meta->getAttribute('content')));
    }

    if ($nameAttr === 'keywords') {
        $keywords = trim(strip_tags($meta->getAttribute('content')));
    }
}

// متن
$bodyTags = $doc->getElementsByTagName('body');
if ($bodyTags->length > 0) {
    $fullText = trim($bodyTags->item(0)->textContent);
    $fullText = preg_replace('/\s+/', ' ', $fullText);
    $fullText = mb_substr($fullText, 0, 10000); // محدود به 10 هزار کاراکتر
}

// خروجی نهایی
echo json_encode([
    'title' => $title ?: null,
    'description' => $description ?: null,
    'keywords' => $keywords ?: null,
    'text' => $fullText ?: null
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
