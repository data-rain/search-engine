<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

if (!isset($_GET['url'])) {
    echo json_encode(['error' => 'No URL provided']);
    exit;
}

$url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
if (!$url) {
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
);

$html = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

// حذف تگ‌های ناخواسته مثل <script>، <style>، <noscript> و ...
$tagsToRemove = ['script', 'style', 'noscript', 'svg', 'iframe'];

foreach ($tagsToRemove as $tag) {
    $elements = $doc->getElementsByTagName($tag);
    while ($elements->length > 0) {
        $element = $elements->item(0);
        $element->parentNode->removeChild($element);
    }
}

$title = '';
$description = '';
$keywords = '';
$fullText = '';

// عنوان
$titleTags = $doc->getElementsByTagName('title');
if ($titleTags->length > 0) {
    $title = trim($titleTags->item(0)->textContent);
}

// متا تگ‌ها
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

// استخراج متن خالص
$bodyTags = $doc->getElementsByTagName('body');
if ($bodyTags->length > 0) {
    $fullText = trim($bodyTags->item(0)->textContent);
    // فشرده‌سازی متن: حذف فاصله‌ها و خطوط اضافه
    $fullText = preg_replace('/\s+/', ' ', $fullText);
}

echo json_encode([
    'title' => $title ?: null,
    'description' => $description ?: null,
    'keywords' => $keywords ?: null,
    'text' => $fullText ?: null
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
