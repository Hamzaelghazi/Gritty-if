// Two-step login: email first, then password.
(function () {
    const form = document.getElementById('login-form');
    const emailStep = form.querySelector('[data-step="email"]');
    const passwordStep = form.querySelector('[data-step="password"]');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const emailChip = document.getElementById('email-chip');
    const submitBtn = document.getElementById('submit-btn');
    const btnLabel = submitBtn.querySelector('.btn-label');
    const subtitle = document.getElementById('login-subtitle');
    const success = document.getElementById('success');

    let step = 'email';
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    function setError(input, el, message) {
        input.parentElement.classList.toggle('invalid', Boolean(message));
        el.textContent = message;
    }

    function loading(on) {
        submitBtn.classList.toggle('loading', on);
        submitBtn.disabled = on;
    }

    function goToPassword() {
        step = 'password';
        emailStep.hidden = true;
        passwordStep.hidden = false;
        emailChip.textContent = email.value.trim();
        subtitle.textContent = 'Enter your password';
        btnLabel.textContent = 'Log in';
        password.focus();
    }

    function goToEmail() {
        step = 'email';
        passwordStep.hidden = true;
        emailStep.hidden = false;
        subtitle.textContent = 'Continue to Gritty-ify';
        btnLabel.textContent = 'Continue with email';
        setError(password, passwordError, '');
        email.focus();
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (step === 'email') {
            const value = email.value.trim();
            if (!value) return setError(email, emailError, 'Enter an email address');
            if (!EMAIL_RE.test(value)) return setError(email, emailError, 'Enter a valid email address');
            setError(email, emailError, '');
            loading(true);
            setTimeout(function () { loading(false); goToPassword(); }, 600);
            return;
        }

        if (password.value.length < 6) {
            return setError(password, passwordError, 'Password must be at least 6 characters');
        }
        setError(password, passwordError, '');
        loading(true);
        // Replace this timeout with a real call to your auth backend.
        setTimeout(function () {
            loading(false);
            success.hidden = false;
        }, 900);
    });

    email.addEventListener('input', function () { setError(email, emailError, ''); });
    password.addEventListener('input', function () { setError(password, passwordError, ''); });
    document.getElementById('edit-email').addEventListener('click', goToEmail);

    const toggle = document.getElementById('toggle-pw');
    toggle.addEventListener('click', function () {
        const show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
    });
})();
