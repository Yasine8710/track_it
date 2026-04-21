<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DINERI | Obsidian Edition</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container { width: 100%; max-width: 440px; margin: 60px auto; padding: 0 20px; }
        .auth-logo { width: 60px; height: 60px; background: var(--accent); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #000; font-size: 24px; box-shadow: 0 0 30px var(--accent-glow); }
        .auth-tab-btn { background: none; border: none; color: var(--text-sub); font-weight: 700; font-size: 16px; cursor: pointer; padding: 12px 20px; position: relative; transition: color 0.3s; }
        .auth-tab-btn.active { color: var(--text-main); }
        .auth-tab-btn.active::after { content: ""; position: absolute; bottom: 0; left: 20%; width: 60%; height: 2px; background: var(--accent); }
        .auth-form { display: none; }
        .auth-form.active { display: block; }
        #toast { position: fixed; top: 30px; left: 50%; transform: translateX(-50%) translateY(-100px); background: var(--danger); color: #fff; padding: 12px 24px; border-radius: 50px; font-weight: 600; transition: transform 0.4s; z-index: 1000; }
        #toast.visible { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body class="flex items-center min-vh-100" style="background:var(--bg); color:var(--text-main); font-family:Inter, sans-serif;">
    <div id="toast"></div>
    <div class="auth-container">
        <div class="auth-logo"><i class="fas fa-wallet"></i></div>
        <h1 style="text-align:center; font-family:Outfit; font-size: 32px; margin-bottom: 40px;">DINERI</h1>
        <div style="display:flex; justify-content: center; gap: 20px; margin-bottom: 30px;">
            <button class="auth-tab-btn active" id="btn-login" onclick="switchAuth('login-form')">Login</button>
            <button class="auth-tab-btn" id="btn-register" onclick="switchAuth('register-form')">Join</button>
        </div>
        <div class="premium-card">
            <form id="login-form" class="auth-form active">
                <input type="hidden" name="action" value="login">
                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Username</label>
                    <input type="text" name="username" class="modern-input" placeholder="Your account name" required>
                </div>
                <div style="margin-bottom: 30px;">
                    <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Password</label>
                    <input type="password" name="password" class="modern-input" placeholder="��������" required>
                </div>
                <button type="submit" class="btn-glass" style="width: 100%; background:var(--accent); color:#000; border:none; padding:16px;">Login Account</button>
            </form>
            <form id="register-form" class="auth-form">
                <input type="hidden" name="action" value="register">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Username</label>
                        <input type="text" name="username" class="modern-input" placeholder="Unique ID" required>
                    </div>
                    <div>
                        <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="full_name" class="modern-input" placeholder="Personal Name" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Phone</label>
                        <input type="text" name="phone" class="modern-input" placeholder="+1..." required>
                    </div>
                    <div>
                        <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Address</label>
                        <input type="text" name="address" class="modern-input" placeholder="Home/Work" required>
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Short Bio</label>
                    <textarea name="bio" class="modern-input" style="height: 60px; resize: none;" placeholder="Tell us about yourself..."></textarea>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 8px;">Secret Password</label>
                    <input type="password" name="password" class="modern-input" placeholder="Min. 4 characters" required>
                </div>
                <button type="submit" class="btn-glass" style="width: 100%; background:var(--accent); color:#000; border:none; padding:16px;">Create Portfolio</button>
            </form>
        </div>
    </div>
    <script>
        function switchAuth(id) {
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.querySelectorAll('.auth-tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if(id === 'login-form') document.getElementById('btn-login').classList.add('active');
            else document.getElementById('btn-register').classList.add('active');
        }

        document.querySelectorAll('form').forEach(form => {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const btn = form.querySelector('button');
                btn.disabled = true;
                btn.innerText = 'Syncing...';
                try {
                    const res = await fetch('api/auth.php', { method: 'POST', body: new FormData(form) });
                    const data = await res.json();
                    if(data.success) window.location.href = 'dashboard.php';
                    else { alert(data.message); btn.disabled = false; btn.innerText = 'Try Again'; }
                } catch(e) { alert('Server error'); btn.disabled = false; btn.innerText = 'Retry'; }
            }
        });
    </script>
</body>
</html>
