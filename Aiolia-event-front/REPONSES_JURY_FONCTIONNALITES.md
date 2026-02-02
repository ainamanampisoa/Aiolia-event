# 🎤 Réponses Jury - Fonctionnalités Aiolia Event Front

## 1. Comment as-tu fait la fonctionnalité générer QR CODE ?

**Réponse :** J'utilise la bibliothèque **Endroid/QrCode** pour générer les QR codes. Lors de la création d'un billet après paiement, je génère un code unique avec `uniqid()` et `random_bytes()` (format : `TICKET_[uniqid]_[hex]`), je le stocke en base de données, puis lors du téléchargement du PDF, j'utilise `Endroid\QrCode\Builder\Builder` avec `PngWriter` pour créer l'image QR en Base64 que j'intègre directement dans le PDF via DomPDF.

---

## 2. Comment as-tu fait la liste événements "Pour vous" ?

**Réponse :** J'ai implémenté un algorithme de recommandation basé sur les catégories : je récupère d'abord les catégories d'intérêt de l'utilisateur en combinant (UNION) les catégories de ses favoris et de ses achats passés, puis je recherche les événements futurs dans ces catégories en excluant ceux déjà en favoris ou déjà achetés, triés par date de début, avec un fallback vers les événements populaires si l'utilisateur n'a pas d'historique.

---

## 3. Comment as-tu fait le Top 3 événements ?

**Réponse :** Dans la page statistiques, j'utilise la méthode `fetchTopPurchasedEvents()` qui fait une requête SQL avec `COUNT` et `GROUP BY` sur les commandes payées pour compter le nombre de billets achetés par événement, puis je trie par nombre décroissant et je limite à 3 résultats avec `LIMIT 3`, en respectant le filtre de période sélectionné par l'utilisateur.

---

## 4. Comment as-tu fait le calcul moyenne des statistiques ?

**Réponse :** Pour le panier moyen, je calcule `total_spent / total_orders` dans `OrderRepository::findFinancialHistory()` : je fais une requête SQL qui somme le `total_amount` des commandes payées et compte le nombre de commandes, puis je divise le total par le nombre pour obtenir la moyenne, avec une protection contre la division par zéro. Pour les autres moyennes (dépenses mensuelles, etc.), j'utilise des agrégations SQL similaires avec `AVG()` ou des calculs manuels selon le besoin.

---

## 5. Comment as-tu fait les notifications ?

**Réponse :** J'ai créé un système multi-canaux avec `NotificationService` qui crée les notifications en base de données dans la table `notifications`, puis les envoie via trois canaux : in-app (affichées dans `/notifications`), push web (via Service Worker API avec permission navigateur), et emails (via Symfony Mailer). Pour les rappels automatiques, j'utilise `EventReminderService` avec une commande Symfony `app:send-event-reminders` qui s'exécute chaque heure, détecte les événements dans 24h et 2h, et notifie les utilisateurs ayant des billets avec vérification de leurs préférences.

---

## 6. Comment as-tu fait les activités récentes ?

**Réponse :** J'utilise `ActivityRepository::findRecentActivities()` qui agrège trois sources : les billets confirmés récents (derniers 30 jours via JOIN orders → order_items → tickets → events), les favoris récents ajoutés dans la wishlist, et les ajouts au panier (depuis la base de données `cart_items` et la session). Je combine ces résultats, je les trie par date décroissante, et j'affiche les 5 plus récentes avec leurs icônes et métadonnées (type, titre, date formatée) sur la page d'accueil du profil.

---

**Date** : Décembre 2025  
**Version** : 1.0
