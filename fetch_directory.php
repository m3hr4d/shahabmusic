<?php
session_start();
require_once 'error_log.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['url']) || empty($data['url'])) {
    echo json_encode(['error' => 'URL is required']);
    exit;
}

$url = $data['url'];

try {
    // Log the URL being processed
    custom_error_log("Fetching directory listing from: " . $url);

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
    // Force IPv4 resolution
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    // Execute cURL request
    $html = curl_exec($ch);

    // Check for cURL errors
    if ($html === false) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new Exception('Failed to fetch directory listing: ' . $error_msg);
    }

    // Close cURL session
    curl_close($ch);

    // Parse HTML and extract video links
    $videos = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $links = $dom->getElementsByTagName('a');

    // Common video file extensions
    $videoExtensions = ['mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm'];

    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        // Check if the link is a video file
        $extension = strtolower(pathinfo($href, PATHINFO_EXTENSION));
        if (in_array($extension, $videoExtensions)) {
            // Make sure we have an absolute URL
            if (strpos($href, 'http') !== 0) {
                $href = rtrim($url, '/') . '/' . ltrim($href, '/');
            }
            $videos[] = $href;
            custom_error_log("Found video: " . $href);
        }
    }

    if (empty($videos)) {
        throw new Exception('No video files found in the directory');
    }

    echo json_encode(['videos' => $videos]);

} catch (Exception $e) {
    custom_error_log("Error in fetch_directory.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
