# 📖 Livre du Projet : Gestion des Billets

Une fois les billets achetés, l'utilisateur doit pouvoir les consulter, les télécharger, les transférer et les utiliser facilement. Ce module couvre toutes les fonctionnalités liées à la gestion des billets après l'achat, garantissant une expérience complète jusqu'au jour de l'événement.

---

## 🎫 a. Consultation des billets

La page "Mes billets" est l'espace central où l'utilisateur peut voir tous ses billets, organisés de manière claire et accessible.

### L'expérience utilisateur
**Affichage des billets** :
- Les billets sont organisés par statut :
  - **À venir** : Événements futurs pour lesquels l'utilisateur a des billets valides
  - **Passés** : Événements terminés (pour consultation historique)
  - **Annulés** : Événements annulés avec billets remboursés ou à rembourser
- Chaque billet affiche :
  - L'image et le nom de l'événement
  - La date et l'heure de l'événement
  - Le lieu (ville et adresse)
  - Le type de billet (VIP, Standard, etc.)
  - Le code QR unique du billet
  - Le statut (valide, utilisé, remboursé, etc.)

**Navigation** :
- Des onglets permettent de filtrer rapidement par statut
- Un compteur affiche le nombre de billets dans chaque catégorie
- La recherche permet de trouver rapidement un billet spécifique

### Sous le capot 🛠️
La consultation des billets est gérée par TicketController::myTickets() :
1. **Récupération** : TicketRepository::findUserTickets() récupère les billets de l'utilisateur avec filtrage par statut
2. **Organisation** : Les billets sont groupés par événement et triés par date
3. **Statuts** : Le système calcule automatiquement le statut de chaque billet (à venir, passé, annulé) basé sur la date de l'événement et le statut du billet

---

## 📄 b. Téléchargement et impression des billets

Chaque billet peut être téléchargé au format PDF pour impression ou conservation, avec toutes les informations nécessaires pour l'entrée à l'événement.

