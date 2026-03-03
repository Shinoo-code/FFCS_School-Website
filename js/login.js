document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const forgotPasswordLink = document.querySelector('.forgot-password a');
    
    // Elements needed for both manual and potentially 2FA flow 
    const emailInput = document.getElementById('email'); 
    const passwordInput = document.getElementById('password'); 
    
    // NEW: Modal Elements (for Forgot Password)
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const closeForgotPasswordModalBtn = document.getElementById('closeForgotPasswordModalBtn');
    const okForgotPasswordModalBtn = document.getElementById('okForgotPasswordModalBtn');

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!emailInput || !passwordInput) {
                console.error("Login form elements are missing!");
                if (errorMessage) {
                    errorMessage.textContent = 'Login form elements are missing.';
                    errorMessage.style.display = 'block';
                }
                return;
            }

            const email = emailInput.value;
            const password = passwordInput.value;
            const loginButton = loginForm.querySelector('.login-btn');

            if (errorMessage) { errorMessage.style.display = 'none'; }
            if (loginButton) loginButton.disabled = true;

            try {
                const response = await fetch('./api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        // include CSRF token exposed by server in login.php
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: JSON.stringify({ email, password, csrf_token: window.APP_CSRF_TOKEN || '' }),
                });

                const responseText = await response.text();
                // Check if response is empty string (sometimes happens on success/failure)
                const data = responseText ? JSON.parse(responseText) : {};

                if (response.ok && data.success) {
                    
                    // --- OTP REDIRECTION LOGIC ---
                    if (data.twoFactorRequired && data.email) {
                        // The server successfully authenticated password and sent the OTP.
                        // We must now redirect to the verification page.
                        window.location.href = `verify_otp.php?email=${encodeURIComponent(data.email)}`;
                        return; // Stop execution to ensure redirect happens
                    } 
                    // --- END OTP REDIRECTION LOGIC ---
                    
                    // Successful login (no 2FA/OTP required)
                    window.location.href = 'dashboard.php';

                } else {
                    // Final failed login (invalid password or server-side error)
                    let errorMsg = data.message || `Login Failed: ${response.statusText}`;

                    if (errorMessage) {
                        errorMessage.textContent = errorMsg;
                        errorMessage.style.display = 'block';
                    }
                    console.error("Error signing in with backend:", errorMsg);
                }
            } catch (error) {
                if (errorMessage) {
                    errorMessage.textContent = 'Login request failed. Please check server console.';
                    errorMessage.style.display = 'block';
                }
                console.error("Network or other error during login:", error);
            } finally {
                if (loginButton) loginButton.disabled = false;
            }
        });
    } else {
        console.error("Login form element not found!");
    }

    // --- Forgot Password Modal Logic (unmodified) ---
    function showForgotPasswordModal() {
        if (forgotPasswordModal) {
            forgotPasswordModal.style.display = 'flex'; 
            setTimeout(() => { forgotPasswordModal.classList.add('active'); }, 10);
        }
    }

    function hideForgotPasswordModal() {
        if (forgotPasswordModal) {
            forgotPasswordModal.classList.remove('active');
            setTimeout(() => { forgotPasswordModal.style.display = 'none'; }, 300); 
        }
    }

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            showForgotPasswordModal();
        });
    }

    if (closeForgotPasswordModalBtn) {
        closeForgotPasswordModalBtn.addEventListener('click', hideForgotPasswordModal);
    }
    if (okForgotPasswordModalBtn) {
        okForgotPasswordModalBtn.addEventListener('click', hideForgotPasswordModal);
    }
    // Optional: Close modal if user clicks on the overlay
    if (forgotPasswordModal) {
        forgotPasswordModal.addEventListener('click', function(event) {
            if (event.target === forgotPasswordModal) {
                hideForgotPasswordModal();
            }
        });
    }
});