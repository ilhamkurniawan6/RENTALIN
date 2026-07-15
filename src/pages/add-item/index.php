<?php
require_once __DIR__ . '/../../services/session-init.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login/index.php');
    exit;
}

$user = $_SESSION['auth_user'];
$currentRole = $user['current_role'] ?? 'penyewa';
$roleLabel = $currentRole === 'penyewakan' ? 'Penyewakan' : 'Penyewa';
$roleToneClass = $currentRole === 'penyewakan' ? 'is-active' : 'is-warning';
$roleGateMessage = $currentRole === 'penyewakan'
  ? 'Mode sudah aktif. Kamu bisa langsung mempublikasikan barang.'
  : 'Kamu sedang di mode Penyewa. Ubah ke mode Penyewakan dari dashboard sebelum mempublikasikan barang.';
$isLoggedIn = true;
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Tambah Barang</title>
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
    <header class="navbar">
      <div class="container nav-wrap">
        <a class="brand" href="../home/index.php">
          <span class="brand-mark">R</span>
          <span class="brand-text">Rentalin</span>
        </a>
        <nav class="nav-links">
          <a href="../home/index.php">Beranda</a>
          <a href="../browse/index.php">Jelajahi Barang</a>
          <a href="../dashboard/index.php" class="active">Dashboard</a>
          <a href="#" id="navLoginLink">Logout</a>
        </nav>
        <span class="mode-pill <?php echo $roleToneClass; ?>">
          Mode: <strong><?php echo htmlspecialchars($roleLabel); ?></strong>
        </span>
      </div>
    </header>

    <main class="page">
      <div class="container wrap">
        <a class="back-link" href="../dashboard/index.php">&larr; Kembali ke Dashboard</a>

        <header class="hero">
          <h1>Tambah Barang Baru</h1>
          <p>Daftarkan barangmu untuk disewakan dan mulai menghasilkan.</p>
        </header>

        <section class="card form-card">
          <div class="role-gate <?php echo $roleToneClass; ?>">
            <div>
              <h2>Status Mode</h2>
              <p><?php echo htmlspecialchars($roleGateMessage); ?></p>
            </div>
            <a class="btn btn-outline" href="../dashboard/index.php">Buka Dashboard</a>
          </div>

          <form id="addItemForm" class="form" method="POST" action="./app.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />

            <?php if (!empty($_SESSION['add_item_error'])): ?>
            <div class="form-alert is-error"><?php echo htmlspecialchars($_SESSION['add_item_error']); ?></div>
            <?php unset($_SESSION['add_item_error']); endif; ?>

            <?php if (!empty($_SESSION['add_item_success'])): ?>
            <div class="form-alert is-success"><?php echo htmlspecialchars($_SESSION['add_item_success']); ?></div>
            <?php unset($_SESSION['add_item_success']); endif; ?>
            <label for="name">Nama Barang *</label>
            <input id="name" name="name" type="text" placeholder="contoh: Kamera Sony A7 III" required />

            <label for="category">Kategori *</label>
            <select id="category" name="category" required>
              <option value="">Pilih kategori</option>
            </select>

            <label for="pricePerDay">Harga per Hari (Rp) *</label>
            <input id="pricePerDay" name="pricePerDay" type="number" min="1000" step="1000" placeholder="contoh: 250000" required />

            <label for="location">Lokasi Pengambilan *</label>
            <input id="location" name="location" type="text" placeholder="contoh: Kampus Utama" required />

            <label for="description">Deskripsi *</label>
            <textarea id="description" name="description" rows="5" placeholder="Deskripsikan barangmu..." required></textarea>

            <label for="imageFile">Foto Barang *</label>
            <div class="upload-box">
              <input id="imageFile" name="imageFile" type="file" accept="image/*" required />
              <p>Klik untuk upload (PNG, JPG, GIF sampai 10MB)</p>
            </div>

            <img id="previewImage" class="preview hidden" alt="Preview foto barang" />

            <div class="guideline">
              <h3>Panduan Penyewaan</h3>
              <ul>
                <li>Jujur tentang kondisi barang</li>
                <li>Tetapkan harga yang wajar sesuai pasaran</li>
                <li>Tanggapi permintaan sewa dengan cepat</li>
                <li>Temui penyewa di lokasi kampus yang aman</li>
              </ul>
            </div>

            <p id="formError" class="form-error hidden"></p>

            <div class="actions">
              <button type="submit" class="btn btn-primary"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-upload"></use></svg>Publikasikan Barang</button>
              <a class="btn btn-outline" href="../dashboard/index.php"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg>Batal</a>
            </div>
          </form>
        </section>
      </div>
    </main>

    <script src="../../mock/data.js"></script>
    <script src="../../store/storage.js"></script>
    <script src="../../services/api.js"></script>
    <script>
        const navLoginLink = document.getElementById("navLoginLink");
        let categories = [];

        if (navLoginLink) {
          navLoginLink.addEventListener("click", async (event) => {
            event.preventDefault();
            await window.RENTAL_API.logout();
            window.location.href = "../home/index.php";
          });
        }

        const addItemForm = document.getElementById("addItemForm");
        const categorySelect = document.getElementById("category");
        const imageInput = document.getElementById("imageFile");
        const previewImage = document.getElementById("previewImage");
        const formError = document.getElementById("formError");

        function showError(message) {
          formError.textContent = message;
          formError.classList.remove("hidden");
        }

        function clearError() {
          formError.textContent = "";
          formError.classList.add("hidden");
        }

        function clearFieldErrors() {
          const prev = addItemForm.querySelectorAll('.field-error-msg');
          prev.forEach((el) => el.remove());
          const inputs = addItemForm.querySelectorAll('input, textarea, select');
          inputs.forEach(i => i.classList.remove('input-error'));
        }

        function showFieldError(fieldEl, message) {
          fieldEl.classList.add('input-error');
          const msg = document.createElement('div');
          msg.className = 'field-error-msg';
          msg.textContent = message;
          fieldEl.insertAdjacentElement('afterend', msg);
        }

        function renderCategories() {
          if (!categories.length) {
            return;
          }

          categorySelect.innerHTML = '<option value="">Pilih kategori</option>' + categories
            .map((category) => {
              const categoryLabel = category.name === 'Camera' ? 'Kamera' : category.name;
              return `<option value="${category.name}">${categoryLabel}</option>`;
            })
            .join("");
        }

        imageInput.addEventListener("change", () => {
          const file = imageInput.files && imageInput.files[0];
          if (!file) {
            previewImage.classList.add("hidden");
            previewImage.removeAttribute("src");
            return;
          }

          if (previewImage.dataset.objectUrl) {
            URL.revokeObjectURL(previewImage.dataset.objectUrl);
          }

          const imageUrl = URL.createObjectURL(file);
          previewImage.dataset.objectUrl = imageUrl;
          previewImage.src = imageUrl;
          previewImage.classList.remove("hidden");
        });

        addItemForm.addEventListener("submit", (event) => {
          clearError();

          const formData = new FormData(addItemForm);
          const name = String(formData.get("name") || "").trim();
          const category = String(formData.get("category") || "").trim();
          const pricePerDay = Number(formData.get("pricePerDay"));
          const location = String(formData.get("location") || "").trim();
          const description = String(formData.get("description") || "").trim();
          const file = imageInput.files && imageInput.files[0];

          clearFieldErrors();

          const errors = [];
          if (!name) errors.push({ field: 'name', msg: 'Nama barang wajib diisi.' });
          if (!category) errors.push({ field: 'category', msg: 'Pilih kategori.' });
          if (!location) errors.push({ field: 'location', msg: 'Lokasi pengambilan wajib diisi.' });
          if (!description) errors.push({ field: 'description', msg: 'Deskripsi wajib diisi.' });
          if (!file) errors.push({ field: 'imageFile', msg: 'Foto barang wajib diunggah.' });
          if (!Number.isFinite(pricePerDay) || pricePerDay < 1000) errors.push({ field: 'pricePerDay', msg: 'Harga per hari minimal Rp1.000.' });
          if (file && file.size > 10 * 1024 * 1024) errors.push({ field: 'imageFile', msg: 'Ukuran file terlalu besar (maksimal 10MB).' });

          if (errors.length) {
            event.preventDefault();
            // show per-field errors
            errors.forEach(e => {
              const el = document.getElementById(e.field);
              if (el) showFieldError(el, e.msg);
            });
            const first = document.getElementById(errors[0].field);
            if (first) {
              first.focus();
            }
            // If multiple missing fields, show generic message to match tests
            if (errors.length > 1) {
              showError('Semua field wajib diisi, termasuk foto barang.');
            } else {
              showError(errors[0].msg);
            }
            return;
          }
        });

        async function initializePage() {
          if (window.RENTAL_DATA && Array.isArray(window.RENTAL_DATA.categories)) {
            categories = window.RENTAL_DATA.categories;
            renderCategories();
          }

          try {
            const nextCategories = await window.RENTAL_API.getCategories();
            if (Array.isArray(nextCategories) && nextCategories.length) {
              categories = nextCategories;
              renderCategories();
            }
          } catch (error) {
            if (!categories.length) {
              showError("Gagal memuat kategori. Coba refresh halaman.");
            }
          }
        }

        initializePage();
    </script>
  </body>
</html>
