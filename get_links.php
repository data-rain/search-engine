<?php

ini_set('default_socket_timeout', 5);

// Function to fetch all links from a given URL
function getAllLinks($url) {
    $html = @file_get_contents($url);
    if ($html === false) {
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $links = [];
    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!empty($href)) {
            if (strpos($href, 'http') === 0) {
                // Match until .com or .ir (including the extension)
                if (preg_match('/^(https?:\/\/[^\/]+?\.(com|ir|org))/', $href, $matches)) {
                    $links[] = $matches[1];
                }
            }
        }
    }
    return $links;
}

// Example usage:
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $links = getAllLinks($url);
    $valid_links=[];

    // Remove duplicate links
    $links = array_unique($links);

    foreach ($links as $link) {
        $response = @file_get_contents('http://datarain.ir/get_title.php?url=' . urlencode($link));
        $titleData = json_decode($response, true);
        if(!empty($titleData['title']))
        {
            $valid_links[]=["url"=>$link,"title"=>$titleData['title'],"description"=>$titleData['description']];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($valid_links, JSON_PRETTY_PRINT);
}
?>