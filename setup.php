<?php
// ============================================================
// Database Auto-Setup — run once via browser
// http://localhost/SIA_Project/setup.php
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';

echo "<pre style='font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:30px;border-radius:12px;max-width:700px;margin:60px auto;'>";
echo "╔══════════════════════════════════════════╗\n";
echo "║  Student Task Management System Setup    ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

try {
    // Connect without database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS student_task_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅  Database 'student_task_db' created/verified.\n";

    // Select database
    $pdo->exec("USE student_task_db");

    // Create students table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    echo "✅  Table 'students' created/verified.\n";

    // Create tasks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            deadline DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅  Table 'tasks' created/verified.\n";

    // Insert demo student (if not exists)
    $check = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $check->execute(['STU-2026-001']);
    if (!$check->fetch()) {
        $hash = password_hash('password', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute(['STU-2026-001', 'John Carl Padilla', 'johncarl@example.com', $hash]);
        echo "✅  Demo student created.\n";
        echo "    Student ID : STU-2026-001\n";
        echo "    Password   : password\n";

        // Insert demo tasks
        $tasks = [
            ['STU-2026-001', 'SIA101 Final Project',        'Build the Student Task Management System.',    date('Y-m-d', strtotime('+5 days')),  'Ongoing'],
            ['STU-2026-001', 'Database Design Assignment',   'Create ER diagram for the task manager.',      date('Y-m-d', strtotime('+2 days')),  'Pending'],
            ['STU-2026-001', 'HTML/CSS Lab Exercise',        'Complete responsive layout exercise.',          date('Y-m-d', strtotime('-1 day')),   'Completed'],
            ['STU-2026-001', 'PHP Fundamentals Quiz',        'Study PHP basics for upcoming quiz.',          date('Y-m-d', strtotime('+7 days')),  'Pending'],
            ['STU-2026-001', 'JavaScript Practice',          'Practice DOM manipulation exercises.',          date('Y-m-d', strtotime('+3 days')),  'Ongoing'],
        ];
        $stmt = $pdo->prepare("INSERT INTO tasks (student_id, title, description, deadline, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($tasks as $t) {
            $stmt->execute($t);
        }
        echo "✅  5 demo tasks inserted.\n";
    } else {
        echo "ℹ️  Demo student already exists, skipping.\n";
    }

    echo "\n══════════════════════════════════════════\n";
    echo "🎉  Setup complete!\n";
    echo "👉  <a href='index.php' style='color:#7c5cfc;text-decoration:underline;'>Go to Login Page →</a>\n";

} catch (PDOException $e) {
    echo "❌  Error: " . $e->getMessage() . "\n";
    echo "\n⚠️  Make sure XAMPP Apache & MySQL are running!\n";
}

echo "</pre>";
?>
