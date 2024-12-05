<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    custom_error_log("Unauthorized access attempt to delete_course.php");
    header('Location: index.php');
    exit;
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($course_id === 0) {
    custom_error_log("Course ID not provided or invalid.");
    header('Location: admin_dashboard.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->exec('BEGIN TRANSACTION');

    // Fetch course details to get image and video file paths
    $course_stmt = $conn->prepare('SELECT * FROM courses WHERE id = :course_id');
    $course_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $course_result = $course_stmt->execute();
    $course = $course_result->fetchArray(SQLITE3_ASSOC);

    if (!$course) {
        custom_error_log("Course not found: ID " . $course_id);
        $conn->exec('ROLLBACK');
        header('Location: admin_dashboard.php');
        exit;
    }

    // Fetch videos associated with the course
    $videos_stmt = $conn->prepare('SELECT * FROM videos WHERE course_id = :course_id');
    $videos_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $videos_result = $videos_stmt->execute();

    $videos = [];
    while ($video = $videos_result->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $video;
    }

    // Delete course from 'courses' table
    $delete_course_stmt = $conn->prepare('DELETE FROM courses WHERE id = :course_id');
    $delete_course_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $delete_course_stmt->execute();

    // Commit transaction
    $conn->exec('COMMIT');

    // Remove course image file if it exists
    if (!empty($course['image']) && file_exists($course['image'])) {
        unlink($course['image']);
        custom_error_log("Course image deleted: " . $course['image']);
    }

    // Remove video files if they exist
    foreach ($videos as $video) {
        if (!empty($video['url']) && file_exists($video['url'])) {
            unlink($video['url']);
            custom_error_log("Course video deleted: " . $video['url']);
        }
    }

    custom_error_log("Course deleted successfully: ID " . $course_id);
    $_SESSION['success_message'] = "دوره با موفقیت حذف شد!";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->exec('ROLLBACK');
    custom_error_log("Failed to delete course: ID " . $course_id . ". Error: " . $e->getMessage());
    $_SESSION['error_message'] = "خطا در حذف دوره. لطفا دوباره تلاش کنید.";
}

header('Location: admin_dashboard.php');
exit;
?>
