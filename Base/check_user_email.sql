-- Script pour vérifier si l'utilisateur existe déjà
SELECT 
    id, 
    email, 
    first_name, 
    last_name, 
    role, 
    status,
    created_at
FROM aiolia.users 
WHERE email ILIKE 'fifalianavalea@gmail.com';

-- Vérifier aussi s'il y a une contrainte UNIQUE sur l'email
SELECT 
    conname AS constraint_name,
    contype AS constraint_type,
    a.attname AS column_name
FROM pg_constraint c
JOIN pg_class t ON c.conrelid = t.oid
JOIN pg_namespace n ON t.relnamespace = n.oid
LEFT JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
WHERE t.relname = 'users' 
  AND n.nspname = 'aiolia'
  AND contype IN ('u', 'p')  -- 'u' = UNIQUE, 'p' = PRIMARY KEY
ORDER BY conname, a.attnum;

