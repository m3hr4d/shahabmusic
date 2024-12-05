<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    custom_error_log("Unauthorized access attempt to process add course");
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_course.php');
    exit;
}

try {
    // Log received data
    custom_error_log("Received POST data: " . print_r($_POST, true));

    // Get course data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $lessons = json_decode($_POST['lessons'] ?? '[]', true);

    // Log decoded lessons data
    custom_error_log("Decoded lessons data: " . print_r($lessons, true));

    if (empty($title) || empty($image_url) || empty($lessons)) {
        custom_error_log("Missing required fields. Title: $title, Image: $image_url, Lessons count: " . count($lessons));
        header('Location: add_course.php?error=missing_fields');
        exit;
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Start transaction
    custom_error_log("Starting transaction");
    $conn->exec('BEGIN TRANSACTION');

    try {
        // Insert course
        $stmt = $conn->prepare('
            INSERT INTO courses (title, description, image) 
            VALUES (:title, :description, :image)
        ');
        
        custom_error_log("Inserting course - Title: $title, Description: $description, Image: $image_url");
        
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':image', $image_url, SQLITE3_TEXT);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute course insert statement");
        }

        // Get the new course ID
        $courseId = $conn->lastInsertRowID();
        custom_error_log("Created course with ID: " . $courseId);

        // Insert videos
        $stmt = $conn->prepare('
            INSERT INTO videos (course_id, url, title, description, order_index) 
            VALUES (:course_id, :url, :title, :description, :order_index)
        ');

        foreach ($lessons as $index => $lesson) {
            custom_error_log("Processing lesson $index: " . print_r($lesson, true));
            
            $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
            $stmt->bindValue(':url', $lesson['url'], SQLITE3_TEXT);
            $stmt->bindValue(':title', $lesson['title'], SQLITE3_TEXT);
            $stmt->bindValue(':description', $lesson['description'], SQLITE3_TEXT);
            $stmt->bindValue(':order_index', $index, SQLITE3_INTEGER);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute video insert statement for index $index");
            }
            custom_error_log("Added video at index $index for course $courseId");
        }

        // Commit transaction
        custom_error_log("Committing transaction");
        $conn->exec('COMMIT');
        
        custom_error_log("Successfully created course and added videos");
        header('Location: admin_dashboard.php?success=course_added');
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        custom_error_log("Rolling back transaction due to error: " . $e->getMessage());
        $conn->exec('ROLLBACK');
        throw $e;
    }

} catch (Exception $e) {
    custom_error_log("Failed to save course data: " . $e->getMessage());
    header('Location: add_course.php?error=save_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>
