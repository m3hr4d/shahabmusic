<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare('SELECT username, password, role FROM users WHERE username = :username AND role = "admin"');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['username'] = $username;
                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
            }
        } catch (Exception $e) {
            custom_error_log("Database error in login_admin.php: " . $e->getMessage());
            $error = 'خطا در ورود به سیستم. لطفا دوباره تلاش کنید.';
        }
    } else {
        $error = 'لطفا نام کاربری و رمز عبور را وارد کنید.';
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-4">ورود مدیریت</h1>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="login_admin.php" class="max-w-md">
        <div class="mb-4">
            <input type="text" name="username" placeholder="نام کاربری مدیریت" required 
                   class="w-full px-3 py-2 border rounded">
        </div>
        <div class="mb-4">
            <input type="password" name="password" placeholder="رمز عبور مدیریت" required 
                   class="w-full px-3 py-2 border rounded">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            ورود
        </button>
    </form>
</div>

<?php include('footer.php'); ?>
