<?php
require_once __DIR__ . '/config.php';

const BUILTIN_NAME = 'Abyss';
const BUILTIN_PASSWORD = 'ssyba';

function get_json_payload(): array
{
    static $cached = null;
    if ($cached !== null) return $cached;

    $data = [];
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        } else {
            $parsed = [];
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                $data = $parsed;
            }
        }
    }

    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }

    if (empty($data) && !empty($_REQUEST)) {
        $data = array_merge($data, $_REQUEST);
    }

    $cached = $data;
    return $data;
}

function ensure_auth_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(191) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        default_tab VARCHAR(32) NOT NULL DEFAULT 'dashboard',
        session_timeout INT NOT NULL DEFAULT 30
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS access_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_id INT NOT NULL,
        section VARCHAR(64) NOT NULL,
        can_view TINYINT(1) NOT NULL DEFAULT 0,
        can_edit TINYINT(1) NOT NULL DEFAULT 0,
        allow_upload TINYINT(1) NOT NULL DEFAULT 0,
        allow_delete TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uniq_perm (level_id, section),
        CONSTRAINT fk_perm_level FOREIGN KEY (level_id) REFERENCES access_levels(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(191) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        password_plain VARCHAR(191) NOT NULL,
        level_id INT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_builtin TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uniq_name (name),
        UNIQUE KEY uniq_password (password_plain),
        CONSTRAINT fk_user_level FOREIGN KEY (level_id) REFERENCES access_levels(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function seed_default_admin(PDO $pdo): void
{
    ensure_auth_schema($pdo);

    $count = (int)$pdo->query('SELECT COUNT(*) FROM access_levels')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO access_levels (name, description, default_tab, session_timeout) VALUES (:name, :desc, :tab, :timeout)');
        $stmt->execute([
            'name' => 'Администратор',
            'desc' => 'Полный доступ',
            'tab' => 'dashboard',
            'timeout' => 30,
        ]);
        $levelId = (int)$pdo->lastInsertId();

        $sections = ['dashboard', 'cards', 'workorders', 'archive', 'users', 'access', 'attachments'];
        $permStmt = $pdo->prepare('INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete) VALUES (:level_id, :section, 1, 1, 1, 1)');
        foreach ($sections as $section) {
            $permStmt->execute(['level_id' => $levelId, 'section' => $section]);
        }
    } else {
        $levelId = (int)$pdo->query('SELECT id FROM access_levels ORDER BY id ASC LIMIT 1')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT id, password_plain, level_id, is_active FROM users WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => BUILTIN_NAME]);
    $existing = $stmt->fetch();
    if ($existing) {
        if ($existing['password_plain'] !== BUILTIN_PASSWORD) {
            $update = $pdo->prepare('UPDATE users SET password_plain = :plain, password_hash = :hash WHERE id = :id');
            $update->execute([
                'plain' => BUILTIN_PASSWORD,
                'hash' => password_hash(BUILTIN_PASSWORD, PASSWORD_DEFAULT),
                'id' => $existing['id'],
            ]);
        }
        if ((int)$existing['level_id'] !== $levelId || (int)$existing['is_active'] !== 1) {
            $pdo->prepare('UPDATE users SET level_id = :level_id, is_active = 1 WHERE id = :id')->execute([
                'level_id' => $levelId,
                'id' => $existing['id'],
            ]);
        }
        return;
    }

    $insert = $pdo->prepare('INSERT INTO users (name, password_hash, password_plain, level_id, is_active, is_builtin) VALUES (:name, :hash, :plain, :level, 1, 1)');
    $insert->execute([
        'name' => BUILTIN_NAME,
        'hash' => password_hash(BUILTIN_PASSWORD, PASSWORD_DEFAULT),
        'plain' => BUILTIN_PASSWORD,
        'level' => $levelId,
    ]);
}

function get_permissions(PDO $pdo, ?int $levelId): array
{
    if (!$levelId) return [];
    $stmt = $pdo->prepare('SELECT section, can_view, can_edit, allow_upload, allow_delete FROM access_permissions WHERE level_id = :id');
    $stmt->execute(['id' => $levelId]);
    $perms = [];
    foreach ($stmt as $row) {
        $perms[$row['section']] = [
            'view' => (bool)$row['can_view'],
            'edit' => (bool)$row['can_edit'],
            'upload' => (bool)$row['allow_upload'],
            'delete' => (bool)$row['allow_delete'],
        ];
    }
    return $perms;
}

function load_current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT u.*, l.name as level_name, l.default_tab, l.session_timeout FROM users u LEFT JOIN access_levels l ON l.id = u.level_id WHERE u.id = :id AND u.is_active = 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $timeoutMinutes = max(1, (int)($user['session_timeout'] ?? 30));
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if ($lastActivity && (time() - $lastActivity) > ($timeoutMinutes * 60)) {
        session_unset();
        session_destroy();
        return null;
    }

    $_SESSION['last_activity'] = time();
    $user['permissions'] = get_permissions($pdo, $user['level_id'] ? (int)$user['level_id'] : null);
    return $user;
}

