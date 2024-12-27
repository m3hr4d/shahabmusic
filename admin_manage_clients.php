<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';
$filterByEmail = $_GET['email'] ?? '';
$filterByDate = $_GET['date'] ?? '';

// Build SQL query with filters
$sql = 'SELECT * FROM users WHERE 1=1';
$params = [];

if (!empty($search)) {
    $sql .= ' AND (first_name || " " || last_name) LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filterByEmail)) {
    $sql .= ' AND email LIKE :email';
    $params[':email'] = '%' . $filterByEmail . '%';
}

if (!empty($filterByDate)) {
    $sql .= ' AND DATE(created_at) = :date';
    $params[':date'] = $filterByDate;
}

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}

$result = $stmt->execute();

$users = [];
while ($user = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $user;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">مدیریت کاربران</h1>
    <form method="GET" action="" class="mb-6">
        <div class="flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-4 md:mb-0">
                <label for="search" class="block text-gray-700 font-bold mb-2">جستجو بر اساس نام</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex-1 mb-4 md:mb-0">
                <label for="filter_email" class="block text-gray-700 font-bold mb-2">فیلتر بر اساس ایمیل</label>
                <input type="text" id="filter_email" name="email" value="<?php echo htmlspecialchars($filterByEmail); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex-1 mb-4 md:mb-0">
                <label for="filter_date" class="block text-gray-700 font-bold mb-2">فیلتر بر اساس تاریخ ثبت‌نام</label>
                <input type="date" id="filter_date" name="date" value="<?php echo htmlspecialchars($filterByDate); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    فیلتر
                </button>
            </div>
        </div>
    </form>
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border px-4 py-2 text-left">نام</th>
                <th class="border px-4 py-2 text-left">ایمیل</th>
                <th class="border px-4 py-2 text-left">تاریخ ثبت‌نام</th>
                <th class="border px-4 py-2 text-center">اقدامات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2">
                        <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                    </td>
                    <td class="border border-gray-300 px-4 py-2">
                        <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                    </td>
                    <td class="border border-gray-300 px-4 py-2">
                        <?php echo htmlspecialchars(substr($user['created_at'] ?? '', 0, 10)); ?>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        <a href="admin_edit_client.php?username=<?php echo urlencode($user['username']); ?>" class="text-blue-500 hover:underline">ویرایش</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>
