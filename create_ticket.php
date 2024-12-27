<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

$username = $_SESSION['username'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // Check if the user is an admin

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $client = $isAdmin ? ($_POST['client'] ?? '') : $username; // Admin selects client, otherwise use logged-in user

    if (!empty($title) && !empty($description) && !empty($client)) {
        // Check if the client exists
        $userStmt = $conn->prepare('SELECT * FROM users WHERE username = :username');
        $userStmt->bindValue(':username', $client, SQLITE3_TEXT);
        $userResult = $userStmt->execute();
        $clientData = $userResult->fetchArray(SQLITE3_ASSOC);

        if (!$clientData) {
            $error = 'کاربر مورد نظر یافت نشد.';
        } else {
            // Generate a unique ticket ID
            $ticketId = uniqid();

            // Insert the ticket into the database
            $insertStmt = $conn->prepare('
                INSERT INTO tickets (id, user_username, title, description, status, created_at, updated_at)
                VALUES (:id, :user_username, :title, :description, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ');
            $insertStmt->bindValue(':id', $ticketId, SQLITE3_TEXT);
            $insertStmt->bindValue(':user_username', $client, SQLITE3_TEXT);
            $insertStmt->bindValue(':title', $title, SQLITE3_TEXT);
            $insertStmt->bindValue(':description', $description, SQLITE3_TEXT);
            $insertStmt->bindValue(':status', 'Open', SQLITE3_TEXT);

            if ($insertStmt->execute()) {
                $_SESSION['success_message'] = 'تیکت با موفقیت ایجاد شد.';
                header('Location: my_tickets.php');
                exit;
            } else {
                $error = 'خطا در ایجاد تیکت. لطفا دوباره تلاش کنید.';
            }
        }
    } else {
        $error = 'لطفا همه فیلدها را پر کنید.';
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">ایجاد تیکت جدید</h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <form action="create_ticket.php" method="POST" class="max-w-lg mx-auto">
        <?php if ($isAdmin): ?>
            <div class="mb-4">
                <label for="client" class="block text-gray-700 font-bold mb-2">انتخاب مشتری</label>
                <select id="client" name="client" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    <?php
                    // Fetch all users who are not admins
                    $usersStmt = $conn->prepare('SELECT username FROM users WHERE role != :admin_role OR role IS NULL');
                    $usersStmt->bindValue(':admin_role', 'admin', SQLITE3_TEXT);
                    $usersResult = $usersStmt->execute();

                    while ($userRow = $usersResult->fetchArray(SQLITE3_ASSOC)) {
                        $userOption = htmlspecialchars($userRow['username']);
                        echo "<option value=\"{$userOption}\">{$userOption}</option>";
                    }
                    ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <label for="title" class="block text-gray-700 font-bold mb-2">عنوان تیکت</label>
            <input type="text" id="title" name="title" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-bold mb-2">توضیحات تیکت</label>
            <textarea id="description" name="description" rows="6" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">ارسال تیکت</button>
    </form>
</div>

<?php include('footer.php'); ?>
