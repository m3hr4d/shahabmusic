<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$username = isset($_GET['username']) ? $_GET['username'] : '';

if ($course_id == 0 || empty($username)) {
    echo "پارامترهای نامعتبر.";
    exit;
}

$reviews_file = 'reviews.json';
$reviews = file_exists($reviews_file) ? json_decode(file_get_contents($reviews_file), true) : [];

// Find and remove the review
$reviews = array_filter($reviews, function($review) use ($course_id, $username) {
    return !($review['course_id'] == $course_id && $review['username'] == $username);
});

// Re-index the array
$reviews = array_values($reviews);

// Write the updated reviews back to the file
file_put_contents($reviews_file, json_encode($reviews, JSON_PRETTY_PRINT));

// Redirect to the feedback management page with a success message
header('Location: admin_feedback.php?message=deleted');
exit;
?>
