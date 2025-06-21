<?php
/**
 * Extracts all unique domain links (ending with .com, .ir, .org, .ru) from a given URL.
 * Returns only the protocol and domain part, always starting with https:// if missing.
 */

// Main function to fetch and normalize links from a URL
function getAllLinks($url) {
    // Fetch HTML content
    $html = @file_get_contents($url);
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

// If called via GET, output JSON result
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $links = getAllLinks($url);

    header('Content-Type: application/json');
    echo json_encode(array_values($links), JSON_PRETTY_PRINT);
}
?>