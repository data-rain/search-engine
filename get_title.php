<?php
if (!isset($_GET['url'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No URL provided']);
    exit;
}

$url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
if (!$url) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

// Fetch the HTML content
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$html = curl_exec($ch);
curl_close($ch);

if (!$html) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unable to fetch the URL']);
    exit;
}

// Parse HTML
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML(mb_convert_encoding($html,'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

// Get title
$title = '';
$titleTags = $doc->getElementsByTagName('title');
if ($titleTags->length > 0) {
    $title = trim($titleTags->item(0)->textContent);
}

// Get meta description
$description = '';
$metaTags = $doc->getElementsByTagName('meta');
foreach ($metaTags as $meta) {
    if (strtolower($meta->getAttribute('name')) === 'description') {
        $description = trim($meta->getAttribute('content'));
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'title' => $title,
    'description' => $description
]);