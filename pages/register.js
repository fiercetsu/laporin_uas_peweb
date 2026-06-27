(function () {
    'use strict';

    var form = document.getElementById('registerForm');
    if (!form) {
        return;
    }

    var digitFields = ['nik', 'no_kk'];
    digitFields.forEach(function (id) {
        var field = document.getElementById(id);
        if (!field) {
            return;
        }

        field.addEventListener('input', function () {
            field.value = field.value.replace(/\D/g, '').slice(0, 16);
        });
    });

    var password = document.getElementById('password');
    var confirm = document.getElementById('password_confirm');
    var message = document.getElementById('passwordMatchMessage');

    function validatePasswordMatch() {
        if (!password || !confirm || !message) {
            return true;
        }

        if (confirm.value === '') {
            message.textContent = '';
            confirm.setCustomValidity('');
            return true;
        }

        if (password.value !== confirm.value) {
            message.textContent = 'Konfirmasi password tidak sama.';
            confirm.setCustomValidity('Konfirmasi password tidak sama.');
            return false;
        }

        message.textContent = '';
        confirm.setCustomValidity('');
        return true;
    }

    if (password && confirm) {
        password.addEventListener('input', validatePasswordMatch);
        confirm.addEventListener('input', validatePasswordMatch);
    }

    form.addEventListener('submit', function (event) {
        validatePasswordMatch();

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
})();
