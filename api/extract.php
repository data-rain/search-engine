<?php
// =========================================
// API: Safe HTML Extractor v1.0
// POST JSON: { "url": "https://example.com" }
// =========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// --- CORS ---
$allowedOrigins = ['https://datarain.php', 'http://localhost:8080'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// --- Rate limiting (by IP) ---
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . "/extract_rate_$ip.txt";
$lastRequestTime = file_exists($rateFile) ? (int)file_get_contents($rateFile) : 0;
$currentTime = time();

if ($currentTime - $lastRequestTime < 3) { // 1 request per 3 seconds
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests, please wait.']);
    exit;
}
file_put_contents($rateFile, $currentTime);

// --- Read and parse JSON ---
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input. Expecting { "url": "..." }']);
    exit;
}

$url = filter_var($input['url'], FILTER_VALIDATE_URL);
if (!$url) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

// --- Prevent SSRF ---
$host = parse_url($url, PHP_URL_HOST);
$resolvedIp = gethostbyname($host);
if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    http_response_code(403);
    echo json_encode(['error' => 'Blocked private/reserved IP']);
    exit;
}

// --- Fetch with cURL ---
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'SafeBot/1.0',
]);
$html = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    logError("cURL Error for $url from $ip: $curlError");
    echo json_encode(['error' => 'cURL Error: ' . $curlError]);
    exit;
}
if (stripos($contentType, 'text/html') === false) {
    http_response_code(415);
    echo json_encode(['error' => 'Unsupported content type']);
    exit;
}

// --- Parse HTML ---
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();

// --- Clean unwanted tags ---
$tagsToRemove = ['script', 'style', 'noscript', 'svg', 'iframe'];
foreach ($tagsToRemove as $tag) {
    while (($elements = $doc->getElementsByTagName($tag))->length > 0) {
        $element = $elements->item(0);
        $element->parentNode->removeChild($element);
    }
}

// --- Extract content ---
$title = '';
$description = '';
$keywords = '';
$fullText = '';

$titleTags = $doc->getElementsByTagName('title');
if ($titleTags->length > 0) {
    $title = trim($titleTags->item(0)->textContent);
}

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

$bodyTags = $doc->getElementsByTagName('body');
if ($bodyTags->length > 0) {
    $fullText = trim($bodyTags->item(0)->textContent);
    $fullText = preg_replace('/\s+/', ' ', $fullText);
    $fullText = mb_substr($fullText, 0, 10000);
}

// --- Log success ---
logRequest($url, $ip);

// --- Final output ---
echo json_encode([
    'title' => $title ?: null,
    'description' => $description ?: null,
    'keywords' => $keywords ?: null,
    'text' => $fullText ?: null
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


// --- Logging function ---
function logRequest($url, $ip) {
    $log = sprintf("[%s] IP: %s - URL: %s\n", date('Y-m-d H:i:s'), $ip, $url);
    file_put_contents(__DIR__ . '/log_requests.txt', $log, FILE_APPEND);
}
function logError($msg) {
    $log = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $msg);
    file_put_contents(__DIR__ . '/log_errors.txt', $log, FILE_APPEND);
}
?>
