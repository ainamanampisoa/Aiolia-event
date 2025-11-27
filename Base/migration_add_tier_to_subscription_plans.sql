-- ============================================================
-- Migration : Ajout de la colonne niveau à plans_abonnements
-- ============================================================
-- Cette migration ajoute les colonnes niveau, ordre_affichage
-- et la contrainte UNIQUE si elles n'existent pas déjà

\c aiolia_event;
SET search_path TO aiolia, public;

-- Vérifier et ajouter la colonne niveau
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'plans_abonnements' 
        AND column_name = 'niveau'
    ) THEN
        ALTER TABLE aiolia.plans_abonnements
        ADD COLUMN niveau TEXT NOT NULL DEFAULT 'basic'
            CHECK (niveau IN ('basic', 'pro', 'enterprise'));
        
        RAISE NOTICE 'Colonne niveau ajoutée à plans_abonnements';
    ELSE
        RAISE NOTICE 'Colonne niveau existe déjà dans plans_abonnements';
    END IF;
END
$$;

-- Vérifier et ajouter la colonne ordre_affichage
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'plans_abonnements' 
        AND column_name = 'ordre_affichage'
    ) THEN
        ALTER TABLE aiolia.plans_abonnements
        ADD COLUMN ordre_affichage INTEGER NOT NULL DEFAULT 0;
        
        RAISE NOTICE 'Colonne ordre_affichage ajoutée à plans_abonnements';
    ELSE
        RAISE NOTICE 'Colonne ordre_affichage existe déjà dans plans_abonnements';
    END IF;
END
$$;

-- Vérifier et ajouter la contrainte UNIQUE sur (niveau, periode_facturation)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_schema = 'aiolia'
        AND table_name = 'plans_abonnements'
        AND constraint_name = 'uq_plans_abonnements_niveau'
    ) THEN
        ALTER TABLE aiolia.plans_abonnements
        ADD CONSTRAINT uq_plans_abonnements_niveau UNIQUE (niveau, periode_facturation);
        
        RAISE NOTICE 'Contrainte uq_plans_abonnements_niveau ajoutée';
    ELSE
        RAISE NOTICE 'Contrainte uq_plans_abonnements_niveau existe déjà';
    END IF;
END
$$;

-- Mettre à jour les plans existants avec les valeurs niveau appropriées
-- Basé sur le code du plan
UPDATE aiolia.plans_abonnements
SET niveau = CASE 
    WHEN code = 'BASIC' THEN 'basic'
    WHEN code = 'PRO' THEN 'pro'
    WHEN code = 'ENTERPRISE' THEN 'enterprise'
    ELSE 'basic'
END,
ordre_affichage = CASE 
    WHEN code = 'BASIC' THEN 1
    WHEN code = 'PRO' THEN 2
    WHEN code = 'ENTERPRISE' THEN 3
    ELSE 0
END
WHERE niveau IS NULL OR niveau = 'basic';

-- Afficher un résumé
SELECT 
    id,
    code,
    nom,
    niveau,
    ordre_affichage,
    prix,
    devise
FROM aiolia.plans_abonnements
ORDER BY ordre_affichage;

