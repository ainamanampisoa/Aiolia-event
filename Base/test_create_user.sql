-- Script de test pour créer l'utilisateur et identifier le problème exact

-- 1. Vérifier s'il existe un utilisateur avec le même nom/prénom
SELECT 
    id, 
    email, 
    first_name, 
    last_name 
FROM aiolia.users 
WHERE (first_name ILIKE '%valea%' OR last_name ILIKE '%valea%' OR first_name ILIKE '%fifaliana%' OR last_name ILIKE '%fifaliana%');

-- 2. Tenter de créer l'utilisateur directement en SQL pour voir l'erreur exacte
-- Remplacez les valeurs par celles que vous essayez d'utiliser
DO $$
DECLARE
    test_email CITEXT := 'fifalianavalea@gmail.com';
    test_first_name TEXT := 'Fifaliana';  -- Remplacez par le prénom que vous utilisez
    test_last_name TEXT := 'Valea';       -- Remplacez par le nom que vous utilisez
    test_login_identifier VARCHAR(255) := 'fifalianavalea@gmail.com';
    test_password_hash TEXT := crypt('TestPassword123!', gen_salt('bf', 12));
    user_exists BOOLEAN;
    name_exists BOOLEAN;
    login_exists BOOLEAN;
BEGIN
    -- Vérifier si l'email existe déjà
    SELECT EXISTS(SELECT 1 FROM aiolia.users WHERE email = test_email) INTO user_exists;
    
    -- Vérifier si le nom/prénom existe déjà
    SELECT EXISTS(SELECT 1 FROM aiolia.users WHERE first_name = test_first_name AND last_name = test_last_name) INTO name_exists;
    
    -- Vérifier si le login_identifier avec login_method existe déjà
    SELECT EXISTS(SELECT 1 FROM aiolia.users WHERE login_identifier = test_login_identifier AND login_method = 'password') INTO login_exists;
    
    RAISE NOTICE 'Email existe: %', user_exists;
    RAISE NOTICE 'Nom/Prénom existe: %', name_exists;
    RAISE NOTICE 'Login existe: %', login_exists;
    
    IF user_exists THEN
        RAISE NOTICE 'ERREUR: Un utilisateur avec cet email existe déjà';
    ELSIF name_exists THEN
        RAISE NOTICE 'ERREUR: Un utilisateur avec ce nom et prénom existe déjà (contrainte UNIQUE sur first_name + last_name)';
    ELSIF login_exists THEN
        RAISE NOTICE 'ERREUR: Un utilisateur avec ce login_identifier et login_method existe déjà';
    ELSE
        RAISE NOTICE 'Aucun conflit détecté. Tentative de création...';
        
        -- Tenter l'insertion
        BEGIN
            INSERT INTO aiolia.users (
                email,
                login_identifier,
                login_method,
                password_hash,
                first_name,
                last_name,
                country_code,
                language_code,
                timezone,
                role,
                status,
                auth_provider,
                is_email_verified,
                is_phone_verified,
                accepted_terms_at,
                created_at,
                updated_at
            ) VALUES (
                test_email,
                test_login_identifier,
                'password',
                test_password_hash,
                test_first_name,
                test_last_name,
                'MG',
                'fr-FR',
                'Indian/Antananarivo',
                'user',
                1,
                'password',
                FALSE,
                FALSE,
                NOW(),
                NOW(),
                NOW()
            );
            
            RAISE NOTICE 'Utilisateur créé avec succès!';
        EXCEPTION WHEN OTHERS THEN
            RAISE NOTICE 'ERREUR lors de la création: %', SQLERRM;
            RAISE NOTICE 'Code d''erreur: %', SQLSTATE;
        END;
    END IF;
END $$;


