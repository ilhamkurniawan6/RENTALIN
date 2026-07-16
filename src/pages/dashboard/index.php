<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/url-helper.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login/index.php');
    exit;
}

$user = $_SESSION['auth_user'];
$isLoggedIn = true;
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Dashboard</title>
    <script>
      try {
        localStorage.setItem('rentalin_csrf', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');
      } catch (error) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../../styles/reset.css" />
    <link rel="stylesheet" href="../../styles/variables.css" />
    <link rel="stylesheet" href="../../styles/global.css" />
    <link rel="stylesheet" href="./styles.css?v=<?php echo filemtime(__DIR__ . '/styles.css'); ?>" />
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
          <a href="../browse/index.php">Jelajahi Barang</a>
          <a href="./index.php" class="active">Dashboard</a>
          <a id="navLoginLink" href="#">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="container">
        <header class="hero">
          <h1>Dashboard Saya</h1>
          <p>Kelola penyewaan aktif, barang yang didaftarkan, dan profil akunmu.</p>
        </header>

        <?php if (!empty($_SESSION['dashboard_message'])): ?>
          <div class="flash-success"><?php echo htmlspecialchars($_SESSION['dashboard_message']); ?></div>
          <?php unset($_SESSION['dashboard_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['dashboard_error'])): ?>
          <div class="flash-error"><?php echo htmlspecialchars($_SESSION['dashboard_error']); ?></div>
          <?php unset($_SESSION['dashboard_error']); ?>
        <?php endif; ?>

        <section class="stats-grid" id="statsGrid"></section>

        <section class="dashboard-layout">
          <aside class="sidebar card">
            <div class="profile">
                <div class="avatar-wrap">
                    <img id="profileAvatar"
                    src="<?php echo htmlspecialchars(rentalin_normalize_avatar_url($user['avatar'] ?? '') ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop'); ?>"
                    alt="Avatar pengguna"
                  />
                </div>
                <div class="profile-actions">
                  <label id="avatarLabel" for="avatarInput" class="btn btn-sm btn-outline">Ubah Avatar</label>
                  <input id="avatarInput" type="file" name="avatar" accept="image/*" hidden />
                  <span id="avatarSpinner" class="avatar-spinner" aria-hidden="true" hidden></span>
                  <div id="avatarMsg" class="field-error-msg avatar-message" hidden></div>
                </div>
              <h2 id="profileName"><?php echo htmlspecialchars($user['name']); ?></h2>
              <p id="profileEmail"><?php echo htmlspecialchars($user['email']); ?></p>

              <div class="role-toggle-container">
                <p>Mode:</p>
                <div class="role-toggle-grid">
                  <button type="button" id="rolePenyewaBtn" class="btn btn-role-toggle" data-role="penyewa">🔍 Penyewa</button>
                  <button type="button" id="rolePenyewakanBtn" class="btn btn-role-toggle" data-role="penyewakan">📦 Penyewakan</button>
                </div>
              </div>
            </div>

            <div class="menu">
              <button type="button" class="tab-btn active" data-tab="rentals">Penyewaan Saya</button>
              <button type="button" class="tab-btn" data-tab="notifications" id="notificationsTabBtn">📢 Notifikasi<span id="notificationBadge" class="badge-count" hidden>0</span></button>
              <button type="button" class="tab-btn" data-tab="items" id="itemsTabBtn" hidden>Barang Saya</button>
              <button type="button" class="tab-btn" data-tab="settings">Pengaturan</button>
            </div>

            <a class="btn btn-primary full" href="../add-item/index.php" id="addItemBtn" hidden>Tambah Barang Baru</a>
            <?php if (($user['role'] ?? '') === 'admin' || ($user['role'] ?? '') === 'super_admin'): ?>
            <a class="btn btn-outline full" href="../admin-dashboard/index.php">Mode Admin</a>
            <?php endif; ?>
          </aside>

          <section class="main-content">
            <article id="rentalsPanel" class="card panel active">
              <h3>Penyewaan Saya</h3>
              <div id="rentalsList" class="list"></div>
            </article>

            <article id="notificationsPanel" class="card panel">
              <div class="panel-header">
                <h3>Notifikasi</h3>
                <button type="button" class="btn btn-sm btn-outline" id="markAllReadBtn" hidden>Tandai Semua Dibaca</button>
              </div>
              <div id="notificationsList" class="list"></div>
            </article>

            <article id="itemsPanel" class="card panel">
              <h3>Barang yang Didaftarkan</h3>
              <div id="itemsList" class="list"></div>
            </article>

            <article id="settingsPanel" class="card panel">
              <h3>Pengaturan Akun</h3>
              <form class="settings-form" id="settingsForm">
                <label for="fullName">Nama Lengkap</label>
                <input id="fullName" type="text" value="<?php echo htmlspecialchars($user['name']); ?>" />

                <label for="email">Email</label>
                <input id="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" />

                <label for="phone">Nomor Telepon</label>
                <input id="phone" type="tel" placeholder="08xx-xxxx-xxxx" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" />

                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
              </form>
            </article>
          </section>
        </section>
      </div>
    </main>

    <script src="../../mock/data.js"></script>
    <script src="../../store/storage.js"></script>
    <script src="../../services/api.js"></script>
    <script>
        let rentalItems = [];
        let currentRole = '<?php echo htmlspecialchars($user['current_role'] ?? 'penyewa'); ?>';
        let notifications = [];

        const statsGrid = document.getElementById("statsGrid");
        const rentalsList = document.getElementById("rentalsList");
        const notificationsList = document.getElementById("notificationsList");
        const itemsList = document.getElementById("itemsList");
        const profileName = document.getElementById("profileName");
        const profileEmail = document.getElementById("profileEmail");
        const navLoginLink = document.getElementById("navLoginLink");
        const menuToggle = document.getElementById("menuToggle");
        const navLinks = document.getElementById("navLinks");
        const tabButtons = document.querySelectorAll(".tab-btn");
        const rolePenyewaBtn = document.getElementById("rolePenyewaBtn");
        const rolePenyewakanBtn = document.getElementById("rolePenyewakanBtn");
        const addItemBtn = document.getElementById("addItemBtn");
        const itemsTabBtn = document.getElementById("itemsTabBtn");
        const notificationsTabBtn = document.getElementById("notificationsTabBtn");
        const markAllReadBtn = document.getElementById("markAllReadBtn");
        const notificationBadge = document.getElementById("notificationBadge");
        const panels = {
          rentals: document.getElementById("rentalsPanel"),
          notifications: document.getElementById("notificationsPanel"),
          items: document.getElementById("itemsPanel"),
          settings: document.getElementById("settingsPanel"),
        };

        function formatRupiah(amount) {
          return `Rp${new Intl.NumberFormat("id-ID").format(amount)}`;
        }

        function setupAuthNavigation() {
          navLoginLink.addEventListener("click", async (event) => {
            event.preventDefault();
            try {
              // Use centralized client API which will attach CSRF token
              if (window.RENTAL_API && typeof window.RENTAL_API.logout === 'function') {
                await window.RENTAL_API.logout();
              } else {
                // fallback: POST directly with token from localStorage
                const headers = {};
                try { const csrf = window.RENTAL_API ? window.RENTAL_API.getCsrfToken() : (localStorage.getItem('rentalin_csrf') || ''); if (csrf) headers['X-CSRF-Token'] = csrf; } catch (e) {}
                await fetch('../login/logout.php', { method: 'POST', credentials: 'same-origin', headers });
              }
            } catch (e) {
              // ignore
            }
            // redirect to home after logout attempt
            window.location.href = "../home/index.php";
          });
        }

        // Mobile menu toggle behavior with overlay (close on outside click / resize)
        if (menuToggle && navLinks) {
          const overlayId = 'mobileNavOverlay';

          function createOverlay() {
            let overlay = document.getElementById(overlayId);
            if (!overlay) {
              overlay = document.createElement('div');
              overlay.id = overlayId;
              overlay.className = 'mobile-nav-overlay';
              document.body.appendChild(overlay);
              overlay.addEventListener('click', closeMobileNav);
            }
            return overlay;
          }

          function openMobileNav() {
            navLinks.classList.add('open');
            menuToggle.classList.add('open');
            menuToggle.setAttribute('aria-expanded', 'true');
            const overlay = createOverlay();
            overlay.style.display = 'block';
            // prevent body scroll while menu open
            document.body.style.overflow = 'hidden';
          }

          function closeMobileNav() {
            navLinks.classList.remove('open');
            menuToggle.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
            const overlay = document.getElementById(overlayId);
            if (overlay) overlay.style.display = 'none';
            document.body.style.overflow = '';
          }

          menuToggle.addEventListener('click', () => {
            if (navLinks.classList.contains('open')) closeMobileNav();
            else openMobileNav();
          });

          // ensure menu closes on resize to desktop
          window.addEventListener('resize', () => {
            if (window.innerWidth > 760) {
              closeMobileNav();
            }
          });
        }

        function renderStats() {
          if (!rentalItems.length) {
            statsGrid.innerHTML = `
              <article class="stat-card">
                <p class="stat-title">Total Barang</p>
                <p class="stat-value">0</p>
              </article>
            `;
            return;
          }

          const activeItems = rentalItems.filter((item) => item.availability).length;
          const busyItems = rentalItems.length - activeItems;

          const stats = [
            { title: "Total Barang", value: rentalItems.length },
            { title: "Barang Tersedia", value: activeItems },
            { title: "Barang Disewakan", value: busyItems },
            {
              title: "Rata-rata Harga",
              value: formatRupiah(
                Math.round(
                  rentalItems.reduce((sum, item) => sum + item.pricePerDay, 0) / rentalItems.length,
                ),
              ),
            },
          ];

          statsGrid.innerHTML = stats
            .map(
              (stat) => `
              <article class="stat-card">
                <p class="stat-title">${stat.title}</p>
                <p class="stat-value">${stat.value}</p>
              </article>
            `,
            )
              .join("");
        }

        function renderMyItems() {
          const myItems = rentalItems.slice(0, 4);

          if (!myItems.length) {
            itemsList.innerHTML = "<p>Belum ada barang yang didaftarkan.</p>";
            return;
          }

          itemsList.innerHTML = myItems
            .map(
              (item) => `
              <article class="row-card">
                <img src="${item.image}" alt="${item.name}" loading="lazy" />
                <div>
                  <h4 class="row-title">${item.name}</h4>
                  <p class="row-meta">${item.category} - ${item.location}</p>
                  <span class="status ${item.availability ? "active" : "done"}">
                    ${item.availability ? "Tersedia" : "Disewakan"}
                  </span>
                </div>
                <div class="row-actions">
                  <div class="row-price">${formatRupiah(item.pricePerDay)}/hari</div>
                  <div class="row-action-buttons">
                    <a href="./edit_barang.php?id=${item.id}" class="btn btn-sm btn-outline">Edit</a>
                    <a href="./delete_barang.php?id=${item.id}" class="btn btn-sm btn-outline" onclick="return confirm('Hapus barang ini?');">Delete</a>
                  </div>
                </div>
              </article>
            `,
            )
            .join("");
        }

        function renderNotifications() {
          const typeIcons = {
            'item_available': '🎁',
            'item_updated': '✏️',
            'item_deleted': '🗑️',
            'rental_status_changed': '📦',
            'pending_rental': '🔔',
          };

          if (!notifications.length) {
            notificationsList.innerHTML = "<p>Tidak ada notifikasi.</p>";
            markAllReadBtn.hidden = true;
            return;
          }

          markAllReadBtn.hidden = false;

          notificationsList.innerHTML = notifications
            .map(
              (notif) => {
                // Special rendering for pending rental requests
                if (notif.type === 'pending_rental') {
                  const start = new Date(notif.startDate);
                  const end = new Date(notif.endDate);
                  return `
                    <article class="notification-card pending-rental">
                      <div class="notification-content">
                        <div class="notification-header">
                          <span class="notification-icon">${typeIcons[notif.type]}</span>
                          <div>
                            <h4 class="notification-title">${notif.renterName} ingin menyewa "${notif.itemName}"</h4>
                            <p class="notification-message">
                              📅 ${start.toLocaleDateString('id-ID')} - ${end.toLocaleDateString('id-ID')} (${notif.days} hari)<br/>
                              💰 ${formatRupiah(notif.totalPrice)}
                            </p>
                          </div>
                        </div>
                        <p class="notification-meta">📧 ${notif.renterEmail}</p>
                        <small class="notification-time">${new Date(notif.createdAt).toLocaleDateString('id-ID')}</small>
                      </div>
                      <div class="notification-actions">
                        <button type="button" class="btn btn-sm btn-primary js-approve-rental" data-rental-id="${notif.rentalId}">✓ Setujui</button>
                        <button type="button" class="btn btn-sm btn-outline js-reject-rental" data-rental-id="${notif.rentalId}">✗ Tolak</button>
                      </div>
                    </article>
                  `;
                }

                // Regular notification rendering
                return `
                  <article class="notification-card ${notif.is_read ? 'is-read' : ''}">
                    <div class="notification-content">
                      <div class="notification-header">
                        <span class="notification-icon">${typeIcons[notif.type] || '📬'}</span>
                        <h4 class="notification-title">${notif.title}</h4>
                      </div>
                      <p class="notification-message">${notif.message || ''}</p>
                      <small class="notification-time">${new Date(notif.created_at).toLocaleDateString('id-ID')}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline js-delete-notification" data-notification-id="${notif.id}">Hapus</button>
                  </article>
                `;
              }
            )
            .join("");

          // Attach event listeners for approve/reject buttons
          document.querySelectorAll('.js-approve-rental').forEach(btn => {
            btn.addEventListener('click', (e) => handleApproveRental(e.target));
          });
          document.querySelectorAll('.js-reject-rental').forEach(btn => {
            btn.addEventListener('click', (e) => handleRejectRental(e.target));
          });

          updateNotificationBadge();
        }

        function updateNotificationBadge() {
          const unreadCount = notifications.filter((n) => !n.is_read).length;
          if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.hidden = false;
          } else {
            notificationBadge.hidden = true;
          }
        }

        async function loadNotifications() {
          try {
            let allNotifications = [];

            // Load general notifications
            try {
              const response = await fetch('../api/notifications.php?page=1&limit=20');
              const data = await response.json();
              if (data.success && Array.isArray(data.notifications)) {
                allNotifications = allNotifications.concat(data.notifications);
              }
            } catch (error) {
              console.error('Failed to load general notifications:', error);
            }

            try {
              const response = await fetch('../api/owner-notifications.php?action=list');
              const data = await response.json();
              if (data.success && Array.isArray(data.notifications)) {
                allNotifications = allNotifications.concat(data.notifications);
              }
            } catch (error) {
              console.error('Failed to load owner notifications:', error);
            }

            notifications = allNotifications.sort((a, b) => {
              const timeA = new Date(a.createdAt || a.created_at).getTime();
              const timeB = new Date(b.createdAt || b.created_at).getTime();
              return timeB - timeA;
            });

            renderNotifications();
          } catch (error) {
            console.error('Failed to load notifications:', error);
          }
        }

        async function handleDeleteNotification(button) {
          const notificationId = Number(button.dataset.notificationId || 0);
          if (!notificationId) return;

          try {
            const response = await fetch('../api/notifications.php?action=delete', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              credentials: 'same-origin',
              body: JSON.stringify({ notification_id: notificationId }),
            });

            const data = await response.json();
            if (data.success) {
              notifications = notifications.filter((n) => n.id !== notificationId);
              renderNotifications();
            }
          } catch (error) {
            console.error('Failed to delete notification:', error);
          }
        }

        async function handleMarkAllRead() {
          try {
            const response = await fetch('../api/notifications.php?action=mark_all_read', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              credentials: 'same-origin',
              body: JSON.stringify({}),
            });

            const data = await response.json();
            if (data.success) {
              notifications = notifications.map((n) => ({ ...n, is_read: true }));
              renderNotifications();
            }
          } catch (error) {
            console.error('Failed to mark all as read:', error);
          }
        }

        async function handleApproveRental(button) {
          const rentalId = Number(button.dataset.rentalId || 0);
          if (!rentalId) return;

          button.disabled = true;
          const originalText = button.textContent;
          button.textContent = '⏳ Memproses...';

          try {
            const response = await fetch('../api/owner-notifications.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              credentials: 'same-origin',
              body: JSON.stringify({ 
                rental_id: rentalId,
                action: 'approve'
              }),
            });

            const data = await response.json();
            if (data.success) {
              notifications = notifications.filter((n) => n.rentalId !== rentalId);
              renderNotifications();
              alert('✓ Request penyewaan berhasil disetujui!');
            } else {
              alert('Gagal: ' + (data.message || 'Unknown error'));
              button.disabled = false;
              button.textContent = originalText;
            }
          } catch (error) {
            console.error('Failed to approve rental:', error);
            alert('Gagal memproses request. Coba lagi.');
            button.disabled = false;
            button.textContent = originalText;
          }
        }

        async function handleRejectRental(button) {
          const rentalId = Number(button.dataset.rentalId || 0);
          if (!rentalId) return;

          if (!confirm('Yakin ingin menolak request penyewaan ini?')) return;

          button.disabled = true;
          const originalText = button.textContent;
          button.textContent = '⏳ Memproses...';

          try {
            const response = await fetch('../api/owner-notifications.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              credentials: 'same-origin',
              body: JSON.stringify({ 
                rental_id: rentalId,
                action: 'reject'
              }),
            });

            const data = await response.json();
            if (data.success) {
              notifications = notifications.filter((n) => n.rentalId !== rentalId);
              renderNotifications();
              alert('Request penyewaan sudah ditolak.');
            } else {
              alert('Gagal: ' + (data.message || 'Unknown error'));
              button.disabled = false;
              button.textContent = originalText;
            }
          } catch (error) {
            console.error('Failed to reject rental:', error);
            alert('Gagal memproses request. Coba lagi.');
            button.disabled = false;
            button.textContent = originalText;
          }
        }

        function renderRentals() {
          // Placeholder for rental history / active rentals. If you have a
          // server endpoint for rentals, replace this to fetch and render.
          rentalsList.innerHTML = '<p>Belum ada penyewaan aktif.</p>';
        }

        function switchTab(nextTab) {
          tabButtons.forEach((button) => {
            button.classList.toggle("active", button.dataset.tab === nextTab);
          });

          Object.keys(panels).forEach((key) => {
            panels[key].classList.toggle("active", key === nextTab);
          });
        }

        function updateRoleUI() {
          rolePenyewaBtn.classList.toggle('is-active', currentRole === 'penyewa');
          rolePenyewakanBtn.classList.toggle('is-active', currentRole === 'penyewakan');

          itemsTabBtn.hidden = currentRole !== 'penyewakan';
          addItemBtn.hidden = currentRole !== 'penyewakan';

          if (currentRole === 'penyewakan') {
            switchTab('items');
          } else {
            switchTab('rentals');
          }
        }

        async function switchRole(newRole) {
          if (currentRole === newRole) return;

          try {
            const formData = new FormData();
            formData.append('role', newRole);

            // Ensure CSRF token is available — fetch from server if needed
            let csrf = window.RENTAL_API.getCsrfToken ? window.RENTAL_API.getCsrfToken() : '';
            if (!csrf && window.RENTAL_API.fetchCurrentUser) {
              try {
                await window.RENTAL_API.fetchCurrentUser();
                csrf = window.RENTAL_API.getCsrfToken ? window.RENTAL_API.getCsrfToken() : '';
              } catch (e) {
                // ignore
              }
            }

            const headers = csrf ? { 'X-CSRF-Token': csrf } : {};
            // Also include csrf in form as fallback for servers that don't read headers
            if (csrf) formData.append('csrf_token', csrf);

            const response = await fetch('./switch-role.php', {
              method: 'POST',
              body: formData,
              credentials: 'same-origin',
              headers
            });

            const text = await response.text();
            let result = null;

            try {
              result = JSON.parse(text);
            } catch (e) {
              console.error('Non-JSON response from switch-role.php:', text);
              alert('Terjadi kesalahan server saat mengubah role. Cek console untuk detail.');
              return;
            }

            if (!response.ok) {
              console.error('switch-role.php returned error', response.status, result);
              alert('Gagal mengubah role: ' + (result.message || 'Unknown error'));
              return;
            }

            if (result.success) {
              currentRole = newRole;
              updateRoleUI();
              // Reload notifications when role changes (especially for penyewakan mode)
              await loadNotifications();
              console.log('Role switched to:', newRole, result);
            } else {
              console.warn('Role switch failed:', result);
              alert('Gagal mengubah role: ' + (result.message || 'Tidak diketahui'));
            }
          } catch (error) {
            console.error('Error switching role:', error);
            alert('Terjadi kesalahan saat mengubah role. Cek console.');
          }
        }

        rolePenyewaBtn.addEventListener('click', () => switchRole('penyewa'));
        rolePenyewakanBtn.addEventListener('click', () => switchRole('penyewakan'));

        tabButtons.forEach((button) => {
          button.addEventListener("click", () => {
            switchTab(button.dataset.tab);
          });
        });

        const settingsForm = document.getElementById("settingsForm");
        const settingsMsg = document.createElement('div');
        settingsMsg.className = 'field-error-msg';
        settingsForm.appendChild(settingsMsg);

        function setAvatarMessage(message, tone = '') {
          const avatarMsg = document.getElementById('avatarMsg');
          avatarMsg.hidden = !message;
          avatarMsg.classList.remove('is-success', 'is-error');
          if (tone) {
            avatarMsg.classList.add(tone);
          }
          avatarMsg.textContent = message || '';
        }

        function buildProfileFormData() {
          const formData = new FormData();
          const nameInput = document.getElementById('fullName');
          const emailInput = document.getElementById('email');
          const phoneInput = document.getElementById('phone');

          formData.append('name', nameInput ? nameInput.value.trim() : '');
          formData.append('email', emailInput ? emailInput.value.trim() : '');
          formData.append('phone', phoneInput ? phoneInput.value.trim() : '');

          return formData;
        }

        settingsForm.addEventListener("submit", async (event) => {
          event.preventDefault();
          settingsMsg.textContent = '';
          const formData = buildProfileFormData();
          // attach csrf token if available
          const csrf = window.RENTAL_API.getCsrfToken ? window.RENTAL_API.getCsrfToken() : '';
          if (csrf) formData.append('csrf_token', csrf);

          try {
            const resp = await fetch('./update-profile.php', {
              method: 'POST',
              body: formData,
              credentials: 'same-origin',
              headers: csrf ? { 'X-CSRF-Token': csrf } : {}
            });

            const text = await resp.text();
            let data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            if (!resp.ok) {
              settingsMsg.textContent = (data && data.message) ? data.message : 'Gagal menyimpan profil.';
              return;
            }

            settingsMsg.style.color = '#166534';
            settingsMsg.textContent = (data && data.message) ? data.message : 'Profil tersimpan.';

            // update UI with new name/email if provided
            try {
              if (data && data.user) {
                profileName.textContent = data.user.name || profileName.textContent;
                profileEmail.textContent = data.user.email || profileEmail.textContent;
              }
            } catch (e) {}

          } catch (error) {
            settingsMsg.textContent = 'Terjadi kesalahan saat menyimpan. Coba lagi.';
          }
        });

        updateRoleUI();

        async function initializePage() {
          setupAuthNavigation();
          updateRoleUI();

          // Avatar upload handling
          const avatarInput = document.getElementById('avatarInput');
          const avatarLabel = document.getElementById('avatarLabel');
          const avatarSpinner = document.getElementById('avatarSpinner');
          const avatarMsg = document.getElementById('avatarMsg');
          const profileAvatar = document.getElementById('profileAvatar');

          avatarInput.addEventListener('change', async () => {
            setAvatarMessage('');

            const file = avatarInput.files && avatarInput.files[0];
            if (!file) {
              avatarSpinner.hidden = true;
              return;
            }

            if (file.size > 2 * 1024 * 1024) {
              setAvatarMessage('Ukuran file maksimal 2MB.', 'is-error');
              avatarSpinner.hidden = true;
              return;
            }

            avatarSpinner.hidden = false;

            try {
              const reader = new FileReader();
              reader.onload = () => {
                profileAvatar.src = reader.result;
              };
              reader.readAsDataURL(file);
            } catch (error) {
              // preview is optional; continue with upload even if preview fails
            }

            const form = new FormData();
            form.append('avatar', file);

            const csrf = window.RENTAL_API.getCsrfToken ? window.RENTAL_API.getCsrfToken() : '';
            if (csrf) {
              form.append('csrf_token', csrf);
            }

            try {
              const resp = await fetch('./upload-avatar.php', {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                headers: csrf ? { 'X-CSRF-Token': csrf } : {}
              });

              const text = await resp.text();
              const data = text ? JSON.parse(text) : null;

              if (!resp.ok) {
                setAvatarMessage((data && data.message) ? data.message : 'Gagal mengunggah avatar.', 'is-error');
                return;
              }

              setAvatarMessage((data && data.message) ? data.message : 'Avatar tersimpan.', 'is-success');

              if (data && data.avatar) {
                const cacheBuster = data.avatar.includes('?') ? '&' : '?';
                profileAvatar.src = data.avatar + cacheBuster + 'v=' + Date.now();
              }
            } catch (error) {
              setAvatarMessage('Terjadi kesalahan saat mengunggah.', 'is-error');
            } finally {
              avatarSpinner.hidden = true;
              avatarInput.value = '';
            }
          });


          try {
            rentalItems = await window.RENTAL_API.getItems({ scope: 'dashboard' });
          } catch (error) {
            rentalItems = [];
          }

          // Load notifications
          await loadNotifications();

          // Refresh notifications periodically
          setInterval(loadNotifications, 30000);

          renderStats();
          renderRentals();
          renderMyItems();
        }

        // Event listener for notification delete buttons
        notificationsList.addEventListener('click', (event) => {
          const target = event.target;
          if (!(target instanceof HTMLElement)) {
            return;
          }

          const deleteBtn = target.closest('.js-delete-notification');
          if (deleteBtn) {
            handleDeleteNotification(deleteBtn);
          }
        });

        // Event listener for mark all read button
        if (markAllReadBtn) {
          markAllReadBtn.addEventListener('click', handleMarkAllRead);
        }

        initializePage();
    </script>
  </body>
</html>
