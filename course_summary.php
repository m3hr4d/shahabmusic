<?php
session_start();

require_once 'db.php';

$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId === 0) {
    echo "Invalid course ID.";
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch course details
$course_stmt = $conn->prepare('SELECT * FROM courses WHERE id = :course_id');
$course_stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
$course_result = $course_stmt->execute();
$course = $course_result->fetchArray(SQLITE3_ASSOC);

if (!$course) {
    echo "Course not found.";
    exit;
}

// Fetch videos for the course
$videos_stmt = $conn->prepare('SELECT * FROM videos WHERE course_id = :course_id ORDER BY order_index ASC');
$videos_stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
$videos_result = $videos_stmt->execute();

$videos = [];
while ($video = $videos_result->fetchArray(SQLITE3_ASSOC)) {
    $videos[] = $video;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($course['title']); ?></h2>
        <p class="mb-4"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

        <?php if (!empty($course['image'])): ?>
            <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="تصویر دوره" class="mb-4 max-w-md">
        <?php endif; ?>

        <h3 class="text-xl font-bold mb-2">ویدیوها:</h3>
        <?php if (!empty($videos)): ?>
            <ul class="list-disc pl-5 mb-4">
                <?php foreach ($videos as $video): ?>
                    <li><?php echo htmlspecialchars($video['title']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="mb-4">هنوز ویدیویی آپلود نشده است.</p>
        <?php endif; ?>

        <a href="edit_course.php?id=<?php echo $courseId; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ویرایش دوره
        </a>
    </div>
</div>

<?php include('footer.php'); ?>
