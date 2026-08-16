/* SmartCampus - login & registration client-side helpers */

document.addEventListener('DOMContentLoaded', () => {
  // Show / hide password toggles
  const toggleButtons = document.querySelectorAll('[data-toggle-pass]');
  toggleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePass);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = `<i class="bi bi-${show ? 'eye-slash' : 'eye'}"></i>`;
    });
  });

  // Single toggle button (login page)
  const togglePassword = document.getElementById('togglePassword');
  if (togglePassword) {
    togglePassword.addEventListener('click', () => {
      const input = document.getElementById('password');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      togglePassword.innerHTML = `<i class="bi bi-${show ? 'eye-slash' : 'eye'}"></i>`;
    });
  }

  // One-click demo login: fill the form with a role's credentials
  document.querySelectorAll('[data-demo-login]').forEach(btn => {
    btn.addEventListener('click', () => {
      const email = document.getElementById('email');
      const pass = document.getElementById('password');
      if (email) email.value = btn.dataset.demoLogin;
      if (pass) pass.value = 'Password123!';
      if (email) {
        email.classList.remove('is-invalid');
        email.focus();
      }
      if (pass) pass.classList.remove('is-invalid');
    });
  });

  // Login validation
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', (ev) => {
      const email = document.getElementById('email');
      const pass = document.getElementById('password');
      let valid = true;
      [email, pass].forEach(f => f.classList.remove('is-invalid'));
      if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) { email.classList.add('is-invalid'); valid = false; }
      if (!pass.value) { pass.classList.add('is-invalid'); valid = false; }
      if (!valid) ev.preventDefault();
    });
  }

  // Register validation
  const regForm = document.getElementById('registerForm');
  if (regForm) {
    const passInput = document.getElementById('regPassword');
    const confirmInput = document.getElementById('regConfirm');
    confirmInput.addEventListener('input', () => {
      if (confirmInput.value && confirmInput.value !== passInput.value) {
        confirmInput.classList.add('is-invalid');
        confirmInput.setCustomValidity('Passwords do not match.');
      } else {
        confirmInput.classList.remove('is-invalid');
        confirmInput.setCustomValidity('');
      }
    });
  }
});
