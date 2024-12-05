<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Load data from the database

// Fetch courses
$courses_result = $conn->query('SELECT * FROM courses ORDER BY created_at DESC');
$courses = [];
while ($row = $courses_result->fetchArray(SQLITE3_ASSOC)) {
    $courses[] = $row;
}

// Fetch users
$users_result = $conn->query('SELECT * FROM users ORDER BY created_at DESC');
$users = [];
while ($row = $users_result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

// Fetch enrollments
$enrollments_result = $conn->query('SELECT * FROM enrollments');
$enrollments = [];
while ($row = $enrollments_result->fetchArray(SQLITE3_ASSOC)) {
    $enrollments[] = $row;
}

// Fetch tickets
$tickets_result = $conn->query('SELECT * FROM tickets ORDER BY created_at DESC');
$tickets = [];
while ($row = $tickets_result->fetchArray(SQLITE3_ASSOC)) {
    $tickets[] = $row;
}

// Fetch reviews
$reviews_result = $conn->query('SELECT * FROM reviews ORDER BY created_at DESC');
$reviews = [];
while ($row = $reviews_result->fetchArray(SQLITE3_ASSOC)) {
    $reviews[] = $row;
}

// Create a course map indexed by course IDs
$course_map = [];
foreach ($courses as $course) {
    $course_map[$course['id']] = $course;
}

$total_courses = count($courses);
$total_users = count($users);
$total_enrollments = count($enrollments);
$total_tickets = count($tickets);
$open_tickets = count(array_filter($tickets, function($ticket) {
    return $ticket['status'] === 'Open';
}));

// Get the 5 most recent courses, users, tickets, and reviews
$recent_courses = array_slice($courses, 0, 5); // Since courses are ordered by created_at DESC
$recent_users = array_slice($users, 0, 5);
$recent_tickets = array_slice($tickets, 0, 5);
$recent_reviews = array_slice($reviews, 0, 5);

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">داشبورد مدیریت</h1>
    <p class="mb-8 text-xl">خوش آمدید، <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!</p>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <h2 class="text-2xl font-bold text-blue-600">کل دوره‌ها</h2>
            <p class="text-4xl mt-2"><?php echo $total_courses; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <h2 class="text-2xl font-bold text-green-600">کل کاربران</h2>
            <p class="text-4xl mt-2"><?php echo $total_users; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <h2 class="text-2xl font-bold text-yellow-600">کل ثبت‌نام‌ها</h2>
            <p class="text-4xl mt-2"><?php echo $total_enrollments; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <h2 class="text-2xl font-bold text-red-600">تیکت‌های باز</h2>
            <p class="text-4xl mt-2"><?php echo $open_tickets; ?> / <?php echo $total_tickets; ?></p>
        </div>
    </div>
    
    <!-- Course Management -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">مدیریت دوره‌ها</h2>
            <a href="add_course.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                افزودن دوره جدید
            </a>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left">نام دوره</th>
                    <th class="border px-4 py-2 text-left">توضیحات</th>
                    <th class="border px-4 py-2 text-center">اقدامات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_courses as $course): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($course['title'] ?? 'دوره بدون عنوان'); ?></td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars(mb_substr($course['description'] ?? 'توضیحات موجود نیست.', 0, 100)) . '...'; ?></td>
                    <td class="border px-4 py-2 text-center">
                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="text-blue-500 hover:underline mr-2">ویرایش</a>
                        <a href="delete_course.php?id=<?php echo $course['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این دوره را حذف کنید؟');">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-4 text-right">
            <a href="courses.php" class="text-blue-500 hover:underline">مشاهده همه دوره‌ها</a>
        </div>
    </div>
    
    <!-- User Management -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">مدیریت کاربران</h2>
            <a href="admin_manage_clients.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                مدیریت مشتریان
            </a>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left">نام کاربری</th>
                    <th class="border px-4 py-2 text-left">ایمیل</th>
                    <th class="border px-4 py-2 text-center">اقدامات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="border px-4 py-2 text-center">
                        <a href="admin_edit_client.php?username=<?php echo urlencode($user['username']); ?>" class="text-blue-500 hover:underline">ویرایش</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-4 text-right">
            <a href="admin_manage_clients.php" class="text-blue-500 hover:underline">مشاهده همه کاربران</a>
        </div>
    </div>
    
    <!-- Ticket Management -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">مدیریت تیکت‌ها</h2>
            <a href="admin_tickets.php" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                مدیریت تیکت‌ها (<?php echo $open_tickets; ?>)
            </a>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 text-left">شناسه تیکت</th>
                    <th class="border px-4 py-2 text-left">عنوان</th>
                    <th class="border px-4 py-2 text-left">وضعیت</th>
                    <th class="border px-4 py-2 text-left">تاریخ ایجاد</th>
                    <th class="border px-4 py-2 text-center">اقدامات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_tickets as $ticket): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($ticket['id']); ?></td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($ticket['title']); ?></td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($ticket['status']); ?></td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                    <td class="border px-4 py-2 text-center">
                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-500 hover:underline">مشاهده</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-4 text-right">
            <a href="admin_tickets.php" class="text-blue-500 hover:underline">مشاهده همه تیکت‌ها</a>
        </div>
    </div>

    <!-- Feedback Management -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">مدیریت نظرات و امتیازات</h2>
            <a href="admin_feedback.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                مدیریت نظرات
            </a>
        </div>
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
                <?php foreach ($recent_reviews as $review): ?>
                <tr class="hover:bg-gray-50">
                    <?php 
                    // Use course_map to access the course by its ID
                    $courseId = intval($review['course_id']);
                    $courseTitle = isset($course_map[$courseId]) ? $course_map[$courseId]['title'] : 'دوره نامشخص';
                    ?>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($courseTitle); ?></td>
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
        <div class="mt-4 text-right">
            <a href="admin_feedback.php" class="text-blue-500 hover:underline">مشاهده همه نظرات</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