function require_login(PDO $pdo): array
{
    seed_default_admin($pdo);
    $user = load_current_user($pdo);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Требуется авторизация']);
        exit;
    }
    return $user;
}

function require_builtin_admin(array $user): void
{
    if (empty($user['is_builtin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Доступно только администратору Abyss']);
        exit;
    }
}

function ensure_permission(array $user, string $section, string $mode = 'view'): void
{
    $perms = $user['permissions'][$section] ?? null;
    $allowed = false;
    if ($mode === 'view') {
        $allowed = $perms && !empty($perms['view']);
    } else {
        $allowed = $perms && !empty($perms['edit']);
    }
    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['error' => 'Нет прав доступа']);
        exit;
    }
}

function handle_login(PDO $pdo): void
{
    $body = get_json_payload();
    $password = $body['password'] ?? '';
    if (!is_string($password) || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Введите пароль']);
        return;
    }

    seed_default_admin($pdo);

    $stmt = $pdo->prepare('SELECT u.*, l.name as level_name, l.default_tab, l.session_timeout FROM users u LEFT JOIN access_levels l ON l.id = u.level_id WHERE u.password_plain = :pass AND u.is_active = 1 LIMIT 1');
    $stmt->execute(['pass' => $password]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверный пароль']);
        return;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();

    $user['permissions'] = get_permissions($pdo, $user['level_id'] ? (int)$user['level_id'] : null);
    echo json_encode(['user' => sanitize_user($user)]);
}

function sanitize_user(array $user): array
{
    $defaultTab = $user['name'] === BUILTIN_NAME ? 'dashboard' : ($user['default_tab'] ?? 'dashboard');
    return [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'level_id' => $user['level_id'] ? (int)$user['level_id'] : null,
        'level_name' => $user['level_name'] ?? null,
        'default_tab' => $defaultTab,
        'session_timeout' => (int)($user['session_timeout'] ?? 30),
        'permissions' => $user['permissions'] ?? [],
        'is_builtin' => !empty($user['is_builtin'])
    ];
}

function handle_logout(): void
{
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'ok']);
}

function fetch_users(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT u.id, u.name, u.level_id, u.is_active, u.is_builtin, l.name AS level_name FROM users u LEFT JOIN access_levels l ON l.id = u.level_id ORDER BY u.id ASC');
    return $stmt->fetchAll();
}

function fetch_levels(PDO $pdo): array
{
    $levels = $pdo->query('SELECT * FROM access_levels ORDER BY id ASC')->fetchAll();
    foreach ($levels as &$level) {
        $level['permissions'] = get_permissions($pdo, (int)$level['id']);
    }
    return $levels;
}

