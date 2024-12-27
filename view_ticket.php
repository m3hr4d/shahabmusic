<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

$ticketId = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($ticketId)) {
    echo "Invalid ticket ID.";
    exit;
}

$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Fetch ticket details
    $stmtTicket = $conn->prepare('SELECT * FROM tickets WHERE id = :ticket_id');
    $stmtTicket->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
    $resultTicket = $stmtTicket->execute();
    $ticket = $resultTicket->fetchArray(SQLITE3_ASSOC);

    if (!$ticket) {
        echo "Ticket not found.";
        exit;
    }

    // Check permissions
    if (!$isAdmin && $ticket['user_username'] !== $username) {
        echo "You do not have permission to view this ticket.";
        exit;
    }

    // Handle form submission for adding a response
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $responseText = isset($_POST['response']) ? trim($_POST['response']) : '';
        if (!empty($responseText)) {
            $stmtResponse = $conn->prepare('
                INSERT INTO ticket_responses (ticket_id, user_username, message, created_at)
                VALUES (:ticket_id, :username, :message, CURRENT_TIMESTAMP)
            ');
            $stmtResponse->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
            $stmtResponse->bindValue(':username', $username, SQLITE3_TEXT);
            $stmtResponse->bindValue(':message', $responseText, SQLITE3_TEXT);
            if ($stmtResponse->execute()) {
                // Update ticket's updated_at timestamp
                $stmtUpdateTicket = $conn->prepare('
                    UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = :ticket_id
                ');
                $stmtUpdateTicket->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
                $stmtUpdateTicket->execute();

                $_SESSION['success_message'] = 'پاسخ شما با موفقیت ثبت شد.';
                header('Location: view_ticket.php?id=' . urlencode($ticketId));
                exit;
            } else {
                $error = 'خطا در ثبت پاسخ. لطفاً دوباره تلاش کنید.';
            }
        } else {
            $error = 'لطفاً پاسخ خود را وارد کنید.';
        }
    }

    // Fetch ticket responses
    $stmtResponses = $conn->prepare('
        SELECT * FROM ticket_responses
        WHERE ticket_id = :ticket_id
        ORDER BY created_at ASC
    ');
    $stmtResponses->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
    $resultResponses = $stmtResponses->execute();

    $responses = [];
    while ($row = $resultResponses->fetchArray(SQLITE3_ASSOC)) {
        $responses[] = $row;
    }

    include('header.php');
    ?>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h1 class="text-3xl font-bold mb-6 text-center">مشاهده تیکت</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($ticket['title']); ?></h2>
                    <p class="mb-2"><strong>وضعیت:</strong> <?php echo htmlspecialchars($ticket['status']); ?></p>
                    <p class="mb-2"><strong>ایجاد شده توسط:</strong> <?php echo htmlspecialchars($ticket['user_username']); ?></p>
                    <p class="mb-2"><strong>تاریخ ایجاد:</strong> <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></p>
                    <p class="mb-4"><strong>آخرین به‌روزرسانی:</strong> <?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?></p>
                    <p class="mb-4"><strong>توضیحات:</strong></p>
                    <p class="mb-4"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                </div>

                <?php if ($isAdmin || $ticket['user_username'] === $username): ?>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                        <form action="change_ticket_status.php" method="POST">
                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticketId); ?>">
                            <label for="status" class="block text-gray-700 font-bold mb-2">تغییر وضعیت:</label>
                            <select name="status" class="border rounded px-2 py-1 w-full">
                                <option value="Open" <?php echo $ticket['status'] === 'Open' ? 'selected' : ''; ?>>باز</option>
                                <option value="Closed" <?php echo $ticket['status'] === 'Closed' ? 'selected' : ''; ?>>بسته</option>
                                <?php if ($isAdmin): ?>
                                    <option value="In Progress" <?php echo $ticket['status'] === 'In Progress' ? 'selected' : ''; ?>>در حال انجام</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded mt-4 w-full">تغییر وضعیت</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-2xl font-bold mb-4">پاسخ‌ها</h3>
            <?php if (empty($responses)): ?>
                <p class="text-gray-500">هنوز پاسخی داده نشده است.</p>
            <?php else: ?>
                <?php foreach ($responses as $response): ?>
                    <div class="bg-gray-100 rounded-lg p-4 mb-4 shadow-sm">
                        <p class="mb-2"><strong><?php echo htmlspecialchars($response['user_username']); ?></strong> - <?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($response['message'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($ticket['status'] !== 'Closed'): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-2xl font-bold mb-4">افزودن پاسخ</h3>
                <form action="view_ticket.php?id=<?php echo urlencode($ticketId); ?>" method="POST" class="max-w-lg mx-auto">
                    <div class="mb-4">
                        <label for="response" class="block text-gray-700 font-bold mb-2">پاسخ شما</label>
                        <textarea id="response" name="response" rows="4" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">ارسال پاسخ</button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-8">
                این تیکت بسته شده است و نمی‌توانید پاسخی اضافه کنید.
            </div>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>

    <?php
} catch (Exception $e) {
    // Log the error
    custom_error_log("Error in view_ticket.php: " . $e->getMessage());
    echo "مشکلی در بارگذاری تیکت رخ داد. لطفاً دوباره تلاش کنید.";
}
?>
