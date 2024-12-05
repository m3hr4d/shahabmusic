<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

$faqJson = '[
    {
        "question": "چه دوره‌هایی در دسترس هستند؟",
        "answer": "ما دوره‌های متنوعی از جمله پیانو، گیتار، ویولن و تئوری موسیقی ارائه می‌دهیم. دوره‌های ما برای همه سطوح مهارتی، از مبتدی تا پیشرفته طراحی شده‌اند."
    },
    {
        "question": "دوره‌ها چقدر طول می‌کشند؟",
        "answer": "مدت زمان دوره‌ها متفاوت است، اما بیشتر دوره‌ها را می‌توان در 8-12 هفته به پایان رساند. هر دوره به گونه‌ای ساختار یافته است که به شما امکان می‌دهد با سرعت خود یاد بگیرید و گزینه‌های برنامه‌ریزی انعطاف‌پذیر داشته باشید."
    },
    {
        "question": "آیا پس از اتمام دوره گواهینامه دریافت می‌کنم؟",
        "answer": "بله، شما برای هر دوره‌ای که به پایان می‌رسانید، گواهینامه‌ای دریافت خواهید کرد. این گواهینامه می‌تواند به رزومه یا پورتفولیوی شما ارزش افزوده کند."
    },
    {
        "question": "آیا پشتیبانی در دسترس است؟",
        "answer": "بله، ما پشتیبانی 24/7 برای همه دانشجویان خود از طریق ایمیل و چت زنده ارائه می‌دهیم. تیم پشتیبانی اختصاصی ما اینجاست تا به هر سوال یا نگرانی شما پاسخ دهد."
    },
    {
        "question": "آیا پیش‌نیازهایی برای دوره‌ها وجود دارد؟",
        "answer": "بیشتر دوره‌های مبتدی پیش‌نیازی ندارند. دوره‌های پیشرفته ممکن است به دانش یا مهارت‌های قبلی نیاز داشته باشند که در توضیحات دوره مشخص خواهد شد."
    }
]';

$faqItems = json_decode($faqJson, true);

include 'header.php';
?>

<style>
    body {
        font-family: 'BYekan', 'Vairmatn', 'Montserrat', sans-serif;
        transition: background-color 0.3s, color 0.3s;
    }
    .light-mode {
        background-color: #f9f9f9;
        color: #333;
    }
    .dark-mode {
        background-color: #1a202c;
        color: #cbd5e0;
    }
    .banner-bg {
        background-image: url('https://images.unsplash.com/photo-1509335919466-c196457ea95a?q=80&w=3270&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
        background-size: cover;
        background-position: center;
    }
    .faq-item {
        border-bottom: 1px solid #e5e5e5;
    }
    .faq-question {
        cursor: pointer;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s ease;
        font-size: 1.1rem;
    }
    .faq-question:hover {
        background-color: #f0f0f0;
    }
    .faq-question.active {
        background-color: #3490dc;
        color: white;
    }
    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
        font-size: 1rem;
    }
    .faq-answer.active {
        max-height: 500px;
        padding: 1.5rem;
    }
    .faq-icon {
        transition: transform 0.3s ease;
    }
    .faq-icon.active {
        transform: rotate(180deg);
    }
    .dark-mode .newsletter-section {
        background-color: #2d3748;
    }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #38a169;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
        z-index: 1000;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .notification-icon {
        font-size: 1.5rem;
    }
    .notification.hide {
        opacity: 0;
        transform: translateY(-20px);
    }
</style>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="notification" id="successNotification">
        <span class="notification-icon">✔️</span>
        <span><?php echo $_SESSION['success_message']; ?></span>
    </div>
    <script>
        setTimeout(function() {
            document.getElementById('successNotification').classList.add('hide');
        }, 3000);
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<section class="banner-bg text-white py-20 px-4" dir="rtl">
    <div class="max-w-6xl mx-auto text-center">
        <h1 class="text-4xl md:text-6xl font-bold mb-6 banner-title">به SemiTone خوش آمدید!</h1>
        <p class="text-xl mb-8">به جامعه‌ای از یادگیرندگان پرشور بپیوندید!</p>
        <a href="courses.php" class="bg-white text-blue-600 font-bold py-3 px-8 rounded-full hover:bg-blue-100 transition duration-300">مشاهده دوره‌ها</a>
    </div>
