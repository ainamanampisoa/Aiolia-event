-- Script pour corriger les mois de facturation
-- Met à jour les factures d'abonnement pour Juin 2025

\c aiolia_event;
SET search_path TO aiolia;

-- Mettre à jour les factures qui ont mois_facturation en Mai 2025 vers Juin 2025
-- (si elles devraient être en Juin selon les critères)
UPDATE factures_abonnements fa
SET mois_facturation = '2025-06-01'
WHERE fa.mois_facturation >= '2025-05-01' 
  AND fa.mois_facturation < '2025-06-01'
  AND EXISTS (
      SELECT 1 
      FROM abonnements_organisateurs ao 
      WHERE ao.id = fa.id_abonnement 
        AND ao.debut_periode_courante = '2025-06-01'
  );

-- Vérifier les résultats
SELECT 
    id,
    numero_facture,
    mois_facturation,
    emise_le,
    statut
FROM factures_abonnements
WHERE mois_facturation >= '2025-06-01' 
  AND mois_facturation < '2025-07-01'
ORDER BY id DESC
LIMIT 10;
