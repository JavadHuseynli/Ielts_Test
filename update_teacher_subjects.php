<?php
/**
 * Database Update Script - Add Teacher Subjects Functionality
 * This script creates the teacher_subjects junction table
 */

require_once "includes/db.php";

$database = new Database();
$db = $database->getConnection();

try {
    echo "Starting database update...\n<br>";

    // Create teacher_subjects junction table
    $query = "CREATE TABLE IF NOT EXISTS `teacher_subjects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `teacher_id` INT NOT NULL,
        `subject_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id_users`) ON DELETE CASCADE,
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id_subject`) ON DELETE CASCADE,
        UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `subject_id`),
        INDEX `idx_teacher` (`teacher_id`),
        INDEX `idx_subject` (`subject_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

    $db->exec($query);
    echo "✓ teacher_subjects table created successfully\n<br>";

    // Migrate existing subject_id data from users table to teacher_subjects (if column exists)
    $check_column = "SHOW COLUMNS FROM `users` LIKE 'subject_id'";
    $stmt = $db->query($check_column);

    if ($stmt->rowCount() > 0) {
        echo "Found existing subject_id column, migrating data...\n<br>";

        // Migrate existing data
        $migrate_query = "INSERT IGNORE INTO `teacher_subjects` (`teacher_id`, `subject_id`)
                         SELECT `id_users`, `subject_id`
                         FROM `users`
                         WHERE `status` = 'muellim' AND `subject_id` IS NOT NULL";
        $db->exec($migrate_query);
        echo "✓ Migrated existing teacher-subject relationships\n<br>";

        // Drop the old subject_id column
        $drop_query = "ALTER TABLE `users` DROP COLUMN `subject_id`";
        $db->exec($drop_query);
        echo "✓ Removed old subject_id column from users table\n<br>";
    } else {
        echo "No subject_id column found in users table (already migrated or fresh install)\n<br>";
    }

    echo "\n<br><strong>Database update completed successfully!</strong>\n<br>";
    echo "<a href='admin/users.php'>Go to Users Management</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n<br>";
    die();
}
?>
