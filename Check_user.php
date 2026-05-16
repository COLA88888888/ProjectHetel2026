<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

// Language Selection Logic
$current_lang = $_SESSION['lang'] ?? 'la';
$lang_file = "lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include "lang/la.php";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => $lang['login_required_msg']]);
    exit();
}

try {
    // 1. First, find the user by username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Check password using password_verify (for $2y$ hashes)
        $password_matches = false;
        
        if (password_verify($password, $user['password'])) {
            $password_matches = true;
        } 
        // 3. Fallback: Check if it's MySQL PASSWORD() (for legacy accounts)
        else {
            $stmtLegacy = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = PASSWORD(?)");
            $stmtLegacy->execute([$username, $password]);
            if ($stmtLegacy->fetch()) {
                $password_matches = true;
            }
            // 4. Fallback: Check if it's plain text (for newly created simple accounts)
            else if ($password === $user['password']) {
                $password_matches = true;
            }
        }

        if ($password_matches) {
            $_SESSION['checked'] = 1;
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['permissions'] = $user['permissions'];
            $_SESSION['profile_img'] = $user['profile_img'];

            $redirect = ($user['status'] == "ຜູ້ບໍລິຫານ") ? 'menu_admin.php' : 'menu_user.php';
            
            logActivity($pdo, $lang['log_login'], $lang['username'] . ": $username");

            echo json_encode([
                'success' => true, 
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $lang['login_failed_password']]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $lang['login_failed_user']]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
