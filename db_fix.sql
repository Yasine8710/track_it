ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL;
ALTER TABLE categories MODIFY COLUMN user_id INT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS type ENUM('income', 'expense') NOT NULL DEFAULT 'expense';
UPDATE transactions SET type = 'inflow' WHERE type = 'income';
UPDATE transactions SET type = 'outflow' WHERE type = 'expense';
ALTER TABLE transactions MODIFY COLUMN type ENUM('inflow', 'outflow') NOT NULL DEFAULT 'inflow';
