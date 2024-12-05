<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$ticketId = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($ticketId)) {
    echo "Invalid ticket ID.";
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Begin transaction
    $conn->exec('BEGIN TRANSACTION');

    // Delete ticket responses associated with this ticket
    $stmtResponses = $conn->prepare('DELETE FROM ticket_responses WHERE ticket_id = :ticket_id');
    $stmtResponses->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
    $stmtResponses->execute();

    // Delete the ticket
    $stmtTicket = $conn->prepare('DELETE FROM tickets WHERE id = :ticket_id');
    $stmtTicket->bindValue(':ticket_id', $ticketId, SQLITE3_TEXT);
    $stmtTicket->execute();

    // Commit transaction
    $conn->exec('COMMIT');

    // Redirect with success message
    $_SESSION['success_message'] = 'تیکت با موفقیت حذف شد.';
    header("Location: admin_tickets.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->exec('ROLLBACK');
    // Log the error
    custom_error_log("Error deleting ticket ID $ticketId: " . $e->getMessage());
    $_SESSION['error_message'] = 'خطا در حذف تیکت. لطفا دوباره تلاش کنید.';
    header("Location: admin_tickets.php");
    exit;
}
?>
