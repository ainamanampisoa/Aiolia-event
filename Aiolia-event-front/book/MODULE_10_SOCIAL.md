# 👥 Module 10 : Social et Partage

## Description

Le module Social permet aux utilisateurs de partager des événements, d'inviter des amis et de créer des connexions au sein de la plateforme. Il renforce l'aspect communautaire d'Aiolia-event et favorise le bouche-à-oreille.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/SocialController.php` |
| Templates | `templates/social/invitations.html.twig` |
| Templates | `templates/ticket/my_tickets.html.twig` (partage) |

---

## 🎯 Fonctionnalités

### 1. Invitations d'amis
- **Route** : `/friends`
- Inviter des amis par email
- Suivi des invitations envoyées
- Bonus pour le parrain et le filleul

### 2. Partage d'événements
- Boutons de partage sur les réseaux sociaux
- Génération de liens partageables
- Tracking des partages

### 3. Partage de billets
- Partager un billet acheté (transfert)
- Invitation à un événement avec place réservée

### 4. Ajout au calendrier (depuis Mes Billets)
- **Google Calendar** : Lien direct
- **Apple Calendar** : Fichier .ics
- **Outlook** : Lien ou fichier .ics

---

## 🔄 Flux d'invitation

```
┌─────────────────┐
│   Utilisateur   │
│ invite un ami   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Formulaire     │
│  d'invitation   │
└────────┬────────┘
         │ Email de l'ami
         ▼
┌─────────────────┐
│  Envoi email    │
│  d'invitation   │
└────────┬────────┘
         │
         ├──► Lien d'inscription avec code parrain
         │
         ▼
┌─────────────────┐
│    L'ami        │
│   s'inscrit     │
└────────┬────────┘
         │
         ├──► Bonus pour le parrain
         ├──► Bonus pour le filleul
         │
         ▼
┌─────────────────┐
│    Bonus        │
│   appliqués     │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Inviter un ami

1. **Miora** accède à `/friends`
2. Elle voit son code parrain : "MIORA2024"
3. Elle entre l'email de son ami : ami@example.com
4. Elle clique sur "Envoyer l'invitation"
5. Son ami reçoit un email avec un lien d'inscription
6. L'ami s'inscrit avec le code parrain
7. **Miora** reçoit 5 000 MGA de crédit wallet
8. L'ami reçoit 2 500 MGA de bienvenue

### Scénario 2 : Partage sur les réseaux sociaux

1. **Hery** consulte un événement qui lui plaît
2. Il clique sur le bouton Facebook
3. Une fenêtre de partage s'ouvre
4. Le message est pré-rempli :
   > "Je vais au Festival Gasy ! 🎉 Rejoins-moi sur Aiolia-event"
5. Il publie sur son mur
6. Ses amis voient l'événement

### Scénario 3 : Ajout au calendrier

1. **Soa** a acheté un billet pour un concert
2. Elle accède à "Mes billets"
3. Elle clique sur l'icône calendrier 📅
4. Un menu déroulant s'affiche :
   - Google Calendar
   - Apple Calendar (.ics)
   - Outlook
5. Elle choisit Google Calendar
6. Un nouvel onglet s'ouvre avec l'événement pré-rempli :
   - Titre : "Concert Rock - Aiolia Event"
   - Date et heure : 15 Feb 2024, 19:00
   - Lieu : "Palais des Sports, Antananarivo"
7. Elle confirme l'ajout à son agenda

---

## 🛠️ Points techniques

### Génération lien Google Calendar

```javascript
function generateGoogleCalendarLink(event) {
    const baseUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
    
    const startDate = formatDateForGoogle(event.startDate);
    const endDate = formatDateForGoogle(event.endDate);
    
    const params = new URLSearchParams({
        text: event.title,
        dates: `${startDate}/${endDate}`,
        details: event.description,
        location: event.location,
        sf: 'true',
        output: 'xml'
    });
    
    return `${baseUrl}&${params.toString()}`;
}

