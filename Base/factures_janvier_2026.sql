-- ============================================================
-- GÉNÉRATION DES FACTURES JANVIER 2026
-- Version simplifiée - Juste la génération
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

-- Générer les factures de janvier 2026 pour tous les organisateurs actifs
SELECT * FROM generer_factures_organisateurs_actifs('2026-01-01'::DATE);

-- Vérification rapide
SELECT 
    'Organisateurs actifs' as type,
    COUNT(DISTINCT ao.id_profil_organisateur) as nombre
FROM abonnements_organisateurs ao
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
WHERE ao.statut IN ('active', 'paused')
AND po.statut_verification = 'verified'

UNION ALL

SELECT 
    'Factures janvier 2026' as type,
    COUNT(*) as nombre
FROM factures_abonnements fa
JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
WHERE fa.mois_facturation = '2026-01-01'::DATE
AND po.statut_verification = 'verified';

-- Message de confirmation
DO $$
DECLARE
    v_organisateurs INTEGER;
    v_factures INTEGER;
BEGIN
    SELECT COUNT(DISTINCT ao.id_profil_organisateur) INTO v_organisateurs
    FROM abonnements_organisateurs ao
    JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
    WHERE ao.statut IN ('active', 'paused')
    AND po.statut_verification = 'verified';
    
    SELECT COUNT(*) INTO v_factures
    FROM factures_abonnements fa
    JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
    JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
    WHERE fa.mois_facturation = '2026-01-01'::DATE
    AND po.statut_verification = 'verified';
    
    IF v_organisateurs = v_factures THEN
        RAISE NOTICE '✅ Janvier 2026 : % organisateurs facturés avec succès!', v_factures;
    ELSE
        RAISE WARNING '⚠️  Janvier 2026 : % organisateurs, % factures (différence: %)', 
            v_organisateurs, v_factures, (v_organisateurs - v_factures);
    END IF;
END $$;