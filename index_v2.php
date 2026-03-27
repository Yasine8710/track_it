<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dineri - Login & Register</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        primary: '#000000',
                        secondary: '#ffffff',
                        accent: '#3b82f6', // subtle blue
                        surface: '#f3f4f6',
                        textMain: '#111827',
                        textMuted: '#6b7280',
                        border: '#e5e7eb',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--surface);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .auth-container {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            min-height: 600px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .auth-container.right-panel-active .sign-in-container {
            transform: translateX(100%);
            opacity: 0;
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .auth-container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .auth-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: #000;
            background: linear-gradient(to right, #111, #000);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #ffffff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .auth-container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-left {
            transform: translateX(-20%);
        }

        .auth-container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .auth-container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
        }

        .input-group input {
            width: 100%;
            padding: 14px 20px 14px 45px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #000;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .input-group input:focus + i {
            color: #000;
        }

        .btn {
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
            padding: 14px 35px;
            transition: transform 80ms ease-in, all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-primary {
            background-color: #000;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #222;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid #fff;
            color: #fff;
        }

        .btn-outline:hover {
            background-color: #fff;
            color: #000;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .auth-container {
                min-height: 100vh;
                border-radius: 0;
                display: flex;
                flex-direction: column;
                overflow-y: auto;
            }
            form {
               padding: 20px !important;
            }
            .form-container {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
                opacity: 1;
                transform: translateX(0);
                z-index: 1;
            }

            .sign-in-container {
                display: block;
            }

            .sign-up-container {
                display: none;
                opacity: 1;
            }
            
            .auth-container.right-panel-active .sign-in-container {
                display: none;
                transform: translateX(0);
                opacity: 0;
            }

            .auth-container.right-panel-active .sign-up-container {
                display: block;
                transform: translateX(0);
                opacity: 1;
                animation: none;
            }

            .overlay-container {
                display: none; /* Hide overlay on mobile, we will use simple toggle links */
            }

            .mobile-toggle {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: #6b7280;
                font-size: 14px;
            }
            .mobile-toggle span {
                color: #000;
                font-weight: 600;
                cursor: pointer;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-toggle {
                display: none;
            }
        }

    </style>
</head>
<body class="bg-surface antialiased">

<div class="auth-container" id="auth-container">
    <!-- Register Form -->
    <div class="form-container sign-up-container">
        <form id="registerForm" class="flex flex-col justify-center h-full px-12 bg-white" onsubmit="handleForm(event, 'registerForm', 'registerMsg')">
            <h2 class="text-3xl font-bold mb-2 text-textMain">Create Account</h2>
            <p class="text-textMuted mb-8 text-sm">Join Dineri and start managing your finances</p>
            
            <div id="registerMsg" class="mb-4 text-sm font-medium hidden"></div>

            <div class="input-group">
                <input type="text" name="name" placeholder="Full Name" required />
                <i class="fa-regular fa-user"></i>
            </div>
            
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required />
                <i class="fa-regular fa-envelope"></i>
            </div>
            
            <div class="input-group mb-6">
                <input type="password" name="password" placeholder="Password" required />
                <i class="fa-solid fa-lock"></i>
            </div>
            
            <input type="hidden" name="action" value="register">
            <button type="submit" class="btn btn-primary">Sign Up</button>
            <div class="mobile-toggle">
                Already have an account? <span id="signInMobile">Sign In</span>
            </div>
        </form>
    </div>

    <!-- Login Form -->
    <div class="form-container sign-in-container">
        <form id="loginForm" class="flex flex-col justify-center h-full px-12 bg-white" onsubmit="handleForm(event, 'loginForm', 'loginMsg')">
            <h2 class="text-3xl font-bold mb-2 text-textMain">Welcome Back</h2>
            <p class="text-textMuted mb-8 text-sm">Sign in to your Dineri account</p>
            
            <div id="loginMsg" class="mb-4 text-sm font-medium hidden"></div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required />
                <i class="fa-regular fa-envelope"></i>
            </div>
            
            <div class="input-group mb-6">
                <input type="password" name="password" placeholder="Password" required />
                <i class="fa-solid fa-lock"></i>
            </div>
            
            <input type="hidden" name="action" value="login">
            <button type="submit" class="btn btn-primary">Sign In</button>
            <div class="mobile-toggle">
                Don't have an account? <span id="signUpMobile">Sign Up</span>
            </div>
        </form>
    </div>

    <!-- Overlay Container for Desktop Animation -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1 class="text-4xl font-bold mb-4">Welcome Back!</h1>
                <p class="mb-8 text-gray-300">To keep connected with us please login with your personal info</p>
                <button class="btn btn-outline" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1 class="text-4xl font-bold mb-4">Hello, Friend!</h1>
                <p class="mb-8 text-gray-300">Enter your personal details and start your journey with us</p>
                <button class="btn btn-outline" id="signUp">Sign Up</button>
            </div>
        </div>
    </div>
</div>

<script>
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const signUpMobile = document.getElementById('signUpMobile');
    const signInMobile = document.getElementById('signInMobile');
    const authContainer = document.getElementById('auth-container');

    // Desktop toggles
    signUpButton.addEventListener('click', () => {
        authContainer.classList.add("right-panel-active");
    });

    signInButton.addEventListener('click', () => {
        authContainer.classList.remove("right-panel-active");
    });

    // Mobile toggles
    signUpMobile.addEventListener('click', () => {
        authContainer.classList.add("right-panel-active");
    });

    signInMobile.addEventListener('click', () => {
        authContainer.classList.remove("right-panel-active");
    });

    // Handle form submissions universally via fetch to api/auth.php
    async function handleForm(e, formId, msgId) {
        e.preventDefault();
        const form = document.getElementById(formId);
        const msgDiv = document.getElementById(msgId);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        msgDiv.classList.add('hidden');

        try {
            const formData = new FormData(form);
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            msgDiv.classList.remove('hidden');
            if (data.status === 'success') {
                msgDiv.className = `mb-4 text-sm font-medium text-green-600 p-3 bg-green-50 rounded-lg`;
                msgDiv.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i>${data.message}`;
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1000);
            } else {
                msgDiv.className = `mb-4 text-sm font-medium text-red-600 p-3 bg-red-50 rounded-lg`;
                msgDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation mr-2"></i>${data.message || 'An error occurred'}`;
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            msgDiv.classList.remove('hidden');
            msgDiv.className = `mb-4 text-sm font-medium text-red-600 p-3 bg-red-50 rounded-lg`;
            msgDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation mr-2"></i>An error occurred while connecting to the server.`;
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
</script>

</body>
</html>