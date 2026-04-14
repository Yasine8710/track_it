<?php
header('Location: index.php');
exit;
?>
            </div>
        </section>

        <section class="hidden lg:flex relative overflow-hidden bg-primary text-white p-14 flex-col justify-between order-1 lg:order-2">
            <div class="absolute inset-0 mesh opacity-50"></div>
            <div class="absolute top-10 right-10 w-72 h-72 rounded-full bg-accent/30 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>

            <div class="relative z-10 flex items-center gap-3 justify-end">
                <span class="text-2xl font-bold tracking-wide">Dineri</span>
                <div class="w-10 h-10 rounded-xl bg-white text-primary flex items-center justify-center font-bold">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <div class="relative z-10 max-w-xl self-end text-right">
                <p class="uppercase tracking-[0.25em] text-xs text-orange-200 mb-5">Smart Setup</p>
                <h1 class="text-5xl font-bold leading-tight mb-5">Register once and start logging in minutes.</h1>
                <p class="text-slate-200 text-lg leading-relaxed">Dineri creates your starter income and expense categories so you can add transactions immediately.</p>
            </div>

            <div class="relative z-10 text-sm text-slate-300 text-right">&copy; 2026 Dineri</div>
        </section>
    </div>

    <script>
        const registerForm = document.getElementById('register-form');
        const messageDiv = document.getElementById('message');
        const registerBtn = document.getElementById('register-btn');

        async function handleAuth(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>Creating...';

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showMessage("Account created. Redirecting to login...", 'bg-green-50 text-green-600 border border-green-100');
                    setTimeout(() => { window.location.href = 'index.php'; }, 1000);
                } else {
                    showMessage(data.message, 'bg-red-50 text-red-600 border border-red-100');
                    registerBtn.disabled = false;
                    registerBtn.textContent = 'Create My Account';
                }
            } catch (err) {
                showMessage("Connection aborted", 'bg-red-50 text-red-600 border border-red-100');
                registerBtn.disabled = false;
                registerBtn.textContent = 'Create My Account';
            }
        }

        function showMessage(msg, classes) {
            messageDiv.textContent = msg;
            messageDiv.className = `mt-6 text-center text-sm font-medium ${classes} px-4 py-3 rounded-xl slide-up`;
            messageDiv.classList.remove('hidden');
        }

        registerForm.addEventListener('submit', handleAuth);
    </script>
</body>
</html>



