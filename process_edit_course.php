<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
if ($courseId === 0) {
    echo "Invalid course ID.";
    exit;
}

$title = $_POST['title'];
$description = $_POST['description'];
$imageUrl = $_POST['image_url'];

$lessonsCount = isset($_POST['lessons_count']) ? intval($_POST['lessons_count']) : 0;
$lessons = [];

for ($i = 0; $i < $lessonsCount; $i++) {
    $lessonTitle = $_POST['lesson_title_' . $i];
    $lessonDescription = $_POST['lesson_description_' . $i];
    $lessonUrl = $_POST['lesson_url_' . $i];
    $lessons[] = [
        'title' => $lessonTitle,
        'description' => $lessonDescription,
        'url' => $lessonUrl
    ];
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Start transaction
    $conn->exec('BEGIN TRANSACTION');

    try {
        // Update course
        $stmt = $conn->prepare('
            UPDATE courses
            SET title = :title, description = :description, image = :image, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');

        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':image', $imageUrl, SQLITE3_TEXT);
        $stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update course");
        }

        // Delete existing videos
        $deleteVideosStmt = $conn->prepare('DELETE FROM videos WHERE course_id = :course_id');
        $deleteVideosStmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
        $deleteVideosStmt->execute();

        // Insert updated videos
        $insertVideoStmt = $conn->prepare('
            INSERT INTO videos (course_id, url, title, description, order_index)
            VALUES (:course_id, :url, :title, :description, :order_index)
        ');

        foreach ($lessons as $index => $lesson) {
            $insertVideoStmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
            $insertVideoStmt->bindValue(':url', $lesson['url'], SQLITE3_TEXT);
            $insertVideoStmt->bindValue(':title', $lesson['title'], SQLITE3_TEXT);
            $insertVideoStmt->bindValue(':description', $lesson['description'], SQLITE3_TEXT);
            $insertVideoStmt->bindValue(':order_index', $index, SQLITE3_INTEGER);
            if (!$insertVideoStmt->execute()) {
                throw new Exception("Failed to insert video at index $index");
            }
        }

        // Commit transaction
        $conn->exec('COMMIT');

        header("Location: admin_dashboard.php?message=course_updated");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->exec('ROLLBACK');
        throw $e;
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
    exit;
}
?>
