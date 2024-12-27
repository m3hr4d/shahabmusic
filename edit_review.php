<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$username = isset($_GET['username']) ? $_GET['username'] : '';

if ($course_id == 0 || empty($username)) {
    echo "پارامترهای نامعتبر.";
    exit;
}

// Read reviews from JSON file
$reviews_file = 'reviews.json';
$reviews = file_exists($reviews_file) ? json_decode(file_get_contents($reviews_file), true) : [];

// Find the review
$review = null;
foreach ($reviews as $index => $r) {
    if ($r['course_id'] == $course_id && $r['username'] == $username) {
        $review = $r;
        $review_index = $index;
        break;
    }
}

if (!$review) {
    echo "نظر مورد نظر یافت نشد.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update the review
    $new_review = $_POST['review'];
    $new_rating = intval($_POST['rating']);

    $reviews[$review_index]['review'] = $new_review;
    $reviews[$review_index]['rating'] = $new_rating;

    // Save updated reviews
    file_put_contents($reviews_file, json_encode($reviews, JSON_PRETTY_PRINT));

    // Redirect to the feedback management page
    header('Location: admin_feedback.php?message=edited');
    exit;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">ویرایش نظر</h1>
    <form method="POST" action="">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">دوره:</label>
            <p class="border px-4 py-2"><?php echo htmlspecialchars($review['course_id']); ?></p>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">کاربر:</label>
            <p class="border px-4 py-2"><?php echo htmlspecialchars($username); ?></p>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="rating">امتیاز:</label>
            <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($review['rating']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="review">نظر:</label>
            <textarea id="review" name="review" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($review['review']); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                ذخیره تغییرات
            </button>
            <a href="admin_feedback.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                بازگشت
            </a>
        </div>
    </form>
</div>

<?php include('footer.php'); ?>
