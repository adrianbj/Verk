<?php namespace ProcessWire;

class VerkDB {

    public static function install($db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_sprints` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(255) NOT NULL,
                `status`     ENUM('planned','active','completed') NOT NULL DEFAULT 'planned',
                `start_date` DATE DEFAULT NULL,
                `end_date`   DATE DEFAULT NULL,
                `goal`       TEXT,
                `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_tasks` (
                `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title`        VARCHAR(255) NOT NULL,
                `description`  TEXT,
                `status`       ENUM('open','in_progress','review','done') NOT NULL DEFAULT 'open',
                `priority`     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
                `due_date`     DATE DEFAULT NULL,
                `page_id`      INT UNSIGNED DEFAULT NULL,
                `assignee_id`  INT UNSIGNED DEFAULT NULL,
                `section`      VARCHAR(100) DEFAULT NULL,
                `sprint_id`    INT UNSIGNED DEFAULT NULL,
                `estimate_h`   DECIMAL(5,2) DEFAULT NULL,
                `actual_h`     DECIMAL(5,1) DEFAULT NULL,
                `story_points` TINYINT UNSIGNED DEFAULT NULL,
                `created_by`   INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at`   DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `status_priority` (`status`, `priority`),
                KEY `page_id` (`page_id`),
                KEY `assignee_id` (`assignee_id`),
                KEY `due_date` (`due_date`),
                KEY `sprint_id` (`sprint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_time_logs` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `task_id`     INT UNSIGNED NOT NULL,
                `user_id`     INT UNSIGNED NOT NULL,
                `hours`       DECIMAL(5,1) NOT NULL,
                `note`        VARCHAR(255) DEFAULT '',
                `logged_date` DATE NOT NULL,
                `created_at`  DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `task_id` (`task_id`),
                KEY `user_id` (`user_id`),
                KEY `logged_date` (`logged_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_comments` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `task_id`    INT UNSIGNED NOT NULL,
                `user_id`    INT UNSIGNED NOT NULL,
                `text`       TEXT NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `task_id` (`task_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_notes` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title`      VARCHAR(255) NOT NULL,
                `body`       MEDIUMTEXT,
                `category`   VARCHAR(100) DEFAULT '',
                `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_files` (
                `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity_type`   ENUM('task','note','comment') NOT NULL,
                `entity_id`     INT UNSIGNED NOT NULL,
                `stored_name`   VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `ext`           VARCHAR(16)  NOT NULL,
                `mime`          VARCHAR(127) NOT NULL,
                `size`          INT UNSIGNED NOT NULL DEFAULT 0,
                `width`         SMALLINT UNSIGNED DEFAULT NULL,
                `height`        SMALLINT UNSIGNED DEFAULT NULL,
                `embedded`      TINYINT(1) NOT NULL DEFAULT 0,
                `uploaded_by`   INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at`    DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `entity` (`entity_type`, `entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function uninstall($db, ?string $assetsDir = null): void {
        foreach (['vk_time_logs', 'vk_comments', 'vk_tasks', 'vk_sprints', 'vk_notes', 'vk_files'] as $t) {
            $db->exec("DROP TABLE IF EXISTS `$t`");
        }
        if ($assetsDir && is_dir($assetsDir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($assetsDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
            @rmdir($assetsDir);
        }
    }

    public static function migrate($db): void {
        // vk_tasks columns added in v1.0.1
        $colType = [];
        $stmt = $db->query("SHOW COLUMNS FROM `vk_tasks`");
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) $colType[$r['Field']] = strtolower((string)$r['Type']);
        $cols = array_keys($colType);

        $add = [];
        if (!in_array('sprint_id', $cols))    $add[] = "ADD COLUMN `sprint_id`    INT UNSIGNED DEFAULT NULL AFTER `section`";
        if (!in_array('estimate_h', $cols))   $add[] = "ADD COLUMN `estimate_h`   DECIMAL(5,2) DEFAULT NULL AFTER `sprint_id`";
        if (!in_array('actual_h', $cols))     $add[] = "ADD COLUMN `actual_h`     DECIMAL(5,1) DEFAULT NULL AFTER `estimate_h`";
        if (!in_array('story_points', $cols)) $add[] = "ADD COLUMN `story_points` TINYINT UNSIGNED DEFAULT NULL AFTER `actual_h`";
        if ($add) {
            $db->exec("ALTER TABLE `vk_tasks` " . implode(', ', $add));
            // Ensure indexes exist (may be missing on column-only ALTER from old installs)
            try { $db->exec("ALTER TABLE `vk_tasks` ADD KEY `sprint_id` (`sprint_id`)"); } catch (\Exception $e) {}
            try { $db->exec("ALTER TABLE `vk_tasks` ADD KEY `status_priority` (`status`, `priority`)"); } catch (\Exception $e) {}
        }

        // Widen estimate_h from the original TINYINT to DECIMAL so fractional
        // hours (e.g. 0.25, 0.5) can be stored. Idempotent: only runs if the
        // column isn't already decimal. Existing whole-hour values are kept.
        if (isset($colType['estimate_h']) && strpos($colType['estimate_h'], 'decimal') === false) {
            $db->exec("ALTER TABLE `vk_tasks` MODIFY COLUMN `estimate_h` DECIMAL(5,2) DEFAULT NULL");
        }

        // vk_sprints — create if old install missing it
        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_sprints` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(255) NOT NULL,
                `status`     ENUM('planned','active','completed') NOT NULL DEFAULT 'planned',
                `start_date` DATE DEFAULT NULL,
                `end_date`   DATE DEFAULT NULL,
                `goal`       TEXT,
                `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // vk_time_logs — added in v1.0.2
        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_time_logs` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `task_id`     INT UNSIGNED NOT NULL,
                `user_id`     INT UNSIGNED NOT NULL,
                `hours`       DECIMAL(5,1) NOT NULL,
                `note`        VARCHAR(255) DEFAULT '',
                `logged_date` DATE NOT NULL,
                `created_at`  DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `task_id` (`task_id`),
                KEY `user_id` (`user_id`),
                KEY `logged_date` (`logged_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // vk_files — added for attachments feature
        $db->exec("
            CREATE TABLE IF NOT EXISTS `vk_files` (
                `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entity_type`   ENUM('task','note','comment') NOT NULL,
                `entity_id`     INT UNSIGNED NOT NULL,
                `stored_name`   VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `ext`           VARCHAR(16)  NOT NULL,
                `mime`          VARCHAR(127) NOT NULL,
                `size`          INT UNSIGNED NOT NULL DEFAULT 0,
                `width`         SMALLINT UNSIGNED DEFAULT NULL,
                `height`        SMALLINT UNSIGNED DEFAULT NULL,
                `embedded`      TINYINT(1) NOT NULL DEFAULT 0,
                `uploaded_by`   INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at`    DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `entity` (`entity_type`, `entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
