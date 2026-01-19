# VUES DATABASE UTILISÉES DANS AIOLIA-EVENTS-BACK

**Date d'analyse :** 2025-12-17  
**Total :** 3 vues PostgreSQL

---

## 📊 RÉSUMÉ

Le backend **aiolia-events-back** utilise **3 vues PostgreSQL** pour faciliter les requêtes sur les transactions de paiement mobile.

Toutes ces vues sont définies dans `Base/schema.sql` et sont liées au module **Transactions Mobiles**.

---

## 📋 LISTE DES VUES

### 1. `vue_transactions_en_attente`

**Description :** Vue des transactions de paiement mobile en attente de confirmation

**Tables utilisées :**
- `transactions_paiement_mobile`
- `utilisateurs`
- `profils_organisateurs`
- `factures_abonnements`

**Colonnes retournées :**
- `id` - ID de la transaction
- `reference_transaction` - Référence unique
- `id_facture` - ID de la facture associée
- `numero_facture` - Numéro de facture
- `id_utilisateur` - ID utilisateur
- `email` - Email utilisateur
- `nom_utilisateur` - Nom complet (prénom + nom)
- `id_profil_organisateur` - ID organisateur
- `organisateur` - Nom d'affichage organisateur
- `operateur_mobile` - Opérateur (mvola, orange, airtel, espace)
- `numero_telephone` - Numéro de téléphone
- `montant` - Montant de la transaction
- `statut_paiement` - Statut actuel
- `initie_le` - Date d'initiation
- `expire_le` - Date d'expiration
- `statut_expiration` - Statut calculé ('expiree' ou 'valide')

**Filtres :**
- `statut_paiement IN ('initiated', 'processing')`
- `annule_le IS NULL`
- `confirme_le IS NULL`

**Permissions :** `GRANT SELECT ON vue_transactions_en_attente TO aiolia_user;`

---

### 2. `vue_transactions_reussies_par_mois`

**Description :** Vue agrégée des transactions réussies groupées par mois et opérateur

**Tables utilisées :**
- `transactions_paiement_mobile`

**Colonnes retournées :**
- `mois` - Mois (DATE_TRUNC)
- `operateur_mobile` - Opérateur mobile
- `nombre_transactions` - Nombre total de transactions
- `montant_total` - Somme des montants
- `montant_moyen` - Moyenne des montants

**Filtres :**
- `statut_paiement = 'paid'`
- `confirme_le IS NOT NULL`

**Groupement :** Par mois et opérateur

**Permissions :** `GRANT SELECT ON vue_transactions_reussies_par_mois TO aiolia_user;`

---

### 3. `vue_transactions_par_organisateur`

**Description :** Vue agrégée des statistiques de transactions par organisateur

**Tables utilisées :**
- `profils_organisateurs`
- `transactions_paiement_mobile` (LEFT JOIN)

**Colonnes retournées :**
- `id_organisateur` - ID de l'organisateur
- `organisateur` - Nom d'affichage
- `total_transactions` - Nombre total de transactions
- `transactions_reussies` - Nombre de transactions réussies (statut = 'paid')
- `transactions_en_cours` - Nombre de transactions en cours ('initiated', 'processing')
- `transactions_echouees` - Nombre de transactions échouées (statut = 'failed')
- `montant_total_reussi` - Somme des montants des transactions réussies

**Groupement :** Par organisateur

**Permissions :** `GRANT SELECT ON vue_transactions_par_organisateur TO aiolia_user;`

---

## 🔍 UTILISATION DANS LE BACKEND

### État actuel

**⚠️ Note :** Ces vues ne sont actuellement **pas directement utilisées** dans le code PHP du backend.

Les recherches dans le code source (`Aiolia-event-back/src`) ne montrent aucune référence directe à ces vues.

### Recommandations

1. **Créer des Repository dédiés** pour utiliser ces vues via Doctrine DQL ou requêtes natives
2. **Utiliser dans les Services** de reporting/statistiques pour les organisateurs
3. **Exposer via API** pour les tableaux de bord organisateurs

### Exemple d'utilisation suggérée

```php
// Dans un Repository
public function getTransactionsEnAttente(): array
{
    return $this->getEntityManager()
        ->getConnection()
        ->fetchAllAssociative('SELECT * FROM aiolia.vue_transactions_en_attente');
}

// Dans un Service
public function getStatsTransactionsParOrganisateur(int $organisateurId): array
{
    return $this->getEntityManager()
        ->getConnection()
        ->fetchAssociative(
            'SELECT * FROM aiolia.vue_transactions_par_organisateur WHERE id_organisateur = ?',
            [$organisateurId]
        );
}
```

---

## 📊 RÉSUMÉ PAR MODULE

| Module | Nombre de vues | Vues |
|--------|----------------|------|
| **Transactions Mobiles** | 3 | `vue_transactions_en_attente`<br>`vue_transactions_reussies_par_mois`<br>`vue_transactions_par_organisateur` |

---

## ✅ CONCLUSION

**Total : 3 vues PostgreSQL**

- ✅ Toutes les vues sont définies dans `Base/schema.sql`
- ✅ Toutes les vues ont les permissions GRANT configurées
- ⚠️ Aucune vue n'est actuellement utilisée dans le code PHP
- 💡 Potentiel d'utilisation dans les modules de reporting et statistiques

---

**Note :** Ces vues sont optimisées pour les requêtes de reporting et peuvent améliorer significativement les performances des requêtes complexes sur les transactions de paiement mobile.

Liste des vues
vue_transactions_en_attente
    Transactions de paiement mobile en attente de confirmation
    Affiche les transactions avec statut 'initiated' ou 'processing'

vue_transactions_reussies_par_mois
    Transactions réussies groupées par mois et opérateur
    Statistiques agrégées (nombre, montant total, moyenne)

vue_transactions_par_organisateur
    Statistiques de transactions par organisateur
    Compte les transactions réussies, en cours, échouées

v_ticket_sales_history
    historique des ventes par facture / type de billet / événement