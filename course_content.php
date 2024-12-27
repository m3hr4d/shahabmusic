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

$courseId = intval($_GET['course_id']);
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

    // Get course details
    $stmt = $conn->prepare('
        SELECT * FROM courses 
        WHERE id = :course_id
    ');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $course = $result->fetchArray(SQLITE3_ASSOC);

    if (!$course) {
        header('Location: courses.php');
        exit;
    }

    // Get course videos
    $stmt = $conn->prepare('
        SELECT * FROM videos
        WHERE course_id = :course_id
        ORDER BY order_index ASC
    ');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $videosResult = $stmt->execute();

    $videos = [];
    while ($row = $videosResult->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $row;
    }

    // Get user's completed videos for this course
    $stmt = $conn->prepare('
        SELECT video_id FROM progress
        WHERE user_username = :username AND course_id = :course_id
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $progressResult = $stmt->execute();

    $completedVideoIds = [];
    while ($row = $progressResult->fetchArray(SQLITE3_ASSOC)) {
        $completedVideoIds[] = $row['video_id'];
    }

    // Check if user has already reviewed this course
    $stmt = $conn->prepare('
        SELECT 1 FROM reviews
        WHERE user_username = :username AND course_id = :course_id
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $reviewResult = $stmt->execute();
    $hasReviewed = (bool)$reviewResult->fetchArray();

    // Determine if the course is completed
    $totalVideos = count($videos);
    $completedVideos = count($completedVideoIds);
    $isCourseCompleted = ($totalVideos > 0 && $completedVideos === $totalVideos);

    include('header.php');
    ?>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Sidebar for Lessons -->
            <div class="col-span-1 bg-gray-100 p-4 rounded-lg">
                <h2 class="text-xl font-bold mb-4">فهرست دروس</h2>
                <ul class="space-y-2">
                    <?php foreach ($videos as $index => $video): ?>
                        <li>
                            <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $video['id']; ?>" 
                               class="block p-2 rounded hover:bg-gray-200 <?php echo in_array($video['id'], $completedVideoIds) ? 'text-green-600' : 'text-blue-600'; ?> transition duration-200">
                                درس <?php echo $index + 1; ?>: <?php echo htmlspecialchars($video['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-span-1 md:col-span-3 bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-4xl font-bold mb-8 text-center text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="text-lg text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

                <?php if ($isCourseCompleted): ?>
                    <!-- Congratulations Message -->
                    <div class="bg-green-100 p-4 rounded-lg mb-4">
                        <p class="text-green-700 font-bold">تبریک! شما این دوره را با موفقیت به پایان رسانده‌اید.</p>
                    </div>

                    <!-- Review Button (only shown if user hasn't reviewed yet) -->
                    <?php if (!$hasReviewed): ?>
                        <div class="mt-4">
                            <a href="submit_review.php?course_id=<?php echo $courseId; ?>" 
                               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                                ثبت نظر و امتیاز‌دهی
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Browse Course Button -->
                    <div class="mt-4">
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $videos[0]['id']; ?>" 
                           class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                            مرور دوره
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Progress Message -->
                    <div class="bg-blue-100 p-4 rounded-lg mb-4">
                        <p class="text-blue-700 font-bold">
                            شما تاکنون <?php echo $completedVideos; ?> از <?php echo $totalVideos; ?> درس را تکمیل کرده‌اید.
                        </p>
                    </div>

                    <!-- Continue Course Button -->
                    <?php
                    // Find the next uncompleted video
                    $nextVideoId = null;
                    foreach ($videos as $video) {
                        if (!in_array($video['id'], $completedVideoIds)) {
                            $nextVideoId = $video['id'];
                            break;
                        }
                    }
                    // If all videos are completed but course is not marked as completed
                    if (!$nextVideoId && !$isCourseCompleted) {
                        $nextVideoId = $videos[0]['id'];
                    }
                    ?>
                    <?php if ($nextVideoId): ?>
                        <div class="mt-4">
                            <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $nextVideoId; ?>" 
                               class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                                ادامه دوره
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('footer.php');

} catch (Exception $e) {
    custom_error_log("Error in course_content.php: " . $e->getMessage());
    echo "Error loading course content. Please try again later.";
}
?>
