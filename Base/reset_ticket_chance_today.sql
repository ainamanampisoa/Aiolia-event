-- ============================================================
-- Script pour réinitialiser les tirages Ticket-Chance d'aujourd'hui
-- Permet de rejouer à Ticket-Chance aujourd'hui
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    target_user_id BIGINT;
    deleted_count INTEGER;
BEGIN
    -- Option 1: Utiliser l'utilisateur "Aina Fanelie" par défaut
    SELECT id INTO target_user_id
    FROM aiolia.users
    WHERE LOWER(first_name) = 'aina' AND LOWER(last_name) = 'fanelie'
    LIMIT 1;
    
    -- Si pas trouvé, vous pouvez spécifier un user_id directement
    -- target_user_id := 2; -- Remplacez 2 par l'ID de l'utilisateur souhaité
    
    IF target_user_id IS NULL THEN
        RAISE EXCEPTION 'Utilisateur "Aina Fanelie" non trouvé. Modifiez le script pour spécifier un user_id.';
    END IF;
    
    RAISE NOTICE 'Utilisateur trouvé : ID = %', target_user_id;
    
    -- Supprimer tous les tirages d'aujourd'hui pour cet utilisateur
    DELETE FROM aiolia.ticket_chance_entries
    WHERE user_id = target_user_id
      AND DATE(created_at) = CURRENT_DATE;
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    -- Supprimer aussi le dernier tirage gratuit pour permettre un nouveau tirage gratuit immédiatement
    -- On supprime le dernier tirage gratuit (play_type = 'free') s'il existe
    -- Cela permet de contourner la règle des 7 jours
    DELETE FROM aiolia.ticket_chance_entries
    WHERE user_id = target_user_id
      AND play_type = 'free'
      AND id = (
          SELECT id 
          FROM aiolia.ticket_chance_entries 
          WHERE user_id = target_user_id 
            AND play_type = 'free'
          ORDER BY created_at DESC 
          LIMIT 1
      );
    
    RAISE NOTICE '✅ % tirage(s) supprimé(s) pour aujourd''hui', deleted_count;
    RAISE NOTICE '✅ Dernier tirage gratuit réinitialisé (règle des 7 jours contournée)';
    RAISE NOTICE 'Vous pouvez maintenant rejouer à Ticket-Chance !';
    
END $$;

-- ============================================================
-- Vérification : Afficher les tirages restants pour aujourd'hui
-- ============================================================

SELECT 
    u.id as user_id,
    u.first_name || ' ' || u.last_name as nom_complet,
    COUNT(tce.id) as tirages_aujourdhui,
    MAX(tce.created_at) as dernier_tirage
FROM aiolia.users u
LEFT JOIN aiolia.ticket_chance_entries tce ON tce.user_id = u.id 
    AND DATE(tce.created_at) = CURRENT_DATE
WHERE LOWER(u.first_name) = 'aina' AND LOWER(u.last_name) = 'fanelie'
GROUP BY u.id, u.first_name, u.last_name;
