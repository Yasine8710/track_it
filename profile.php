<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $avatarPath = null;

        // Handle file upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['avatar']['tmp_name']);
            finfo_close($fileInfo);

            if (in_array($mimeType, $allowedTypes)) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('avatar_') . '.' . $ext;
                $destinationDir = 'uploads/avatars/';
                $destination = $destinationDir . $filename;
                
                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    $avatarPath = $destination;
                }
            } else {
                $message = 'Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.';
                $messageType = 'error';
            }
        }

        if ($username && $messageType !== 'error') {
            if ($avatarPath) {
                // Delete old avatar if exists
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $oldAvatar = $stmt->fetchColumn();
                if ($oldAvatar && file_exists($oldAvatar)) {
                    unlink($oldAvatar);
                }

                $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_picture = ? WHERE id = ?");
                $stmt->execute([$username, $avatarPath, $_SESSION['user_id']]);
                $_SESSION['profile_picture'] = $avatarPath;
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$username, $_SESSION['user_id']]);
            }
            $_SESSION['username'] = $username;
            $message = 'Profile updated successfully.';
            $messageType = 'success';
        }
    } elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (password_verify($currentPassword, $user['password'])) {
            if (strlen($newPassword) >= 6) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $_SESSION['user_id']]);
                $message = 'Password updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'New password must be at least 6 characters.';
                $messageType = 'error';
            }
        } else {
            $message = 'Current password is incorrect.';
            $messageType = 'error';
        }
    }
}

// Fetch user data for displaying the current avatar
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
$currentAvatar = $currentUser['profile_picture'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=4338CA&color=fff';

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Dineri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        primary: '#1E1B4B',
                        accent: '#4338CA',
                        highlight: '#818CF8'
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); 
            border-radius: 1.5rem; 
        }
        html.dark .glass-card {
            background: #0a0a0a; 
            border-color: #2a2a2a;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); 
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 h-screen flex transition-colors duration-200">

    <main class="flex-1 flex flex-col h-full relative w-full">
        <!-- Header -->
        <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center px-6 lg:px-10 justify-between z-30 sticky top-0 transition-colors duration-200">
            <div class="flex items-center">
                <a href="dashboard.php" class="mr-4 text-slate-500 hover:text-accent dark:text-slate-400 dark:hover:text-accent transition-colors">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-xl font-semibold text-primary dark:text-white">User Profile</h1>
            </div>
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-6 lg:p-10">
            <div class="max-w-3xl mx-auto space-y-6">
                
                <?php if ($message): ?>
                <div class="p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30' : 'bg-red-50 text-red-600 dark:bg-red-900/30'; ?> border <?php echo $messageType === 'success' ? 'border-emerald-200 dark:border-emerald-800' : 'border-red-200 dark:border-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="glass-card p-8">
                    <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">Profile Identity</h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="flex items-center space-x-6">
                            <div class="shrink-0">
                                <img id="avatar-preview" class="h-20 w-20 object-cover rounded-full border-4 border-slate-100 dark:border-slate-800 shadow-md" src="<?php echo htmlspecialchars($currentAvatar); ?>" alt="Current profile photo" />
                            </div>
                            <label class="block">
                                <span class="sr-only">Choose profile photo</span>
                                <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatar-preview').src = window.URL.createObjectURL(this.files[0])" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 dark:hover:file:bg-indigo-900/50 transition-colors cursor-pointer"/>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400 mb-2">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-accent focus:ring-1 focus:ring-accent outline-none font-medium dark:text-white transition-colors">
                        </div>
                        <button type="submit" class="bg-primary dark:bg-accent hover:bg-accent dark:hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-semibold shadow-sm transition-colors">Save Identity</button>
                    </form>
                </div>

                <div class="glass-card p-8">
                    <h2 class="text-xl font-bold mb-6 text-slate-800 dark:text-white">Security Settings</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_password">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400 mb-2">Current Password</label>
                            <input type="password" name="current_password" required class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-accent focus:ring-1 focus:ring-accent outline-none font-medium dark:text-white transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-400 mb-2">New Password <span class="text-xs font-normal text-slate-500">(min. 6 characters)</span></label>
                            <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:border-accent focus:ring-1 focus:ring-accent outline-none font-medium dark:text-white transition-colors">
                        </div>
                        <button type="submit" class="bg-primary dark:bg-accent hover:bg-accent dark:hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-semibold shadow-sm transition-colors">Update Integrity Key</button>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        // Dark Mode Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const root = document.documentElement;

        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        themeToggleBtn.addEventListener('click', () => {
            root.classList.toggle('dark');
            if (root.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    </script>
</body>
</html>

