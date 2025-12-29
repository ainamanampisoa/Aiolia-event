-- Script pour supprimer l'utilisateur s'il existe déjà
-- ATTENTION: Cette opération supprime aussi les données associées (événements, commandes, etc.)

DO $$
DECLARE
    user_id_val BIGINT;
BEGIN
    -- Récupérer l'ID de l'utilisateur
    SELECT id INTO user_id_val 
    FROM aiolia.users 
    WHERE email ILIKE 'fifalianavalea@gmail.com';
    
    IF user_id_val IS NOT NULL THEN
        RAISE NOTICE 'Utilisateur trouvé avec ID: %', user_id_val;
        
        -- Supprimer les données associées (en cascade si les contraintes le permettent)
        -- Sinon, supprimer manuellement dans l'ordre
        
        -- 1. Supprimer le profil organisateur s'il existe
        DELETE FROM aiolia.organizer_profiles WHERE user_id = user_id_val;
        
        -- 2. Supprimer les tokens d'authentification
        DELETE FROM aiolia.auth_tokens WHERE user_id = user_id_val;
        
        -- 3. Supprimer les préférences utilisateur
        DELETE FROM aiolia.user_preferences WHERE user_id = user_id_val;
        
        -- 4. Supprimer l'utilisateur
        DELETE FROM aiolia.users WHERE id = user_id_val;
        
        RAISE NOTICE 'Utilisateur % supprimé avec succès', 'fifalianavalea@gmail.com';
    ELSE
        RAISE NOTICE 'Aucun utilisateur trouvé avec cet email';
    END IF;
END $$;

-- Vérifier que l'utilisateur a bien été supprimé
SELECT 
    id, 
    email 
FROM aiolia.users 
WHERE email ILIKE 'fifalianavalea@gmail.com';


