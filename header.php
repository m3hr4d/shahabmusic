<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SemiTone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: 'BYekan';
            src: url('fonts/BYekan/BYekan+.ttf') format('truetype'),
                 url('fonts/BYekan/BYekan+ Bold.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'BYekan', sans-serif;
        }

        /* Base styles for light mode */
        body.light-mode {
            background-color: #f8fafc;
            color: #1f2937;
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #111827;
            color: #f9fafb;
        }

        body.dark-mode a {
            color: #93c5fd;
        }

        body.dark-mode .bg-white {
            background-color: #1f2937;
        }

        body.dark-mode .text-gray-700 {
            color: #e5e7eb;
        }

        body.dark-mode .bg-red-500 {
            background-color: #ef4444;
        }

        body.dark-mode .bg-red-500:hover {
            background-color: #dc2626;
        }

        body.dark-mode .bg-blue-500 {
            background-color: #3b82f6;
        }

        body.dark-mode .bg-blue-500:hover {
            background-color: #2563eb;
        }

        body.dark-mode .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 light-mode" dir="rtl">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold text-blue-600 hover:text-blue-800 transition duration-300">SemiTone</a>
                <div class="flex items-center space-x-reverse space-x-6">
                    <nav class="hidden md:flex items-center space-x-reverse space-x-6">
                        <a href="index.php" class="text-gray-700 hover:text-blue-600 transition duration-300">خانه</a>
                        <a href="courses.php" class="text-gray-700 hover:text-blue-600 transition duration-300">دوره‌ها</a>

                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                            <div class="relative">
                                <button class="text-gray-700 hover:text-blue-600 transition duration-300 focus:outline-none" id="adminMenuButton">
                                    داشبورد مدیریت
                                </button>
                                <div class="absolute hidden bg-white shadow-md rounded mt-2" id="adminMenu">
                                    <a href="admin_dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">داشبورد</a>
                                    <a href="admin_manage_clients.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">مدیریت مشتریان</a>
                                    <a href="admin_tickets.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">مدیریت تیکت‌ها</a>
                                    <a href="courses.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">مدیریت دوره‌ها</a>
                                </div>
                            </div>
                            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-full transition duration-300">خروج مدیریت</a>
                        <?php elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                            <a href="client_dashboard.php" class="text-gray-700 hover:text-blue-600 transition duration-300">داشبورد</a>
                            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-full transition duration-300">خروج</a>
                        <?php else: ?>
                            <a href="login_member.php" class="text-gray-700 hover:text-blue-600 transition duration-300">ورود</a>
                            <a href="register.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full transition duration-300">ثبت‌نام</a>
                        <?php endif; ?>
                    </nav>
                    <button id="darkModeToggle" class="text-2xl">
                        <svg id="sunIcon" class="w-8 h-8 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-8.66h-1M4.34 12H3m15.66 4.34l-.7-.7m-11.32 0l-.7.7m11.32-11.32l-.7.7m-11.32 0l-.7-.7M12 5a7 7 0 110 14 7 7 0 010-14z"></path>
                        </svg>
                        <svg id="moonIcon" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"></path>
                        </svg>
                    </button>
                </div>
                <button class="md:hidden" id="mobileMenuButton">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>

        <div class="md:hidden hidden" id="mobileMenu" dir="rtl">
            <!-- Mobile menu items -->
            <a href="index.php" class="block py-2 px-4 text-sm hover:bg-gray-200">خانه</a>
            <a href="courses.php" class="block py-2 px-4 text-sm hover:bg-gray-200">دوره‌ها</a>
            <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                <a href="client_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-200">داشبورد</a>
                <a href="logout.php" class="block py-2 px-4 text-sm bg-red-500 text-white">خروج</a>
            <?php elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="admin_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-200">داشبورد مدیریت</a>
                <a href="logout.php" class="block py-2 px-4 text-sm bg-red-500 text-white">خروج مدیریت</a>
            <?php else: ?>
                <a href="login_member.php" class="block py-2 px-4 text-sm hover:bg-gray-200">ورود</a>
                <a href="register.php" class="block py-2 px-4 text-sm bg-blue-500 text-white">ثبت‌نام</a>
            <?php endif; ?>
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('darkModeToggle');
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');
            const body = document.body;
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            const adminMenuButton = document.getElementById('adminMenuButton');
            const adminMenu = document.getElementById('adminMenu');

            toggleButton.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                body.classList.toggle('light-mode');

                if (body.classList.contains('dark-mode')) {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                } else {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            });

            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });

            adminMenuButton.addEventListener('click', function() {
                adminMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            window.addEventListener('click', function(event) {
                if (!adminMenuButton.contains(event.target) && !adminMenu.contains(event.target)) {
                    adminMenu.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
