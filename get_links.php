<?php
/**
 * Extracts all unique domain links (ending with .com, .ir, .org, .ru) from a given URL or multiple paginated URLs.
 * Returns only the protocol and domain part, always starting with https:// if missing.
 */

// Main function to fetch and normalize links from a URL
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

    // 1. Extract links from <a href="">
    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!empty($href) && strpos($href, 'http') === 0) {
            // Extract only protocol and domain
            if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org|ru))\b/i', $href, $matches)) {
                $links[] = $matches[1];
            }
        }
    }

    // 2. Extract plain text URLs from the HTML body
    $body = '';
    $bodyNodes = $dom->getElementsByTagName('body');
    if ($bodyNodes->length > 0) {
        $body = $dom->saveHTML($bodyNodes->item(0));
    } else {
        $body = $html;
    }

    // Match URLs with http(s), www, or just domain in plain text
    if (preg_match_all('/((https?:\/\/)?(www\.)?([a-zA-Z0-9\-]+\.)+(com|ir|org|ru)\b[^\s"<>()]*)/i', $body, $matches)) {
        foreach ($matches[0] as $foundUrl) {
            $links[] = $foundUrl;
        }
    }

    // Remove duplicate links
    $links = array_unique($links);

    // Normalize links: ensure https:// and keep only protocol+domain
    $normalizedLinks = [];
    foreach ($links as $link) {
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . ltrim($link, '/');
        }
        // Extract only protocol and domain (ending with .com, .ir, .org, .ru)
        if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org|ru))\b/i', $link, $m)) {
            $normalizedLinks[] = $m[1];
        }
    }

    // Return unique, normalized links
    return array_unique($normalizedLinks);
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