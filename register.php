<?php
session_start();

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    // Check if all required fields are filled
    if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'لطفاً تمامی فیلدها را پر کنید.';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if username or email already exists
        $stmt = $conn->prepare('SELECT * FROM users WHERE username = :username OR email = :email');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            $error = 'نام کاربری یا ایمیل قبلاً ثبت شده است.';
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database
            $stmt = $conn->prepare('
                INSERT INTO users (username, password, email, first_name, last_name, created_at)
                VALUES (:username, :password, :email, :first_name, :last_name, CURRENT_TIMESTAMP)
            ');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);

            if ($stmt->execute()) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['username'] = $username;
                header("Location: client_dashboard.php");
                exit();
            } else {
                $error = 'خطایی در ثبت‌نام رخ داده است. لطفاً دوباره تلاش کنید.';
            }
        }
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-4xl font-bold mb-8 text-center text-blue-600">ثبت‌نام</h1>
    <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form action="register.php" method="post" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
        <div class="mb-4">
            <label for="username" class="block text-gray-700 font-bold mb-2">نام کاربری</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-bold mb-2">رمز عبور</label>
            <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">ایمیل</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label for="first_name" class="block text-gray-700 font-bold mb-2">نام</label>
            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label for="last_name" class="block text-gray-700 font-bold mb-2">نام خانوادگی</label>
            <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            ثبت‌نام
        </button>
    </form>
</div>

<?php include('footer.php'); ?>
