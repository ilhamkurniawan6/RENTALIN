<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/sanitizer.php';

if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login/index.php');
    exit;
}

$user = $_SESSION['auth_user'];
$userId = (int) ($user['id'] ?? 0);

if ($userId <= 0) {
    $_SESSION['dashboard_error'] = 'Sesi pengguna tidak valid.';
    header('Location: index.php');
    exit;
}

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = null;

if ($itemId > 0) {
    $stmt = $conn->prepare('SELECT id, user_id, name, category, description, price_per_day, location FROM items WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $itemId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

if (!$item) {
    $_SESSION['dashboard_error'] = 'Barang tidak ditemukan atau bukan milikmu.';
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_text($_POST['name'] ?? '', 255);
    $category = sanitize_text($_POST['category'] ?? '', 100);
    $pricePerDay = isset($_POST['price_per_day']) ? sanitize_int($_POST['price_per_day'], 0, 100000000) : 0;
    $description = sanitize_textarea($_POST['description'] ?? '', 2000);

    if ($name === '' || $category === '' || $description === '') {
        $errors[] = 'Nama barang, kategori, dan deskripsi wajib diisi.';
    }

    if ($pricePerDay < 1000) {
        $errors[] = 'Harga per hari minimal Rp1.000.';
    }

    if (empty($errors)) {
        $updateStmt = $conn->prepare('UPDATE items SET name = ?, category = ?, description = ?, price_per_day = ? WHERE id = ? AND user_id = ?');
        $updateStmt->bind_param('sssiii', $name, $category, $description, $pricePerDay, $itemId, $userId);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $_SESSION['dashboard_message'] = 'Barang berhasil diperbarui.';
            header('Location: index.php');
            exit;
        }

        $errors[] = 'Gagal memperbarui barang. Coba lagi.';
        $updateStmt->close();
    }
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Barang - Rentalin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../styles/reset.css" />
    <link rel="stylesheet" href="../../styles/variables.css" />
    <link rel="stylesheet" href="../../styles/global.css" />
    <link rel="stylesheet" href="./styles.css" />
    <style>
      .edit-page { max-width: 760px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 18px; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08); }
      .edit-page h1 { margin-bottom: 0.5rem; }
      .edit-page p { color: #64748b; margin-bottom: 1.2rem; }
      .edit-form { display: grid; gap: 0.85rem; }
      .edit-form label { font-weight: 600; color: #334155; }
      .edit-form input, .edit-form textarea, .edit-form select { width: 100%; border: 1px solid #d7e2f4; border-radius: 12px; padding: 0.72rem 0.8rem; font: inherit; }
      .edit-form textarea { min-height: 140px; resize: vertical; }
      .edit-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.5rem; }
      .flash-error { margin-bottom: 1rem; padding: 0.8rem 1rem; border-radius: 10px; background: #fee2e2; color: #991b1b; }
    </style>
  </head>
  <body>
    <main class="page">
      <div class="container">
        <section class="edit-page">
          <h1>Edit Barang</h1>
          <p>Perbarui detail barang yang kamu miliki.</p>

          <?php if (!empty($errors)): ?>
            <div class="flash-error">
              <?php echo htmlspecialchars(implode(' ', $errors)); ?>
            </div>
          <?php endif; ?>

          <form class="edit-form" method="post">
            <label for="name">Nama Barang</label>
            <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required />

            <label for="category">Kategori</label>
            <input id="category" name="category" type="text" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" required />

            <label for="price_per_day">Harga per Hari</label>
            <input id="price_per_day" name="price_per_day" type="number" min="1000" step="1000" value="<?php echo (int) ($item['price_per_day'] ?? 0); ?>" required />

            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>

            <div class="edit-actions">
              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              <a href="index.php" class="btn btn-outline">Batal</a>
            </div>
          </form>
        </section>
      </div>
    </main>
  </body>
</html>
