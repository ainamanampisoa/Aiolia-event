# 📖 Livre du Projet : Réservation et Achat de Billets

Le processus de réservation et d'achat de billets est l'aboutissement du parcours utilisateur sur Aiolia-event. C'est le moment où l'intérêt se transforme en action concrète. Ce module a été conçu pour être fluide, sécurisé et rassurant, guidant l'utilisateur de la sélection des billets jusqu'à la confirmation de paiement, sans friction ni complexité inutile.

---

## 🛒 a. Gestion du panier

Le panier est l'espace personnel où l'utilisateur rassemble les billets qu'il souhaite acquérir. Il peut y ajouter plusieurs événements, modifier les quantités et revenir plus tard pour finaliser son achat.

### L'expérience utilisateur
L'ajout au panier est une action simple et intuitive :
- **Sélection des billets** : Sur la page de détails d'un événement, l'utilisateur choisit le type de billet (VIP, Standard, etc.) et la quantité (adulte et/ou enfant si applicable)
- **Feedback immédiat** : Dès l'ajout, un message de confirmation apparaît et l'icône du panier en haut de page affiche le nombre d'articles
- **Persistance** : Le panier est sauvegardé automatiquement, permettant à l'utilisateur de le retrouver même après fermeture du navigateur (pour les utilisateurs connectés)
- **Synchronisation** : Pour les utilisateurs connectés, le panier est synchronisé entre tous leurs appareils, offrant une expérience cohérente

La page du panier présente une vue claire de tous les articles :
- **Récapitulatif par événement** : Chaque événement est présenté avec son image, son titre, sa date et son lieu
- **Détails des billets** : Pour chaque événement, les types de billets sélectionnés sont listés avec leurs quantités et prix unitaires
- **Calcul automatique** : Le total est calculé en temps réel, incluant les promotions actives
- **Modification facile** : L'utilisateur peut modifier les quantités ou retirer des articles directement depuis le panier
- **Action rapide** : Un bouton "Procéder au paiement" permet de passer à l'étape suivante

### Sous le capot 🛠️
Le panier utilise un système hybride intelligent :
1. **Stockage session** : Pour les visiteurs non connectés, le panier est stocké dans la session PHP, permettant une navigation immédiate
2. **Synchronisation base de données** : Pour les utilisateurs connectés, le panier est également sauvegardé en base de données via CartSyncService, garantissant la persistance
3. **Fusion intelligente** : Lors de la connexion, le système fusionne automatiquement le panier de session avec celui de la base de données, évitant les doublons
4. **Gestion des clés** : Chaque article du panier est identifié par une clé unique basée sur l'événement et le type de billet, permettant une gestion précise
5. **Validation en temps réel** : Le système vérifie la disponibilité des billets avant chaque ajout, empêchant la réservation de billets épuisés

---

## 💳 b. Processus de paiement

Le processus de paiement est l'étape cruciale où l'utilisateur finalise son achat. Il a été conçu pour être sécurisé, transparent et rassurant, avec des étapes claires et un suivi en temps réel.

### L'expérience utilisateur
Le parcours de paiement se déroule en plusieurs étapes claires :

**Étape 1 : Récapitulatif de commande**
- L'utilisateur voit un récapitulatif complet de sa commande
- Le total est détaillé : sous-total, frais de service (si applicable), et total final
- Les informations de l'événement sont rappelées pour confirmation
- Un délai de paiement est affiché (généralement 15 minutes) pour créer un sentiment d'urgence positif

**Étape 2 : Saisie des informations**
- L'utilisateur renseigne ses coordonnées : nom, email, numéro de téléphone
- Ces informations sont pré-remplies si l'utilisateur est connecté
- Une case à cocher pour accepter les conditions générales d'utilisation est obligatoire

**Étape 3 : Choix du mode de paiement**
- **Mobile Money (Mvola)** : Le mode de paiement principal, intégré nativement à la plateforme
- L'interface guide l'utilisateur vers le paiement via son téléphone mobile
- Un code QR ou un numéro de transaction est généré pour faciliter le paiement

**Étape 4 : Confirmation du paiement**
- Une fois le paiement validé, l'utilisateur est redirigé vers une page de confirmation
- Un numéro de commande unique est généré et affiché
- Les billets sont automatiquement créés et disponibles dans "Mes billets"
- Un email de confirmation est envoyé avec tous les détails de la commande

### Les coulisses techniques ⚙️
Le processus de paiement est orchestré par TicketController::processPayment() :
1. **Validation** : Le système vérifie que le panier n'est pas vide et que les informations sont complètes
2. **Création de la commande** : Une commande (order) est créée avec le statut "pending" (en attente)
3. **Intégration Mvola** : Le service de paiement PaymentService initie la transaction via l'API Mvola
4. **Suivi de transaction** : Chaque tentative de paiement est enregistrée dans payment_transactions pour traçabilité
5. **Webhook de confirmation** : Un webhook Mvola confirme automatiquement le paiement réussi
6. **Génération des billets** : Une fois le paiement confirmé, les billets individuels sont créés dans la table tickets
7. **Nettoyage du panier** : Le panier est vidé automatiquement après confirmation du paiement
8. **Notifications** : Des notifications sont envoyées à l'utilisateur pour l'informer de chaque étape

---

## ✅ c. Confirmation et réception des billets

Une fois le paiement validé, l'utilisateur reçoit confirmation de son achat et peut accéder immédiatement à ses billets.

