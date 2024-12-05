<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

$courseId = isset($_POST['course_id']) ? trim($_POST['course_id']) : '';
$username = $_SESSION['username'] ?? '';

if (empty($courseId) || empty($username)) {
    echo "Invalid request.";
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if course exists
    $stmt = $conn->prepare('SELECT id FROM courses WHERE id = :course_id');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if (!$result->fetchArray()) {
        header('Location: courses.php');
        exit;
    }

    // Check if user is already enrolled
    $stmt = $conn->prepare('SELECT id FROM enrollments WHERE user_username = :username AND course_id = :course_id');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if ($result->fetchArray()) {
        // Already enrolled
        header("Location: course_detail.php?course_id=$courseId&message=already_enrolled");
        exit;
    }

    // Create new enrollment
    $stmt = $conn->prepare('INSERT INTO enrollments (user_username, course_id) VALUES (:username, :course_id)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        header("Location: course_detail.php?course_id=$courseId&message=enrolled");
    } else {
        throw new Exception("Failed to create enrollment");
    }

} catch (Exception $e) {
    custom_error_log("Error in enroll.php: " . $e->getMessage());
    echo "Failed to enroll in the course. Please try again later.";
}
exit;
?>
