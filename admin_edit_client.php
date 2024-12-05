<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if (empty($username)) {
    echo "Invalid username.";
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch user data
$stmt = $conn->prepare('SELECT * FROM users WHERE username = :username');
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';

    // Update user data
    $update_stmt = $conn->prepare('
        UPDATE users
        SET email = :email, first_name = :first_name, last_name = :last_name
        WHERE username = :username
    ');
    $update_stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $update_stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
    $update_stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
    $update_stmt->bindValue(':username', $username, SQLITE3_TEXT);

    if ($update_stmt->execute()) {
        header('Location: admin_manage_clients.php?message=updated');
        exit;
    } else {
        echo "Failed to update user.";
        exit;
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">ویرایش مشتری: <?php echo htmlspecialchars($username); ?></h1>
    <form method="POST" action="">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">ایمیل</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">نام</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">نام خانوادگی</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                ذخیره تغییرات
            </button>
            <a href="admin_manage_clients.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                بازگشت
            </a>
        </div>
    </form>
</div>

<?php include('footer.php'); ?>
