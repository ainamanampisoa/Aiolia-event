# 📖 Livre du Projet : Gestion des Utilisateurs

Bienvenue dans le cœur de l'interaction humaine de notre plateforme. La gestion des utilisateurs n'est pas qu'une question de bases de données et de tokens ; c'est le premier pas de nos visiteurs dans l'univers **Aiolia-event**.

---

## 🚪 a. Connexion de l'utilisateur (Login)

La connexion est la porte d'entrée. Nous l'avons voulue fluide et sécurisée, pour que l'utilisateur se sente "chez lui" dès le premier clic.

### L'expérience utilisateur
Lorsqu'un utilisateur revient nous voir, il lui suffit de saisir son adresse email et son mot de passe. 
- **La petite touche** : Si l'utilisateur vient de s'inscrire, il est accueilli avec un message de bienvenue personnalisé sur la page de connexion, l'invitant à franchir le seuil.
- **La sécurité invisible** : Derrière chaque tentative, nous vérifions non seulement les identifiants, mais aussi si le compte est actif. Nous veillons sur nos membres.

### Sous le capot 🛠️
Techniquement, la connexion repose sur le `AuthService`. 
1. **Vérification** : Nous comparons le mot de passe (haché, bien sûr, pour une sécurité maximale) avec celui en base.
2. **Session & JWT** : Pour le confort de la navigation, nous utilisons une session PHP classique pour le front-end, tout en générant des tokens JWT (Access & Refresh) qui nous permettent de sécuriser les futurs échanges avec nos APIs.
3. **Persistance** : Une fois connecté, l'utilisateur est reconnu partout sur le site grâce à son profil stocké en session.

---

## 📝 b. Inscription de l'utilisateur (Register)

L'inscription est le début d'une aventure. Nous avons simplifié le formulaire pour ne demander que l'essentiel : nom, prénom, email et un mot de passe solide.

### Le parcours d'accueil
1. **Formulaire chaleureux** : Un design épuré qui guide l'utilisateur pas à pas. Nous avons notamment intégré le **drapeau de Madagascar 🇲🇬** et pré-rempli l'indicatif **+261**, facilitant ainsi la saisie du numéro de téléphone.
2. **Validation bienveillante** : Si une information manque ou si le mot de passe est trop court, nous l'informons immédiatement par des messages clairs, sans jargon technique.
3. **Le mail de bienvenue** : Dès que l'inscription est validée, un email automatique est envoyé via `UserMailer`. C'est notre façon de dire "Heureux de vous compter parmi nous".

### Les coulisses techniques ⚙️
Le `AuthService::register` orchestre cette naissance :
- **Hachage** : Le mot de passe n'est jamais stocké en clair. Il est transformé en une empreinte numérique indéchiffrable.
- **Normalisation** : Nous nettoyons les données (minuscules pour l'email, espaces supprimés) pour éviter les doublons accidentels.
- **Prêt pour la suite** : À la fin du processus, un compte utilisateur est créé avec le statut "Actif", prêt à acheter son premier ticket.

---

## 🎭 Scénario d'utilisation : Le parcours de Rindra

Pour mieux comprendre l'impact de ces fonctionnalités, suivisons **Rindra**, une passionnée de musique qui souhaite assister au prochain grand concert sur Aiolia-event.

### 1. La découverte
Rindra arrive sur la page d'accueil. Elle a un coup de cœur pour un événement. En cliquant sur "Acheter un ticket", elle se rend compte qu'elle doit être connectée pour finaliser son achat.

### 2. Le premier pas (Inscription)
N'ayant pas encore de compte, elle clique sur "Créer un compte". Elle saisit son prénom, son nom, et son email. Pour son téléphone, elle remarque le **drapeau Malagasy 🇲🇬** et le **+261** déjà présents, elle n'a plus qu'à taper son numéro (ex: 32 00 000 00). Elle choisit un mot de passe sécurisé. 
*   **Moment de confort** : Le formulaire est clair, elle n'est pas submergée par des demandes inutiles et l'indicatif visuel avec le drapeau lui fait gagner du temps et renforce sa confiance. Elle valide.

### 3. L'accueil (Connexion)
Après avoir validé, Rindra est automatiquement redirigée vers la page de connexion. Un petit bandeau vert lui indique : *"Inscription réussie ! Vous pouvez vous connecter"*. Elle se sent rassurée. Elle entre ses nouveaux identifiants.

### 4. L'accès au rêve
Une fois connectée, Rindra est redirigée vers l'accueil (ou l'événement qu'elle convoitait). Son nom apparaît désormais en haut de l'écran. Elle n'est plus une simple visiteuse, elle est membre de la communauté. Elle peut maintenant charger son panier et réserver sa place en quelques clics.

---

> [!TIP]
> **Le saviez-vous ?**
> Nous supportons également la connexion via des fournisseurs tiers (Google, Facebook). C'est la voie rapide pour ceux qui veulent rejoindre l'événement sans attendre !
