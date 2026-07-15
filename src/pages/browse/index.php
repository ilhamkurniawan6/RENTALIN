<?php
require_once __DIR__ . '/../../services/session-init.php';
$isLoggedIn = isset($_SESSION['auth_user']);
$currentRole = $_SESSION['auth_user']['current_role'] ?? 'penyewa';
$footerActionHref = $isLoggedIn
  ? ($currentRole === 'penyewakan' ? '../add-item/index.php' : '../dashboard/index.php')
  : '../register/index.php';
$footerActionLabel = $isLoggedIn
  ? ($currentRole === 'penyewakan' ? 'Tambah Barang' : 'Dashboard Saya')
  : 'Daftar Gratis';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Jelajahi Barang</title>
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

        <nav class="nav-links" id="navLinks" role="navigation" aria-label="Main navigation">
        <a href="../home/index.php">Beranda</a>
        <a href="./index.php" class="active">Jelajahi Barang</a>
        <a href="../dashboard/index.php">Dashboard</a>
        <a id="navLoginLink" href="<?php echo $isLoggedIn ? '#' : '../login/index.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Login'; ?></a>
        <a id="navRegisterLink" href="../register/index.php" class="btn btn-sm btn-primary<?php echo $isLoggedIn ? ' hidden' : ''; ?>">Daftar</a>
        </nav>
    </div>
    </header>

    <main class="page">
    <section class="container header-block">
        <h1>Jelajahi Barang Sewa</h1>
        <p>Temukan peralatan yang tersedia dari mahasiswa di kampus</p>
    </section>

    <section class="container toolbar">
      <div class="search-wrap">
      <svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-search"></use></svg>
      <input id="searchInput" type="text" placeholder="Cari barang..." />
      </div>
      <button id="filterToggle" class="btn btn-outline"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg>Filter</button>
    </section>

    <section class="container content-layout">
        <aside class="filters" id="filtersPanel">
        <div class="panel">
            <h2>Filter</h2>

            <div class="filter-group">
            <h3>Kategori</h3>
            <div id="categoryList" class="checkbox-list"></div>
            </div>

            <div class="filter-group">
            <h3>Harga per Hari</h3>
            <div class="range-pair">
                <label for="minPrice">Minimum</label>
                <input id="minPrice" type="range" min="0" max="500000" step="50000" value="0" />
                <span id="minPriceLabel">Rp0</span>
            </div>
            <div class="range-pair">
                <label for="maxPrice">Maksimum</label>
                <input id="maxPrice" type="range" min="0" max="500000" step="50000" value="500000" />
                <span id="maxPriceLabel">Rp500.000</span>
            </div>
            </div>

            <button id="resetButton" class="btn btn-outline full">Hapus Filter</button>
        </div>
        </aside>

        <div class="items-area">
        <p id="resultCount" class="result-count">0 barang ditemukan</p>
        <div id="itemsGrid" class="items-grid"></div>
        <div id="emptyState" class="empty hidden">Tidak ada barang yang sesuai dengan kriteria pencarian</div>
        </div>
    </section>
    </main>

    <footer class="footer">
    <div class="container footer-grid">
        <section>
        <div class="brand">
            <span class="brand-mark">R</span>
            <span class="brand-text">Rentalin</span>
        </div>
        <p>Platform marketplace untuk menyewakan barang antar mahasiswa.</p>
        </section>

        <section>
        <h4>Tautan Cepat</h4>
        <a href="../home/index.php">Beranda</a>
        <a href="./index.php">Jelajahi Barang</a>
        <a href="../dashboard/index.php">Dashboard</a>
        <a href="<?php echo htmlspecialchars($footerActionHref); ?>"><?php echo htmlspecialchars($footerActionLabel); ?></a>
        </section>

        <section>
        <h4>Bantuan</h4>
        <a href="#">Pusat Bantuan</a>
        <a href="#">Panduan Keamanan</a>
        <a href="#">Syarat Layanan</a>
        <a href="#">Kebijakan Privasi</a>
        </section>

        <section>
        <h4>Terhubung Dengan Kami</h4>
        <div class="socials">
            <a href="#" aria-label="Facebook">f</a>
            <a href="#" aria-label="Twitter">x</a>
            <a href="#" aria-label="Instagram">i</a>
            <a href="#" aria-label="LinkedIn">in</a>
        </div>
        </section>
    </div>
    <div class="footer-bottom">&copy; 2026 Rentalin. All rights reserved.</div>
    </footer>

    <script src="../../mock/data.js"></script>
    <script src="../../store/storage.js"></script>
    <script src="../../services/api.js"></script>
    <script>
        let categories = [];
        let rentalItems = [];

        const state = {
          searchQuery: "",
          selectedCategories: new Set(),
          minPrice: 0,
          maxPrice: 500000,
        };

        const categoryList = document.getElementById("categoryList");
        const itemsGrid = document.getElementById("itemsGrid");
        const emptyState = document.getElementById("emptyState");
        const resultCount = document.getElementById("resultCount");
        const searchInput = document.getElementById("searchInput");
        const minPrice = document.getElementById("minPrice");
        const maxPrice = document.getElementById("maxPrice");
        const minPriceLabel = document.getElementById("minPriceLabel");
        const maxPriceLabel = document.getElementById("maxPriceLabel");
        const resetButton = document.getElementById("resetButton");
        const filterToggle = document.getElementById("filterToggle");
        const filtersPanel = document.getElementById("filtersPanel");
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");
        const navLoginLink = document.getElementById("navLoginLink");
        const navRegisterLink = document.getElementById("navRegisterLink");

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

        function renderCategoryFilters() {
          if (!categories.length) {
            categoryList.innerHTML = "<p>Kategori belum tersedia.</p>";
            return;
          }

          categoryList.innerHTML = categories
            .map(
              (category) => `
              <label class="checkbox-item">
                <input type="checkbox" value="${category.name}" />
                <span>${category.name} (${category.count})</span>
              </label>
            `,
            )
            .join("");

          categoryList.querySelectorAll("input[type='checkbox']").forEach((checkbox) => {
            checkbox.addEventListener("change", (event) => {
              const categoryName = event.target.value;
              if (event.target.checked) {
                state.selectedCategories.add(categoryName);
              } else {
                state.selectedCategories.delete(categoryName);
              }
              renderItems();
            });
          });
        }

        function itemMatchesFilter(item) {
          const matchesSearch = item.name
            .toLowerCase()
            .includes(state.searchQuery.toLowerCase().trim());

          const matchesCategory =
            state.selectedCategories.size === 0 ||
            state.selectedCategories.has(item.category);

          const matchesPrice =
            item.pricePerDay >= state.minPrice && item.pricePerDay <= state.maxPrice;

          return matchesSearch && matchesCategory && matchesPrice;
        }

        function renderItems() {
          const filteredItems = rentalItems.filter(itemMatchesFilter);

          resultCount.textContent = `${filteredItems.length} barang ditemukan`;

          if (!filteredItems.length) {
            itemsGrid.innerHTML = "";
            emptyState.classList.remove("hidden");
            return;
          }

          emptyState.classList.add("hidden");

          itemsGrid.innerHTML = filteredItems
            .map(
              (item) => `
                    <article class="item-card">
                <a href="../item-detail/index.php?id=${item.id}">
                  <img class="item-image" src="${item.image}" alt="${item.name}" loading="lazy" />
                </a>
                <div class="item-body">
                  <div class="item-head">
                    <a href="../item-detail/index.php?id=${item.id}"><h3 class="item-title">${item.name}</h3></a>
                    ${item.availability ? "" : '<span class="badge">Disewakan</span>'}
                  </div>
                  <p class="item-location">${item.location}</p>
                  <div class="item-foot">
                    <div class="price">${formatRupiah(item.pricePerDay)} <small>/hari</small></div>
                    <a href="../item-detail/index.php?id=${item.id}" class="btn btn-sm btn-primary">
                      ${item.availability ? "Sewa" : "Disewakan"}
                    </a>
                  </div>
                </div>
              </article>
            `,
            )
            .join("");
        }

        function syncPriceLabels() {
          minPriceLabel.textContent = formatRupiah(state.minPrice);
          maxPriceLabel.textContent = formatRupiah(state.maxPrice);
        }

        searchInput.addEventListener("input", (event) => {
          state.searchQuery = event.target.value;
          renderItems();
        });

        minPrice.addEventListener("input", (event) => {
          const value = Number(event.target.value);
          state.minPrice = Math.min(value, state.maxPrice);
          minPrice.value = String(state.minPrice);
          syncPriceLabels();
          renderItems();
        });

        maxPrice.addEventListener("input", (event) => {
          const value = Number(event.target.value);
          state.maxPrice = Math.max(value, state.minPrice);
          maxPrice.value = String(state.maxPrice);
          syncPriceLabels();
          renderItems();
        });

        resetButton.addEventListener("click", () => {
          state.searchQuery = "";
          state.selectedCategories.clear();
          state.minPrice = 0;
          state.maxPrice = 500000;

          searchInput.value = "";
          minPrice.value = "0";
          maxPrice.value = "500000";

          categoryList.querySelectorAll("input[type='checkbox']").forEach((checkbox) => {
            checkbox.checked = false;
          });

          syncPriceLabels();
          renderItems();
        });

        filterToggle.addEventListener("click", () => {
          filtersPanel.classList.toggle("hidden-mobile");
        });

        menuToggle.addEventListener("click", () => {
          const open = navLinks.classList.toggle('open');
          menuToggle.classList.toggle('open', open);
          menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Close mobile nav when clicking outside
        document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
                if (navLinks.classList.contains('open')) {
                    navLinks.classList.remove('open');
                    menuToggle.classList.remove('open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });

        filtersPanel.classList.add("hidden-mobile");

        async function initializePage() {
          setupAuthNavigation();

          try {
            const [nextCategories, nextItems] = await Promise.all([
              window.RENTAL_API.getCategories(),
              window.RENTAL_API.getItems(),
            ]);

            categories = nextCategories;
            rentalItems = nextItems;
          } catch (error) {
            itemsGrid.innerHTML = "";
            emptyState.textContent = "Gagal memuat data barang. Coba refresh halaman.";
            emptyState.classList.remove("hidden");
            resultCount.textContent = "0 barang ditemukan";
            return;
          }

          renderCategoryFilters();
          syncPriceLabels();
          renderItems();
        }

        initializePage();
    </script>
</body>
</html>
