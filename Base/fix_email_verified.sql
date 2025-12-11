-- Corriger l'email vérifié pour l'utilisateur avec billet pour "Music on Sunday"
UPDATE aiolia.users
SET is_email_verified = TRUE
WHERE id = 1; -- L'utilisateur Aina Fanelie

-- Vérification
SELECT 
    id,
    email,
    first_name,
    last_name,
    is_email_verified
FROM aiolia.users
WHERE id = 1;

