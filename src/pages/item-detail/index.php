<?php
require_once __DIR__ . '/../../services/session-init.php';
$isLoggedIn = isset($_SESSION['auth_user']);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Detail Barang</title>
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
        <button class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false">
          <svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-menu"></use></svg>
        </button>

        <nav class="nav-links" role="navigation" aria-label="Main navigation">
          <a href="../home/index.php">Beranda</a>
          <a href="../browse/index.php" class="active">Jelajahi Barang</a>
          <a href="../dashboard/index.php">Dashboard</a>
          <a id="navLoginLink" href="<?php echo $isLoggedIn ? '#' : '../login/index.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Login'; ?></a>
          <a id="navRegisterLink" href="../register/index.php"<?php echo $isLoggedIn ? ' class="hidden"' : ''; ?>>Daftar</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="container">
        <a class="back-link" href="../browse/index.php">&larr; Kembali ke Jelajah</a>

        <section id="notFound" class="not-found hidden">
          <h1>Barang Tidak Ditemukan</h1>
          <p>Item yang kamu cari tidak tersedia atau ID tidak valid.</p>
          <a class="btn btn-primary" href="../browse/index.php">Jelajahi Barang</a>
        </section>

        <section id="detailLayout" class="layout hidden">
          <div class="left-col">
            <article class="card image-card">
                <img id="itemImage" alt="Gambar barang" />
            </article>

            <article class="card details-card">
              <div class="headline">
                <div>
                  <span id="itemCategory" class="badge"></span>
                  <h1 id="itemName"></h1>
                  <p id="itemLocation" class="muted"></p>
                </div>
                <div class="price-wrap">
                  <div id="itemPrice" class="big-price"></div>
                  <small>per hari</small>
                </div>
              </div>

              <section class="block">
                <h2>Deskripsi</h2>
                <p id="itemDescription"></p>
              </section>

              <section class="block">
                <h2>Fitur</h2>
                <ul class="feature-grid">
                  <li>Shield Pemilik Terverifikasi</li>
                  <li>Shield Perlindungan Kerusakan</li>
                  <li>Shield Pengambilan di Kampus</li>
                  <li>Shield Pembayaran Aman</li>
                </ul>
              </section>
            </article>

            <article class="card owner-card">
              <h2>Informasi Pemilik</h2>
              <div class="owner-wrap">
                <img id="ownerAvatar" alt="Avatar pemilik" class="owner-avatar" />
                <div>
                  <h3 id="ownerName"></h3>
                  <p id="ownerRating" class="muted"></p>
                </div>
                <button class="btn btn-outline" type="button"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg>Hubungi</button>
              </div>
            </article>
          </div>

          <aside class="right-col">
            <article class="card booking-card">
              <h2>Sewa Barang Ini</h2>
              <div id="availabilityNotice" class="notice hidden"></div>

              <div class="field">
                <label for="startDate">Tanggal Mulai</label>
                <input id="startDate" type="date" />
              </div>

              <div class="field">
                <label for="endDate">Tanggal Selesai</label>
                <input id="endDate" type="date" />
              </div>

              <div id="priceBreakdown" class="summary hidden"></div>

              <button id="rentButton" class="btn btn-primary full" type="button"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-upload"></use></svg>Ajukan Penyewaan</button>
              <p class="muted tiny">Kamu belum akan dikenakan biaya</p>
            </article>
          </aside>
        </section>
      </div>
    </main>

    <script src="../../mock/data.js"></script>
    <script src="../../store/storage.js"></script>
    <script src="../../services/api.js"></script>
    <script>
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.querySelector('.nav-links');
        if (menuToggle && navLinks) {
          menuToggle.addEventListener('click', () => {
            const open = navLinks.classList.toggle('open');
            menuToggle.classList.toggle('open', open);
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
          });

          document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
              if (navLinks.classList.contains('open')) {
                navLinks.classList.remove('open');
                menuToggle.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
              }
            }
          });
        }
        const notFound = document.getElementById("notFound");
        const detailLayout = document.getElementById("detailLayout");

        const itemImage = document.getElementById("itemImage");
        const itemCategory = document.getElementById("itemCategory");
        const itemName = document.getElementById("itemName");
        const itemLocation = document.getElementById("itemLocation");
        const itemPrice = document.getElementById("itemPrice");
        const itemDescription = document.getElementById("itemDescription");
        const ownerAvatar = document.getElementById("ownerAvatar");
        const ownerName = document.getElementById("ownerName");
        const ownerRating = document.getElementById("ownerRating");

        const availabilityNotice = document.getElementById("availabilityNotice");
        const startDate = document.getElementById("startDate");
        const endDate = document.getElementById("endDate");
        const priceBreakdown = document.getElementById("priceBreakdown");
        const rentButton = document.getElementById("rentButton");
        const navLoginLink = document.getElementById("navLoginLink");
        const navRegisterLink = document.getElementById("navRegisterLink");
        let currentItem = null;

        function formatRupiah(amount) {
          return `Rp${new Intl.NumberFormat("id-ID").format(amount)}`;
        }

        function setupAuthNavigation() {
          if (!navLoginLink || !<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {
            return;
          }

          navLoginLink.addEventListener("click", async (event) => {
            event.preventDefault();
            await window.RENTAL_API.logout();
            window.location.href = "../home/index.php";
          });
        }

        function getDaysCount(startValue, endValue) {
          if (!startValue || !endValue) {
            return 0;
          }

          const start = new Date(startValue);
          const end = new Date(endValue);

          if (end < start) {
            return 0;
          }

          const diffTime = end.getTime() - start.getTime();
          return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
        }

        function renderPriceSummary(item) {
          const daysCount = getDaysCount(startDate.value, endDate.value);

          if (!daysCount) {
            priceBreakdown.classList.add("hidden");
            rentButton.disabled = !item.availability;
            return;
          }

          const subtotal = daysCount * item.pricePerDay;
          const serviceFee = subtotal * 0.1;
          const total = subtotal + serviceFee;

          priceBreakdown.classList.remove("hidden");
          priceBreakdown.innerHTML = `
            <div class="summary-row">
              <span>${formatRupiah(item.pricePerDay)} x ${daysCount} hari</span>
              <span>${formatRupiah(subtotal)}</span>
            </div>
            <div class="summary-row">
              <span>Biaya Layanan</span>
              <span>${formatRupiah(serviceFee)}</span>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <span>${formatRupiah(total)}</span>
            </div>
          `;

          rentButton.disabled = !item.availability;
        }

        function setDefaultDateLimit() {
          const today = new Date();
          const dateString = today.toISOString().split("T")[0];
          startDate.min = dateString;
          endDate.min = dateString;
        }

        async function loadItemDetail() {
          const params = new URLSearchParams(window.location.search);
          const itemId = params.get("id");
          let item = null;

          try {
            item = await window.RENTAL_API.getItemById(itemId);
          } catch (error) {
            item = null;
          }

          if (!item) {
            notFound.classList.remove("hidden");
            detailLayout.classList.add("hidden");
            return;
          }

          currentItem = item;

          notFound.classList.add("hidden");
          detailLayout.classList.remove("hidden");

          itemImage.src = item.image;
          itemImage.alt = item.name;
          itemCategory.textContent = item.category;
          itemName.textContent = item.name;
          itemLocation.textContent = `📍 ${item.location}`;
          itemPrice.textContent = formatRupiah(item.pricePerDay);
          itemDescription.textContent = item.description;
          ownerAvatar.src = item.owner.avatar;
          ownerAvatar.alt = item.owner.name;
          ownerName.textContent = item.owner.name;
          ownerRating.textContent = `⭐ Rating ${item.owner.rating}`;

          if (!item.availability) {
            availabilityNotice.textContent = "Barang ini sedang disewakan";
            availabilityNotice.classList.remove("hidden");
            rentButton.textContent = "Tidak Tersedia";
            rentButton.disabled = true;
          } else {
            availabilityNotice.classList.add("hidden");
            rentButton.textContent = "Ajukan Penyewaan";
          }

          startDate.addEventListener("change", () => {
            if (endDate.value && endDate.value < startDate.value) {
              endDate.value = startDate.value;
            }
            endDate.min = startDate.value || endDate.min;
            renderPriceSummary(item);
          });

          endDate.addEventListener("change", () => {
            renderPriceSummary(item);
          });

          renderPriceSummary(item);
        }

        rentButton.addEventListener("click", async () => {
          if (!currentItem) {
            return;
          }

          if (!startDate.value || !endDate.value) {
            availabilityNotice.textContent = "Pilih tanggal mulai dan selesai terlebih dahulu.";
            availabilityNotice.classList.remove("hidden");
            return;
          }

          rentButton.disabled = true;
          rentButton.textContent = "Mengirim...";
          availabilityNotice.classList.add("hidden");

          try {
            const response = await fetch("../api/rentals.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                item_id: currentItem.id,
                start_date: startDate.value,
                end_date: endDate.value,
              }),
            });

            const data = await response.json();

            if (response.ok && data && data.success) {
              alert(data.message || "Booking berhasil dikirim.");
              rentButton.textContent = "Booking Terkirim";
              return;
            }

            throw new Error(data && data.message ? data.message : "Gagal mengirim booking.");
          } catch (error) {
            availabilityNotice.textContent = error.message || "Gagal mengirim booking.";
            availabilityNotice.classList.remove("hidden");
            rentButton.textContent = "Ajukan Penyewaan";
            rentButton.disabled = !currentItem.availability;
          }
        });

        setDefaultDateLimit();
        setupAuthNavigation();
        loadItemDetail();
    </script>
  </body>
</html>
