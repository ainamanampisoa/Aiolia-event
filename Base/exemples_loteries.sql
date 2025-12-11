-- ============================================================
-- EXEMPLES DE PARTICIPATIONS À DES LOTERIES POUR UN UTILISATEUR
-- ============================================================
-- Ces exemples montrent différents types de lots et statuts

-- Exemple 1 : Participation en attente (pending) - Lot en pourcentage
INSERT INTO participations_loteries_billets (
    id_utilisateur,
    id_evenement,
    type_lot,
    valeur_lot,
    statut,
    cree_le
) VALUES (
    (SELECT id FROM utilisateurs WHERE role = 'user' LIMIT 1),
    (SELECT id FROM evenements WHERE statut = 'published' LIMIT 1),
    'percent',
    15.00,  -- 15% de réduction
    'pending',
    NOW() - INTERVAL '2 days'
);

-- Exemple 2 : Participation gagnée (won) - Lot en montant
INSERT INTO participations_loteries_billets (
    id_utilisateur,
    id_evenement,
    type_lot,
    valeur_lot,
    statut,
    cree_le,
    reclame_le
) VALUES (
    (SELECT id FROM utilisateurs WHERE role = 'user' LIMIT 1),
    (SELECT id FROM evenements WHERE statut = 'published' LIMIT 1),
    'amount',
    5000.00,  -- 5000 MGA de réduction
    'won',
    NOW() - INTERVAL '5 days',
    NOW() - INTERVAL '4 days'  -- Réclamé le lendemain
);

-- Exemple 3 : Participation perdue (lost) - Lot en pourcentage
INSERT INTO participations_loteries_billets (
    id_utilisateur,
    id_evenement,
    type_lot,
    valeur_lot,
    statut,
    cree_le
) VALUES (
    (SELECT id FROM utilisateurs WHERE role = 'user' LIMIT 1),
    (SELECT id FROM evenements WHERE statut = 'published' LIMIT 1),
    'percent',
    20.00,  -- 20% de réduction (non gagné)
    'lost',
    NOW() - INTERVAL '7 days'
);

-- Exemple 4 : Participation gagnée et réclamée (claimed) - Lot en montant
INSERT INTO participations_loteries_billets (
    id_utilisateur,
    id_evenement,
    type_lot,
    valeur_lot,
    statut,
    cree_le,
    reclame_le
) VALUES (
    (SELECT id FROM utilisateurs WHERE role = 'user' LIMIT 1),
    (SELECT id FROM evenements WHERE statut = 'published' LIMIT 1),
    'amount',
    10000.00,  -- 10000 MGA de réduction
    'claimed',
    NOW() - INTERVAL '10 days',
    NOW() - INTERVAL '9 days'  -- Réclamé le jour même
);

-- Exemple 5 : Participation en attente récente - Lot en pourcentage (gros lot)
INSERT INTO participations_loteries_billets (
    id_utilisateur,
    id_evenement,
    type_lot,
    valeur_lot,
    statut,
    cree_le
) VALUES (
    (SELECT id FROM utilisateurs WHERE role = 'user' LIMIT 1),
    (SELECT id FROM evenements WHERE statut = 'published' LIMIT 1),
    'percent',
    50.00,  -- 50% de réduction (gros lot)
    'pending',
    NOW() - INTERVAL '1 hour'
);

-- ============================================================
-- RÉSUMÉ DES EXEMPLES
-- ============================================================
-- 1. Participation pending - 15% de réduction (il y a 2 jours)
-- 2. Participation won - 5000 MGA (gagnée et réclamée il y a 4 jours)
-- 3. Participation lost - 20% de réduction (perdue il y a 7 jours)
-- 4. Participation claimed - 10000 MGA (gagnée et réclamée il y a 9 jours)
-- 5. Participation pending - 50% de réduction (il y a 1 heure)

