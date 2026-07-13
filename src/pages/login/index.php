<?php
require_once __DIR__ . '/../../services/session-init.php';

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Check if user already logged in
if (isset($_SESSION['auth_user'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Check if coming from registration
$registered = isset($_GET['registered']);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Login</title>
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
          <h1>Selamat Datang Kembali</h1>
          <p>Masuk ke akunmu</p>
        </header>

        <?php if ($registered): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #c3e6cb;">
          Registrasi berhasil! Silakan login dengan akun barumu.
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['login_error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #f5c6cb;">
          <?php echo htmlspecialchars($_SESSION['login_error']); ?>
        </div>
        <?php unset($_SESSION['login_error']); endif; ?>

        <form id="loginForm" class="auth-form" method="POST" action="./app.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
          <label for="email">Alamat Email</label>
          <input id="email" name="email" type="email" placeholder="kamu@university.edu" required />

          <label for="password">Kata Sandi</label>
          <input id="password" name="password" type="password" placeholder="••••••••" required minlength="6" />

          <div class="row-between">
            <label class="checkbox-row">
              <input type="checkbox" id="remember" name="remember" />
              <span>Ingat saya</span>
            </label>
            <a href="#">Lupa kata sandi?</a>
          </div>

          <p id="formError" class="form-error hidden"></p>

          <button class="btn btn-primary" type="submit"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg>Masuk</button>
        </form>

        <p class="auth-foot">
          Belum punya akun?
          <a href="../register/index.php">Daftar</a>
        </p>
      </section>
    </main>

    <script>
      const loginForm = document.getElementById("loginForm");
      const formError = document.getElementById("formError");
      const submitButton = loginForm.querySelector("button[type='submit']");
      const emailInput = document.getElementById("email");
      const rememberInput = document.getElementById("remember");

      const REMEMBER_EMAIL_KEY = "rentalin_remember_email";

      const rememberedEmail = window.localStorage.getItem(REMEMBER_EMAIL_KEY) || "";
      if (rememberedEmail) {
        emailInput.value = rememberedEmail;
        rememberInput.checked = true;
      }

      function showError(message) {
        formError.textContent = message;
        formError.classList.remove("hidden");
      }

      function clearError() {
        formError.textContent = "";
        formError.classList.add("hidden");
      }

      function clearFieldErrors() {
        const prev = loginForm.querySelectorAll('.field-error-msg');
        prev.forEach((el) => el.remove());
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(i => i.classList.remove('input-error'));
      }

      function showFieldError(fieldEl, message) {
        fieldEl.classList.add('input-error');
        const msg = document.createElement('div');
        msg.className = 'field-error-msg';
        msg.textContent = message;
        fieldEl.insertAdjacentElement('afterend', msg);
      }

      function setSubmitting(isSubmitting) {
        submitButton.disabled = isSubmitting;
        submitButton.textContent = isSubmitting ? "Memproses..." : "Masuk";
      }

      loginForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        const formData = new FormData(loginForm);
        const email = String(formData.get("email") || "").trim();
        const password = String(formData.get("password") || "").trim();

        clearFieldErrors();

        const errors = [];
        if (!email) errors.push({ field: 'email', msg: 'Email wajib diisi.' });
        else if (!email.includes('@')) errors.push({ field: 'email', msg: 'Format email tidak valid.' });
        if (!password) errors.push({ field: 'password', msg: 'Kata sandi wajib diisi.' });
        else if (password.length < 6) errors.push({ field: 'password', msg: 'Kata sandi minimal 6 karakter.' });

        if (errors.length) {
          errors.forEach(e => {
            const el = document.getElementById(e.field);
            if (el) showFieldError(el, e.msg);
          });
          const first = document.getElementById(errors[0].field);
          if (first) first.focus();
          showError(errors[0].msg);
          return;
        }

        const rememberChecked = Boolean(formData.get("remember"));
        if (rememberChecked) {
          window.localStorage.setItem(REMEMBER_EMAIL_KEY, email);
        } else {
          window.localStorage.removeItem(REMEMBER_EMAIL_KEY);
        }

        setSubmitting(true);

        try {
          const response = await fetch("./app.php", {
            method: "POST",
            body: formData,
            credentials: 'same-origin'
          });

          const text = await response.text();
          let data;
          try {
            data = JSON.parse(text);
          } catch (err) {
            throw new Error('Unexpected server response: ' + text);
          }

          if (!data || data.success !== true) {
            showError(data && data.message ? data.message : 'Login gagal.');
            setSubmitting(false);
            return;
          }

          // Redirect to provided location (server may include redirect)
          window.location.href = data.redirect || '../dashboard/index.php';
        } catch (err) {
          showError(err.message || 'Terjadi kesalahan jaringan.');
          setSubmitting(false);
        }
      });
    </script>
  </body>
</html>
