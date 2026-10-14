<?php
function load_env_file(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;
    $path = __DIR__ . '/.env';
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) putenv($key . '=' . $value);
    }
}
function env_value(string $name, ?string $default = null): ?string { load_env_file(); $value = getenv($name); return $value === false ? $default : $value; }
function db_server(): PDO {
    $host = env_value('DB_HOST', '127.0.0.1');
    $port = env_value('DB_PORT', '3306');
    $user = env_value('DB_USER', '');
    $password = env_value('DB_PASSWORD', '');
    if (!$host || !$user) throw new RuntimeException('Set DB_HOST and DB_USER in .env first.');
    return new PDO('mysql:host=' . $host . ';port=' . (int)$port . ';charset=utf8mb4', $user, $password, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
}
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $name = env_value('DB_NAME', '');
    if (!$name || !preg_match('/^[A-Za-z0-9_]+$/', $name)) throw new RuntimeException('Set a valid DB_NAME in .env first.');
    $host = env_value('DB_HOST', '127.0.0.1');
    $port = env_value('DB_PORT', '3306');
    $pdo = new PDO('mysql:host=' . $host . ';port=' . (int)$port . ';dbname=' . $name . ';charset=utf8mb4', env_value('DB_USER', ''), env_value('DB_PASSWORD', ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function ensure_schema(PDO $pdo): void {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $pdo->exec($statement);
    }
    ensure_room_public_ids($pdo);
    ensure_saint_martin_table($pdo);
}
function ensure_saint_martin_table(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS saint_martin_requests (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(190) NOT NULL,
      whatsapp VARCHAR(60) NOT NULL,
      email VARCHAR(190) NOT NULL,
      members_count VARCHAR(100) NOT NULL,
      service_needed VARCHAR(100) NOT NULL,
      need_ship_ticket TINYINT(1) NOT NULL DEFAULT 0,
      need_hotel TINYINT(1) NOT NULL DEFAULT 0,
      approx_travel_date VARCHAR(60) NOT NULL,
      notes TEXT DEFAULT NULL,
      extra_requests TEXT DEFAULT NULL,
      status VARCHAR(40) NOT NULL DEFAULT 'new',
      admin_notes TEXT DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_sm_status (status),
      INDEX idx_sm_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $hasExtra = $pdo->query("SHOW COLUMNS FROM saint_martin_requests LIKE 'extra_requests'")->fetch();
    if (!$hasExtra) {
        $pdo->exec("ALTER TABLE saint_martin_requests ADD COLUMN extra_requests TEXT DEFAULT NULL AFTER notes");
    }
    $ready = true;
}
function generate_room_public_id(PDO $pdo): string {
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    do {
        $id = '';
        for ($i = 0; $i < 14; $i++) $id .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $stmt = $pdo->prepare('SELECT 1 FROM rooms WHERE public_id=? LIMIT 1'); $stmt->execute([$id]);
    } while ($stmt->fetchColumn());
    return $id;
}
function ensure_room_public_ids(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;
    $column = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'public_id'")->fetch(PDO::FETCH_ASSOC);
    if (!$column) {
        $pdo->exec('ALTER TABLE rooms ADD public_id CHAR(14) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL AFTER id');
        $column = ['Null' => 'YES'];
    }
    $stmt = $pdo->query("SHOW INDEX FROM rooms WHERE Key_name='uq_rooms_public_id'");
    $hasUniqueIndex = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$hasUniqueIndex) {
        $stmt = $pdo->prepare('SELECT id FROM rooms WHERE public_id IS NULL OR public_id=\'\'');
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $roomId) {
            $id = generate_room_public_id($pdo);
            $update = $pdo->prepare('UPDATE rooms SET public_id=? WHERE id=?'); $update->execute([$id, $roomId]);
        }
        $pdo->exec('ALTER TABLE rooms ADD UNIQUE KEY uq_rooms_public_id (public_id)');
    } elseif (($column['Null'] ?? 'NO') === 'YES') {
        $stmt = $pdo->prepare('SELECT id FROM rooms WHERE public_id IS NULL OR public_id=\'\''); $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $roomId) {
            $id = generate_room_public_id($pdo);
            $update = $pdo->prepare('UPDATE rooms SET public_id=? WHERE id=?'); $update->execute([$id, $roomId]);
        }
    }
    $column = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'public_id'")->fetch(PDO::FETCH_ASSOC);
    if (($column['Null'] ?? 'NO') === 'YES') $pdo->exec('ALTER TABLE rooms MODIFY public_id CHAR(14) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
    $ready = true;
}
function e(string|int|null $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_POST['csrf_token'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf_token'])) { http_response_code(419); exit('Your form expired. Please go back and try again.'); }
}
function admin_required(): void { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); if (empty($_SESSION['admin_id'])) { header('Location: /admin/'); exit; } }
function upload_image(array $file, string $folder): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed. Please try another file.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('Please use an image smaller than 8 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
    if (!$mime || !str_starts_with($mime, 'image/') || in_array($mime, ['image/svg+xml','image/svg'], true)) throw new RuntimeException('Upload a valid raster image.');
    $relative = 'uploads/' . trim($folder, '/') . '/' . bin2hex(random_bytes(16)) . '.webp';
    $destination = __DIR__ . '/' . $relative;
    if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true) && !is_dir(dirname($destination))) throw new RuntimeException('Could not prepare the image folder.');
    $saved = false; $bytes = file_get_contents($file['tmp_name']);
    if ($bytes !== false && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $source = @imagecreatefromstring($bytes);
        if ($source) {
            $width = imagesx($source); $height = imagesy($source);
            if ($width < 1 || $height < 1 || $width * $height > 40000000) { imagedestroy($source); throw new RuntimeException('This image is too large to process.'); }
            $maxDimension = 2400;
            if ($width > $maxDimension || $height > $maxDimension) {
                $scale = min($maxDimension / $width, $maxDimension / $height);
                $resized = imagecreatetruecolor((int)round($width * $scale), (int)round($height * $scale));
                imagealphablending($resized, false); imagesavealpha($resized, true);
                imagecopyresampled($resized, $source, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
                imagedestroy($source); $source = $resized;
            }
            $saved = imagewebp($source, $destination, 82); imagedestroy($source);
        }
    }
    if (!$saved && class_exists('Imagick')) {
        try {
            $image = new Imagick(); $image->readImage($file['tmp_name']); $image->setIteratorIndex(0); $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            $width = $image->getImageWidth(); $height = $image->getImageHeight();
            if ($width < 1 || $height < 1 || $width * $height > 40000000) { $image->clear(); throw new RuntimeException('This image is too large to process.'); }
            $image->thumbnailImage(2400, 2400, true, true); $image->setImageFormat('webp'); $image->setImageCompressionQuality(82); $image->stripImage();
            $saved = $image->writeImage($destination); $image->clear();
        } catch (RuntimeException $e) { throw $e; } catch (Throwable) { $saved = false; }
    }
    if (!$saved) { if (is_file($destination)) @unlink($destination); throw new RuntimeException('Could not convert this image to WebP.'); }
    return $relative;
}
function delete_uploaded_image(?string $relative): void {
    if (!$relative || str_contains($relative, "\0")) return;
    $uploadsRoot = realpath(__DIR__ . '/uploads');
    $file = realpath(__DIR__ . '/' . ltrim($relative, '/'));
    if ($uploadsRoot && $file && str_starts_with($file, $uploadsRoot . DIRECTORY_SEPARATOR) && is_file($file)) @unlink($file);
}
function room_image_paths(PDO $pdo, int $roomId): array {
    $stmt = $pdo->prepare('SELECT cover_path FROM rooms WHERE id=?'); $stmt->execute([$roomId]);
    $paths = [$stmt->fetchColumn() ?: null];
    $stmt = $pdo->prepare('SELECT image_path FROM room_images WHERE room_id=?'); $stmt->execute([$roomId]);
    return array_values(array_filter(array_merge($paths, $stmt->fetchAll(PDO::FETCH_COLUMN))));
}
function admin_header(string $title): void {
    $path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/admin/', '/') ?: '/';
    $links = [['/admin/','Overview'],['/admin/requests.php','Requests'],['/admin/saint-martin.php','Saint Martin'],['/admin/hotels.php','Hotels'],['/admin/categories.php','Categories'],['/admin/rooms.php','Rooms']];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . ' — Cox Booking Admin</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/admin/admin.css"></head><body><header class="admin-top"><a class="admin-brand" href="/admin/" aria-label="Cox Booking admin overview"><span class="admin-brand-mark">c.</span><span class="admin-brand-name">coxbooking</span><span class="admin-brand-tag">ADMIN</span></a><nav aria-label="Admin navigation">';
    foreach ($links as [$href, $label]) { $active = $path === (rtrim($href, '/') ?: '/') || ($href === '/admin/' && $path === '/admin/index.php'); echo '<a href="' . $href . '"' . ($active ? ' class="active" aria-current="page"' : '') . '>' . $label . '</a>'; }
    echo '</nav><a class="admin-signout" href="/admin/logout.php">Sign out <span aria-hidden="true">↗</span></a></header><main class="admin-main"><div class="admin-page-heading"><div><p class="admin-kicker">COX BOOKING · ADMIN</p><h1>' . e($title) . '</h1></div><a class="admin-view-site" href="/" target="_blank" rel="noopener">View website <span aria-hidden="true">↗</span></a></div>';
}
function admin_footer(): void { echo '</main></body></html>'; }
