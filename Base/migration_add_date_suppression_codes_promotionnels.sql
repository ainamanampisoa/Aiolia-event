-- Migration: Ajout de la colonne date_suppression pour soft delete des codes promotionnels
-- Date: 2024

-- Ajouter la colonne date_suppression si elle n'existe pas déjà
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'codes_promotionnels' 
        AND column_name = 'date_suppression'
    ) THEN
        ALTER TABLE aiolia.codes_promotionnels 
        ADD COLUMN date_suppression TIMESTAMPTZ DEFAULT NULL;
        
        -- Créer un index pour améliorer les performances des requêtes
        CREATE INDEX IF NOT EXISTS idx_codes_promotionnels_date_suppression 
        ON aiolia.codes_promotionnels(date_suppression) 
        WHERE date_suppression IS NULL;
        
        RAISE NOTICE 'Colonne date_suppression ajoutée avec succès';
    ELSE
        RAISE NOTICE 'La colonne date_suppression existe déjà';
    END IF;
END $$;

