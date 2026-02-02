-- ============================================================
-- Script pour mettre à jour "Music on Sunday" pour demain
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    music_sunday_id BIGINT;
    tomorrow_start TIMESTAMPTZ;
    tomorrow_end TIMESTAMPTZ;
    event_duration INTERVAL;
BEGIN
    -- Trouver l'événement "Music on Sunday"
    SELECT id INTO music_sunday_id
    FROM aiolia.events
    WHERE LOWER(title) LIKE '%music on sunday%'
       OR LOWER(slug) LIKE '%music-sunday%'
    LIMIT 1;
    
    IF music_sunday_id IS NULL THEN
        RAISE EXCEPTION 'Événement "Music on Sunday" non trouvé';
    END IF;
    
    -- Calculer demain à la même heure (ou à 14h00 par défaut)
    -- On garde l'heure actuelle de l'événement, ou on met 14h00 si l'événement est passé
    SELECT 
        CASE 
            WHEN starts_at > NOW() THEN 
                -- Si l'événement est dans le futur, on garde l'heure
                DATE_TRUNC('day', NOW() + INTERVAL '1 day') + (starts_at::TIME)
            ELSE 
                -- Sinon, on met 14h00 demain
                DATE_TRUNC('day', NOW() + INTERVAL '1 day') + INTERVAL '14 hours'
        END
    INTO tomorrow_start
    FROM aiolia.events
    WHERE id = music_sunday_id;
    
    -- Calculer la durée de l'événement
    SELECT ends_at - starts_at INTO event_duration
    FROM aiolia.events
    WHERE id = music_sunday_id;
    
    -- Si pas de durée trouvée, mettre 3 heures par défaut
    IF event_duration IS NULL THEN
        event_duration := INTERVAL '3 hours';
    END IF;
    
    -- Calculer la fin de l'événement
    tomorrow_end := tomorrow_start + event_duration;
    
    -- Mettre à jour l'événement
    UPDATE aiolia.events
    SET 
        starts_at = tomorrow_start,
        ends_at = tomorrow_end,
        updated_at = NOW()
    WHERE id = music_sunday_id;
    
    RAISE NOTICE '✅ Événement "Music on Sunday" mis à jour pour demain';
    RAISE NOTICE '   - ID: %', music_sunday_id;
    RAISE NOTICE '   - Début: %', tomorrow_start;
    RAISE NOTICE '   - Fin: %', tomorrow_end;
    RAISE NOTICE '   - Durée: %', event_duration;
    
END $$;

-- ============================================================
-- Vérification
-- ============================================================

SELECT 
    id,
    title,
    slug,
    starts_at,
    ends_at,
    starts_at AT TIME ZONE 'Indian/Antananarivo' as starts_at_eat,
    ends_at AT TIME ZONE 'Indian/Antananarivo' as ends_at_eat,
    NOW() AT TIME ZONE 'Indian/Antananarivo' as current_time_eat,
    EXTRACT(EPOCH FROM (starts_at - NOW())) / 3600 as hours_until_start
FROM aiolia.events
WHERE LOWER(title) LIKE '%music on sunday%'
   OR LOWER(slug) LIKE '%music-sunday%';
