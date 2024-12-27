<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login_member.php');
    exit;
}

$username = $_SESSION['username'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id === 0) {
    echo "پارامترهای نامعتبر.";
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if user has completed the course
$stmt = $conn->prepare('
    SELECT COUNT(DISTINCT v.id) as total_videos, COUNT(DISTINCT p.video_id) as completed_videos
    FROM videos v
    LEFT JOIN progress p ON v.id = p.video_id AND p.user_username = :username
    WHERE v.course_id = :course_id
');
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$progress = $result->fetchArray(SQLITE3_ASSOC);

if ($progress['total_videos'] == 0 || $progress['total_videos'] != $progress['completed_videos']) {
    echo "شما این دوره را تکمیل نکرده‌اید.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_text = isset($_POST['review']) ? trim($_POST['review']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    if (empty($review_text) || $rating < 1 || $rating > 5) {
        echo "لطفاً تمامی فیلدها را به درستی پر کنید.";
        exit;
    }

    // Check if the user has already submitted a review for this course
    $stmt = $conn->prepare('
        SELECT 1 FROM reviews 
        WHERE user_username = :username AND course_id = :course_id
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    if ($result->fetchArray()) {
        echo "شما قبلاً برای این دوره نظر ثبت کرده‌اید.";
        exit;
    }

    // Insert the new review into the database
    $stmt = $conn->prepare('
        INSERT INTO reviews (user_username, course_id, review, rating, created_at)
        VALUES (:username, :course_id, :review, :rating, CURRENT_TIMESTAMP)
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':course_id', $course_id, SQLITE3_INTEGER);
    $stmt->bindValue(':review', $review_text, SQLITE3_TEXT);
    $stmt->bindValue(':rating', $rating, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        // Redirect to the course page or user dashboard with a success message
        $_SESSION['success_message'] = 'نظر شما با موفقیت ثبت شد.';
        header('Location: course_detail.php?id=' . $course_id);
        exit;
    } else {
        echo "خطا در ثبت نظر. لطفاً دوباره تلاش کنید.";
        exit;
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-6">ثبت نظر و امتیاز برای دوره</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <form action="submit_review.php?course_id=<?php echo $course_id; ?>" method="POST" class="max-w-md mx-auto">
        <div class="mb-4">
            <label for="rating" class="block text-gray-700 text-sm font-bold mb-2">امتیاز (1 تا 5)</label>
            <select id="rating" name="rating" required class="w-full px-3 py-2 border rounded">
                <option value="">انتخاب امتیاز</option>
                <option value="5">5 - عالی</option>
                <option value="4">4 - خوب</option>
                <option value="3">3 - متوسط</option>
                <option value="2">2 - ضعیف</option>
                <option value="1">1 - بسیار ضعیف</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="review" class="block text-gray-700 text-sm font-bold mb-2">نظر شما</label>
            <textarea id="review" name="review" rows="5" required class="w-full px-3 py-2 border rounded"></textarea>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            ثبت نظر و امتیاز
        </button>
    </form>
</div>

<?php include('footer.php'); ?>