### L'expérience utilisateur
**Génération du PDF** :
- Sur la page "Mes billets", chaque billet a un bouton "Télécharger PDF"
- Le PDF généré contient :
  - Les informations de l'événement (nom, date, lieu)
  - Les informations du détenteur (nom, email)
  - Le code QR unique du billet (pour validation à l'entrée)
  - Les conditions d'utilisation
  - Un design professionnel et lisible

**Utilisation** :
- Le PDF peut être imprimé pour présentation à l'entrée
- Il peut également être affiché sur smartphone (version numérique)
- Le code QR peut être scanné directement depuis l'écran

### Les coulisses techniques ⚙️
La génération PDF est gérée par TicketController::generateTicketPdf() :
1. **Récupération des données** : Les informations du billet et de l'événement sont récupérées
2. **Génération QR** : Un code QR unique est généré contenant l'ID du billet et un hash de sécurité
3. **Création PDF** : DomPDF génère le PDF à partir d'un template Twig
4. **Sécurité** : Le PDF inclut des éléments anti-contrefaçon (hash, timestamp)

---

## 🔄 c. Transfert de billets

Le transfert de billets permet à un utilisateur de donner ou vendre ses billets à une autre personne, offrant flexibilité et facilité.

### L'expérience utilisateur
**Processus de transfert** :
1. L'utilisateur sélectionne un billet qu'il souhaite transférer
2. Il clique sur "Transférer ce billet"
3. Il saisit l'email ou le numéro de téléphone du destinataire
4. Il peut ajouter un message personnel (optionnel)
5. Le système envoie une notification au destinataire
6. Le destinataire accepte le transfert
7. Le billet est transféré et apparaît dans "Mes billets" du destinataire

**Gestion des transferts** :
- L'utilisateur peut voir l'historique de ses transferts (billets envoyés et reçus)
- Les transferts peuvent être annulés avant acceptation
- Une fois accepté, le transfert est définitif

### Les coulisses techniques ⚙️
Le transfert de billets est géré par TicketController::transferTicket() :
1. **Validation** : Le système vérifie que le billet peut être transféré (non utilisé, événement à venir)
2. **Création de la demande** : Une entrée est créée dans ticket_transfers avec le statut "pending"
3. **Notification** : Le destinataire reçoit une notification (email et/ou push)
4. **Acceptation** : Lors de l'acceptation, le user_id du billet est mis à jour
5. **Historique** : Le transfert est enregistré pour traçabilité

---

## ✅ d. Validation des billets à l'entrée

Le jour de l'événement, les billets doivent être validés à l'entrée. Le système offre des outils pour les organisateurs et une expérience fluide pour les participants.

### L'expérience utilisateur (participant)
**Présentation du billet** :
- Le participant présente son billet (PDF imprimé ou sur smartphone)
- Le code QR est scanné par l'organisateur
- Le billet est validé instantanément
- Le participant peut entrer

**Statut du billet** :
- Une fois validé, le billet passe au statut "used" (utilisé)
- Il apparaît dans la section "Passés" de "Mes billets"
- Il ne peut plus être transféré ou remboursé

### L'expérience utilisateur (organisateur)
**Validation** :
- L'organisateur utilise une application ou interface web de validation
- Il scanne le code QR du billet
- Le système vérifie :
  - Que le billet existe et est valide
  - Que l'événement correspond
  - Que le billet n'a pas déjà été utilisé
- Si tout est valide, le billet est marqué comme "used"

### Sous le capot 🛠️
La validation est gérée par TicketController::validateTicket() :
1. **Scan QR** : Le code QR est décodé pour extraire l'ID du billet et le hash
2. **Vérification** : Le système vérifie la validité du billet et du hash
3. **Validation** : Le statut du billet est mis à jour à "used"
4. **Traçabilité** : L'heure et le lieu de validation sont enregistrés

---

## 📊 e. Détails d'un billet

Chaque billet a une page de détails complète affichant toutes les informations pertinentes.

### L'expérience utilisateur
**Informations affichées** :
- **Événement** : Nom, description, image, date, heure, lieu complet
- **Billet** : Type, prix payé, code unique, statut
- **Détenteur** : Nom, email (peut être différent si transféré)
- **Accès** : Instructions pour se rendre à l'événement, plan d'accès
- **Contact** : Informations de contact de l'organisateur en cas de question

**Actions disponibles** :
- Télécharger le PDF
- Transférer le billet (si possible)
- Partager le billet (lien de partage)
- Voir l'événement complet

### Sous le capot 🛠️
Les détails sont récupérés par TicketRepository::findTicketById() qui agrège :
- Données du billet depuis tickets
- Données de l'événement depuis events
- Données du détenteur depuis users
- Historique de transfert depuis ticket_transfers

---

## 🎭 Scénario d'utilisation : Les billets de Hery

Suivons **Hery**, qui a acheté des billets pour un festival.

### 1. Consultation des billets
Hery accède à "Mes billets" et voit ses 3 billets pour le festival. Ils sont dans l'onglet "À venir" car l'événement est dans 2 semaines. Il voit les informations essentielles : date, lieu, type de billet.

### 2. Téléchargement des PDF
Hery télécharge les 3 PDFs de ses billets. Il les imprime et les garde dans son portefeuille pour le jour J. Il garde aussi les versions numériques sur son téléphone au cas où.

### 3. Transfert d'un billet
Un de ses amis ne peut finalement pas venir. Hery décide de transférer un billet à son cousin. Il clique sur "Transférer", saisit l'email de son cousin, et envoie. Son cousin reçoit une notification, accepte le transfert, et le billet apparaît dans son compte.

### 4. Le jour de l'événement
Hery arrive au festival avec ses 2 billets (lui et son cousin). À l'entrée, les codes QR sont scannés. Les billets sont validés instantanément. Hery et son cousin entrent sans problème.

### 5. Consultation après l'événement
Quelques jours après le festival, Hery consulte à nouveau "Mes billets". Le festival apparaît maintenant dans l'onglet "Passés". Il peut toujours consulter les détails et le PDF pour ses archives.

---

> [!TIP]
> **Le saviez-vous ?**
> Vous pouvez afficher vos billets directement sur votre smartphone. Pas besoin d'imprimer ! Le code QR fonctionne parfaitement depuis l'écran de votre téléphone.

