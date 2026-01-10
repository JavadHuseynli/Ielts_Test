-- Dekan rolunu users cədvəlinə əlavə etmək üçün ALTER TABLE sorğusu
ALTER TABLE `users`
MODIFY COLUMN `status` ENUM('admin', 'prorektor', 'dekan', 'kafedra', 'muellim', 'student')
NOT NULL DEFAULT 'student';
