-- Add is_superadmin column to remember_tokens table
ALTER TABLE remember_tokens 
ADD COLUMN is_superadmin BOOLEAN DEFAULT FALSE 
AFTER expires_at; 