-- ============================================================
-- MISE À JOUR DU SCHÉMA POUR TICKET CHANCE
-- ============================================================

-- Ajouter les nouvelles colonnes à ticket_chance_entries
DO $$
BEGIN
    -- Colonne play_type (free ou bonus)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'ticket_chance_entries' 
        AND column_name = 'play_type'
    ) THEN
        ALTER TABLE aiolia.ticket_chance_entries 
        ADD COLUMN play_type VARCHAR(20) NOT NULL DEFAULT 'free';
    END IF;
    
    -- Colonne order_id (pour les tirages bonus liés à une commande)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'ticket_chance_entries' 
        AND column_name = 'order_id'
    ) THEN
        ALTER TABLE aiolia.ticket_chance_entries 
        ADD COLUMN order_id BIGINT REFERENCES aiolia.orders(id) ON DELETE SET NULL;
    END IF;
    
    -- Colonne prize_code (code du prix gagné)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'ticket_chance_entries' 
        AND column_name = 'prize_code'
    ) THEN
        ALTER TABLE aiolia.ticket_chance_entries 
        ADD COLUMN prize_code VARCHAR(50);
    END IF;
    
    -- Colonne promo_code (code promo généré)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'ticket_chance_entries' 
        AND column_name = 'promo_code'
    ) THEN
        ALTER TABLE aiolia.ticket_chance_entries 
        ADD COLUMN promo_code VARCHAR(50);
    END IF;
    
    -- Colonne metadata (données supplémentaires en JSON)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'ticket_chance_entries' 
        AND column_name = 'metadata'
    ) THEN
        ALTER TABLE aiolia.ticket_chance_entries 
        ADD COLUMN metadata JSONB DEFAULT '{}'::jsonb;
    END IF;
END
$$;

-- Ajouter la colonne ticket_chance_entry_id à promo_codes
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'promo_codes' 
        AND column_name = 'ticket_chance_entry_id'
    ) THEN
        ALTER TABLE aiolia.promo_codes 
        ADD COLUMN ticket_chance_entry_id BIGINT REFERENCES aiolia.ticket_chance_entries(id) ON DELETE SET NULL;
    END IF;
    
    -- Ajouter la colonne metadata si elle n'existe pas
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'promo_codes' 
        AND column_name = 'metadata'
    ) THEN
        ALTER TABLE aiolia.promo_codes 
        ADD COLUMN metadata JSONB DEFAULT '{}'::jsonb;
    END IF;
END
$$;

-- Index pour les performances
CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_user_date 
    ON aiolia.ticket_chance_entries(user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_play_type 
    ON aiolia.ticket_chance_entries(user_id, play_type);

CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_order 
    ON aiolia.ticket_chance_entries(order_id) WHERE order_id IS NOT NULL;

-- Vérification
SELECT 
    column_name, 
    data_type, 
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'aiolia' 
  AND table_name = 'ticket_chance_entries'
ORDER BY ordinal_position;

