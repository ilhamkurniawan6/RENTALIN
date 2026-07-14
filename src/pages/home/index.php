<?php
require_once __DIR__ . '/../../services/session-init.php';
$isLoggedIn = isset($_SESSION['auth_user']);
$currentRole = $_SESSION['auth_user']['current_role'] ?? 'penyewa';
$heroSecondaryHref = $isLoggedIn
    ? ($currentRole === 'penyewakan' ? '../add-item/index.php' : '../dashboard/index.php')
    : '../register/index.php';
$heroSecondaryLabel = $isLoggedIn
    ? ($currentRole === 'penyewakan' ? 'Tambah Barang' : 'Buka Dashboard')
    : 'Sewakan Barang Anda';
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
    <title>Rentalin - Beranda</title>
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
    <div class="site-bg"></div>

    <header class="navbar">
    <div class="container nav-wrap">
        <a class="brand" href="./index.php">
        <span class="brand-mark">R</span>
        <span class="brand-text">Rentalin</span>
        </a>

                <button class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false">
                    <svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-menu"></use></svg>
                </button>

        <nav class="nav-links" id="navLinks" role="navigation" aria-label="Main navigation">
        <a href="./index.php" class="active">Beranda</a>
        <a href="../browse/index.php">Jelajahi Barang</a>
        <a href="../dashboard/index.php">Dashboard</a>
        <a id="navLoginLink" href="<?php echo $isLoggedIn ? '#' : '../login/index.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Login'; ?></a>
        <a id="navRegisterLink" href="../register/index.php" class="btn btn-sm btn-primary<?php echo $isLoggedIn ? ' hidden' : ''; ?>">Daftar</a>
        </nav>
    </div>
    </header>

    <main>
    <section class="hero">
        <div class="container hero-inner">
            <h1>
                Sewa Berbagai Barang Dari<br />
                <span>Sesama Mahasiswa</span>
            </h1>
            <p>
                Temukan kamera, laptop, dan peralatan lainnya tanpa harus membeli.
                Berbagi, menyewa, dan terhubung dengan komunitas kampus.
            </p>

            <div class="search-box">
                <span class="search-icon">&#128269;</span>
                <input id="searchInput" type="text" placeholder="Cari kamera, laptop, proyektor..." />
            </div>

            <div class="hero-actions">
                <a href="../browse/index.php" class="btn btn-primary"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-search"></use></svg>Jelajahi Semua Barang</a>
                <a href="<?php echo htmlspecialchars($heroSecondaryHref); ?>" class="btn btn-outline"><svg class="svg-icon" aria-hidden="true"><use href="../../assets/icons.svg#icon-plus"></use></svg><?php echo htmlspecialchars($heroSecondaryLabel); ?></a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
        <h2 class="section-title">Jelajahi Berdasarkan Kategori</h2>
        <div class="category-grid" id="categoryGrid"></div>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
        <div class="section-head">
            <h2 class="section-title left">Barang Pilihan</h2>
            <a href="../browse/index.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="items-grid" id="featuredGrid"></div>
        </div>
    </section>

    <section class="section">
        <div class="container">
        <h2 class="section-title">Cara Kerja</h2>
        <div class="steps-grid">
            <article class="step-card">
            <div class="step-num">1</div>
            <h3>Jelajahi Barang</h3>
            <p>Temukan peralatan yang kamu butuhkan dari mahasiswa lain di kampus</p>
            </article>
            <article class="step-card">
            <div class="step-num">2</div>
            <h3>Pesan &amp; Bayar</h3>
            <p>Pilih tanggal penyewaan dan selesaikan pembayaran dengan aman</p>
            </article>
            <article class="step-card">
            <div class="step-num">3</div>
            <h3>Ambil &amp; Gunakan</h3>
            <p>Temui pemilik di kampus dan nikmati masa penyewaanmu</p>
            </article>
        </div>
        </div>
    </section>

    <section class="cta">
        <div class="container cta-inner">
        <h2>Siap Untuk Mulai Menyewa?</h2>
        <p>Bergabung dengan ratusan mahasiswa yang sudah berbagi dan menghemat</p>
        <a href="../register/index.php" class="btn btn-light">Mulai Gratis</a>
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
        <a href="./index.php">Beranda</a>
        <a href="../browse/index.php">Jelajahi Barang</a>
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

        const categoryGrid = document.getElementById("categoryGrid");
        const featuredGrid = document.getElementById("featuredGrid");
        const searchInput = document.getElementById("searchInput");
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");
        const navLoginLink = document.getElementById("navLoginLink");
        const navRegisterLink = document.getElementById("navRegisterLink");

        const iconMap = {
            Camera: "📷",
            Laptop: "💻",
            BookOpen: "📚",
            Package: "📦",
        };

        function formatRupiah(amount) {
            return new Intl.NumberFormat("id-ID").format(amount);
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

        function renderCategories() {
            if (!categories.length) {
                categoryGrid.innerHTML = "<p>Kategori belum tersedia.</p>";
                return;
            }

            const markup = categories
                .map(
                    (category) => `
                    <a href="../browse/index.php" class="category-card">
                        <div class="category-icon">${iconMap[category.icon] || "🔹"}</div>
                        <h3>${category.name}</h3>
                        <p>${category.count} barang</p>
                    </a>
                `,
                )
                .join("");

            categoryGrid.innerHTML = markup;
        }

        function renderFeatured(filterText = "") {
            const normalizedFilter = filterText.trim().toLowerCase();
            const featuredItems = rentalItems.slice(0, 4);

            const filteredItems = featuredItems.filter((item) => {
                if (!normalizedFilter) {
                    return true;
                }

                return (
                    item.name.toLowerCase().includes(normalizedFilter) ||
                    item.location.toLowerCase().includes(normalizedFilter)
                );
            });

            if (!filteredItems.length) {
                featuredGrid.innerHTML = "<p>Tidak ada barang yang cocok dengan pencarian.</p>";
                return;
            }

            featuredGrid.innerHTML = filteredItems
                .map(
                    (item) => `
                    <article class="item-card">
                        <a href="../item-detail/index.php?id=${item.id}">
                            <img class="item-image" src="${item.image}" alt="${item.name}" loading="lazy" />
                        </a>
                        <div class="item-body">
                            <div class="item-row">
                                <a href="../item-detail/index.php?id=${item.id}"><h3 class="item-title">${item.name}</h3></a>
                                ${item.availability ? "" : '<span class="badge">Disewakan</span>'}
                            </div>
                            <p class="item-location">📍 ${item.location}</p>
                            <div class="item-footer">
                                <div class="price">Rp${formatRupiah(item.pricePerDay)} <small>/hari</small></div>
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

        searchInput.addEventListener("input", (event) => {
            renderFeatured(event.target.value);
        });

        menuToggle.addEventListener("click", () => {
            const open = navLinks.classList.toggle("open");
            menuToggle.classList.toggle("open", open);
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
                categoryGrid.innerHTML = "<p>Gagal memuat data kategori.</p>";
                featuredGrid.innerHTML = "<p>Gagal memuat data barang.</p>";
                return;
            }

            renderCategories();
            renderFeatured();
        }

        initializePage();
    </script>
</body>
</html>
