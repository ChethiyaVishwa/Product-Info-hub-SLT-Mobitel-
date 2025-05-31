-- Update superadmin password to 'Admin@123' with proper PHP password_hash
UPDATE superadmins 
SET password = '$2y$10$YourNewSecureHashHere123'
WHERE email = 'admin@productinfohub.com'; 