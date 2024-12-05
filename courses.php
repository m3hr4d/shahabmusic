<?php
session_start();

require_once 'db.php';

$username = $_SESSION['username'] ?? null;

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch courses
$courses_result = $conn->query('SELECT * FROM courses ORDER BY created_at DESC');
$courses = [];
while ($course = $courses_result->fetchArray(SQLITE3_ASSOC)) {
    // Fetch videos for each course
    $videos_stmt = $conn->prepare('SELECT * FROM videos WHERE course_id = :course_id ORDER BY order_index ASC');
    $videos_stmt->bindValue(':course_id', $course['id'], SQLITE3_INTEGER);
    $videos_result = $videos_stmt->execute();

    $videos = [];
    while ($video = $videos_result->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $video;
    }
    $course['videos'] = $videos;
    $courses[] = $course;
}

$userProgress = [];
$userEnrollments = [];

if ($username) {
    // Fetch user enrollments
    $enrollments_stmt = $conn->prepare('SELECT course_id FROM enrollments WHERE user_username = :username');
    $enrollments_stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $enrollments_result = $enrollments_stmt->execute();

    while ($enrollment = $enrollments_result->fetchArray(SQLITE3_ASSOC)) {
        $userEnrollments[] = $enrollment['course_id'];
    }

    // Fetch user progress
    $progress_stmt = $conn->prepare('SELECT course_id, video_id FROM progress WHERE user_username = :username');
    $progress_stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $progress_result = $progress_stmt->execute();

    while ($progress = $progress_result->fetchArray(SQLITE3_ASSOC)) {
        $course_id = $progress['course_id'];
        $video_id = $progress['video_id'];
        if (!isset($userProgress[$course_id])) {
            $userProgress[$course_id] = [];
        }
        $userProgress[$course_id][] = $video_id;
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <?php if (!isset($_SESSION['user_logged_in'])): ?>
        <p class="mb-4 text-red-500">لطفا برای دسترسی به دوره‌ها وارد شوید</p>
    <?php endif; ?>

    <?php if (empty($courses)): ?>
        <p>هیچ دوره‌ای موجود نیست</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($course['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($course['title'] ?? ''); ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($course['title'] ?? 'دوره بدون عنوان'); ?></h2>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($course['description'] ?? 'توضیحی موجود نیست'); ?></p>
                        <?php if (isset($_SESSION['user_logged_in'])): ?>
                            <?php
                            $course_videos_count = count($course['videos']);
                            $user_progress_count = isset($userProgress[$course['id']]) ? count($userProgress[$course['id']]) : 0;
                            $isCourseCompleted = ($user_progress_count === $course_videos_count) && ($course_videos_count > 0);
                            $isEnrolled = in_array($course['id'], $userEnrollments);
                            ?>
                            <?php if ($isCourseCompleted): ?>
                                <a href="course_content.php?course_id=<?php echo $course['id']; ?>"
                                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded inline-block">
                                    مرور دوره
                                </a>
                            <?php elseif ($isEnrolled): ?>
                                <a href="course_content.php?course_id=<?php echo $course['id']; ?>"
                                   class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-block">
                                    ادامه دوره
                                </a>
                            <?php else: ?>
                                <form action="enroll.php" method="POST">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-block">
                                        ثبت نام و شروع دوره
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login_member.php"
                               class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-block">
                                ورود برای شروع دوره
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
