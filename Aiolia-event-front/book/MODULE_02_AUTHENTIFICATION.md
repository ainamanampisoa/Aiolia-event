# 🔐 Module 02 : Authentification

## Description

Le module d'authentification gère l'ensemble du cycle de vie de l'identité utilisateur : inscription, connexion, déconnexion et récupération de mot de passe. Il utilise un système hybride combinant sessions PHP et tokens JWT pour une sécurité optimale.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/AuthController.php` |
| Service | `src/Service/Security/AuthService.php` |
| Service | `src/Service/Security/AuthTokenService.php` |
| Mailer | `src/Service/Notification/UserMailer.php` |
| Templates | `templates/auth/login.html.twig` |
| Templates | `templates/auth/register.html.twig` |
| Templates | `templates/auth/forgot_password.html.twig` |

---

## 🎯 Fonctionnalités

### 1. Inscription (Register)
- **Route** : `/register` (GET/POST)
- **Champs requis** : Prénom, nom, email, téléphone (optionnel), mot de passe
- **Validations** :
  - Email unique et format valide
  - Mot de passe minimum 8 caractères
  - Téléphone au format international (+261...)
- **Post-inscription** : Email de bienvenue automatique

### 2. Connexion (Login)
- **Route** : `/login` (GET/POST)
- **Mécanisme** : Vérification email/mot de passe hashé
- **Tokens** : Génération JWT (access + refresh)
- **Session** : Stockage du profil utilisateur

### 3. Déconnexion (Logout)
- **Route** : `/logout`
- **Actions** : Invalidation des tokens, destruction de la session

### 4. Récupération de mot de passe
- **Route** : `/forgot-password`
- **Statut** : Page disponible, fonctionnalité à compléter

### 5. API REST
- `/api/auth/register` - Inscription via API
- `/api/auth/login` - Connexion via API
- `/api/auth/logout` - Déconnexion via API
- `/api/auth/refresh` - Renouvellement du token
- `/api/auth/profile` - Récupération/Mise à jour du profil

---

## 🔄 Flux d'inscription

```
┌─────────────────┐
│   Formulaire    │
│   Inscription   │
└────────┬────────┘
         │ POST /register
         ▼
┌─────────────────┐
│ AuthController  │
│  registerPage() │
└────────┬────────┘
         │ Validation des champs
         ▼
┌─────────────────┐
│  AuthService    │
│   register()    │
└────────┬────────┘
         │
         ├──► Vérification email unique
         ├──► Hachage du mot de passe
         ├──► Création compte en BDD
         ├──► Envoi email de bienvenue
         │
         ▼
┌─────────────────┐
│   Redirection   │
│   vers /login   │
└─────────────────┘
```

---

## 🔄 Flux de connexion

```
┌─────────────────┐
│   Formulaire    │
│    Connexion    │
└────────┬────────┘
         │ POST /login
         ▼
┌─────────────────┐
│ AuthController  │
│   loginPage()   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  AuthService    │
│     login()     │
└────────┬────────┘
         │
         ├──► Vérification identifiants
         ├──► Génération tokens JWT
         ├──► Stockage session
         │
         ▼
┌─────────────────┐
│   Redirection   │
│    vers home    │
└─────────────────┘
```

---

## 📋 Scénario d'utilisation

### Scénario 1 : Première inscription de Rindra

1. **Rindra** clique sur "Créer un compte"
2. Elle remplit le formulaire :
   - Prénom : Rindra
   - Nom : Rasoarivelo
   - Email : rindra@example.com
   - Téléphone : +261 32 00 000 00 (le drapeau 🇲🇬 est affiché)
   - Mot de passe : ******** (minimum 8 caractères)
3. Elle valide le formulaire
4. Un message de succès s'affiche
5. Elle est redirigée vers la page de connexion
6. Son email est pré-rempli dans le champ
7. Elle reçoit un email de bienvenue

### Scénario 2 : Connexion quotidienne de Tojo

1. **Tojo** accède à la page de connexion
2. Il saisit son email et son mot de passe
3. Le système vérifie ses identifiants
4. Une session est créée avec son profil
5. Des tokens JWT sont générés
6. Il est redirigé vers la page d'accueil
7. Un message de bienvenue s'affiche

### Scénario 3 : Tentative de connexion échouée

1. **Mamy** saisit un mauvais mot de passe
2. Le système affiche : "Identifiants incorrects"
3. L'email reste pré-rempli pour réessayer
4. Après 5 tentatives, un CAPTCHA pourrait s'afficher

---

## 🛠️ Points techniques

### Structure de la session utilisateur

```php
$session->set('user', [
    'id' => $result['user']['id'],
    'email' => $result['user']['email'],
    'username' => $result['user']['full_name'],
    'profile' => $result['user'],
    'tokens' => $result['tokens'],
]);
```

### Validation du formulaire d'inscription

```php
if ('' === $formData['first_name']) {
    $errors[] = 'Le prénom est obligatoire.';
}

if (strlen($password) < 8) {
    $errors[] = 'Le mot de passe doit comporter au moins 8 caractères.';
}

if (!preg_match('/^\+\d{6,18}$/', $formData['phone'])) {
    $errors[] = 'Le numéro de téléphone doit être au format international.';
}
```

### Normalisation du téléphone

```php
if ('' !== $phoneRaw && !str_starts_with($phoneRaw, '+')) {
    $formData['phone'] = '+261' . ltrim($phoneRaw, '0');
}
```

---

## 🔒 Sécurité

| Mesure | Description |
|--------|-------------|
| Hachage mot de passe | Algorithme bcrypt via Symfony |
| Tokens JWT | Access token (15min) + Refresh token (7 jours) |
| HTTPS | Obligatoire en production |
| CSRF | Protection sur les formulaires |
| Rate limiting | À implémenter pour éviter le brute force |

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/login` | GET/POST | Page de connexion |
| `/register` | GET/POST | Page d'inscription |
| `/logout` | GET | Déconnexion |
| `/forgot-password` | GET | Récupération mot de passe |
| `/api/auth/login` | POST | API connexion |
| `/api/auth/register` | POST | API inscription |
| `/api/auth/logout` | POST | API déconnexion |
| `/api/auth/refresh` | POST | API renouvellement token |
| `/api/auth/profile` | GET/PUT | API profil utilisateur |

---

## 🎨 Éléments d'interface

| Élément | Description |
|---------|-------------|
| Formulaire login | Email + mot de passe + bouton connexion |
| Formulaire register | Prénom, nom, email, téléphone (🇲🇬), mot de passe |
| Messages d'erreur | Alertes rouges sous les champs |
| Message de succès | Bandeau vert après inscription |
| Liens de navigation | "Pas de compte ? Inscrivez-vous" |

---

## 🔗 Dépendances

- **AuthService** : Logique métier d'authentification
- **AuthTokenService** : Génération et validation JWT
- **UserMailer** : Envoi des emails de bienvenue
- **PasswordHasher** : Hachage sécurisé des mots de passe

