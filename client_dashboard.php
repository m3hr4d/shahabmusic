<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login_member.php');
    exit;
}

$username = $_SESSION['username'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get all enrolled courses with progress
    $stmt = $conn->prepare('
        SELECT 
            c.*,
            COUNT(DISTINCT v.id) as total_videos,
            COUNT(DISTINCT p.video_id) as completed_videos,
            e.enrolled_at
        FROM courses c
        INNER JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN videos v ON c.id = v.course_id
        LEFT JOIN progress p ON c.id = p.course_id AND p.user_username = :username
        WHERE e.user_username = :username
        GROUP BY c.id
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();

    $activeCourses = [];
    $completedCourses = [];
    $totalLessons = 0;
    $completedLessons = 0;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Determine if the course is completed
        if ($row['total_videos'] > 0 && $row['total_videos'] == $row['completed_videos']) {
            $completedCourses[] = $row;
        } else {
            $activeCourses[] = $row;
        }
        $totalLessons += $row['total_videos'];
        $completedLessons += $row['completed_videos'];
    }

    // Calculate overall progress
    $overallProgress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

    // Calculate learning streak (dummy data - replace with actual logic)
    $learningStreak = 5;

    // Get recent activity (last 10 items)
    $recentActivity = [];

    // Get recent enrollments
    $stmt = $conn->prepare('
        SELECT 
            "enrollment" as type,
            c.title as course_title,
            e.enrolled_at as activity_date
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.user_username = :username
        ORDER BY e.enrolled_at DESC
        LIMIT 5
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recentActivity[] = $row;
    }

    // Get recent completed lessons
    $stmt = $conn->prepare('
        SELECT 
            "completion" as type,
            c.title as course_title,
            v.title as video_title,
            p.completed_at as activity_date
        FROM progress p
        JOIN courses c ON p.course_id = c.id
        JOIN videos v ON p.video_id = v.id
        WHERE p.user_username = :username
        ORDER BY p.completed_at DESC
        LIMIT 5
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recentActivity[] = $row;
    }

    // Sort recent activity by date
    usort($recentActivity, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });

    // Limit to 3 most recent activities
    $recentActivity = array_slice($recentActivity, 0, 3);

    // Calculate achievements
    $achievements = [];
    if ($completedLessons >= 5) {
        $achievements[] = ['title' => 'یادگیرنده سریع', 'description' => '5 درس را به پایان رسانده‌اید', 'icon' => '🚀'];
    }
    if ($learningStreak >= 7) {
        $achievements[] = ['title' => 'برنامه‌نویس مداوم', 'description' => '7 روز متوالی وارد سیستم شده‌اید', 'icon' => '🔥'];
    }
    if (count($completedCourses) >= 3) {
        $achievements[] = ['title' => 'کلکسیونر دوره', 'description' => 'در 3 دوره یا بیشتر ثبت‌نام کرده‌اید', 'icon' => '📚'];
    }

    // Fetch user's submitted reviews
    $stmt = $conn->prepare('
        SELECT course_id FROM reviews
        WHERE user_username = :username
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $reviewsResult = $stmt->execute();
    $submittedReviews = [];
    while ($row = $reviewsResult->fetchArray(SQLITE3_ASSOC)) {
        $submittedReviews[] = $row['course_id'];
    }

    include('header.php');
    ?>

    <style>
    .full-width-banner {
        width: 100vw;
        margin-left: calc(-50vw + 50%);
        padding-left: 0;
        padding-right: 0;
        margin-top: -1px;
    }
    .progress-chart {
        position: relative;
    }
    .progress-ring {
        transition: stroke-dashoffset 0.35s;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }
    </style>

    <div class="full-width-banner bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-12" dir="rtl">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold mb-8 text-center text-white">داشبورد</h1>
            <p class="text-xl text-center">پیشرفت خود را پیگیری کنید</p>
            <p class="text-2xl text-center mt-4">🔥 <?php echo $learningStreak; ?> روز متوالی!</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8" dir="rtl">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8" role="alert">
                <p><?php echo $_SESSION['success_message']; ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
            <!-- Active Courses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">دوره‌های فعال</h2>
                <?php if (empty($activeCourses)): ?>
                    <p class="text-gray-600">دوره فعالی وجود ندارد</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($activeCourses as $course):
                            $courseProgress = $course['total_videos'] > 0 ?
                                ($course['completed_videos'] / $course['total_videos']) * 100 : 0;
                        ?>
                            <li>
                                <a href="course_content.php?course_id=<?php echo urlencode($course['id']); ?>"
                                   class="block p-2 rounded hover:bg-blue-100 transition duration-200 text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $courseProgress; ?>%"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="courses.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        کاوش دوره‌های جدید
                    </a>
                </div>
            </div>

            <!-- Completed Courses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">دوره‌های تکمیل شده</h2>
                <?php if (empty($completedCourses)): ?>
                    <p class="text-gray-600">دوره تکمیل شده‌ای وجود ندارد</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($completedCourses as $course):
                            $hasReviewed = in_array($course['id'], $submittedReviews);
                        ?>
                            <li>
                                <a href="course_content.php?course_id=<?php echo urlencode($course['id']); ?>"
                                   class="block p-2 rounded hover:bg-blue-100 transition duration-200 text-green-600 hover:text-green-800">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                                <?php if (!$hasReviewed): ?>
                                    <a href="submit_review.php?course_id=<?php echo $course['id']; ?>"
                                       class="inline-block mt-2 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded transition duration-200">
                                        ثبت نظر و امتیاز‌دهی
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">لینک‌های سریع</h2>
                <ul class="space-y-2">
                    <li>
                        <a href="edit_profile.php" class="block p-2 rounded hover:bg-blue-100 transition duration-200 text-blue-600 hover:text-blue-800">
                            ویرایش پروفایل
                        </a>
                    </li>
                    <li>
                        <a href="my_tickets.php" class="block p-2 rounded hover:bg-blue-100 transition duration-200 text-blue-600 hover:text-blue-800">
                            تیکت‌های من
                        </a>
                    </li>
                    <li>
                        <a href="create_ticket.php" class="block p-2 rounded hover:bg-blue-100 transition duration-200 text-blue-600 hover:text-blue-800">
                            ارسال تیکت جدید
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- New row for Overall Progress, Recent Activity, and Achievements -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Overall Progress -->
            <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition-transform duration-300">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">پیشرفت کلی</h2>
                <div class="progress-chart flex justify-center items-center">
                    <svg width="200" height="200" viewBox="0 0 200 200">
                        <circle cx="100" cy="100" r="90" fill="none" stroke="#e6e6e6" stroke-width="20"/>
                        <circle cx="100" cy="100" r="90" fill="none" stroke="#3498db" stroke-width="20"
                                stroke-dasharray="565.48" stroke-dashoffset="565.48" class="progress-ring"/>
                        <text x="100" y="100" text-anchor="middle" dominant-baseline="central" font-size="30" fill="#333">0%</text>
                    </svg>
                </div>
                <p class="text-center mt-4 text-gray-600">به کار خوب خود ادامه دهید!</p>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition-transform duration-300">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">فعالیت‌های اخیر</h2>
                <ul class="space-y-3">
                    <?php foreach ($recentActivity as $activity): ?>
                        <li class="flex items-center space-x-reverse space-x-2 p-2 bg-blue-50 rounded-lg">
                            <span class="text-2xl"><?php echo $activity['type'] === 'enrollment' ? '📚' : '✅'; ?></span>
                            <div>
                                <?php if ($activity['type'] === 'enrollment'): ?>
                                    <p class="text-sm font-semibold text-gray-800">ثبت‌نام در دوره</p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($activity['course_title']); ?></p>
                                <?php else: ?>
                                    <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($activity['video_title']); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($activity['course_title']); ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400"><?php echo date('Y-m-d', strtotime($activity['activity_date'])); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Achievements -->
            <div class="bg-white rounded-lg shadow-md p-6 transform hover:scale-105 transition-transform duration-300">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">دستاوردها</h2>
                <ul class="space-y-3">
                    <?php foreach ($achievements as $achievement): ?>
                        <li class="flex items-center space-x-reverse space-x-3 p-2 bg-yellow-50 rounded-lg">
                            <span class="text-3xl"><?php echo htmlspecialchars($achievement['icon']); ?></span>
                            <div>
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($achievement['title']); ?></h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($achievement['description']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($achievements)): ?>
                        <p class="text-center mt-4 text-gray-600">برای باز کردن قفل دستاوردها به یادگیری ادامه دهید!</p>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
    function setProgress(percent) {
        const circle = document.querySelector('.progress-ring');
        const text = document.querySelector('.progress-chart text');
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        const offset = circumference - (percent / 100 * circumference);
        circle.style.strokeDashoffset = offset;
        text.textContent = `${Math.round(percent)}%`;
    }

    // Set the progress
    setProgress(<?php echo $overallProgress; ?>);
    </script>

    <?php include('footer.php');

} catch (Exception $e) {
    custom_error_log("Error in client_dashboard.php: " . $e->getMessage());
    echo "Error loading dashboard. Please try again later.";
}
?>
