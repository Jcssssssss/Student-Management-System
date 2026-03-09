<?php
// ============================================================
// Registration Page
// ============================================================
require_once 'config.php';

if (isset($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (empty($student_id) || empty($full_name) || empty($password)) {
        $error = 'Student ID, Full Name, and Password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = getDB();

            // Check if student_id already exists
            $check = $db->prepare("SELECT id FROM students WHERE student_id = ?");
            $check->execute([$student_id]);
            if ($check->fetch()) {
                $error = 'Student ID already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO students (student_id, full_name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $full_name, $email ?: null, $hash]);
                $success = 'Account created successfully! You can now log in.';
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
    <title>Register — Student Task Manager</title>
    <meta name="description" content="Create a new account for the Student Task Management System.">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="bg-shapes">
        <span></span><span></span><span></span>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <div class="icon">📝</div>
                <h1>Create Account</h1>
                <p>Join the Student Task Manager</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" 
                           placeholder="e.g. STU-2026-001" 
                           value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           placeholder="Enter your full name" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email <span style="color:var(--text-muted);">(optional)</span></label>
                    <input type="email" id="email" name="email" 
                           placeholder="your@email.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="registerBtn">
                    ✨ Create Account
                </button>
            </form>

            <p class="text-center mt-24" style="font-size: 14px; color: var(--text-secondary);">
                Already have an account? 
                <a href="index.php" id="loginLink">Sign in →</a>
            </p>
        </div>
    </div>
</body>
</html>
