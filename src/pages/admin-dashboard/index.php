<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/role-helper.php';

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Check admin access
if (!isset($_SESSION['auth_user']) || !rentalin_is_admin_like($_SESSION['auth_user'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

$isLoggedIn = true;
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rentalin - Dashboard Admin</title>
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
    <script>
      try {
        localStorage.setItem('rentalin_csrf', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');
      } catch (error) {}
    </script>
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
          <a href="../dashboard/index.php">Dashboard User</a>
          <a href="./index.php" class="active">Dashboard Admin</a>
          <a href="#" id="navLoginLink">Logout</a>
        </nav>
      </div>
    </header>

    <main class="page">
      <div class="container">
        <header class="hero">
          <h1>Dashboard Admin</h1>
          <p>Kelola pengguna, barang, dan transaksi penyewaan marketplace.</p>
        </header>

        <section id="statsGrid" class="stats-grid"></section>

        <section class="card section-tabs">
          <div class="tabs" id="sectionTabs">
            <button class="tab active" data-section="users">Pengguna</button>
            <button class="tab" data-section="items">Barang</button>
            <button class="tab" data-section="transactions">Transaksi</button>
          </div>
          <div class="search-wrap">
            <input id="searchInput" type="text" placeholder="Cari data..." />
          </div>
        </section>

        <section class="card table-card">
          <div class="table-wrap">
            <table id="adminTable"></table>
          </div>
          <div class="table-footer">
            <small id="paginationInfo">Halaman 1 dari 1</small>
            <div class="table-actions">
              <button type="button" id="prevPageBtn" class="btn btn-sm btn-outline">Prev</button>
              <button type="button" id="nextPageBtn" class="btn btn-sm btn-outline">Next</button>
            </div>
          </div>
        </section>
      </div>
    </main>

    <script src="../../mock/data.js"></script>
    <script src="../../store/storage.js"></script>
    <script src="../../services/api.js"></script>
    <script>
      const navLoginLink = document.getElementById("navLoginLink");
      let adminItems = [];
      let adminUsers = [];
      let adminTransactions = [];

      if (navLoginLink) {
        navLoginLink.addEventListener("click", async (event) => {
          event.preventDefault();
          await window.RENTAL_API.logout();
          window.location.href = "../home/index.php";
        });
      }

      const statsGrid = document.getElementById("statsGrid");
      const sectionTabs = document.getElementById("sectionTabs");
      const searchInput = document.getElementById("searchInput");
      const adminTable = document.getElementById("adminTable");
      const paginationInfo = document.getElementById("paginationInfo");
      const prevPageBtn = document.getElementById("prevPageBtn");
      const nextPageBtn = document.getElementById("nextPageBtn");
      const adminNotice = document.createElement("p");
      adminNotice.id = "adminNotice";
      adminNotice.className = "admin-notice";
      adminNotice.hidden = true;
      document.querySelector(".section-tabs").insertAdjacentElement("afterend", adminNotice);

      const state = {
        section: "users",
        search: "",
        limit: 10,
        pagination: {
          users: { page: 1, totalPages: 1, total: 0 },
          items: { page: 1, totalPages: 1, total: 0 },
          transactions: { page: 1, totalPages: 1, total: 0 },
        },
        stats: {
          totalUsers: 0,
          activeUsers: 0,
          activeListings: 0,
          totalRevenue: 0,
        },
      };

      function formatRupiah(amount) {
        return `Rp${new Intl.NumberFormat("id-ID").format(amount)}`;
      }

      function formatDate(dateStr) {
        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) {
          return "-";
        }

        return date.toLocaleDateString("id-ID", {
          day: "2-digit",
          month: "short",
          year: "numeric",
        });
      }

      function statusLabel(status) {
        if (status === "completed") return "Selesai";
        if (status === "active") return "Aktif";
        if (status === "cancelled") return "Batal";
        if (status === "pending") return "Menunggu";
        return status || "-";
      }

      function statusOptionsMarkup(currentStatus) {
        const options = [
          { value: "pending", label: "Menunggu" },
          { value: "active", label: "Aktif" },
          { value: "completed", label: "Selesai" },
          { value: "cancelled", label: "Batal" },
        ];

        return options
          .map((opt) => `<option value="${opt.value}"${opt.value === currentStatus ? " selected" : ""}>${opt.label}</option>`)
          .join("");
      }

      function badge(status) {
        if (status === "active" || status === "completed" || status === "available") {
          return "ok";
        }
        if (status === "pending" || status === "rented") {
          return "warn";
        }
        return "idle";
      }

      function accountRoleLabel(role) {
        if (role === "admin") return "Admin";
        return "User";
      }

      function currentRoleLabel(role) {
        if (role === "penyewakan") return "Penyewakan";
        return "Penyewa";
      }

      function roleBadgeTone(role) {
        return role === "admin" || role === "penyewakan" ? "ok" : "idle";
      }

      function setAdminNotice(message, tone = "error") {
        if (!message) {
          adminNotice.hidden = true;
          adminNotice.textContent = "";
          adminNotice.classList.remove("is-error", "is-success");
          return;
        }

        adminNotice.hidden = false;
        adminNotice.textContent = message;
        adminNotice.classList.remove("is-error", "is-success");
        adminNotice.classList.add(tone === "success" ? "is-success" : "is-error");
      }

      function renderStats() {
        const stats = [
          { label: "Total Pengguna", value: state.stats.totalUsers },
          { label: "Pengguna Aktif", value: state.stats.activeUsers },
          { label: "Daftar Aktif", value: state.stats.activeListings },
          { label: "Pendapatan", value: formatRupiah(state.stats.totalRevenue) },
        ];

        statsGrid.innerHTML = stats
          .map(
            (stat) => `
            <article class="stat-card">
              <p class="stat-label">${stat.label}</p>
              <p class="stat-value">${stat.value}</p>
            </article>
          `,
          )
          .join("");
      }

      function renderUsersTable() {
        const rows = adminUsers
          .map(
            (user) => `
            <tr>
              <td>${user.name}</td>
              <td>${user.email}</td>
              <td><span class="badge ${badge(user.status)}">${user.status === "active" ? "Aktif" : "Nonaktif"}</span></td>
              <td>${user.items}</td>
              <td>${user.rentals}</td>
              <td>
                <div class="badge-stack">
                  <span class="badge ${roleBadgeTone(user.role)}">${accountRoleLabel(user.role)}</span>
                  <span class="badge ${roleBadgeTone(user.current_role)}">${currentRoleLabel(user.current_role)}</span>
                </div>
              </td>
            </tr>
          `,
          )
          .join("");

        adminTable.innerHTML = `
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Status</th>
              <th>Barang</th>
              <th>Total Sewa</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="6">Data tidak ditemukan.</td></tr>'}</tbody>
        `;
      }

      function renderItemsTable() {
        const rows = adminItems
          .map(
            (item) => `
            <tr>
              <td>${item.name}</td>
              <td>${item.category}</td>
              <td>
                <div class="owner-cell">
                  <img class="owner-avatar" src="${item.owner.avatar}" alt="Avatar ${item.owner.name}" />
                  <div>
                    <div>${item.owner.name}</div>
                    <small>${item.owner.email}</small>
                  </div>
                </div>
              </td>
              <td>${formatRupiah(item.pricePerDay)}</td>
              <td><span class="badge ${badge(item.availability ? "available" : "rented")}">${item.availability ? "Tersedia" : "Disewakan"}</span></td>
              <td>
                <div class="actions-group">
                  <button type="button" class="btn btn-sm btn-outline js-toggle-item" data-item-id="${item.id}" data-next="${item.availability ? '0' : '1'}">
                    ${item.availability ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>
                  <button type="button" class="btn btn-sm btn-outline js-edit-item" data-item-id="${item.id}">Edit</button>
                  <button type="button" class="btn btn-sm btn-outline js-delete-item" data-item-id="${item.id}">Hapus</button>
                </div>
              </td>
            </tr>
          `,
          )
          .join("");

        adminTable.innerHTML = `
          <thead>
            <tr>
              <th>Barang</th>
              <th>Kategori</th>
              <th>Pemilik</th>
              <th>Harga/Hari</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="6">Data tidak ditemukan.</td></tr>'}</tbody>
        `;
      }

      async function handleToggleItem(button) {
        const itemId = Number(button.dataset.itemId || 0);
        const nextAvailability = button.dataset.next === '1';
        if (!itemId) {
          return;
        }

        const previousLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Memproses...';

        try {
          const response = await window.RENTAL_API.toggleAdminItemAvailability({
            item_id: itemId,
            availability: nextAvailability,
          });

          if (!response || response.success !== true) {
            throw new Error((response && response.message) || 'Gagal mengubah status barang.');
          }

          adminItems = adminItems.map((item) => {
            if (Number(item.id) !== itemId) {
              return item;
            }
            return {
              ...item,
              availability: Boolean(response.availability),
            };
          });

          state.stats.activeListings += response.availability ? 1 : -1;
          if (state.stats.activeListings < 0) {
            state.stats.activeListings = 0;
          }

          renderStats();
          renderTable();
        } catch (error) {
          setAdminNotice(error && error.message ? error.message : 'Gagal mengubah status barang.');
          button.disabled = false;
          button.textContent = previousLabel;
        }
      }

      function renderTransactionsTable() {
        const rows = adminTransactions
          .map(
            (tx) => `
            <tr>
              <td>TXN${String(tx.id).padStart(4, '0')}</td>
              <td>${tx.user.name}<br /><small>${tx.user.email}</small></td>
              <td>${tx.item.name}</td>
              <td>${formatRupiah(tx.amount)}</td>
              <td>${formatDate(tx.created_at)}</td>
              <td><span class="badge ${badge(tx.status)}">${statusLabel(tx.status)}</span></td>
              <td>
                <select class="js-tx-status-select" data-transaction-id="${tx.id}">
                  ${statusOptionsMarkup(tx.status)}
                </select>
                <button type="button" class="btn btn-sm btn-outline js-save-tx-status" data-transaction-id="${tx.id}">Simpan</button>
              </td>
            </tr>
          `,
          )
          .join("");

        adminTable.innerHTML = `
          <thead>
            <tr>
              <th>ID</th>
              <th>Pengguna</th>
              <th>Barang</th>
              <th>Jumlah</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="7">Data tidak ditemukan.</td></tr>'}</tbody>
        `;
      }

      async function handleSaveTransactionStatus(button) {
        const transactionId = Number(button.dataset.transactionId || 0);
        if (!transactionId) {
          return;
        }

        const row = button.closest('tr');
        if (!row) {
          return;
        }

        const select = row.querySelector(`.js-tx-status-select[data-transaction-id="${transactionId}"]`);
        if (!(select instanceof HTMLSelectElement)) {
          return;
        }

        const nextStatus = String(select.value || '').trim();
        if (!nextStatus) {
          return;
        }

        const previousText = button.textContent;
        button.disabled = true;
        button.textContent = 'Memproses...';

        try {
          const response = await window.RENTAL_API.updateAdminTransactionStatus({
            transaction_id: transactionId,
            status: nextStatus,
          });

          if (!response || response.success !== true) {
            throw new Error((response && response.message) || 'Gagal mengubah status transaksi.');
          }

          const targetTx = adminTransactions.find((tx) => Number(tx.id) === transactionId) || null;

          adminTransactions = adminTransactions.map((tx) => {
            if (Number(tx.id) !== transactionId) {
              return tx;
            }
            return {
              ...tx,
              status: String(response.status || nextStatus),
            };
          });

          if (targetTx) {
            const wasCompleted = targetTx.status === "completed";
            const isCompleted = String(response.status || nextStatus) === "completed";
            if (!wasCompleted && isCompleted) {
              state.stats.totalRevenue += Number(targetTx.amount || 0);
            }
            if (wasCompleted && !isCompleted) {
              state.stats.totalRevenue -= Number(targetTx.amount || 0);
              if (state.stats.totalRevenue < 0) {
                state.stats.totalRevenue = 0;
              }
            }
          }

          renderStats();
          renderTable();
        } catch (error) {
          setAdminNotice(error && error.message ? error.message : 'Gagal mengubah status transaksi.');
          button.disabled = false;
          button.textContent = previousText;
        }
      }

      async function handleDeleteItem(button) {
        const itemId = Number(button.dataset.itemId || 0);
        if (!itemId) {
          return;
        }

        const item = adminItems.find((i) => Number(i.id) === itemId);
        if (!item) {
          return;
        }

        if (!confirm(`Apakah Anda yakin ingin menghapus barang "${item.name}"?`)) {
          return;
        }

        const previousLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Menghapus...';

        try {
          const response = await fetch('../api/admin-item-actions.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': localStorage.getItem('rentalin_csrf') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              action: 'delete',
              item_id: itemId,
              csrf_token: localStorage.getItem('rentalin_csrf') || '',
            }),
          });

          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal menghapus barang.');
          }

          adminItems = adminItems.filter((i) => Number(i.id) !== itemId);
          state.stats.activeListings -= item.availability ? 1 : 0;
          if (state.stats.activeListings < 0) {
            state.stats.activeListings = 0;
          }

          setAdminNotice('Barang berhasil dihapus.', 'success');
          renderStats();
          renderTable();
        } catch (error) {
          setAdminNotice(error && error.message ? error.message : 'Gagal menghapus barang.');
          button.disabled = false;
          button.textContent = previousLabel;
        }
      }

      async function handleEditItem(button) {
        const itemId = Number(button.dataset.itemId || 0);
        if (!itemId) {
          return;
        }

        const item = adminItems.find((i) => Number(i.id) === itemId);
        if (!item) {
          return;
        }

        const newName = prompt('Nama barang:', item.name);
        if (newName === null || !newName.trim()) {
          return;
        }

        const newCategory = prompt('Kategori:', item.category);
        if (newCategory === null || !newCategory.trim()) {
          return;
        }

        const newPrice = prompt('Harga per hari (Rp):', item.pricePerDay);
        if (newPrice === null || isNaN(parseInt(newPrice)) || parseInt(newPrice) <= 0) {
          return;
        }

        const newLocation = prompt('Lokasi:', item.location);
        if (newLocation === null || !newLocation.trim()) {
          return;
        }

        const previousLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Menyimpan...';

        try {
          const response = await fetch('../api/admin-item-actions.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': localStorage.getItem('rentalin_csrf') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              action: 'update',
              item_id: itemId,
              name: newName.trim(),
              category: newCategory.trim(),
              description: item.description || '',
              price_per_day: parseInt(newPrice),
              location: newLocation.trim(),
              availability: item.availability,
              csrf_token: localStorage.getItem('rentalin_csrf') || '',
            }),
          });

          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal memperbarui barang.');
          }

          adminItems = adminItems.map((i) => {
            if (Number(i.id) !== itemId) {
              return i;
            }
            return {
              ...i,
              name: newName.trim(),
              category: newCategory.trim(),
              pricePerDay: parseInt(newPrice),
              location: newLocation.trim(),
            };
          });

          setAdminNotice('Barang berhasil diperbarui.', 'success');
          renderStats();
          renderTable();
        } catch (error) {
          setAdminNotice(error && error.message ? error.message : 'Gagal memperbarui barang.');
          button.disabled = false;
          button.textContent = previousLabel;
        }
      }

      function renderPagination() {
        const current = state.pagination[state.section] || { page: 1, totalPages: 1, total: 0 };
        paginationInfo.textContent = `Halaman ${current.page} dari ${current.totalPages} (${current.total} data)`;
        prevPageBtn.disabled = current.page <= 1;
        nextPageBtn.disabled = current.page >= current.totalPages;
      }

      function renderTable() {
        if (state.section === "users") {
          renderUsersTable();
          renderPagination();
          return;
        }

        if (state.section === "items") {
          renderItemsTable();
          renderPagination();
          return;
        }

        renderTransactionsTable();
        renderPagination();
      }

      function applyPagination(section, payload) {
        const pg = payload && payload.pagination ? payload.pagination : null;
        if (!pg) {
          state.pagination[section] = { page: 1, totalPages: 1, total: 0 };
          return;
        }

        state.pagination[section] = {
          page: Number(pg.page || 1),
          totalPages: Number(pg.total_pages || 1),
          total: Number(pg.total || 0),
        };
      }

      async function loadUsersPage(page) {
        const usersPayload = await window.RENTAL_API.getAdminUsers({ page, limit: state.limit, search: state.search });
        adminUsers = usersPayload && Array.isArray(usersPayload.users) ? usersPayload.users : [];
        applyPagination("users", usersPayload);

        const activeUsers = usersPayload && usersPayload.stats ? Number(usersPayload.stats.active_users || 0) : 0;
        state.stats.totalUsers = state.pagination.users.total;
        state.stats.activeUsers = activeUsers;
      }

      async function loadItemsPage(page) {
        const itemsPayload = await window.RENTAL_API.getAdminItems({ page, limit: state.limit, search: state.search });
        adminItems = itemsPayload && Array.isArray(itemsPayload.items) ? itemsPayload.items : [];
        applyPagination("items", itemsPayload);

        const activeListings = itemsPayload && itemsPayload.stats ? Number(itemsPayload.stats.active_items || 0) : 0;
        state.stats.activeListings = activeListings;
      }

      async function loadTransactionsPage(page) {
        const transactionsPayload = await window.RENTAL_API.getAdminTransactions({ page, limit: state.limit, search: state.search });
        adminTransactions = transactionsPayload && Array.isArray(transactionsPayload.transactions)
          ? transactionsPayload.transactions
          : [];
        applyPagination("transactions", transactionsPayload);

        const totalRevenue = transactionsPayload && transactionsPayload.stats
          ? Number(transactionsPayload.stats.completed_revenue || 0)
          : 0;
        state.stats.totalRevenue = totalRevenue;
      }

      async function loadSectionData(section, page) {
        if (section === "users") {
          await loadUsersPage(page);
          return;
        }

        if (section === "items") {
          await loadItemsPage(page);
          return;
        }

        await loadTransactionsPage(page);
      }

      async function refreshCurrentSection(page = 1) {
        try {
          setAdminNotice("");
          await loadSectionData(state.section, page);
        } catch (error) {
          if (state.section === "users") adminUsers = [];
          if (state.section === "items") adminItems = [];
          if (state.section === "transactions") adminTransactions = [];
          setAdminNotice("Gagal memuat data admin. Coba muat ulang halaman.");
        }

        renderStats();
        renderTable();
      }

      sectionTabs.querySelectorAll(".tab").forEach((button) => {
        button.addEventListener("click", async () => {
          state.section = button.dataset.section;
          sectionTabs.querySelectorAll(".tab").forEach((tab) => {
            tab.classList.toggle("active", tab === button);
          });

          try {
            const currentPage = state.pagination[state.section].page || 1;
            await loadSectionData(state.section, currentPage);
          } catch (error) {
            if (state.section === "users") adminUsers = [];
            if (state.section === "items") adminItems = [];
            if (state.section === "transactions") adminTransactions = [];
          }

          renderStats();
          renderTable();
        });
      });

      searchInput.addEventListener("input", (event) => {
        state.search = event.target.value;
        state.pagination[state.section].page = 1;
        refreshCurrentSection(1);
      });

      prevPageBtn.addEventListener("click", async () => {
        const current = state.pagination[state.section];
        if (!current || current.page <= 1) {
          return;
        }

        refreshCurrentSection(current.page - 1);
      });

      nextPageBtn.addEventListener("click", async () => {
        const current = state.pagination[state.section];
        if (!current || current.page >= current.totalPages) {
          return;
        }

        refreshCurrentSection(current.page + 1);
      });

      adminTable.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }

        const toggleButton = target.closest('.js-toggle-item');
        if (toggleButton) {
          handleToggleItem(toggleButton);
          return;
        }

        const saveTxButton = target.closest('.js-save-tx-status');
        if (saveTxButton) {
          handleSaveTransactionStatus(saveTxButton);
          return;
        }

        const deleteButton = target.closest('.js-delete-item');
        if (deleteButton) {
          handleDeleteItem(deleteButton);
          return;
        }

        const editButton = target.closest('.js-edit-item');
        if (editButton) {
          handleEditItem(editButton);
          return;
        }
      });

      async function initializePage() {
        try {
          setAdminNotice("");
          await Promise.all([
            loadUsersPage(state.pagination.users.page),
            loadItemsPage(state.pagination.items.page),
            loadTransactionsPage(state.pagination.transactions.page),
          ]);
        } catch (error) {
          adminItems = [];
          adminUsers = [];
          adminTransactions = [];
          setAdminNotice("Gagal memuat data admin. Coba muat ulang halaman.");
        }

        renderStats();
        renderTable();
      }

      initializePage();
    </script>
  </body>
</html>
