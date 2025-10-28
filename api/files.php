<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_auth();

$payload = $_POST;
if (empty($payload) && ($input = file_get_contents('php://input'))) {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$csrf = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz güvenlik belirteci.']);
    exit;
}

$action = $payload['action'] ?? 'list';
$user = current_user();
$isAdmin = is_admin();

try {
    switch ($action) {
        case 'list':
            $folderId = isset($payload['folder_id']) ? (int) $payload['folder_id'] : null;
            if ($folderId !== null && $folderId <= 0) {
                $folderId = null;
            }
            $page = isset($payload['page']) ? (int) $payload['page'] : 1;
            $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : null;
            $sort = $payload['sort'] ?? 'name';
            $direction = $payload['direction'] ?? 'asc';
            $package = package_for_user($pdo, (int) $user['id']);
            $data = list_folder_contents($pdo, $user, $folderId, $isAdmin, [
                'page' => $page,
                'per_page' => $perPage,
                'sort' => $sort,
                'direction' => $direction,
            ]);
            $usage = user_storage_usage($pdo, (int) $user['id']);
            $allowedMimes = allowed_mime_types($pdo, $package['id'] ?? null);
            $shareMinutes = share_expiry_minutes($pdo);
            $folders = array_map(static function (array $folder) use ($pdo): array {
                return [
                    'id' => (int) $folder['id'],
                    'name' => $folder['name'],
                    'parent_id' => $folder['parent_id'] ? (int) $folder['parent_id'] : null,
                    'is_public' => (int) $folder['is_public'],
                    'is_protected' => (int) $folder['is_protected'],
                    'file_count' => (int) ($folder['file_count'] ?? 0),
                    'updated_at' => $folder['updated_at'] ?? null,
                ];
            }, $data['folders']);
            $files = array_map(static function (array $file): array {
                return [
                    'id' => (int) $file['id'],
                    'filename' => $file['filename'],
                    'size' => (int) $file['size'],
                    'type' => $file['type'],
                    'uploaded_at' => $file['uploaded_at'],
                    'is_public' => (int) $file['is_public'],
                    'share_token' => $file['share_token'] ?? null,
                    'share_expires_at' => $file['share_expires_at'] ?? null,
                    'owner_name' => $file['owner_name'] ?? null,
                    'folder_id' => isset($file['folder_id']) ? ($file['folder_id'] !== null ? (int) $file['folder_id'] : null) : null,
                ];
            }, $data['files']);
            echo json_encode([
                'status' => 'success',
                'folder' => $data['current'] ? [
                    'id' => (int) $data['current']['id'],
                    'name' => $data['current']['name'],
                    'is_public' => (int) $data['current']['is_public'],
                    'is_protected' => (int) $data['current']['is_protected'],
                    'parent_id' => $data['current']['parent_id'] ? (int) $data['current']['parent_id'] : null,
                ] : [
                    'id' => null,
                    'name' => 'Ana Depo',
                    'is_public' => 0,
                    'is_protected' => 0,
                    'parent_id' => null,
                ],
                'breadcrumbs' => $data['breadcrumbs'],
                'folders' => $folders,
                'files' => $files,
                'limits' => [
                    'storage_used' => (int) $usage['total_size'],
                    'storage_formatted' => format_bytes((int) $usage['total_size']),
                    'storage_total' => $package ? (int) $package['storage_limit'] : null,
                    'max_concurrent_uploads' => $package ? (int) $package['max_concurrent_uploads'] : 3,
                    'allowed_mime_types' => $allowedMimes,
                    'total_files' => (int) $usage['total_files'],
                    'package_name' => $package['name'] ?? null,
                ],
                'settings' => [
                    'public_sharing' => public_sharing_allowed($pdo),
                    'folder_passwords' => folder_passwords_allowed($pdo),
                    'share_expiry_minutes' => $shareMinutes,
                ],
                'all_folders' => list_user_folders($pdo, $user, $isAdmin),
                'pagination' => $data['pagination'],
            ]);
            break;

        case 'create-folder':
            $name = trim((string) ($payload['name'] ?? ''));
            if (mb_strlen($name) < 2) {
                throw new RuntimeException('Klasör adı en az 2 karakter olmalı.');
            }
            $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
            if ($parentId && !$isAdmin) {
                $parent = fetch_folder($pdo, $parentId);
                if (!$parent || (int) $parent['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Geçersiz üst klasör.');
                }
            }
            $password = trim((string) ($payload['password'] ?? ''));
            $isProtected = 0;
            $passwordHash = null;
            if ($password !== '') {
                if (!folder_passwords_allowed($pdo)) {
                    throw new RuntimeException('Klasör şifreleme devre dışı.');
                }
                $isProtected = 1;
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }
            $stmt = $pdo->prepare('INSERT INTO folders (user_id, parent_id, name, is_protected, password_hash, created_at, updated_at) VALUES (:user_id, :parent_id, :name, :is_protected, :password_hash, NOW(), NOW())');
            $stmt->execute([
                ':user_id' => $isAdmin && isset($payload['user_id']) ? (int) $payload['user_id'] : $user['id'],
                ':parent_id' => $parentId ?: null,
                ':name' => $name,
                ':is_protected' => $isProtected,
                ':password_hash' => $passwordHash,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Klasör oluşturuldu.']);
            break;

        case 'rename-folder':
            $folderId = (int) ($payload['folder_id'] ?? 0);
            $name = trim((string) ($payload['name'] ?? ''));
            if ($folderId <= 0 || mb_strlen($name) < 2) {
                throw new RuntimeException('Geçersiz klasör.');
            }
            $folder = fetch_folder($pdo, $folderId);
            if (!$folder) {
                throw new RuntimeException('Klasör bulunamadı.');
            }
            if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu klasörü düzenleyemezsiniz.');
            }
            $pdo->prepare('UPDATE folders SET name = :name, updated_at = NOW() WHERE id = :id')->execute([
                ':name' => $name,
                ':id' => $folderId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Klasör güncellendi.']);
            break;

        case 'move-folder':
            $folderId = (int) ($payload['folder_id'] ?? 0);
            $targetId = isset($payload['target_id']) ? (int) $payload['target_id'] : null;
            if ($folderId <= 0) {
                throw new RuntimeException('Geçersiz klasör.');
            }
            if ($targetId === $folderId) {
                throw new RuntimeException('Klasör kendisine taşınamaz.');
            }
            $folder = fetch_folder($pdo, $folderId);
            if (!$folder) {
                throw new RuntimeException('Klasör bulunamadı.');
            }
            if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu klasörü taşıyamazsınız.');
            }
            if ($targetId) {
                $target = fetch_folder($pdo, $targetId);
                if (!$target) {
                    throw new RuntimeException('Hedef klasör bulunamadı.');
                }
                if (!$isAdmin && (int) $target['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Hedef klasör size ait değil.');
                }
                if (folder_is_descendant($pdo, $folderId, $targetId)) {
                    throw new RuntimeException('Klasör altına taşınamaz.');
                }
            }
            $pdo->prepare('UPDATE folders SET parent_id = :parent_id, updated_at = NOW() WHERE id = :id')->execute([
                ':parent_id' => $targetId ?: null,
                ':id' => $folderId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Klasör taşındı.']);
            break;

        case 'delete-folder':
            $folderId = (int) ($payload['folder_id'] ?? 0);
            if ($folderId <= 0) {
                throw new RuntimeException('Geçersiz klasör.');
            }
            $folder = fetch_folder($pdo, $folderId);
            if (!$folder) {
                throw new RuntimeException('Klasör bulunamadı.');
            }
            if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu klasörü silemezsiniz.');
            }
            delete_folder_recursive($pdo, $folderId);
            echo json_encode(['status' => 'success', 'message' => 'Klasör silindi.']);
            break;

        case 'bulk-delete':
            $fileIds = array_filter(array_map('intval', (array) ($payload['file_ids'] ?? [])));
            $folderIds = array_filter(array_map('intval', (array) ($payload['folder_ids'] ?? [])));
            if (empty($fileIds) && empty($folderIds)) {
                throw new RuntimeException('Silinecek öğe seçilmedi.');
            }
            $deletedFiles = 0;
            $deletedFolders = 0;
            foreach ($folderIds as $folderId) {
                $folder = fetch_folder($pdo, $folderId);
                if (!$folder) {
                    continue;
                }
                if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                    continue;
                }
                delete_folder_recursive($pdo, $folderId);
                $deletedFolders++;
            }
            foreach ($fileIds as $fileId) {
                $file = fetch_file($pdo, $fileId);
                if (!$file) {
                    continue;
                }
                if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                    continue;
                }
                delete_file_record($pdo, $file);
                $deletedFiles++;
            }
            if ($deletedFiles === 0 && $deletedFolders === 0) {
                throw new RuntimeException('Silinecek öğe bulunamadı.');
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'Seçilen öğeler silindi.',
                'summary' => [
                    'files' => $deletedFiles,
                    'folders' => $deletedFolders,
                ],
            ]);
            break;

        case 'set-folder-password':
            if (!folder_passwords_allowed($pdo)) {
                throw new RuntimeException('Klasör şifreleme devre dışı.');
            }
            $folderId = (int) ($payload['folder_id'] ?? 0);
            $password = trim((string) ($payload['password'] ?? ''));
            $folder = fetch_folder($pdo, $folderId);
            if (!$folder) {
                throw new RuntimeException('Klasör bulunamadı.');
            }
            if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu klasörü güncelleyemezsiniz.');
            }
            $isProtected = $password !== '' ? 1 : 0;
            $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
            $stmt = $pdo->prepare('UPDATE folders SET is_protected = :protected, password_hash = :password, updated_at = NOW() WHERE id = :id');
            $stmt->bindValue(':protected', $isProtected, PDO::PARAM_INT);
            if ($passwordHash === null) {
                $stmt->bindValue(':password', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $folderId, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Klasör güvenliği güncellendi.']);
            break;

        case 'toggle-folder-public':
            $folderId = (int) ($payload['folder_id'] ?? 0);
            $folder = fetch_folder($pdo, $folderId);
            if (!$folder) {
                throw new RuntimeException('Klasör bulunamadı.');
            }
            if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu klasörü güncelleyemezsiniz.');
            }
            $isPublic = !empty($payload['is_public']) ? 1 : 0;
            $pdo->prepare('UPDATE folders SET is_public = :public, updated_at = NOW() WHERE id = :id')->execute([
                ':public' => $isPublic,
                ':id' => $folderId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Klasör görünürlüğü güncellendi.']);
            break;

        case 'rename-file':
        case 'rename':
            $fileId = (int) ($payload['file_id'] ?? 0);
            $newName = trim((string) ($payload['filename'] ?? ''));
            if ($fileId <= 0 || mb_strlen($newName) < 3) {
                throw new RuntimeException('Geçersiz dosya.');
            }
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Dosyayı düzenleme yetkiniz yok.');
            }
            $pdo->prepare('UPDATE files SET filename = :name WHERE id = :id')->execute([
                ':name' => $newName,
                ':id' => $fileId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Dosya adı güncellendi.']);
            break;

        case 'move-file':
            $fileId = (int) ($payload['file_id'] ?? 0);
            $targetId = isset($payload['target_id']) ? (int) $payload['target_id'] : null;
            if ($fileId <= 0) {
                throw new RuntimeException('Geçersiz dosya.');
            }
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Dosyayı taşıma yetkiniz yok.');
            }
            if ($targetId) {
                $target = fetch_folder($pdo, $targetId);
                if (!$target) {
                    throw new RuntimeException('Hedef klasör bulunamadı.');
                }
                if (!$isAdmin && (int) $target['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Hedef klasör size ait değil.');
                }
            }
            $pdo->prepare('UPDATE files SET folder_id = :folder_id WHERE id = :id')->execute([
                ':folder_id' => $targetId ?: null,
                ':id' => $fileId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Dosya taşındı.']);
            break;

        case 'delete-file':
        case 'delete':
            $fileId = (int) ($payload['file_id'] ?? 0);
            if ($fileId <= 0) {
                throw new RuntimeException('Geçersiz dosya.');
            }
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Dosyayı silme yetkiniz yok.');
            }
            delete_file_record($pdo, $file);
            echo json_encode(['status' => 'success', 'message' => 'Dosya silindi.', 'removeSelector' => '#file-' . $fileId]);
            break;

        case 'share-file':
            if (!public_sharing_allowed($pdo)) {
                throw new RuntimeException('Paylaşım devre dışı.');
            }
            $fileId = (int) ($payload['file_id'] ?? 0);
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Dosyayı paylaşma yetkiniz yok.');
            }
            $share = share_file($pdo, $file, share_expiry_minutes($pdo));
            echo json_encode(['status' => 'success', 'message' => 'Paylaşım bağlantısı hazır.', 'share' => $share]);
            break;

        case 'revoke-share':
            $fileId = (int) ($payload['file_id'] ?? 0);
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Paylaşımı kaldırma yetkiniz yok.');
            }
            revoke_share($pdo, $fileId);
            echo json_encode(['status' => 'success', 'message' => 'Paylaşım kapatıldı.']);
            break;

        case 'toggle-file-visibility':
            $fileId = (int) ($payload['file_id'] ?? 0);
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Dosyayı güncelleme yetkiniz yok.');
            }
            $isPublic = !empty($payload['is_public']) ? 1 : 0;
            $pdo->prepare('UPDATE files SET is_public = :public WHERE id = :id')->execute([
                ':public' => $isPublic,
                ':id' => $fileId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Dosya görünürlüğü güncellendi.']);
            break;

        case 'zip-files':
            $fileIds = array_filter(array_map('intval', (array) ($payload['file_ids'] ?? [])));
            if (empty($fileIds)) {
                throw new RuntimeException('Zip oluşturmak için dosya seçin.');
            }
            $contextFolderId = isset($payload['context_folder_id']) && $payload['context_folder_id'] !== ''
                ? (int) $payload['context_folder_id']
                : null;
            $archive = create_files_archive($pdo, $fileIds, $user, $isAdmin, $contextFolderId);
            echo json_encode(['status' => 'success', 'message' => 'Zip dosyası oluşturuldu.', 'archive' => $archive]);
            break;

        case 'zip-selection':
            $fileIds = array_filter(array_map('intval', (array) ($payload['file_ids'] ?? [])));
            $folderIds = array_filter(array_map('intval', (array) ($payload['folder_ids'] ?? [])));
            if (empty($fileIds) && empty($folderIds)) {
                throw new RuntimeException('Zip oluşturmak için öğe seçin.');
            }
            $contextFolderId = isset($payload['context_folder_id']) && $payload['context_folder_id'] !== ''
                ? (int) $payload['context_folder_id']
                : null;
            $archive = create_selection_archive($pdo, $fileIds, $folderIds, $user, $isAdmin, $contextFolderId);
            echo json_encode(['status' => 'success', 'message' => 'Zip dosyası hazırlandı.', 'archive' => $archive]);
            break;

        case 'move-selection':
            $fileIds = array_filter(array_map('intval', (array) ($payload['file_ids'] ?? [])));
            $folderIds = array_filter(array_map('intval', (array) ($payload['folder_ids'] ?? [])));
            $targetId = isset($payload['target_id']) && $payload['target_id'] !== '' ? (int) $payload['target_id'] : null;
            if (empty($fileIds) && empty($folderIds)) {
                throw new RuntimeException('Taşınacak öğe seçilmedi.');
            }
            $targetFolder = null;
            if ($targetId) {
                $targetFolder = fetch_folder($pdo, $targetId);
                if (!$targetFolder) {
                    throw new RuntimeException('Hedef klasör bulunamadı.');
                }
                if (!$isAdmin && (int) $targetFolder['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Hedef klasör size ait değil.');
                }
            }

            foreach ($folderIds as $folderId) {
                $folder = fetch_folder($pdo, $folderId);
                if (!$folder) {
                    continue;
                }
                if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Size ait olmayan klasörü taşıyamazsınız.');
                }
                if ($targetFolder && folder_is_descendant($pdo, (int) $folder['id'], $targetFolder['id'])) {
                    throw new RuntimeException('Bir klasörü kendi altına taşıyamazsınız.');
                }
            }

            foreach ($folderIds as $folderId) {
                $folder = fetch_folder($pdo, $folderId);
                if (!$folder) {
                    continue;
                }
                $pdo->prepare('UPDATE folders SET parent_id = :parent WHERE id = :id')->execute([
                    ':parent' => $targetId ?: null,
                    ':id' => $folderId,
                ]);
            }

            foreach ($fileIds as $fileId) {
                $file = fetch_file($pdo, $fileId);
                if (!$file) {
                    continue;
                }
                if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
                    throw new RuntimeException('Size ait olmayan dosya taşınamaz.');
                }
                $pdo->prepare('UPDATE files SET folder_id = :folder WHERE id = :id')->execute([
                    ':folder' => $targetId ?: null,
                    ':id' => $fileId,
                ]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Seçilen öğeler taşındı.']);
            break;

        case 'zip-folder':
            $folderId = (int) ($payload['folder_id'] ?? 0);
            if ($folderId <= 0) {
                throw new RuntimeException('Geçersiz klasör.');
            }
            $archive = create_folder_archive($pdo, $folderId, $user, $isAdmin);
            echo json_encode(['status' => 'success', 'message' => 'Arşiv hazır.', 'archive' => $archive]);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
