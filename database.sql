-- Таблица состояния приложения (карты, операции, участки)
CREATE TABLE IF NOT EXISTS app_state (
  id TINYINT UNSIGNED PRIMARY KEY,
  data LONGTEXT NOT NULL
);

-- Кодировка
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- Уровни доступа
CREATE TABLE IF NOT EXISTS access_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  default_tab VARCHAR(32) NOT NULL DEFAULT 'dashboard',
  session_timeout INT NOT NULL DEFAULT 30
);

-- Права доступа по разделам
CREATE TABLE IF NOT EXISTS access_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level_id INT NOT NULL,
  section VARCHAR(64) NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  allow_upload TINYINT(1) NOT NULL DEFAULT 0,
  allow_delete TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_perm (level_id, section),
  CONSTRAINT fk_perm_level FOREIGN KEY (level_id) REFERENCES access_levels(id) ON DELETE CASCADE
);

-- Пользователи
CREATE TABLE IF NOT EXISTS users (
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
);

-- Базовый уровень доступа «Администратор» и полный набор прав
INSERT INTO access_levels (name, description, default_tab, session_timeout)
SELECT 'Администратор', 'Полный доступ', 'dashboard', 30
WHERE NOT EXISTS (SELECT 1 FROM access_levels);

SET @admin_level := (SELECT id FROM access_levels ORDER BY id ASC LIMIT 1);

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'dashboard', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'dashboard');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'cards', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'cards');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'workorders', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'workorders');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'archive', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'archive');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'users', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'users');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'access', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'access');

INSERT INTO access_permissions (level_id, section, can_view, can_edit, allow_upload, allow_delete)
SELECT @admin_level, 'attachments', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM access_permissions WHERE level_id = @admin_level AND section = 'attachments');

-- Встроенный администратор Abyss (пароль «ssyba», bcrypt-хеш соответствует PASSWORD_DEFAULT)
INSERT INTO users (name, password_hash, password_plain, level_id, is_active, is_builtin)
SELECT 'Abyss', '$2y$12$DbTYUDVNMwGDDMPBfFmUuep7rLAIMn4IHuFBUfW/iF0/OmLaxjyZ2', 'ssyba', @admin_level, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE name = 'Abyss');
