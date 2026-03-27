<?php
session_start();
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DINERI | Terminal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #000000;
            --surface: #111111;
            --accent: #B092F9; 
            --input-bg: #1A1A1A;
            --input-border: #333333;
            --text-main: #FFFFFF;
            --text-sub: #A1A1A1;
            --font-main: "Inter", sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: var(--font-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-box {
            width: 48px;
            height: 48px;
            background: var(--accent);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 24px;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 15px;
            color: var(--text-sub);
            margin-bottom: 32px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 18px;
        }

        .input-group input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 16px 48px;
            color: #fff;
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-group input:focus {
            border-color: var(--accent);
            background: #222;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            cursor: pointer;
        }

        .forgot-link {
            display: block;
            text-align: center;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .primary-btn {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 30px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-bottom: 24px;
        }

        .primary-btn:active {
            transform: scale(0.98);
        }

        .divider {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            color: var(--text-sub);
            font-size: 12px;
            font-weight: 700;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--input-border);
        }

        .divider span {
            padding: 0 16px;
        }

        .social-btn {
            width: 100%;
            padding: 14px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            margin-bottom: 12px;
            transition: background 0.2s ease;
        }

        .social-btn:hover {
            background: #252525;
        }

        .social-btn img {
            width: 20px;
            height: 20px;
        }

        .footer-text {
            text-align: center;
            font-size: 14px;
            color: var(--text-sub);
            margin-top: 40px;
        }

        .footer-text span {
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            margin-left: 4px;
        }

        .auth-module {
            display: none;
        }

        .auth-module.active {
            display: block;
        }

        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #ff3b30;
            color: #fff;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
        }
        #toast.visible { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="brand-header">
            <div class="logo-box">
                <i class="fas fa-door-open"></i>
            </div>
            <div class="brand-name">DINERI</div>
        </div>

        <div id="login-module" class="auth-module active">
            <h1>Login to your account</h1>
            <p class="subtitle">Welcome back! Please enter your details.</p>
            
            <form id="login-form">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <i class="far fa-envelope"></i>
                    <input type="text" name="username" placeholder="Username" required autocomplete="off" spellcheck="false">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="login-pass" placeholder="Password" required>
                    <i class="far fa-eye password-toggle" onclick="togglePass('login-pass')"></i>
                </div>

                <a href="#" class="forgot-link">Forgot password?</a>

                <button type="submit" class="primary-btn" id="login-btn">Login</button>
            </form>

            <div class="divider"><span>OR</span></div>

            <button class="social-btn" type="button">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google">
                Sign In with Google
            </button>
            <button class="social-btn" type="button">
                <i class="fab fa-apple" style="font-size: 20px;"></i>
                Sign In with Apple
            </button>

            <p class="footer-text">Don’t have an account? <span onclick="switchAuth('signup')">Create account</span></p>
        </div>

        <div id="signup-module" class="auth-module">
            <h1>Create an account</h1>
            <p class="subtitle">Join the elite track circle today.</p>
            
            <form id="register-form">
                <input type="hidden" name="action" value="register">
                <div class="input-group">
                    <i class="far fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required autocomplete="off" spellcheck="false">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="reg-pass" placeholder="Create Password" required>
                    <i class="far fa-eye password-toggle" onclick="togglePass('reg-pass')"></i>
                </div>

                <button type="submit" class="primary-btn" id="register-btn">Create account</button>
            </form>

            <div class="divider"><span>OR</span></div>

            <button class="social-btn" type="button">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google">
                Sign up with Google
            </button>

            <p class="footer-text">Already have an account? <span onclick="switchAuth('login')">Log in</span></p>
        </div>
    </div>

    <div id="toast">Error Message</div>

    <script>
        function togglePass(id) {
            const el = document.getElementById(id);
            el.type = el.type === "password" ? "text" : "password";
        }

        function switchAuth(type) {
            document.getElementById("login-module").classList.toggle("active", type === "login");
            document.getElementById("signup-module").classList.toggle("active", type === "signup");
        }

        async function handleAuth(e, btnId, originalText) {
            e.preventDefault();
            const btn = document.getElementById(btnId);
            const formData = new FormData(e.target);
            
            btn.disabled = true;
            btn.textContent = "Processing...";

            try {
                const res = await fetch("api/auth.php", { method: "POST", body: formData });
                const data = await res.json();

                if (data.success) {
                    window.location.href = "dashboard.php";
                } else {
                    showToast(data.message);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                showToast("Connection failed");
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        function showToast(msg) {
            const toast = document.getElementById("toast");
            toast.textContent = msg;
            toast.classList.add("visible");
            setTimeout(() => toast.classList.remove("visible"), 3000);
        }

        document.getElementById("login-form").addEventListener("submit", (e) => handleAuth(e, "login-btn", "Login"));
        document.getElementById("register-form").addEventListener("submit", (e) => handleAuth(e, "register-btn", "Create account"));
    </script>
</body>
</html>