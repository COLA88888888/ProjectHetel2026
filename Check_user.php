<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບ!']);
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

            $redirect = ($user['status'] == "ຜູ້ບໍລິຫານ") ? 'menu_admin.php' : 'menu_user.php';
            
            echo json_encode([
                'success' => true, 
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຊື່ຜູ້ນຳໃຊ້ນີ້ໃນລະບົບ!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
