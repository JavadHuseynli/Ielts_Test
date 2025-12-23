<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";

$database = new Database();
$db = $database->getConnection();

try {
    // Create archived_questions table
    $query1 = "CREATE TABLE IF NOT EXISTS `archived_questions` (
        `id_archived_question` INT AUTO_INCREMENT PRIMARY KEY,
        `id_question_text` INT NOT NULL,
        `id_read_quest_file` INT NOT NULL,
        `id_question_type` INT NOT NULL,
        `question_text` TEXT NOT NULL,
        `question_score` DECIMAL(5,2) NOT NULL,
        `file_type` ENUM('reading', 'listening') NOT NULL,
        `subject_id` INT NOT NULL,
        `subject_name` VARCHAR(100) NOT NULL,
        `question_type_name` VARCHAR(50) NOT NULL,
        `question_var` ENUM('multiple', 'open', 'matching') NOT NULL,
        `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `archived_by_user_id` INT NOT NULL,
        INDEX idx_question (id_question_text),
        INDEX idx_subject (subject_id),
        INDEX idx_archived_at (archived_at)
    )";
    $db->exec($query1);

    // Create archived_multiple_questions table
    $query2 = "CREATE TABLE IF NOT EXISTS `archived_multiple_questions` (
        `id_archived_multiple` INT AUTO_INCREMENT PRIMARY KEY,
        `id_archived_question` INT NOT NULL,
        `var_a` VARCHAR(255) NOT NULL,
        `var_b` VARCHAR(255) NOT NULL,
        `var_c` VARCHAR(255) NOT NULL,
        `var_d` VARCHAR(255) NOT NULL,
        `correct_v` ENUM('a', 'b', 'c', 'd') NOT NULL,
        FOREIGN KEY (id_archived_question) REFERENCES archived_questions(id_archived_question) ON DELETE CASCADE,
        INDEX idx_archived_question (id_archived_question)
    )";
    $db->exec($query2);

    // Create archived_open_questions table
    $query3 = "CREATE TABLE IF NOT EXISTS `archived_open_questions` (
        `id_archived_open` INT AUTO_INCREMENT PRIMARY KEY,
        `id_archived_question` INT NOT NULL,
        `corr_v` TEXT NOT NULL,
        FOREIGN KEY (id_archived_question) REFERENCES archived_questions(id_archived_question) ON DELETE CASCADE,
        INDEX idx_archived_question (id_archived_question)
    )";
    $db->exec($query3);

    // Create archived_matching_questions table
    $query4 = "CREATE TABLE IF NOT EXISTS `archived_matching_questions` (
        `id_archived_matching` INT AUTO_INCREMENT PRIMARY KEY,
        `id_archived_question` INT NOT NULL,
        `variants` TEXT NOT NULL,
        `corr_variant` TEXT NOT NULL,
        FOREIGN KEY (id_archived_question) REFERENCES archived_questions(id_archived_question) ON DELETE CASCADE,
        INDEX idx_archived_question (id_archived_question)
    )";
    $db->exec($query4);

    echo "Arxiv cədvəlləri uğurla yaradıldı!";
} catch(PDOException $e) {
    echo "Xəta: " . $e->getMessage();
}
?>
