<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

$username = $_SESSION['username'];

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch user's enrollments
$enrollments_stmt = $conn->prepare('SELECT course_id FROM enrollments WHERE user_username = :username');
$enrollments_stmt->bindValue(':username', $username, SQLITE3_TEXT);
$enrollments_result = $enrollments_stmt->execute();

$user_enrollments = [];
while ($enrollment = $enrollments_result->fetchArray(SQLITE3_ASSOC)) {
    $user_enrollments[] = $enrollment['course_id'];
}

$completedCourses = [];

foreach ($user_enrollments as $course_id) {
    // Fetch course details
    $course_stmt = $conn->prepare('SELECT * FROM courses WHERE id = :course_id');
    $course_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $course_result = $course_stmt->execute();
    $course = $course_result->fetchArray(SQLITE3_ASSOC);

    if (!$course) {
        continue; // Skip if course not found
    }

    // Fetch total number of videos in the course
    $videos_stmt = $conn->prepare('SELECT COUNT(*) as total_videos FROM videos WHERE course_id = :course_id');
    $videos_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $videos_result = $videos_stmt->execute();
    $videos_data = $videos_result->fetchArray(SQLITE3_ASSOC);
    $total_videos = intval($videos_data['total_videos']);

    // Fetch user's completed videos in this course
    $progress_stmt = $conn->prepare('SELECT COUNT(*) as completed_videos FROM progress WHERE user_username = :username AND course_id = :course_id');
    $progress_stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $progress_stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $progress_result = $progress_stmt->execute();
    $progress_data = $progress_result->fetchArray(SQLITE3_ASSOC);
    $completed_videos = intval($progress_data['completed_videos']);

    if ($total_videos > 0 && $completed_videos === $total_videos) {
        // Course is completed
        $completedCourses[] = $course;
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">دوره‌های تکمیل شده</h1>
    <?php if (empty($completedCourses)): ?>
        <p class="text-center text-gray-600">شما هنوز هیچ دوره‌ای را تکمیل نکرده‌اید.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($completedCourses as $course): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(mb_substr($course['description'], 0, 100) . '...'); ?></p>
                        <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">مرور دوره</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
