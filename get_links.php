<?php

// Function to fetch all links from a given URL
function getAllLinks($url) {
    $html = @file_get_contents($url);
    if ($html === false) {
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $links = [];
    // 1. Extract from <a href="">
    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!empty($href) && strpos($href, 'http') === 0) {
            if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org))/', $href, $matches)) {
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

    // Match URLs with http(s), www, or just domain
    if (preg_match_all('/((https?:\/\/)?(www\.)?([a-zA-Z0-9\-]+\.)+(com|ir|org|ru)\b[^\s"<>()]*)/i', $body, $matches)) {
        foreach ($matches[0] as $foundUrl) {
            $links[] = $foundUrl;
        }
    }

    // Remove duplicates
    $links = array_unique($links);

    // Add https:// if not present and limit to domain only
    $normalizedLinks = [];
    foreach ($links as $link) {
        // Add https:// if missing
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . ltrim($link, '/');
        }
        // Extract only the protocol and domain (ending with .com, .ir, .org)
        if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org|ru))\b/i', $link, $m)) {
            $normalizedLinks[] = $m[1];
        }
    }

    return array_unique($normalizedLinks);
}

// Example usage:
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $links = getAllLinks($url);

    header('Content-Type: application/json');
    echo json_encode(array_values($links), JSON_PRETTY_PRINT);
}
?>