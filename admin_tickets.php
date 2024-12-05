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
$filterByStatus = $_GET['status'] ?? '';
$filterByDate = $_GET['date'] ?? '';

// Build SQL query with filters
$sql = 'SELECT * FROM tickets WHERE 1=1';
$params = [];

if (!empty($search)) {
    $sql .= ' AND (title LIKE :search OR user_username LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filterByStatus)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $filterByStatus;
}

if (!empty($filterByDate)) {
    $sql .= ' AND DATE(created_at) = :date';
    $params[':date'] = $filterByDate;
}

// Order tickets from newest to oldest
$sql .= ' ORDER BY created_at DESC';

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}

$result = $stmt->execute();

$tickets = [];
while ($ticket = $result->fetchArray(SQLITE3_ASSOC)) {
    $tickets[] = $ticket;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">مدیریت تیکت‌ها</h1>

    <!-- Search and Filter Form -->
    <form action="" method="GET" class="mb-8">
        <div class="flex flex-wrap -mx-2 mb-4">
            <div class="w-full md:w-1/3 px-2 mb-4">
                <label for="search" class="block text-gray-700 font-bold mb-2">جستجو</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" placeholder="جستجو بر اساس عنوان یا کاربر">
            </div>
            <div class="w-full md:w-1/3 px-2 mb-4">
                <label for="status" class="block text-gray-700 font-bold mb-2">وضعیت</label>
                <select id="status" name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">همه</option>
                    <option value="Open" <?php echo $filterByStatus === 'Open' ? 'selected' : ''; ?>>باز</option>
                    <option value="Closed" <?php echo $filterByStatus === 'Closed' ? 'selected' : ''; ?>>بسته</option>
                    <option value="In Progress" <?php echo $filterByStatus === 'In Progress' ? 'selected' : ''; ?>>در حال انجام</option>
                </select>
            </div>
            <div class="w-full md:w-1/3 px-2 mb-4">
                <label for="date" class="block text-gray-700 font-bold mb-2">تاریخ ایجاد</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($filterByDate); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">اعمال فیلترها</button>
    </form>

    <!-- Tickets Table -->
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-2">عنوان</th>
                <th class="border border-gray-300 px-4 py-2">کاربر</th>
                <th class="border border-gray-300 px-4 py-2">وضعیت</th>
                <th class="border border-gray-300 px-4 py-2">تاریخ ایجاد</th>
                <th class="border border-gray-300 px-4 py-2">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['title']); ?></td>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['user_username']); ?></td>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['status']); ?></td>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        <a href="view_ticket.php?id=<?php echo urlencode($ticket['id']); ?>" class="text-blue-500 hover:underline">مشاهده</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>
