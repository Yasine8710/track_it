<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$pet_id = $_POST['pet_id'] ?? $_GET['pet_id'] ?? null;

// Handle Avatar Upload
$profile_picture = null;
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
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
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$username, $email, $profile_picture, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $user_id]);
    }
    
    // Handle pet selection
    if ($pet_id !== null) {
        if ($pet_id === '') {
            // Remove pet
            $pdo->prepare("DELETE FROM user_pets WHERE user_id = ?")->execute([$user_id]);
        } else {
            // Add or update pet
            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_id, streak_count) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE pet_id = VALUES(pet_id)");
            $stmt->execute([$user_id, $pet_id]);
        }
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
}
