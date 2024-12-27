<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

if (!isset($_GET['course_id']) || !isset($_GET['video_id'])) {
    header('Location: courses.php');
    exit;
}

$courseId = intval($_GET['course_id']);
$videoId = intval($_GET['video_id']);
$username = $_SESSION['username'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if user is enrolled in the course
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

    // Get video details
    $stmt = $conn->prepare('
        SELECT * FROM videos 
        WHERE id = :video_id AND course_id = :course_id
    ');
    $stmt->bindValue(':video_id', $videoId, SQLITE3_INTEGER);
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $currentVideo = $result->fetchArray(SQLITE3_ASSOC);

    if (!$currentVideo) {
        header('Location: course_content.php?course_id=' . $courseId);
        exit;
    }

    // Get all videos with completion status
    $stmt = $conn->prepare('
        SELECT 
            v.*,
            CASE WHEN p.video_id IS NOT NULL THEN 1 ELSE 0 END as completed
        FROM videos v
        LEFT JOIN progress p ON v.id = p.video_id 
            AND p.course_id = :course_id 
            AND p.user_username = :username
        WHERE v.course_id = :course_id
        ORDER BY v.order_index ASC
    ');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();

    $videos = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $row;
    }

    // Find current video index and adjacent videos
    $currentIndex = array_search($videoId, array_column($videos, 'id'));
    $prevVideo = $currentIndex > 0 ? $videos[$currentIndex - 1] : null;
    $nextVideo = $currentIndex < count($videos) - 1 ? $videos[$currentIndex + 1] : null;

    include('header.php');
    ?>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Sidebar with lesson navigation -->
            <div class="col-span-1 bg-gray-100 p-4 rounded-lg">
                <h2 class="text-xl font-bold mb-4">فهرست دروس</h2>
                <ul class="space-y-2">
                    <?php foreach ($videos as $index => $video): ?>
                        <li>
                            <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $video['id']; ?>" 
                               class="block p-2 rounded <?php echo $video['id'] == $videoId ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-200'; ?> 
                                      <?php echo $video['completed'] ? 'text-green-600' : ''; ?> transition duration-200">
                                درس <?php echo $index + 1; ?>: <?php echo htmlspecialchars($video['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Main content area -->
            <div class="col-span-1 md:col-span-3 bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-2xl font-bold mb-4">درس <?php echo $currentIndex + 1; ?>: <?php echo htmlspecialchars($currentVideo['title']); ?></h1>
                
                <!-- Video Player -->
                <div class="aspect-w-16 aspect-h-9 mb-6">
                    <video id="lessonVideo" controls class="w-full rounded-lg shadow-md">
                        <source src="<?php echo htmlspecialchars($currentVideo['url']); ?>" type="video/mp4">
                        مرورگر شما از تگ ویدیو پشتیبانی نمی‌کند.
                    </video>
                </div>

                <p class="mb-4"><?php echo nl2br(htmlspecialchars($currentVideo['description'])); ?></p>

                <!-- Mark as Complete Button -->
                <button id="markCompleteBtn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-200 mb-4">
                    علامت‌گذاری به عنوان تکمیل شده
                </button>

                <!-- Completion Message -->
                <p id="completionMessage" class="text-green-600 font-bold mb-4" style="display: none;">
                    درس با موفقیت تکمیل شد!
                </p>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-8">
                    <?php if ($prevVideo): ?>
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $prevVideo['id']; ?>" 
                           class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                            درس قبلی &larr;
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    
                    <?php if ($nextVideo): ?>
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&video_id=<?php echo $nextVideo['id']; ?>" 
                           class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                            &rarr; درس بعدی
                        </a>
                    <?php else: ?>
                        <a href="complete_course.php?course_id=<?php echo $courseId; ?>" 
                           class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-200">
                            تکمیل دوره
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var videoElement = document.getElementById('lessonVideo');
            var markCompleteBtn = document.getElementById('markCompleteBtn');
            var completionMessage = document.getElementById('completionMessage');

            function markLessonComplete() {
                fetch('mark_lesson_complete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'course_id=<?php echo $courseId; ?>&video_id=<?php echo $videoId; ?>'
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        completionMessage.style.display = 'block';
                        markCompleteBtn.disabled = true;
                        markCompleteBtn.textContent = 'درس تکمیل شده است';
                    } else {
                        alert('خطا: ' + data.error);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                });
            }

            // Automatically mark lesson as complete when video ends
            videoElement.addEventListener('ended', function() {
                markLessonComplete();
            });

            // Allow user to manually mark lesson as complete
            markCompleteBtn.addEventListener('click', function() {
                markLessonComplete();
            });
        });
    </script>

    <?php include('footer.php');

} catch (Exception $e) {
    custom_error_log("Error in lesson.php: " . $e->getMessage());
    echo "Error loading lesson. Please try again later.";
}
?>