function save_level(PDO $pdo, array $payload): array
{
    $id = isset($payload['id']) ? (int)$payload['id'] : null;
    $name = trim((string)($payload['name'] ?? ''));
    $desc = trim((string)($payload['description'] ?? ''));
    $defaultTab = $payload['default_tab'] ?? 'dashboard';
    $timeout = max(1, (int)($payload['session_timeout'] ?? 30));
    $perms = $payload['permissions'] ?? [];

    if ($name === '') {
        throw new InvalidArgumentException('Введите название уровня');
    }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE access_levels SET name = :name, description = :desc, default_tab = :tab, session_timeout = :timeout WHERE id = :id');
        $stmt->execute(['name' => $name, 'desc' => $desc, 'tab' => $defaultTab, 'timeout' => $timeout, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO access_levels (name, description, default_tab, session_timeout) VALUES (:name, :desc, :tab, :timeout)');
        $stmt->execute(['name' => $name, 'desc' => $desc, 'tab' => $defaultTab, 'timeout' => $timeout]);
        $id = (int)$pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM access_permissions WHERE level_id = :id')->execute(['id' => $id]);
    $permStmt = $pdo->prepare('INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete) VALUES (:level_id, :section, :view, :edit, :upload, :delete)');
    foreach ($perms as $section => $flags) {
        $permStmt->execute([
            'level_id' => $id,
            'section' => $section,
            'view' => empty($flags['view']) ? 0 : 1,
            'edit' => empty($flags['edit']) ? 0 : 1,
            'upload' => empty($flags['upload']) ? 0 : 1,
            'delete' => empty($flags['delete']) ? 0 : 1,
        ]);
    }

    return ['id' => $id];
}

function validate_password_strength(string $password): void
{
    if (!preg_match('/^[A-Za-z0-9]{6,}$/', $password)) {
        throw new InvalidArgumentException('Пароль должен содержать буквы и цифры, не менее 6 символов.');
    }
}

function save_user(PDO $pdo, array $payload, array $actor): array
{
    $id = isset($payload['id']) ? (int)$payload['id'] : null;
    $name = trim((string)($payload['name'] ?? ''));
    $password = isset($payload['password']) ? (string)$payload['password'] : '';
    $levelRaw = $payload['level_id'] ?? null;
    $levelId = ($levelRaw === '' || $levelRaw === null) ? null : (int)$levelRaw;
    $isActive = !empty($payload['is_active']) ? 1 : 0;

    if (empty($actor['is_builtin'])) {
        throw new InvalidArgumentException('Создавать и изменять пользователей может только Abyss');
    }

    if ($name === '') {
        throw new InvalidArgumentException('Введите имя пользователя');
    }

    if ($id === null && $password === '') {
        throw new InvalidArgumentException('Укажите пароль');
    }

    if ($password !== '') {
        validate_password_strength($password);
    }

    if ($password !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE password_plain = :pass AND id <> :id LIMIT 1');
        $stmt->execute(['pass' => $password, 'id' => $id ?? 0]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Пароль уже используется другим пользователем');
        }
    }

    if ($id) {
        $stmt = $pdo->prepare('SELECT is_builtin FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch();
        if (!$existing) throw new InvalidArgumentException('Пользователь не найден');
        $isBuiltin = !empty($existing['is_builtin']);
        if ($isBuiltin && $password !== '') {
            throw new InvalidArgumentException('Пароль администратора изменить нельзя');
        }
        if ($isBuiltin && $name !== BUILTIN_NAME) {
            throw new InvalidArgumentException('Имя встроенного пользователя изменить нельзя');
        }

        if ($isBuiltin) {
            $name = BUILTIN_NAME;
            $isActive = 1;
            $levelId = (int)$pdo->query('SELECT id FROM access_levels ORDER BY id ASC LIMIT 1')->fetchColumn();
        }

        $baseSql = 'UPDATE users SET name = :name, level_id = :level_id, is_active = :active WHERE id = :id';
        $params = ['name' => $name, 'level_id' => $levelId, 'active' => $isActive, 'id' => $id];
        $pdo->prepare($baseSql)->execute($params);

        if ($password !== '') {
            $pdo->prepare('UPDATE users SET password_hash = :hash, password_plain = :plain WHERE id = :id')->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'plain' => $password,
                'id' => $id,
            ]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (name, password_hash, password_plain, level_id, is_active, is_builtin) VALUES (:name, :hash, :plain, :level, :active, 0)');
        $stmt->execute([
            'name' => $name,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'plain' => $password,
            'level' => $levelId,
            'active' => $isActive,
        ]);
        $id = (int)$pdo->lastInsertId();
    }

    return ['id' => $id];
}

function delete_user(PDO $pdo, int $id, array $actor): void
{
    if (empty($actor['is_builtin'])) {
        throw new InvalidArgumentException('Удалять пользователей может только Abyss');
    }
    $stmt = $pdo->prepare('SELECT is_builtin FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new InvalidArgumentException('Пользователь не найден');
    }
    if (!empty($row['is_builtin'])) {
        throw new InvalidArgumentException('Встроенного пользователя удалить нельзя');
    }
    $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
}

seed_default_admin($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($action === 'login' && $method === 'POST') {
        handle_login($pdo);
        exit;
    }

    if ($action === 'logout' && $method === 'POST') {
        handle_logout();
        exit;
    }

    if ($action === 'status') {
        $user = load_current_user($pdo);
        echo json_encode(['user' => $user ? sanitize_user($user) : null]);
        exit;
    }

    $user = require_login($pdo);

    if ($action === 'users' && $method === 'GET') {
        require_builtin_admin($user);
        ensure_permission($user, 'users', 'view');
        echo json_encode(['users' => fetch_users($pdo)]);
        exit;
    }

    if ($action === 'save-user' && $method === 'POST') {
        require_builtin_admin($user);
        ensure_permission($user, 'users', 'edit');
        $payload = get_json_payload();
        $result = save_user($pdo, $payload, $user);
        echo json_encode($result);
        exit;
    }

    if ($action === 'delete-user' && $method === 'POST') {
        require_builtin_admin($user);
        ensure_permission($user, 'users', 'edit');
        $payload = get_json_payload();
        delete_user($pdo, (int)($payload['id'] ?? 0), $user);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($action === 'levels' && $method === 'GET') {
        require_builtin_admin($user);
        ensure_permission($user, 'access', 'view');
        echo json_encode(['levels' => fetch_levels($pdo)]);
        exit;
    }

    if ($action === 'save-level' && $method === 'POST') {
        require_builtin_admin($user);
        ensure_permission($user, 'access', 'edit');
        $payload = get_json_payload();
        $result = save_level($pdo, $payload);
        echo json_encode($result);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Неизвестное действие']);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
