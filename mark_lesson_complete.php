<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['course_id']) || !isset($_POST['video_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$courseId = $_POST['course_id'];
$videoId = $_POST['video_id'];
$username = $_SESSION['username'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if user is enrolled
    $stmt = $conn->prepare('
        SELECT 1 FROM enrollments 
        WHERE user_username = :username AND course_id = :course_id
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Not enrolled in this course']);
        exit;
    }

    // Check if video exists in the course
    $stmt = $conn->prepare('
        SELECT 1 FROM videos 
        WHERE id = :video_id AND course_id = :course_id
    ');
    $stmt->bindValue(':video_id', $videoId, SQLITE3_INTEGER);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Invalid video']);
        exit;
    }

    // Mark lesson as complete (if not already completed)
    $stmt = $conn->prepare('
        INSERT OR IGNORE INTO progress (user_username, course_id, video_id)
        VALUES (:username, :course_id, :video_id)
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $stmt->bindValue(':video_id', $videoId, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to mark lesson as complete");
    }

} catch (Exception $e) {
    custom_error_log("Error in mark_lesson_complete.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
