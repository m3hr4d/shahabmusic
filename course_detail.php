<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

$courseId = isset($_GET['course_id']) ? trim($_GET['course_id']) : (isset($_GET['id']) ? trim($_GET['id']) : '');
if (empty($courseId)) {
    echo "Invalid course ID.";
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get course details with video count
    $stmt = $conn->prepare('
        SELECT 
            c.*,
            COUNT(v.id) as video_count
        FROM courses c
        LEFT JOIN videos v ON c.id = v.course_id
        WHERE c.id = :course_id
        GROUP BY c.id
    ');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $course = $result->fetchArray(SQLITE3_ASSOC);

    if (!$course) {
        echo "Course not found.";
        exit;
    }

    // Check enrollment status
    $username = $_SESSION['username'] ?? null;
    $isEnrolled = false;
    
    if ($username) {
        $stmt = $conn->prepare('
            SELECT 1 FROM enrollments 
            WHERE user_username = :username AND course_id = :course_id
        ');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $isEnrolled = (bool)$result->fetchArray();
    }

    // Get course videos
    $videos = [];
    $stmt = $conn->prepare('
        SELECT * FROM videos 
        WHERE course_id = :course_id 
        ORDER BY order_index ASC
    ');
    $stmt->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $videos[] = $row;
    }

    include('header.php');
    ?>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($course['title']); ?></h1>
        <p class="mb-4"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

        <div class="mb-4">
            <h2 class="text-2xl font-bold mb-4">محتوای دوره</h2>
            <?php if (!empty($videos)): ?>
                <ul class="list-disc pl-8 space-y-2">
                    <?php foreach ($videos as $video): ?>
                        <li>
                            <?php 
                            echo htmlspecialchars($video['title'] ?? "درس " . ($video['order_index'] + 1));
                            if (!empty($video['description'])) {
                                echo '<p class="text-gray-600">' . nl2br(htmlspecialchars($video['description'])) . '</p>';
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>محتوایی برای نمایش وجود ندارد.</p>
            <?php endif; ?>
        </div>

        <?php if ($isEnrolled): ?>
            <a href="course_content.php?course_id=<?php echo urlencode($courseId); ?>" 
               class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                ادامه دوره
            </a>
        <?php else: ?>
            <form action="enroll.php" method="POST">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($courseId); ?>">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                    ثبت نام در دوره
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php include('footer.php');

} catch (Exception $e) {
    custom_error_log("Error in course_detail.php: " . $e->getMessage());
    echo "Error loading course details. Please try again later.";
}
?>
