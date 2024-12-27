<?php
session_start();

require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Prepare and execute the query to fetch the user
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Check if the user is suspended
                if ($user['suspended']) {
                    $error = "حساب کاربری شما تعلیق شده است. لطفاً با پشتیبانی تماس بگیرید.";
                } else {
                    // Set session variables and redirect to the client dashboard
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['username'] = $username;
                    header("Location: client_dashboard.php");
                    exit();
                }
            } else {
                $error = "رمز عبور نادرست است.";
            }
        } else {
            $error = "کاربری با این نام کاربری یافت نشد.";
        }
    } else {
        $error = "نام کاربری و رمز عبور الزامی است.";
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-4">ورود اعضا</h1>
    <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form action="login_member.php" method="post" class="max-w-md">
        <div class="mb-4">
            <input type="text" name="username" placeholder="نام کاربری" required class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-4">
            <input type="password" name="password" placeholder="رمز عبور" required class="w-full px-3 py-2 border rounded">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">ورود</button>
    </form>
</div>

<?php include('footer.php'); ?>