</section>

<main class="container mx-auto px-4 py-8" dir="rtl">
    <section class="mb-16">
        <h2 class="text-3xl font-bold text-center mb-4">چرا SemiTone را انتخاب کنید</h2>
        <p class="text-xl text-center text-gray-600 mb-12">ویژگی‌های کلیدی ما را کشف کنید</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <img src="https://images.unsplash.com/photo-1507838153414-b4b713384a76?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="دوره‌های تخصصی" class="w-full h-40 object-cover rounded-t-lg mb-4">
                <h3 class="text-xl font-semibold mb-2">دوره‌های تخصصی</h3>
                <p>از حرفه‌ای‌های صنعت با سال‌ها تجربه یاد بگیرید</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="یادگیری انعطاف‌پذیر" class="w-full h-40 object-cover rounded-t-lg mb-4">
                <h3 class="text-xl font-semibold mb-2">یادگیری انعطاف‌پذیر</h3>
                <p>با سرعت خود، هر زمان و هر مکان مطالعه کنید</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="گواهینامه‌های معتبر" class="w-full h-40 object-cover rounded-t-lg mb-4">
                <h3 class="text-xl font-semibold mb-2">گواهینامه‌های معتبر</h3>
                <p>گواهینامه‌هایی که توسط کارفرمایان در سراسر جهان ارزش‌گذاری می‌شوند را کسب کنید</p>
            </div>
        </div>
    </section>

    <section class="bg-white py-12 px-4 rounded-lg shadow-md mb-12">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-8">نظر دانشجویان ما</h2>
            <div class="mb-6">
                <img src="https://images.unsplash.com/photo-1463453091185-61582044d556?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="دانشجو" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover">
                <p class="text-lg mb-4">"دوره‌های SemiTone مهارت‌های موسیقی من را متحول کرده‌اند. مربیان بسیار عالی هستند و محتوا جامع است."</p>
                <h4 class="font-semibold">جان دو</h4>
                <p>موسیقیدان مشتاق</p>
            </div>
        </div>
    </section>

    <section class="bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-3xl font-bold text-center mb-8">سوالات متداول</h2>
        <div class="max-w-3xl mx-auto">
            <?php foreach ($faqItems as $index => $item): ?>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(<?php echo $index; ?>)">
                        <span class="font-semibold"><?php echo sanitizeInput($item['question']); ?></span>
                        <svg class="faq-icon w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <div class="faq-answer" id="faq-answer-<?php echo $index; ?>">
                        <p><?php echo sanitizeInput($item['answer']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bg-blue-100 py-12 mt-12 rounded-lg newsletter-section">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-6">به خبرنامه ما بپیوندید</h2>
            <form action="subscribe_newsletter.php" method="post" class="max-w-md mx-auto">
                <div class="flex flex-col sm:flex-row">
                    <input type="email" name="email" required placeholder="ایمیل خود را وارد کنید" class="flex-grow px-4 py-2 mb-2 sm:mb-0 sm:rounded-l-lg rounded-lg sm:rounded-r-none focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg sm:rounded-l-none hover:bg-blue-700 transition duration-300">عضویت</button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
function toggleFAQ(index) {
    const questions = document.querySelectorAll('.faq-question');
    const answers = document.querySelectorAll('.faq-answer');
    const icons = document.querySelectorAll('.faq-icon');
        
    questions.forEach((q, i) => {
        if (i === index) {
            q.classList.toggle('active');
            answers[i].classList.toggle('active');
            icons[i].classList.toggle('active');
        } else {
            q.classList.remove('active');
            answers[i].classList.remove('active');
            icons[i].classList.remove('active');
        }
    });
}
</script>

<?php include 'footer.php'; ?>
