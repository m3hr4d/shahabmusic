<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($courseId == 0) {
    echo "Invalid course ID.";
    exit;
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch course data
$stmt = $conn->prepare('SELECT * FROM courses WHERE id = :id');
$stmt->bindValue(':id', $courseId, SQLITE3_INTEGER);
$courseResult = $stmt->execute();
$course = $courseResult->fetchArray(SQLITE3_ASSOC);

if (!$course) {
    echo "Course not found.";
    exit;
}

// Fetch videos for the course
$videosStmt = $conn->prepare('SELECT * FROM videos WHERE course_id = :course_id ORDER BY order_index ASC');
$videosStmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
$videosResult = $videosStmt->execute();

$videos = [];
while ($video = $videosResult->fetchArray(SQLITE3_ASSOC)) {
    $videos[] = $video;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">ویرایش دوره: <?php echo htmlspecialchars($course['title']); ?></h1>
    <form action="process_edit_course.php" method="POST" class="max-w-lg">
        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">عنوان دوره</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">توضیحات دوره</label>
            <textarea id="description" name="description" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="4"><?php echo htmlspecialchars($course['description']); ?></textarea>
        </div>
        <div class="mb-4">
            <label for="image_url" class="block text-gray-700 text-sm font-bold mb-2">آدرس تصویر ویژه</label>
            <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($course['image']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div id="lessons-container" class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">درس‌ها</label>
            <?php foreach ($videos as $index => $video): ?>
                <div class="lesson-item mb-4 border p-4 rounded">
                    <h3 class="font-bold mb-2">درس <?php echo $index + 1; ?></h3>
                    <div class="mb-2">
                        <label for="lesson_title_<?php echo $index; ?>" class="block text-gray-700 text-sm font-bold mb-2">عنوان درس</label>
                        <input type="text" id="lesson_title_<?php echo $index; ?>" name="lesson_title_<?php echo $index; ?>" value="<?php echo htmlspecialchars($video['title']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-2">
                        <label for="lesson_description_<?php echo $index; ?>" class="block text-gray-700 text-sm font-bold mb-2">توضیحات درس</label>
                        <textarea id="lesson_description_<?php echo $index; ?>" name="lesson_description_<?php echo $index; ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="2"><?php echo htmlspecialchars($video['description']); ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label for="lesson_url_<?php echo $index; ?>" class="block text-gray-700 text-sm font-bold mb-2">آدرس ویدئو</label>
                        <input type="url" id="lesson_url_<?php echo $index; ?>" name="lesson_url_<?php echo $index; ?>" value="<?php echo htmlspecialchars($video['url']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Hidden field to keep track of the number of lessons -->
        <input type="hidden" id="lessons_count" name="lessons_count" value="<?php echo count($videos); ?>">
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                ذخیره تغییرات
            </button>
            <a href="admin_dashboard.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                بازگشت
            </a>
        </div>
    </form>
</div>

<?php include('footer.php'); ?>
