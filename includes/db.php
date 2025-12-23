<?php
class Database {
    private $host = "localhost";
    private $db_name = "edu_system";
    private $username = "root";
    private $password = "23234455";
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            // MySQL bağlantısını yaratmadan öncə serverin əlçatan olmasını yoxlayırıq
            $socket = @fsockopen($this->host, 3306, $errno, $errstr, 5);
            if (!$socket) {
                throw new PDOException("MySQL serverinə qoşulmaq mümkün olmadı: $errstr ($errno)");
            }
            fclose($socket);
            
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return $this->conn;
        } catch(PDOException $exception) {
            echo "Verilənlər bazası xətası: " . $exception->getMessage();
            return null;
        }
    }
    
    // Verilənlər bazasının mövcudluğunu yoxlamaq üçün metod
    public function checkDatabaseExists() {
        try {
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $conn->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db_name");
            $stmt->bindParam(":db_name", $this->db_name);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $exception) {
            echo "Verilənlər bazası yoxlama xətası: " . $exception->getMessage();
            return false;
        }
    }
    
    // Verilənlər bazasını qurmaq üçün metod
    public function setupDatabase() {
        try {
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verilənlər bazasını yarat
            $conn->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
            
            // Verilənlər bazasını seç
            $conn->exec("USE `" . $this->db_name . "`");
            
            // Cədvəlləri yarat
            $queries = [
                // Qruplar cədvəli
                "CREATE TABLE IF NOT EXISTS `student_group` (
                    `id_student_group` INT AUTO_INCREMENT PRIMARY KEY,
                    `group_number` VARCHAR(50) NOT NULL UNIQUE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_group_number (group_number)
                )",
                
                // İstifadəçilər cədvəli
                "CREATE TABLE IF NOT EXISTS `users` (
                    `id_users` INT AUTO_INCREMENT PRIMARY KEY,
                    `f_name` VARCHAR(100) NOT NULL,
                    `group_id` INT NULL,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `status` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (group_id) REFERENCES student_group(id_student_group) ON DELETE SET NULL,
                    INDEX idx_username (username),
                    INDEX idx_status (status),
                    INDEX idx_group (group_id)
                )",
                
                // Admin istifadəçisi əlavə et
                "INSERT INTO `users` (`f_name`, `username`, `password`, `status`, `group_id`) 
                VALUES ('Admin', 'admin', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'admin', NULL)
                ON DUPLICATE KEY UPDATE `id_users` = `id_users`",
                
                // Fənnlər cədvəli
                "CREATE TABLE IF NOT EXISTS `subjects` (
                    `id_subject` INT AUTO_INCREMENT PRIMARY KEY,
                    `subjectname` VARCHAR(100) NOT NULL,
                    `timer` INT NOT NULL COMMENT 'Time in minutes',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_subjectname (subjectname)
                )",
                
                // Sual növləri cədvəli
                "CREATE TABLE IF NOT EXISTS `question_types` (
                    `id_quest_type` INT AUTO_INCREMENT PRIMARY KEY,
                    `quest_type_name` VARCHAR(50) NOT NULL,
                    `question_var` ENUM('multiple', 'open', 'matching') NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_question_var (question_var)
                )",
                
                // Sual tiplərini əlavə et
                "INSERT INTO `question_types` (`quest_type_name`, `question_var`) VALUES 
                ('Çoxseçimli sual', 'multiple'),
                ('Açıq cavab', 'open'),
                ('Uyğunlaşdırma', 'matching')
                ON DUPLICATE KEY UPDATE `id_quest_type` = `id_quest_type`",
                
                // Sual faylları cədvəli
                "CREATE TABLE IF NOT EXISTS `question_files` (
                    `id_read_quest_file` INT AUTO_INCREMENT PRIMARY KEY,
                    `file_type` ENUM('reading', 'listening') NOT NULL,
                    `subject_id` INT NOT NULL,
                    `file_path` VARCHAR(255) NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (subject_id) REFERENCES subjects(id_subject) ON DELETE CASCADE,
                    INDEX idx_file_type (file_type),
                    INDEX idx_subject (subject_id)
                )",
                
                // Suallar cədvəli
                "CREATE TABLE IF NOT EXISTS `question_read` (
                    `id_question_text` INT AUTO_INCREMENT PRIMARY KEY,
                    `id_read_quest_file` INT NOT NULL,
                    `id_question_type` INT NOT NULL,
                    `question_text` TEXT NOT NULL,
                    `question_score` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_read_quest_file) REFERENCES question_files(id_read_quest_file) ON DELETE CASCADE,
                    FOREIGN KEY (id_question_type) REFERENCES question_types(id_quest_type) ON DELETE CASCADE,
                    INDEX idx_quest_file (id_read_quest_file),
                    INDEX idx_quest_type (id_question_type)
                )",
                
                // Çox seçimli suallar cədvəli
                "CREATE TABLE IF NOT EXISTS `multiple_questions` (
                    `id_multiple` INT AUTO_INCREMENT PRIMARY KEY,
                    `id_question_text` INT NOT NULL,
                    `var_a` VARCHAR(255) NOT NULL,
                    `var_b` VARCHAR(255) NOT NULL,
                    `var_c` VARCHAR(255) NOT NULL,
                    `var_d` VARCHAR(255) NOT NULL,
                    `correct_v` ENUM('a', 'b', 'c', 'd') NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_question_text) REFERENCES question_read(id_question_text) ON DELETE CASCADE,
                    INDEX idx_question (id_question_text)
                )",
                
                // Açıq suallar cədvəli
                "CREATE TABLE IF NOT EXISTS `open_questions` (
                    `id_open` INT AUTO_INCREMENT PRIMARY KEY,
                    `id_question_text` INT NOT NULL,
                    `users_v` TEXT NULL,
                    `corr_v` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_question_text) REFERENCES question_read(id_question_text) ON DELETE CASCADE,
                    INDEX idx_question (id_question_text)
                )",
                
                // Uyğunlaşdırma sualları cədvəli
                "CREATE TABLE IF NOT EXISTS `matching_questions` (
                    `id_matching` INT AUTO_INCREMENT PRIMARY KEY,
                    `id_question_text` INT NOT NULL,
                    `variants` TEXT NOT NULL,
                    `corr_variant` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_question_text) REFERENCES question_read(id_question_text) ON DELETE CASCADE,
                    INDEX idx_question (id_question_text)
                )",
                
                // İmtahanlar cədvəli
                "CREATE TABLE IF NOT EXISTS `exams` (
                    `id_exam` INT AUTO_INCREMENT PRIMARY KEY,
                    `date_exam` DATE NOT NULL,
                    `id_subject` INT NOT NULL,
                    `id_student_group` INT NOT NULL,
                    `datetime` DATETIME NOT NULL,
                    `status` ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_subject) REFERENCES subjects(id_subject) ON DELETE CASCADE,
                    FOREIGN KEY (id_student_group) REFERENCES student_group(id_student_group) ON DELETE CASCADE,
                    INDEX idx_subject (id_subject),
                    INDEX idx_group (id_student_group),
                    INDEX idx_date (date_exam)
                )",
                
                // Cavablar cədvəli
                "CREATE TABLE IF NOT EXISTS `answers` (
                    `id_answer` INT AUTO_INCREMENT PRIMARY KEY,
                    `exam_id` INT NOT NULL,
                    `id_questions` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `user_answer` TEXT NOT NULL,
                    `correct_var` TEXT NOT NULL,
                    `is_correct` BOOLEAN GENERATED ALWAYS AS (user_answer = correct_var) STORED,
                    `datetime` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (exam_id) REFERENCES exams(id_exam) ON DELETE CASCADE,
                    FOREIGN KEY (id_questions) REFERENCES question_read(id_question_text) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id_users) ON DELETE CASCADE,
                    INDEX idx_exam (exam_id),
                    INDEX idx_question (id_questions),
                    INDEX idx_user (user_id),
                    INDEX idx_is_correct (is_correct)
                )",
                
                // Nəticələr cədvəli
                "CREATE TABLE IF NOT EXISTS `scores` (
                    `id_score` INT AUTO_INCREMENT PRIMARY KEY,
                    `id_answer` INT NOT NULL,
                    `score` DECIMAL(5,2) NOT NULL,
                    `datetime` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_answer) REFERENCES answers(id_answer) ON DELETE CASCADE,
                    INDEX idx_answer (id_answer)
                )"
            ];
            
            foreach ($queries as $query) {
                $conn->exec($query);
            }
            
            return true;
        } catch(PDOException $exception) {
            echo "Verilənlər bazası qurma xətası: " . $exception->getMessage();
            return false;
        }
    }
}
?>