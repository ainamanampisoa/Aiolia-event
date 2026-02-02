-- ============================================================
-- Script pour vérifier le statut Ticket-Chance d'un utilisateur
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    target_user_id BIGINT;
    total_spent NUMERIC;
    can_play BOOLEAN;
    today_plays INTEGER;
    last_free_play_date DATE;
    can_use_free_play BOOLEAN;
    bonus_plays INTEGER;
    remaining_plays INTEGER;
BEGIN
    -- Trouver l'utilisateur
    SELECT id INTO target_user_id
    FROM aiolia.users
    WHERE LOWER(first_name) = 'aina' AND LOWER(last_name) = 'fanelie'
    LIMIT 1;
    
    IF target_user_id IS NULL THEN
        RAISE EXCEPTION 'Utilisateur "Aina Fanelie" non trouvé.';
    END IF;
    
    -- Calculer le total dépensé
    SELECT COALESCE(SUM(o.total_amount), 0) INTO total_spent
    FROM aiolia.orders o
    WHERE o.user_id = target_user_id
      AND o.status = 'paid';
    
    -- Vérifier si le seuil est atteint (100 000 MGA)
    can_play := total_spent >= 100000.0;
    
    -- Tirages aujourd'hui
    SELECT COUNT(*) INTO today_plays
    FROM aiolia.ticket_chance_entries
    WHERE user_id = target_user_id
      AND DATE(created_at) = CURRENT_DATE;
    
    -- Dernier tirage gratuit
    SELECT DATE(created_at) INTO last_free_play_date
    FROM aiolia.ticket_chance_entries
    WHERE user_id = target_user_id
      AND play_type = 'free'
    ORDER BY created_at DESC
    LIMIT 1;
    
    -- Vérifier si on peut utiliser le tirage gratuit
    can_use_free_play := last_free_play_date IS NULL OR 
                         (CURRENT_DATE - last_free_play_date) >= 7;
    
    -- Tirages bonus disponibles
    SELECT COUNT(*) INTO bonus_plays
    FROM aiolia.orders o
    WHERE o.user_id = target_user_id
      AND o.status = 'paid'
      AND o.total_amount >= 50000
      AND NOT EXISTS (
          SELECT 1 FROM aiolia.ticket_chance_entries tce
          WHERE tce.user_id = o.user_id
            AND tce.play_type = 'bonus'
            AND tce.order_id = o.id
      );
    
    -- Calculer les tirages restants
    remaining_plays := 0;
    IF can_use_free_play THEN
        remaining_plays := remaining_plays + 1;
    END IF;
    remaining_plays := remaining_plays + bonus_plays;
    
    -- Limité par le max journalier (2 par jour)
    remaining_plays := LEAST(remaining_plays, 2 - today_plays);
    remaining_plays := GREATEST(remaining_plays, 0);
    
    -- Afficher les informations
    RAISE NOTICE '========================================';
    RAISE NOTICE 'Statut Ticket-Chance pour utilisateur ID: %', target_user_id;
    RAISE NOTICE '========================================';
    RAISE NOTICE 'Total dépensé: % MGA', total_spent;
    RAISE NOTICE 'Seuil atteint (100 000 MGA): %', CASE WHEN can_play THEN 'OUI ✅' ELSE 'NON ❌' END;
    RAISE NOTICE '';
    RAISE NOTICE 'Tirages aujourd''hui: % / 2', today_plays;
    RAISE NOTICE 'Dernier tirage gratuit: %', COALESCE(last_free_play_date::TEXT, 'Jamais');
    RAISE NOTICE 'Tirage gratuit disponible: %', CASE WHEN can_use_free_play THEN 'OUI ✅' ELSE 'NON ❌' END;
    RAISE NOTICE 'Tirages bonus disponibles: %', bonus_plays;
    RAISE NOTICE '';
    RAISE NOTICE 'Tirages restants aujourd''hui: %', remaining_plays;
    RAISE NOTICE 'Peut jouer maintenant: %', CASE WHEN (can_play AND remaining_plays > 0) THEN 'OUI ✅' ELSE 'NON ❌' END;
    RAISE NOTICE '========================================';
    
END $$;
