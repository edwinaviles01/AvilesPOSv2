document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('togglePassword');
    const passInput = document.getElementById('password');
    const form = document.getElementById('formLogin');
    const btn = document.getElementById('btnLogin');
    const capsWarning = document.getElementById('capsWarning');

    if (toggle && passInput) {
        toggle.addEventListener('click', function () {
            const visible = passInput.type === 'text';
            passInput.type = visible ? 'password' : 'text';
            toggle.classList.toggle('visible', !visible);
            toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    }
    function checkCapsLock(event) {
        if (!capsWarning) return;
        if (event.getModifierState && event.getModifierState('CapsLock')) {
            capsWarning.classList.add('visible');
        } else {
            capsWarning.classList.remove('visible');
        }
    }

    if (passInput && capsWarning) {
        passInput.addEventListener('keydown', checkCapsLock);
        passInput.addEventListener('keyup', checkCapsLock);
        passInput.addEventListener('blur', function () {
            capsWarning.classList.remove('visible');
        });
    }

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = 'Iniciando sesión...';
        });
    }
});