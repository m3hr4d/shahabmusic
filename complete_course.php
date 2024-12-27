<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

if (!isset($_GET['course_id'])) {
    header('Location: courses.php');
    exit;
}

$courseId = $_GET['course_id'];
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
        header('Location: courses.php');
        exit;
    }

    // Get total videos and completed videos count
    $stmt = $conn->prepare('
        SELECT 
            (SELECT COUNT(*) FROM videos WHERE course_id = :course_id) as total_videos,
            (SELECT COUNT(*) FROM progress 
             WHERE user_username = :username 
             AND course_id = :course_id) as completed_videos
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $counts = $result->fetchArray(SQLITE3_ASSOC);

    // Check if all videos are completed
    if ($counts['total_videos'] > 0 && $counts['total_videos'] == $counts['completed_videos']) {
        // All videos completed, redirect to course content with success message
        header("Location: course_content.php?course_id=$courseId&message=completed");
    } else {
        // Not all videos completed, redirect back with error message
        header("Location: course_content.php?course_id=$courseId&message=incomplete");
    }

} catch (Exception $e) {
    custom_error_log("Error in complete_course.php: " . $e->getMessage());
    header("Location: course_content.php?course_id=$courseId&message=error");
}
exit;
?>
