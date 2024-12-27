<?php
class Database {
    private static $instance = null;
    private $db = null;

    private function __construct() {
        try {
            $this->db = new SQLite3('semitone.db');
            $this->createTables();
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    private function createTables() {
        // Users table
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            username TEXT PRIMARY KEY,
            password TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            first_name TEXT,
            last_name TEXT,
            bio TEXT,
            role TEXT DEFAULT 'user',
            suspended BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Courses table
        $this->db->exec("CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Videos table
        $this->db->exec("CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER,
            url TEXT NOT NULL,
            title TEXT,
            description TEXT,
            order_index INTEGER,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        // Enrollments table
        $this->db->exec("CREATE TABLE IF NOT EXISTS enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_username TEXT,
            course_id INTEGER,
            enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE(user_username, course_id)
        )");

        // Progress table
        $this->db->exec("CREATE TABLE IF NOT EXISTS progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_username TEXT,
            course_id INTEGER,
            video_id INTEGER,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
            UNIQUE(user_username, course_id, video_id)
        )");

        // Tickets table
        $this->db->exec("CREATE TABLE IF NOT EXISTS tickets (
            id TEXT PRIMARY KEY,
            user_username TEXT,
            title TEXT NOT NULL,
            description TEXT,
            status TEXT DEFAULT 'Open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE
        )");

        // Ticket responses table
        $this->db->exec("CREATE TABLE IF NOT EXISTS ticket_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id TEXT,
            user_username TEXT,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE
        )");

        // Reviews table
        $this->db->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER,
            user_username TEXT,
            review TEXT,
            rating INTEGER CHECK(rating >= 1 AND rating <= 5),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_username) REFERENCES users(username) ON DELETE CASCADE,
            UNIQUE(course_id, user_username)
        )");
    }

    public function migrateData() {
        // Start transaction
        $this->db->exec('BEGIN TRANSACTION');

        try {
            // Migrate users
            $users = json_decode(file_get_contents('users.json'), true);
            $stmt = $this->db->prepare('INSERT OR IGNORE INTO users (username, password, email, first_name, last_name, bio, role, suspended) VALUES (:username, :password, :email, :first_name, :last_name, :bio, :role, :suspended)');
            
            foreach ($users as $username => $user) {
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', $user['password'], SQLITE3_TEXT);
                $stmt->bindValue(':email', $user['email'], SQLITE3_TEXT);
                $stmt->bindValue(':first_name', $user['first_name'] ?? null, SQLITE3_TEXT);
                $stmt->bindValue(':last_name', $user['last_name'] ?? null, SQLITE3_TEXT);
                $stmt->bindValue(':bio', $user['bio'] ?? null, SQLITE3_TEXT);
                $stmt->bindValue(':role', $user['role'] ?? 'user', SQLITE3_TEXT);
                $stmt->bindValue(':suspended', $user['suspended'] ?? 0, SQLITE3_INTEGER);
                $stmt->execute();
            }

            // Migrate courses and videos
            $courses = json_decode(file_get_contents('courses.json'), true);
            $stmtCourse = $this->db->prepare('INSERT OR IGNORE INTO courses (id, title, description, image) VALUES (:id, :title, :description, :image)');
            $stmtVideo = $this->db->prepare('INSERT OR IGNORE INTO videos (course_id, url, title, order_index) VALUES (:course_id, :url, :title, :order_index)');
            
            foreach ($courses as $course) {
                $stmtCourse->bindValue(':id', $course['id'], SQLITE3_INTEGER);
                $stmtCourse->bindValue(':title', $course['title'], SQLITE3_TEXT);
                $stmtCourse->bindValue(':description', $course['description'], SQLITE3_TEXT);
                $stmtCourse->bindValue(':image', $course['image'], SQLITE3_TEXT);
                $stmtCourse->execute();

                foreach ($course['videos'] as $index => $video) {
                    $stmtVideo->bindValue(':course_id', $course['id'], SQLITE3_INTEGER);
                    $stmtVideo->bindValue(':url', $video, SQLITE3_TEXT);
                    $stmtVideo->bindValue(':title', "درس " . ($index + 1), SQLITE3_TEXT);
                    $stmtVideo->bindValue(':order_index', $index, SQLITE3_INTEGER);
                    $stmtVideo->execute();
                }
            }

            // Migrate enrollments
            $enrollments = json_decode(file_get_contents('enrollments.json'), true);
            $stmtEnroll = $this->db->prepare('INSERT OR IGNORE INTO enrollments (user_username, course_id) VALUES (:username, :course_id)');
            
            foreach ($enrollments as $username => $courseIds) {
                foreach ($courseIds as $courseId) {
                    $stmtEnroll->bindValue(':username', $username, SQLITE3_TEXT);
                    $stmtEnroll->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
                    $stmtEnroll->execute();
                }
            }

            // Migrate progress
            $progress = json_decode(file_get_contents('progress.json'), true);
            $stmtProgress = $this->db->prepare('INSERT OR IGNORE INTO progress (user_username, course_id, video_id) VALUES (:username, :course_id, :video_id)');
            
            foreach ($progress as $username => $courses) {
                foreach ($courses as $courseId => $videos) {
                    foreach ($videos as $videoIndex) {
                        $stmtProgress->bindValue(':username', $username, SQLITE3_TEXT);
                        $stmtProgress->bindValue(':course_id', $courseId, SQLITE3_INTEGER);
                        $stmtProgress->bindValue(':video_id', $videoIndex, SQLITE3_INTEGER);
                        $stmtProgress->execute();
                    }
                }
            }

            // Commit transaction
            $this->db->exec('COMMIT');
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }
}

// Only create database instance
Database::getInstance();
?>
