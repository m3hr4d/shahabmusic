<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

$username = $_SESSION['username'];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get total number of user's tickets for pagination
    $stmt = $conn->prepare('
        SELECT COUNT(*) as total_tickets
        FROM tickets
        WHERE user_username = :username
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $totalTickets = $row['total_tickets'];
    $totalPages = ceil($totalTickets / $limit);

    // Fetch user's tickets with pagination
    $stmt = $conn->prepare('
        SELECT *
        FROM tickets
        WHERE user_username = :username
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $tickets = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tickets[] = $row;
    }

    include('header.php');
    ?>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <h1 class="text-3xl font-bold mb-4">تیکت‌های من</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo $_SESSION['success_message']; ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <p>هیچ تیکتی یافت نشد.</p>
        <?php else: ?>
            <table class="w-full border-collapse border border-gray-300 mt-4">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2 text-left">شناسه</th>
                        <th class="border px-4 py-2 text-left">عنوان</th>
                        <th class="border px-4 py-2 text-left">وضعیت</th>
                        <th class="border px-4 py-2 text-left">تاریخ ایجاد</th>
                        <th class="border px-4 py-2 text-center">اقدامات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['id']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($ticket['status']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                <a href="view_ticket.php?id=<?php echo urlencode($ticket['id']); ?>" class="text-blue-500 hover:underline">مشاهده</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="mt-4 flex justify-between">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="bg-blue-500 text-white px-4 py-2 rounded">قبلی</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="bg-blue-500 text-white px-4 py-2 rounded">بعدی</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>

    <?php
} catch (Exception $e) {
    // Log the error
    custom_error_log("Error in my_tickets.php: " . $e->getMessage());
    echo "مشکلی در بارگذاری تیکت‌های شما رخ داد. لطفاً دوباره تلاش کنید.";
}
?>
