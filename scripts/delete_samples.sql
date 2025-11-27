-- SQL Script to delete all sample emails from database
-- WARNING: This will permanently delete data. Make sure to backup first!

-- Show sample users before deletion
SELECT 'Sample users to be deleted:' as info;
SELECT id, name, email, created_at 
FROM users 
WHERE email LIKE '%@slub.ac.id';

-- Show sample employees before deletion
SELECT 'Sample employees to be deleted:' as info;
SELECT e.id, e.full_name, u.email 
FROM employees e 
INNER JOIN users u ON e.user_id = u.id 
WHERE u.email LIKE '%@slub.ac.id';

-- Delete employees first (foreign key constraint)
DELETE e FROM employees e 
INNER JOIN users u ON e.user_id = u.id 
WHERE u.email LIKE '%@slub.ac.id';

-- Delete users
DELETE FROM users WHERE email LIKE '%@slub.ac.id';

-- Show confirmation
SELECT 'Sample emails deleted successfully!' as result;