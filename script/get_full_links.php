<?php
/**
 * Extracts all unique domain links (ending with .com, .ir, .org, .ru) from a given URL or multiple paginated URLs.
 * Returns only the protocol and domain part, always starting with https:// if missing.
 */

// Main function to fetch and return all links from a URL (no filter)
function getAllLinks($url) {
    // Fetch the HTML content using cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html === false) {
        return [];
    }

    // Load HTML into DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $links = [];

    // 1. Extract all hrefs from <a> tags (no filter)
    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!empty($href)) {
            $links[] = $href;
        }
    }

    // 2. Extract plain text URLs from the HTML body (no filter)
    $body = '';
    $bodyNodes = $dom->getElementsByTagName('body');
    if ($bodyNodes->length > 0) {
        $body = $dom->saveHTML($bodyNodes->item(0));
    } else {
        $body = $html;
    }

    // Match all URLs in plain text (no TLD/domain filter)
    if (preg_match_all('/((https?:\/\/|www\.)[^\s"<>()]+)/i', $body, $matches)) {
        foreach ($matches[0] as $foundUrl) {
            $links[] = $foundUrl;
        }
    }

    // Remove duplicate links
    $links = array_unique($links);

    // Return all links as-is (no normalization)
    return array_values($links);
}

// Accept input from GET or POST
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

if (isset($input['url'])) {

    $url = $input['url'];
    $allLinks = [];
    $debugPages = [];

    // Check if both start_page and end_page are set and valid
    $hasPaging = isset($input['start_page'], $input['end_page']) 
        && is_numeric($input['start_page']) 
        && is_numeric($input['end_page']) 
        && intval($input['end_page']) >= intval($input['start_page']);

    if ($hasPaging) {
        $start_page = max(1, intval($input['start_page']));
        $end_page = max($start_page, intval($input['end_page']));
        // Crawl each page in the range
        for ($i = $start_page; $i <= $end_page; $i++) {
            $pageUrl = $url;
            if (strpos($url, '?') !== false) {
                // Replace existing page=... or append new
                if (preg_match('/([&?])page=\d*/', $url)) {
                    $pageUrl = preg_replace('/([&?])page=\d*/', '${1}page=' . $i, $url);
                } else {
                    $pageUrl .= '&page=' . $i;
                }
            } else {
                // Always add /N (even if ends with /)
                $pageUrl = rtrim($url, '/');
                $pageUrl .= '/' . $i;
            }
            $debugPages[] = $pageUrl;
            $links = getAllLinks($pageUrl);
            $allLinks = array_merge($allLinks, $links);
        }
    } else {
        // No paging: process only the base URL
        $debugPages[] = $url;
        $allLinks = getAllLinks($url);
    }

    // Remove duplicates
    $allLinks = array_unique($allLinks);

    header('Content-Type: application/json');
    echo json_encode([
        'pages_crawled' => $debugPages,
        'links' => array_values($allLinks)
    ], JSON_PRETTY_PRINT);
}
?>