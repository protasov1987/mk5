-- Таблица состояния приложения (карты, операции, участки)
CREATE TABLE IF NOT EXISTS app_state (
  id TINYINT UNSIGNED PRIMARY KEY,
  data LONGTEXT NOT NULL
);

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
