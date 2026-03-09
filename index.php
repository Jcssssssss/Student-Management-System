<?php
// ============================================================
// Login Page
// ============================================================
require_once 'config.php';

// If already logged in, go to dashboard
if (isset($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($student_id) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['full_name']  = $user['full_name'];
                redirect('dashboard.php');
            } else {
                $error = 'Invalid Student ID or Password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please run setup.php first.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Student Task Manager</title>
    <meta name="description" content="Student Task Management System — Login to manage your tasks and assignments.">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Animated background -->
    <div class="bg-shapes">
        <span></span><span></span><span></span>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <div class="icon">📋</div>
                <h1>Task Manager</h1>
                <p>Student Task Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" 
                           placeholder="e.g. STU-2026-001" 
                           value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" 
                           required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password" 
                           required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    🔓 Sign In
                </button>
            </form>

            <p class="text-center mt-24" style="font-size: 14px; color: var(--text-secondary);">
                Don't have an account? 
                <a href="register.php" id="registerLink">Create one →</a>
            </p>
        </div>
    </div>
</body>
</html>
