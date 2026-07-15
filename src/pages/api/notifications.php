<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak ada otentikasi.']);
    exit;
}

$userId = (int) $_SESSION['auth_user']['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? trim((string) $_GET['action']) : 'list';

// Handle GET requests for listing notifications
if ($method === 'GET') {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit < 1 || $limit > 100) {
        $limit = 20;
    }

    if ($action === 'unread_count') {
        $countSql = 'SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0';
        $countStmt = $conn->prepare($countSql);
        if (!$countStmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query.']);
            exit;
        }

        $countStmt->bind_param('i', $userId);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $row = $result->fetch_assoc();
        $countStmt->close();

        echo json_encode([
            'success' => true,
            'unread_count' => (int) ($row['unread_count'] ?? 0),
        ]);
        exit;
    }

    // List notifications
    $offset = ($page - 1) * $limit;

    $countSql = 'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ?';
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghitung notifikasi.']);
        exit;
    }
    $countStmt->bind_param('i', $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $countStmt->close();

    $total = (int) ($countRow['total'] ?? 0);
    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

    $listSql = 'SELECT id, item_id, type, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $listStmt = $conn->prepare($listSql);
    if (!$listStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat notifikasi.']);
        exit;
    }

    $listStmt->bind_param('iii', $userId, $limit, $offset);
    $listStmt->execute();
    $listResult = $listStmt->get_result();

    $notifications = [];
    while ($row = $listResult->fetch_assoc()) {
        $notifications[] = [
            'id' => (int) ($row['id'] ?? 0),
            'item_id' => (int) ($row['item_id'] ?? 0),
            'type' => (string) ($row['type'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'message' => (string) ($row['message'] ?? ''),
            'is_read' => (bool) ($row['is_read'] ?? false),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
    $listStmt->close();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ],
    ]);
    exit;
}

// Handle POST requests for marking as read or marking all as read
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($action === 'mark_read') {
        $notificationId = isset($input['notification_id']) ? (int) $input['notification_id'] : 0;

        if ($notificationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid.']);
            exit;
        }

        $updateSql = 'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?';
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui notifikasi.']);
            exit;
        }

        $updateStmt->bind_param('ii', $notificationId, $userId);
        if (!$updateStmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menandai notifikasi sebagai dibaca.']);
            $updateStmt->close();
            exit;
        }
        $updateStmt->close();

        echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil ditandai.']);
        exit;
    }

    if ($action === 'mark_all_read') {
        $updateSql = 'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0';
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui notifikasi.']);
            exit;
        }

        $updateStmt->bind_param('i', $userId);
        if (!$updateStmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menandai semua notifikasi sebagai dibaca.']);
            $updateStmt->close();
            exit;
        }
        $updateStmt->close();

        echo json_encode(['success' => true, 'message' => 'Semua notifikasi berhasil ditandai.']);
        exit;
    }

    if ($action === 'delete') {
        $notificationId = isset($input['notification_id']) ? (int) $input['notification_id'] : 0;

        if ($notificationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid.']);
            exit;
        }

        $deleteSql = 'DELETE FROM notifications WHERE id = ? AND user_id = ?';
        $deleteStmt = $conn->prepare($deleteSql);
        if (!$deleteStmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus notifikasi.']);
            exit;
        }

        $deleteStmt->bind_param('ii', $notificationId, $userId);
        if (!$deleteStmt->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus notifikasi.']);
            $deleteStmt->close();
            exit;
        }
        $deleteStmt->close();

        echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil dihapus.']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method atau action tidak didukung.']);
exit;