function formatDateForGoogle(date) {
    // Format: YYYYMMDDTHHmmssZ
    return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
}
```

### Génération fichier .ics (Apple/Outlook)

```javascript
function generateICSFile(event) {
    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Aiolia Event//FR',
        'BEGIN:VEVENT',
        `DTSTART:${formatDateForICS(event.startDate)}`,
        `DTEND:${formatDateForICS(event.endDate)}`,
        `SUMMARY:${event.title}`,
        `DESCRIPTION:${event.description}`,
        `LOCATION:${event.location}`,
        `UID:${event.id}@aiolia-event.com`,
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\r\n');
    
    // Créer et télécharger le fichier
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${event.title}.ics`;
    link.click();
}
```

### Menu déroulant calendrier (Template Twig)

```twig
<div class="calendar-dropdown">
    <button class="action-btn btn-calendar" title="Ajouter au calendrier">
        <i class="fas fa-calendar-plus"></i>
    </button>
    <div class="calendar-menu">
        <a href="#" class="calendar-option" data-type="google">
            <i class="fab fa-google"></i> Google Calendar
        </a>
        <a href="#" class="calendar-option" data-type="apple">
            <i class="fab fa-apple"></i> Apple Calendar
        </a>
        <a href="#" class="calendar-option" data-type="outlook">
            <i class="fab fa-microsoft"></i> Outlook
        </a>
    </div>
</div>
```

### CSS pour le dropdown

```css
.calendar-dropdown {
    position: relative;
}

.calendar-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100;
    min-width: 180px;
}

.calendar-dropdown:hover .calendar-menu,
.calendar-dropdown.active .calendar-menu {
    display: block;
}

.calendar-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #333;
    text-decoration: none;
}

.calendar-option:hover {
    background: #f5f5f5;
}
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/friends` | GET | Page invitations |
| `/api/social/invite` | POST | Envoyer invitation |
| `/api/social/share/{eventId}` | POST | Tracker un partage |
| `/api/social/referral-code` | GET | Obtenir son code parrain |

---

## 🎨 Éléments d'interface

### Page invitations

| Élément | Description |
|---------|-------------|
| Code parrain | Affiché avec bouton copier |
| Formulaire | Champ email + bouton envoyer |
| Liste invitations | Statut (En attente, Inscrit) |
| Statistiques | Nombre de filleuls, bonus gagnés |

### Boutons partage (événement)

| Réseau | Icône | Couleur |
|--------|-------|---------|
| Facebook | 📘 | #1877f2 |
| Twitter/X | ✖️ | #000000 |
| WhatsApp | 💬 | #25d366 |
| Copier lien | 🔗 | Gris |

### Dropdown calendrier (billets)

| Option | Icône | Action |
|--------|-------|--------|
| Google Calendar | 📅 | Ouvre nouvel onglet |
| Apple Calendar | 🍎 | Télécharge .ics |
| Outlook | 📧 | Télécharge .ics |

---

## 📊 Structure de données

### Table `referrals`

```sql
CREATE TABLE aiolia.referrals (
    id SERIAL PRIMARY KEY,
    referrer_id INTEGER REFERENCES aiolia.users(id),
    referee_id INTEGER REFERENCES aiolia.users(id),
    referral_code VARCHAR(50),
    status VARCHAR(20), -- 'pending', 'completed'
    referrer_bonus DECIMAL(10,2),
    referee_bonus DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP
);
```

### Table `social_shares`

```sql
CREATE TABLE aiolia.social_shares (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES aiolia.users(id),
    event_id INTEGER REFERENCES aiolia.events(id),
    platform VARCHAR(50), -- 'facebook', 'twitter', 'whatsapp', 'copy'
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

## 🎁 Programme de parrainage

| Action | Bonus Parrain | Bonus Filleul |
|--------|---------------|---------------|
| Inscription | 5 000 MGA | 2 500 MGA |
| Premier achat | 10% du montant | - |

---

## 🔗 Dépendances

- **SocialController** : Logique sociale
- **UserMailer** : Envoi invitations
- **WalletService** : Attribution bonus
- **NotificationService** : Notifications