### L'expérience utilisateur
La page de confirmation est un moment de réjouissance et de rassurance :

**Informations affichées** :
- **Numéro de commande** : Un code unique (format : CMD-YYYYMMDD-XXXXXX) pour référence
- **Montant total** : Le montant payé est clairement affiché
- **Nombre de billets** : Le total de billets achetés est indiqué
- **Statut** : Le statut de la commande (payée, en attente, etc.)

**Actions disponibles** :
- **Voir mes billets** : Un bouton permet d'accéder directement à la page "Mes billets"
- **Télécharger le PDF** : Chaque billet peut être téléchargé au format PDF pour impression
- **Partager** : L'utilisateur peut partager ses billets avec d'autres personnes si nécessaire

**Email de confirmation** :
- Un email automatique est envoyé avec :
  - Le récapitulatif de la commande
  - Les détails de chaque billet
  - Les informations pratiques de l'événement
  - Un lien pour accéder aux billets en ligne

### Sous le capot 🛠️
La confirmation et la génération des billets sont gérées par plusieurs composants :
1. **Création des billets** : TicketRepository crée un enregistrement ticket pour chaque billet acheté, avec un code unique (QR code)
2. **Génération PDF** : TicketController::generateTicketPdf() utilise DomPDF pour créer un PDF sécurisé avec :
   - Les informations de l'événement
   - Le code QR unique du billet
   - Les informations du détenteur
   - Les conditions d'utilisation
3. **Statut de commande** : La commande passe du statut "pending" à "paid" une fois le paiement confirmé
4. **Historique** : La commande est automatiquement ajoutée à l'historique d'achat de l'utilisateur
5. **Notifications** : NotificationService envoie des notifications push et email pour informer l'utilisateur

---

## 🔄 d. Gestion des erreurs et cas particuliers

Le système gère avec élégance les situations exceptionnelles pour garantir une expérience fluide même en cas de problème.

### Gestion des erreurs
**Paiement échoué** :
- Si le paiement échoue, l'utilisateur est informé clairement du problème
- La commande reste en statut "pending" et peut être réessayée
- Le panier est préservé pour permettre une nouvelle tentative

**Timeout de paiement** :
- Si le délai de paiement (15 minutes) est dépassé, la commande est automatiquement annulée
- Les billets réservés sont libérés pour d'autres utilisateurs
- L'utilisateur peut recréer sa commande si les billets sont encore disponibles

**Billets épuisés** :
- Si des billets sont épuisés entre l'ajout au panier et le paiement, l'utilisateur est informé
- Le système propose automatiquement des alternatives si disponibles
- Le panier est mis à jour pour refléter la disponibilité réelle

**Problèmes techniques** :
- En cas d'erreur technique, un message clair est affiché
- Les transactions sont tracées pour permettre un suivi et une résolution manuelle si nécessaire
- Un support est disponible pour aider l'utilisateur en cas de problème persistant

---

## 🎭 Scénario d'utilisation : Le parcours de Fidy

Pour illustrer la fluidité de ce processus, suivons **Fidy**, un père de famille qui souhaite acheter des billets pour un concert avec ses deux enfants.

### 1. La découverte
Fidy découvre un concert de musique traditionnelle malgache qui l'intéresse. Il consulte les détails et voit qu'il y a des billets adultes à 30 000 MGA et des billets enfants à 15 000 MGA.

### 2. L'ajout au panier
Sur la page de détails, Fidy sélectionne :
- 1 billet adulte (pour lui)
- 2 billets enfants (pour ses enfants)
Il clique sur "Ajouter au panier". Un message de confirmation apparaît : "3 billets ajoutés au panier". L'icône du panier en haut de page affiche "3".

### 3. La consultation du panier
Fidy clique sur l'icône du panier pour voir le récapitulatif. Il voit :
- Le concert avec son image et sa date
- 1 billet Adulte × 30 000 MGA = 30 000 MGA
- 2 billets Enfant × 15 000 MGA = 30 000 MGA
- **Total : 60 000 MGA**

Il vérifie que tout est correct et clique sur "Procéder au paiement".

### 4. Le paiement
Sur la page de paiement, Fidy :
- Vérifie le récapitulatif (tout est correct)
- Renseigne son numéro de téléphone Mvola (pré-rempli car il est connecté)
- Accepte les conditions générales
- Clique sur "Payer avec Mvola"

Un code de transaction est généré. Fidy reçoit une notification sur son téléphone pour confirmer le paiement. Il valide sur son téléphone.

### 5. La confirmation
Quelques secondes plus tard, Fidy est redirigé vers la page de confirmation. Il voit :
- **Commande #CMD-20241215-000123**
- **Montant payé : 60 000 MGA**
- **3 billets**

Il reçoit également un email de confirmation avec tous les détails. Il clique sur "Voir mes billets" et voit ses 3 billets avec leurs codes QR uniques. Il peut les télécharger en PDF pour les imprimer.

### 6. L'événement
Le jour du concert, Fidy présente ses billets (sur téléphone ou imprimés) à l'entrée. Le code QR est scanné, validé, et la famille entre sans problème.

---

> [!TIP]
> **Le saviez-vous ?**
> Le panier est automatiquement synchronisé entre tous vos appareils si vous êtes connecté. Vous pouvez ajouter des billets sur votre ordinateur et finaliser l'achat sur votre téléphone, ou inversement !




