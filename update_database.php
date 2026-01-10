<?php
require_once 'includes/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Starting database update...\n";

    // 1. Modify users table
    echo "Modifying 'users' table...\n";
    $alter_users_table_sql = "
        ALTER TABLE `users`
        CHANGE COLUMN `status` `role` ENUM('admin', 'prorektor', 'kafedra', 'muellim', 'student') NOT NULL DEFAULT 'student',
        ADD COLUMN `subject_id` INT(11) NULL DEFAULT NULL,
        ADD CONSTRAINT `fk_users_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id_subject`) ON DELETE SET NULL;
    ";
    $db->exec($alter_users_table_sql);
    echo "'users' table modified successfully.\n";

    // 2. Create exam_results_archive table
    echo "Creating 'exam_results_archive' table...\n";
    $create_archive_table_sql = "
        CREATE TABLE `exam_results_archive` (
          `id_archive` INT(11) NOT NULL AUTO_INCREMENT,
          `exam_id` INT(11) NOT NULL,
          `user_id` INT(11) NOT NULL,
          `score_earned` DECIMAL(5,2) NOT NULL,
          `archived_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `archived_by_user_id` INT(11) NULL,
          PRIMARY KEY (`id_archive`),
          KEY `idx_archive_exam` (`exam_id`),
          KEY `idx_archive_user` (`user_id`),
          KEY `idx_archived_by` (`archived_by_user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
    ";
    $db->exec($create_archive_table_sql);
    echo "'exam_results_archive' table created successfully.\n";

    // 3. (Optional but good practice) Add foreign key for archived_by_user_id
    echo "Adding foreign key to 'exam_results_archive'...\n";
    $add_fk_sql = "
        ALTER TABLE `exam_results_archive`
        ADD CONSTRAINT `fk_archived_by_user` FOREIGN KEY (`archived_by_user_id`) REFERENCES `users`(`id_users`) ON DELETE SET NULL;
    ";
    $db->exec($add_fk_sql);
    echo "Foreign key added successfully.\n";

    echo "Database update completed!\n";

} catch (PDOException $e) {
    die("Database update failed: " . $e->getMessage() . "\n");
}
?>
