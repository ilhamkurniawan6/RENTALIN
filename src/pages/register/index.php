<?php
require_once __DIR__ . '/../../services/session-init.php';
// Ensure CSRF token exists
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../../styles/reset.css" />
    <link rel="stylesheet" href="../../styles/variables.css" />
    <link rel="stylesheet" href="../../styles/global.css" />
    <link rel="stylesheet" href="./styles.css" />
  </head>
  <body>
    <main class="auth-page">
      <section class="auth-panel">
        <a class="brand" href="../home/index.php">
          <span class="brand-mark">R</span>
          <span class="brand-text">Rentalin</span>
        </a>

        <header class="auth-header">
          <h1>Buat Akun</h1>
          <p>Bergabung dengan komunitas penyewaan mahasiswa</p>
        </header>

        <form id="registerForm" class="auth-form" method="POST" action="./app.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
          <label for="name">Nama Lengkap</label>
          <input id="name" name="name" type="text" placeholder="Budi Santoso" required />

          <label for="email">Email Universitas</label>
          <input id="email" name="email" type="email" placeholder="kamu@university.edu" required />

          <label for="phone">Nomor Telepon</label>
          <input id="phone" name="phone" type="tel" placeholder="08xx-xxxx-xxxx" required />

          <label for="password">Kata Sandi</label>
          <input id="password" name="password" type="password" placeholder="••••••••" required minlength="6" />

          <label for="confirmPassword">Konfirmasi Kata Sandi</label>
          <input id="confirmPassword" name="confirmPassword" type="password" placeholder="••••••••" required minlength="6" />

          <label class="checkbox-row">
            <input id="terms" type="checkbox" name="terms" required />
            <span>Saya setuju dengan Syarat Layanan dan Kebijakan Privasi</span>
          </label>

          <p id="formError" class="form-error hidden"></p>

          <button class="btn btn-primary" type="submit"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg>Buat Akun</button>
        </form>

          <?php if (!empty($_SESSION['register_error'])): ?>
          <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($_SESSION['register_error']); ?>
          </div>
          <?php unset($_SESSION['register_error']); endif; ?>

        <p class="auth-foot">
          Sudah punya akun?
          <a href="../login/index.php">Masuk</a>
        </p>
      </section>
    </main>

    <script>
      // Client-side validation
      const registerForm = document.getElementById("registerForm");
      const formError = document.getElementById("formError");
      const submitButton = registerForm.querySelector("button[type='submit']");

      function clearFieldErrors() {
        const prev = registerForm.querySelectorAll('.field-error-msg');
        prev.forEach((el) => el.remove());
        const inputs = registerForm.querySelectorAll('input');
        inputs.forEach(i => i.classList.remove('input-error'));
      }

      function showFieldError(fieldEl, message) {
        fieldEl.classList.add('input-error');
        const msg = document.createElement('div');
        msg.className = 'field-error-msg';
        msg.textContent = message;
        fieldEl.insertAdjacentElement('afterend', msg);
      }

      function showError(message) {
        formError.textContent = message;
        formError.classList.remove("hidden");
      }

      function clearError() {
        formError.textContent = "";
        formError.classList.add("hidden");
      }

      function setSubmitting(isSubmitting) {
        submitButton.disabled = isSubmitting;
        submitButton.textContent = isSubmitting ? "Memproses..." : "Buat Akun";
      }

      registerForm.addEventListener("submit", (event) => {
        clearError();

        const formData = new FormData(registerForm);
        const name = String(formData.get("name") || "").trim();
        const email = String(formData.get("email") || "").trim();
        const phone = String(formData.get("phone") || "").trim();
        const password = String(formData.get("password") || "").trim();
        const confirmPassword = String(formData.get("confirmPassword") || "").trim();
        const termsChecked = document.getElementById("terms").checked;

        clearFieldErrors();

        const errors = [];
        if (!name) errors.push({ field: 'name', msg: 'Nama lengkap wajib diisi.' });
        if (!email) errors.push({ field: 'email', msg: 'Email wajib diisi.' });
        else if (!email.includes('@')) errors.push({ field: 'email', msg: 'Format email tidak valid.' });
        if (!phone) errors.push({ field: 'phone', msg: 'Nomor telepon wajib diisi.' });
        if (password.length < 6) errors.push({ field: 'password', msg: 'Kata sandi minimal 6 karakter.' });
        if (password !== confirmPassword) errors.push({ field: 'confirmPassword', msg: 'Kata sandi tidak cocok.' });
        if (!termsChecked) errors.push({ field: 'terms', msg: 'Kamu harus menyetujui syarat layanan.' });

        if (errors.length) {
          event.preventDefault();
          errors.forEach(e => {
            const el = document.getElementById(e.field);
            if (el) showFieldError(el, e.msg);
          });
          const first = document.getElementById(errors[0].field);
          if (first) first.focus();
          showError(errors[0].msg);
          return;
        }

        setSubmitting(true);
      });
    </script>
  </body>
</html>
