    (function attachRentalApi(global) {
    const TOKEN_KEY = "rentalin_auth_token";
    const USER_KEY = "rentalin_auth_user";

    const config = {
        // Isi dengan URL API backend kamu, contoh: "http://localhost:8000"
        baseUrl: "",
        requestTimeoutMs: 10000,
    };

    function hasBackend() {
        return Boolean(config.baseUrl && config.baseUrl.trim());
    }

    function buildUrl(path) {
        const base = config.baseUrl.replace(/\/$/, "");
        const nextPath = path.startsWith("/") ? path : `/${path}`;
        return `${base}${nextPath}`;
    }

    function getStoredToken() {
        return global.localStorage.getItem(TOKEN_KEY) || "";
    }

    function setSession(session) {
        if (!session || !session.token || !session.user) {
        return;
        }

        global.localStorage.setItem(TOKEN_KEY, String(session.token));
        global.localStorage.setItem(USER_KEY, JSON.stringify(session.user));
    }

    function clearSession() {
        global.localStorage.removeItem(TOKEN_KEY);
        global.localStorage.removeItem(USER_KEY);
    }

    function getCurrentUser() {
        try {
        const raw = global.localStorage.getItem(USER_KEY);
        return raw ? JSON.parse(raw) : null;
        } catch (error) {
        return null;
        }
    }

    async function fetchCurrentUser() {
        // If configured to use a backend API, prefer that
        if (hasBackend()) {
        try {
            const data = await request('/api/auth/status', { method: 'GET' });
            return data && data.user ? data.user : null;
        } catch (err) {
            return getCurrentUser();
        }
        }

        // Try server-side PHP endpoint (relative path). If it fails, fall back to localStorage.
        try {
        const resp = await global.fetch('../api/auth-status.php', {
            method: 'GET',
            credentials: 'same-origin',
        });
        const text = await resp.text();
        const data = text ? JSON.parse(text) : null;
        if (data) {
        if (data.csrf_token) {
            try {
            global.localStorage.setItem('rentalin_csrf', String(data.csrf_token));
            } catch (e) {}
        }

        if (data.success && data.user) {
            try {
            global.localStorage.setItem(USER_KEY, JSON.stringify(data.user));
            } catch (e) {}
            return data.user;
        }
        }
        } catch (e) {
        // ignore network errors and fall back
        }

        return getCurrentUser();
    }

    async function request(path, options) {
        const controller = new AbortController();
        const timeoutId = global.setTimeout(() => {
        controller.abort();
        }, config.requestTimeoutMs);

        const headers = {
        "Content-Type": "application/json",
        ...(options && options.headers ? options.headers : {}),
        };

        // Attach CSRF token for non-GET requests when available
        try {
        const csrf = global.localStorage.getItem('rentalin_csrf');
        if (csrf && !(options && options.method && options.method.toUpperCase() === 'GET')) {
            headers['X-CSRF-Token'] = csrf;
        }
        } catch (e) {}

        const token = getStoredToken();
        if (token) {
        headers.Authorization = `Bearer ${token}`;
        }

        try {
        const response = await global.fetch(buildUrl(path), {
            ...options,
            headers,
            signal: controller.signal,
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;

        if (!response.ok) {
            const message =
            data && typeof data.message === "string"
                ? data.message
                : "Permintaan gagal diproses.";
            throw new Error(message);
        }

        return data;
        } catch (error) {
        if (error && error.name === "AbortError") {
            throw new Error("Request timeout. Coba lagi.");
        }
        throw error;
        } finally {
        global.clearTimeout(timeoutId);
        }
    }

    async function getItems(options = {}) {
        const scope = options && options.scope ? String(options.scope) : "";
        const scopeQuery = scope ? `?scope=${encodeURIComponent(scope)}` : "";

        if (hasBackend()) {
        const data = await request(`/api/items${scopeQuery}`, { method: "GET" });
        return Array.isArray(data) ? data : [];
        }

        try {
        const response = await global.fetch(`../api/items.php${scopeQuery}`, {
            method: "GET",
            credentials: "same-origin",
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : null;

        if (response.ok && data && data.success && Array.isArray(data.items)) {
            return data.items;
        }
        } catch (error) {
        // fall back to local mock data below
        }

        return global.RENTAL_STORE.getAllItems();
    }

    async function getCategories() {
        if (hasBackend()) {
        const data = await request("/api/categories", { method: "GET" });
        return Array.isArray(data) ? data : [];
        }

        try {
        const response = await global.fetch("../api/categories.php", {
            method: "GET",
            credentials: "same-origin",
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : null;

        if (response.ok && data && data.success && Array.isArray(data.categories)) {
            return data.categories;
        }
        } catch (error) {
        // fall back to local mock data below
        }

        return global.RENTAL_STORE.getCategoriesWithCounts();
    }

    async function getItemById(itemId) {
        const id = String(itemId || "").trim();
        if (!id) {
        return null;
        }

        if (hasBackend()) {
        return request(`/api/items/${encodeURIComponent(id)}`, { method: "GET" });
        }

        try {
        const response = await global.fetch(`../api/items.php?id=${encodeURIComponent(id)}`, {
            method: "GET",
            credentials: "same-origin",
            chache: "no-store",
        });
        
        const text = await response.text();
        const data = text ? JSON.parse(text) : null;

        if (response.ok && data && data.success && data.item) {
            return data.item;
        }
        } catch (error) {
        // fall back to local mock data below
        }

        const items = global.RENTAL_STORE.getAllItems();
        return items.find((entry) => String(entry.id) === id) || null;
    }

    async function createBooking(payload) {
        const body = {
        item_id: Number(payload && payload.item_id ? payload.item_id : 0),
        start_date: String(payload && payload.start_date ? payload.start_date : "").trim(),
        end_date: String(payload && payload.end_date ? payload.end_date : "").trim(),
        };

        if (!body.item_id || !body.start_date || !body.end_date) {
        throw new Error("Lengkapi tanggal penyewaan sebelum mengirim booking.");
        }

        if (hasBackend()) {
        return request("/api/bookings", {
            method: "POST",
            body: JSON.stringify(body),
        });
        }

        const headers = { "Content-Type": "application/json" };
        try {
        const csrf = global.localStorage.getItem('rentalin_csrf');
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        } catch (e) {}

        const response = await global.fetch("../api/bookings.php", {
        method: "POST",
        credentials: "same-origin",
        headers,
        body: JSON.stringify(body),
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal membuat booking.";
        throw new Error(message);
        }

        return data;
    }

    async function addItem(payload) {
        if (!hasBackend()) {
        return global.RENTAL_STORE.addCustomItem(payload);
        }

        return request("/api/items", {
        method: "POST",
        body: JSON.stringify(payload),
        });
    }

    async function getAdminUsers() {
        const options = arguments.length > 0 && arguments[0] ? arguments[0] : {};
        const page = Math.max(1, Number(options.page || 1));
        const limit = Math.max(1, Math.min(100, Number(options.limit || 10)));
        const search = String(options.search || '').trim();
        const query = `?page=${encodeURIComponent(String(page))}&limit=${encodeURIComponent(String(limit))}&search=${encodeURIComponent(search)}`;

        if (hasBackend()) {
        return request(`/api/admin/users${query}`, { method: "GET" });
        }

        const response = await global.fetch(`../api/admin-users.php${query}`, {
        method: "GET",
        credentials: "same-origin",
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal memuat data pengguna admin.";
        throw new Error(message);
        }

        return data;
    }

    async function getAdminItems() {
        const options = arguments.length > 0 && arguments[0] ? arguments[0] : {};
        const page = Math.max(1, Number(options.page || 1));
        const limit = Math.max(1, Math.min(100, Number(options.limit || 10)));
        const search = String(options.search || '').trim();
        const query = `?page=${encodeURIComponent(String(page))}&limit=${encodeURIComponent(String(limit))}&search=${encodeURIComponent(search)}`;

        if (hasBackend()) {
        return request(`/api/admin/items${query}`, { method: "GET" });
        }

        const response = await global.fetch(`../api/admin-items.php${query}`, {
        method: "GET",
        credentials: "same-origin",
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal memuat data barang admin.";
        throw new Error(message);
        }

        return data;
    }

    async function toggleAdminItemAvailability(payload) {
        if (hasBackend()) {
        return request("/api/admin/items/toggle", {
            method: "POST",
            body: JSON.stringify(payload),
        });
        }

        const formData = new FormData();
        formData.append("item_id", String(payload.item_id || ""));
        formData.append("availability", payload.availability ? "1" : "0");

        const headers = {};
        try {
        const csrf = global.localStorage.getItem('rentalin_csrf');
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        } catch (e) {}

        const response = await global.fetch("../api/admin-toggle-item.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers,
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal mengubah status barang.";
        throw new Error(message);
        }

        return data;
    }

    async function getAdminTransactions() {
        const options = arguments.length > 0 && arguments[0] ? arguments[0] : {};
        const page = Math.max(1, Number(options.page || 1));
        const limit = Math.max(1, Math.min(100, Number(options.limit || 10)));
        const search = String(options.search || '').trim();
        const query = `?page=${encodeURIComponent(String(page))}&limit=${encodeURIComponent(String(limit))}&search=${encodeURIComponent(search)}`;

        if (hasBackend()) {
        return request(`/api/admin/transactions${query}`, { method: "GET" });
        }

        const response = await global.fetch(`../api/admin-transactions.php${query}`, {
        method: "GET",
        credentials: "same-origin",
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal memuat data transaksi admin.";
        throw new Error(message);
        }

        return data;
    }

    async function updateAdminTransactionStatus(payload) {
        if (hasBackend()) {
        return request("/api/admin/transactions/status", {
            method: "POST",
            body: JSON.stringify(payload),
        });
        }

        const formData = new FormData();
        formData.append("transaction_id", String(payload.transaction_id || ""));
        formData.append("status", String(payload.status || ""));

        const headers = {};
        try {
        const csrf = global.localStorage.getItem('rentalin_csrf');
        if (csrf) {
            headers['X-CSRF-Token'] = csrf;
        }
        } catch (e) {}

        const response = await global.fetch("../api/admin-update-transaction-status.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers,
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : null;
        if (!response.ok) {
        const message = data && data.message ? data.message : "Gagal mengubah status transaksi.";
        throw new Error(message);
        }

        return data;
    }

    async function login(credentials) {
        if (!hasBackend()) {
        const user = {
            id: "local-user-1",
            name: "Budi Santoso",
            email: String(credentials.email || "").trim().toLowerCase(),
            role: "user",
        };

        const session = {
            token: "local-token",
            user,
        };

        setSession(session);
        return session;
        }

        const session = await request("/api/auth/login", {
        method: "POST",
        body: JSON.stringify(credentials),
        });

        setSession(session);
        return session;
    }

    async function register(payload) {
        if (!hasBackend()) {
        const user = {
            id: `local-user-${Date.now()}`,
            name: String(payload.name || "").trim(),
            email: String(payload.email || "").trim().toLowerCase(),
            role: "user",
        };

        const session = {
            token: "local-token",
            user,
        };

        setSession(session);
        return session;
        }

        const session = await request("/api/auth/register", {
        method: "POST",
        body: JSON.stringify(payload),
        });

        setSession(session);
        return session;
    }

    async function logout() {
        if (hasBackend()) {
        try {
            await request("/api/auth/logout", { method: "POST" });
        } catch (error) {
            // Ignore logout error and clear local session anyway.
        }
        } else {
        try {
            // Non-backend (PHP) logout: POST with CSRF token to avoid logout CSRF
            const headers = {};
            try {
            const csrf = global.localStorage.getItem('rentalin_csrf');
            if (csrf) headers['X-CSRF-Token'] = csrf;
            } catch (e) {}

            await global.fetch("../login/logout.php", {
            method: "POST",
            credentials: 'same-origin',
            headers,
            });
        } catch (error) {
            // Ignore logout errors and clear local session anyway.
        }
        }

        clearSession();
    }

    global.RENTAL_API = {
        config,
        hasBackend,
        getCurrentUser,
        fetchCurrentUser,
        getCsrfToken: () => {
        try {
            return global.localStorage.getItem('rentalin_csrf') || '';
        } catch (e) {
            return '';
        }
        },
        getItems,
        getCategories,
        getItemById,
        createBooking,
        addItem,
        getAdminUsers,
        getAdminItems,
        toggleAdminItemAvailability,
        getAdminTransactions,
        updateAdminTransactionStatus,
        login,
        register,
        logout,
    };
    // Attempt to synchronize client-side stored user with server session on load
    (async function syncAuthOnLoad() {
        try {
            await global.RENTAL_API.fetchCurrentUser();
        } catch (e) {
            // ignore errors
        }
    })();

    })(window);
