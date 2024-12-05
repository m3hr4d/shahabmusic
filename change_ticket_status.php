<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (isset($_SESSION['admin_logged_in']) ? 'admin_tickets.php' : 'my_tickets.php'));
    exit;
}

$ticketId = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

// Error handling for missing ticket ID or status
if (empty($ticketId) || empty($newStatus)) {
    $_SESSION['error_message'] = 'شناسه تیکت یا وضعیت جدید نامعتبر است.';
    header('Location: ' . (isset($_SESSION['admin_logged_in']) ? 'admin_tickets.php' : 'my_tickets.php'));
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Fetch the ticket from the database
    $stmt = $conn->prepare('SELECT * FROM tickets WHERE id = :ticket_id');
    $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $ticket = $result->fetchArray(SQLITE3_ASSOC);

    if (!$ticket) {
        $_SESSION['error_message'] = 'تیکت مورد نظر یافت نشد.';
        header('Location: ' . (isset($_SESSION['admin_logged_in']) ? 'admin_tickets.php' : 'my_tickets.php'));
        exit;
    }

    $username = $_SESSION['username'];
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

    // Check if the user has permission to change the status
    if (!$isAdmin && $ticket['user_username'] !== $username) {
        $_SESSION['error_message'] = 'شما اجازه تغییر وضعیت این تیکت را ندارید.';
        header('Location: my_tickets.php');
        exit;
    }

    // Update the ticket status
    $stmtUpdate = $conn->prepare('
        UPDATE tickets
        SET status = :new_status, updated_at = CURRENT_TIMESTAMP
        WHERE id = :ticket_id
    ');
    $stmtUpdate->bindValue(':new_status', $newStatus, SQLITE3_TEXT);
    $stmtUpdate->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);

    if ($stmtUpdate->execute()) {
        $_SESSION['success_message'] = 'وضعیت تیکت به‌روزرسانی شد.';
    } else {
        $_SESSION['error_message'] = 'خطا در به‌روزرسانی تیکت. لطفاً دوباره تلاش کنید.';
    }

    header('Location: view_ticket.php?id=' . urlencode($ticketId));
    exit;

} catch (Exception $e) {
    // Log the error
    custom_error_log("Error in change_ticket_status.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'خطا در به‌روزرسانی تیکت. لطفاً دوباره تلاش کنید.';
    header('Location: ' . (isset($_SESSION['admin_logged_in']) ? 'admin_tickets.php' : 'my_tickets.php'));
    exit;
}
?>
