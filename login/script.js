// THE HANGAR - GUND-ORDER SYSTEM (G.O.S) LOGIN UI SCRIPT

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleBtns = document.querySelectorAll('.passwordToggleBtn');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const wrapper = btn.closest('.inputWrapper');
            const passwordInput = wrapper ? wrapper.querySelector('input') : null;
            if (!passwordInput) return;

            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';

            // Toggle Eye Icon
            btn.innerHTML = isPassword ? `
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
            ` : `
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
        });
    });

    // 2. Tab Switching (Sign In / Register)
    const loginTabBtn = document.getElementById('loginTabBtn');
    const registerTabBtn = document.getElementById('registerTabBtn');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginTabBtn && registerTabBtn && loginForm && registerForm) {
        loginTabBtn.addEventListener('click', () => {
            loginTabBtn.classList.add('activeTab');
            registerTabBtn.classList.remove('activeTab');
            loginForm.classList.remove('hiddenForm');
            registerForm.classList.add('hiddenForm');
        });

        registerTabBtn.addEventListener('click', () => {
            registerTabBtn.classList.add('activeTab');
            loginTabBtn.classList.remove('activeTab');
            registerForm.classList.remove('hiddenForm');
            loginForm.classList.add('hiddenForm');
        });
    }

    // 3. Prevent form submissions for non-functional mockup state
    const forms = document.querySelectorAll('.authForm');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('.submitAuthBtn .btnText');
            if (submitBtn) {
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'AUTHENTICATING...';
                setTimeout(() => {
                    submitBtn.textContent = 'ACCESS GRANTED (DEMO)';
                    setTimeout(() => {
                        submitBtn.textContent = originalText;
                    }, 2000);
                }, 1000);
            }
        });
    });
});
