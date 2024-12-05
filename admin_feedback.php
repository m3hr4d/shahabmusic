<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch reviews along with course titles
$sql = '
SELECT reviews.*, courses.title AS course_title
FROM reviews
INNER JOIN courses ON reviews.course_id = courses.id
ORDER BY reviews.created_at DESC
';
$result = $conn->query($sql);

$reviews = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $reviews[] = $row;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">مدیریت نظرات و امتیازات</h1>
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="border px-4 py-2 text-left">دوره</th>
                <th class="border px-4 py-2 text-left">کاربر</th>
                <th class="border px-4 py-2 text-left">نظر</th>
                <th class="border px-4 py-2 text-left">امتیاز</th>
                <th class="border px-4 py-2 text-center">اقدامات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $review): ?>
            <tr class="hover:bg-gray-50">
                <td class="border px-4 py-2"><?php echo htmlspecialchars($review['course_title']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($review['user_username']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($review['review']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($review['rating']); ?> ★</td>
                <td class="border px-4 py-2 text-center">
                    <a href="edit_review.php?course_id=<?php echo $review['course_id']; ?>&username=<?php echo urlencode($review['user_username']); ?>" class="text-blue-500 hover:underline">ویرایش</a>
                    <a href="delete_review.php?course_id=<?php echo $review['course_id']; ?>&username=<?php echo urlencode($review['user_username']); ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این نظر را حذف کنید؟');">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>
