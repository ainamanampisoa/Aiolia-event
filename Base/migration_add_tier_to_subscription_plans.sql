-- ============================================================
-- Migration : Ajout de la colonne tier à subscription_plans
-- ============================================================
-- Cette migration ajoute les colonnes tier, display_order, is_popular
-- et la contrainte UNIQUE si elles n'existent pas déjà

\c aiolia_event;
SET search_path TO aiolia, public;

-- Vérifier et ajouter la colonne tier
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'subscription_plans' 
        AND column_name = 'tier'
    ) THEN
        ALTER TABLE aiolia.subscription_plans
        ADD COLUMN tier TEXT NOT NULL DEFAULT 'basic'
            CHECK (tier IN ('basic', 'pro', 'enterprise'));
        
        RAISE NOTICE 'Colonne tier ajoutée à subscription_plans';
    ELSE
        RAISE NOTICE 'Colonne tier existe déjà dans subscription_plans';
    END IF;
END
$$;

-- Vérifier et ajouter la colonne display_order
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'subscription_plans' 
        AND column_name = 'display_order'
    ) THEN
        ALTER TABLE aiolia.subscription_plans
        ADD COLUMN display_order INTEGER NOT NULL DEFAULT 0;
        
        RAISE NOTICE 'Colonne display_order ajoutée à subscription_plans';
    ELSE
        RAISE NOTICE 'Colonne display_order existe déjà dans subscription_plans';
    END IF;
END
$$;

-- Vérifier et ajouter la colonne is_popular
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'aiolia' 
        AND table_name = 'subscription_plans' 
        AND column_name = 'is_popular'
    ) THEN
        ALTER TABLE aiolia.subscription_plans
        ADD COLUMN is_popular BOOLEAN NOT NULL DEFAULT FALSE;
        
        RAISE NOTICE 'Colonne is_popular ajoutée à subscription_plans';
    ELSE
        RAISE NOTICE 'Colonne is_popular existe déjà dans subscription_plans';
    END IF;
END
$$;

-- Vérifier et ajouter la contrainte UNIQUE sur (tier, billing_period)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_schema = 'aiolia'
        AND table_name = 'subscription_plans'
        AND constraint_name = 'uq_subscription_plans_tier'
    ) THEN
        ALTER TABLE aiolia.subscription_plans
        ADD CONSTRAINT uq_subscription_plans_tier UNIQUE (tier, billing_period);
        
        RAISE NOTICE 'Contrainte uq_subscription_plans_tier ajoutée';
    ELSE
        RAISE NOTICE 'Contrainte uq_subscription_plans_tier existe déjà';
    END IF;
END
$$;

-- Mettre à jour les plans existants avec les valeurs tier appropriées
-- Basé sur le code du plan
UPDATE aiolia.subscription_plans
SET tier = CASE 
    WHEN code = 'BASIC' THEN 'basic'
    WHEN code = 'PRO' THEN 'pro'
    WHEN code = 'ENTERPRISE' THEN 'enterprise'
    ELSE 'basic'
END,
display_order = CASE 
    WHEN code = 'BASIC' THEN 1
    WHEN code = 'PRO' THEN 2
    WHEN code = 'ENTERPRISE' THEN 3
    ELSE 0
END,
is_popular = (code = 'PRO')
WHERE tier IS NULL OR tier = 'basic';

-- Afficher un résumé
SELECT 
    id,
    code,
    name,
    tier,
    display_order,
    is_popular,
    price,
    currency
FROM aiolia.subscription_plans
ORDER BY display_order;

