<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit();
}

$username = $_SESSION['username'];

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch user data from the database
$stmt = $conn->prepare('SELECT * FROM users WHERE username = :username');
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$result = $stmt->execute();
$userData = $result->fetchArray(SQLITE3_ASSOC);

if (!$userData) {
    echo "User not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $newFirstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $newLastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $newBio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Begin transaction
    $conn->exec('BEGIN TRANSACTION');

    try {
        // Update user data
        $updateStmt = $conn->prepare('
            UPDATE users
            SET email = :email, first_name = :first_name, last_name = :last_name, bio = :bio
            WHERE username = :username
        ');
        $updateStmt->bindValue(':email', $newEmail, SQLITE3_TEXT);
        $updateStmt->bindValue(':first_name', $newFirstName, SQLITE3_TEXT);
        $updateStmt->bindValue(':last_name', $newLastName, SQLITE3_TEXT);
        $updateStmt->bindValue(':bio', $newBio, SQLITE3_TEXT);
        $updateStmt->bindValue(':username', $username, SQLITE3_TEXT);
        $updateResult = $updateStmt->execute();

        // Handle password change
        if (!empty($newPassword)) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $passwordStmt = $conn->prepare('
                    UPDATE users
                    SET password = :password
                    WHERE username = :username
                ');
                $passwordStmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
                $passwordStmt->bindValue(':username', $username, SQLITE3_TEXT);
                $passwordResult = $passwordStmt->execute();

                $_SESSION['success_message'] = 'پروفایل به‌روزرسانی و رمز عبور تغییر یافت. لطفا دوباره وارد شوید.';
                // Commit transaction
                $conn->exec('COMMIT');
                session_destroy();
                header('Location: login_member.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'رمزهای عبور مطابقت ندارند. لطفا دوباره تلاش کنید.';
                // Rollback transaction
                $conn->exec('ROLLBACK');
                header('Location: edit_profile.php');
                exit();
            }
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profile_pictures/';
            $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = $username . '.' . $fileExtension;
                $uploadFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
                    // Update profile picture path in the database
                    $pictureStmt = $conn->prepare('
                        UPDATE users
                        SET profile_picture = :profile_picture
                        WHERE username = :username
                    ');
                    $pictureStmt->bindValue(':profile_picture', $uploadFile, SQLITE3_TEXT);
                    $pictureStmt->bindValue(':username', $username, SQLITE3_TEXT);
                    $pictureResult = $pictureStmt->execute();
                } else {
                    $_SESSION['error_message'] = 'آپلود فایل با شکست مواجه شد. لطفا دوباره تلاش کنید.';
                    // Rollback transaction
                    $conn->exec('ROLLBACK');
                    header('Location: edit_profile.php');
                    exit();
                }
            } else {
                $_SESSION['error_message'] = 'نوع فایل نامعتبر است. لطفا یک فایل JPG، JPEG، PNG یا GIF آپلود کنید.';
                // Rollback transaction
                $conn->exec('ROLLBACK');
                header('Location: edit_profile.php');
                exit();
            }
        }

        $_SESSION['success_message'] = 'پروفایل با موفقیت به‌روزرسانی شد.';
        // Commit transaction
        $conn->exec('COMMIT');
        header('Location: edit_profile.php');
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->exec('ROLLBACK');
        $_SESSION['error_message'] = 'خطایی رخ داد: ' . $e->getMessage();
        header('Location: edit_profile.php');
        exit();
    }
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-4xl font-bold mb-8 text-center text-blue-600">ویرایش پروفایل</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p><?php echo $_SESSION['success_message']; ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?php echo $_SESSION['error_message']; ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">نام کاربری (قابل ویرایش نیست)</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="first_name" class="block text-gray-700 font-bold mb-2">نام</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label for="last_name" class="block text-gray-700 font-bold mb-2">نام خانوادگی</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">ایمیل</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="bio" class="block text-gray-700 font-bold mb-2">بیوگرافی</label>
                <textarea id="bio" name="bio" rows="4" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
            </div>
            <div class="mb-4">
                <label for="profile_picture" class="block text-gray-700 font-bold mb-2">عکس پروفایل</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="new_password" class="block text-gray-700 font-bold mb-2">رمز عبور جدید</label>
                <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="block text-gray-700 font-bold mb-2">تایید رمز عبور</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                به‌روزرسانی پروفایل
            </button>
        </form>
    </div>
</div>

<?php include('footer.php'); ?>
