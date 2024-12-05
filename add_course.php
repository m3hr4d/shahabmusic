<?php
session_start();
require_once 'error_log.php';
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    custom_error_log("Unauthorized access attempt to add course page");
    header('Location: index.php');
    exit;
}

include('header.php');
?>

<div class="container mx-auto px-4 py-8" dir="rtl">
    <h1 class="text-3xl font-bold mb-8">افزودن دوره جدید</h1>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            if ($_GET['error'] === 'missing_fields') {
                echo 'لطفا تمام فیلدهای ضروری را پر کنید.';
            } elseif ($_GET['error'] === 'save_failed') {
                echo 'خطا در ذخیره دوره. ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '');
            }
            ?>
        </div>
    <?php endif; ?>
    
    <form id="courseForm" action="process_add_course.php" method="POST" class="max-w-2xl">
        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">عنوان دوره</label>
            <input type="text" id="title" name="title" required 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">توضیحات دوره</label>
            <textarea id="description" name="description" required 
                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                      rows="4"></textarea>
        </div>
        
        <div class="mb-4">
            <label for="image_url" class="block text-gray-700 text-sm font-bold mb-2">آدرس تصویر دوره</label>
            <input type="url" id="image_url" name="image_url" required 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        
        <div class="mb-4">
            <label for="directory_url" class="block text-gray-700 text-sm font-bold mb-2">آدرس فهرست ویدئوها</label>
            <div class="flex">
                <input type="url" id="directory_url" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                       placeholder="آدرس فهرست را وارد کنید">
                <button type="button" onclick="fetchVideos()" 
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">
                    دریافت ویدئوها
                </button>
            </div>
        </div>

        <!-- Container for video details forms -->
        <div id="videoDetailsContainer" class="space-y-6"></div>

        <!-- Hidden input for lessons data -->
        <input type="hidden" id="lessons" name="lessons" value="[]">

        <div id="submitContainer" class="flex items-center justify-between mt-6" style="display: none;">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                افزودن دوره
            </button>
        </div>
    </form>
</div>

<script>
async function fetchVideos() {
    const directoryUrl = document.getElementById('directory_url').value;
    if (!directoryUrl) {
        alert('لطفا آدرس فهرست را وارد کنید');
        return;
    }

    try {
        const response = await fetch('fetch_directory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url: directoryUrl })
        });

        if (!response.ok) {
            throw new Error('خطا در دریافت اطلاعات');
        }

        const data = await response.json();
        if (data.error) {
            alert(data.error);
            return;
        }

        // Sort videos by their numerical prefix
        const sortedVideos = data.videos.sort((a, b) => {
            const numA = parseInt(a.match(/\d+/)?.[0] || '0');
            const numB = parseInt(b.match(/\d+/)?.[0] || '0');
            return numA - numB;
        });

        // Generate forms for each video
        const container = document.getElementById('videoDetailsContainer');
        container.innerHTML = '';

        sortedVideos.forEach((url, index) => {
            const lessonNumber = (index + 1).toString().padStart(2, '0');
            const videoFileName = url.split('/').pop();
            
            const form = document.createElement('div');
            form.className = 'p-4 border rounded-lg bg-gray-50';
            form.innerHTML = `
                <div class="mb-2 font-bold text-gray-700">درس ${lessonNumber}</div>
                <div class="mb-2 text-sm text-gray-600">${videoFileName}</div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">عنوان درس *</label>
                    <input type="text" name="lesson_title_${index}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           value="درس ${lessonNumber}">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">توضیحات درس</label>
                    <textarea name="lesson_description_${index}" rows="2"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <input type="hidden" name="lesson_url_${index}" value="${url}">
            `;
            container.appendChild(form);
        });

        // Show submit button
        document.getElementById('submitContainer').style.display = 'flex';

    } catch (error) {
        alert('خطا در دریافت اطلاعات: ' + error.message);
    }
}

// Form submission handler
document.getElementById('courseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const container = document.getElementById('videoDetailsContainer');
    const lessons = [];
    let index = 0;
    
    while (true) {
        const titleInput = container.querySelector(`[name="lesson_title_${index}"]`);
        const descInput = container.querySelector(`[name="lesson_description_${index}"]`);
        const urlInput = container.querySelector(`[name="lesson_url_${index}"]`);
        
        if (!titleInput || !urlInput) break;
        
        lessons.push({
            title: titleInput.value,
            description: descInput.value || '',
            url: urlInput.value
        });
        
        index++;
    }

    if (lessons.length === 0) {
        alert('لطفا ابتدا ویدئوها را دریافت کنید');
        return;
    }

    document.getElementById('lessons').value = JSON.stringify(lessons);
    console.log('Submitting lessons:', lessons); // Debug log
    this.submit();
});
</script>

<?php include('footer.php'); ?>
