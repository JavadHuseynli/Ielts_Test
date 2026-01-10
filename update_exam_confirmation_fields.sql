-- Add separate confirmation fields for Dekan and Kafedra
ALTER TABLE exams
ADD COLUMN IF NOT EXISTS confirmed_by_dekan INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS confirmed_at_dekan DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS confirmed_by_kafedra INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS confirmed_at_kafedra DATETIME DEFAULT NULL;

-- Add foreign keys
ALTER TABLE exams
ADD FOREIGN KEY (confirmed_by_dekan) REFERENCES users(id_users) ON DELETE SET NULL,
ADD FOREIGN KEY (confirmed_by_kafedra) REFERENCES users(id_users) ON DELETE SET NULL;
