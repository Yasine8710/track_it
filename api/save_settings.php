<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('TEST_MODE')) {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$user_id = $_SESSION['user_id'];
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$currency = $_POST['currency'] ?? 'TND';
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$bio = $_POST['bio'] ?? '';

// Handle Avatar Upload
$profile_picture = null;
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
    if (!defined('TEST_MODE')) {
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
    } else {
        $upload_dir = __DIR__ . '/../uploads/avatars/';
    }
    
    $file_info = pathinfo($_FILES['avatar_file']['name']);
    $extension = strtolower($file_info['extension']);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($extension, $allowed_extensions)) {
        $new_filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $target_path)) {
            $profile_picture = 'uploads/avatars/' . $new_filename;
        }
    }
}

try {
    if ($profile_picture) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, currency = ?, full_name = ?, phone = ?, address = ?, bio = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$username, $email, $currency, $full_name, $phone, $address, $bio, $profile_picture, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, currency = ?, full_name = ?, phone = ?, address = ?, bio = ? WHERE id = ?");
        $stmt->execute([$username, $email, $currency, $full_name, $phone, $address, $bio, $user_id]);
    }
    
    $result = ['success' => true];
    echo json_encode($result);
    if (!defined('TEST_MODE')) {
        exit;
    } else {
        return $result;
    }
} catch (PDOException $e) {
    $result = ['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()];
    echo json_encode($result);
    if (!defined('TEST_MODE')) {
        exit;
    } else {
        return $result;
    }
}
