# 🎰 Module 08 : Ticket Chance (Roue de la Fortune)

## Description

Ticket Chance est un mini-jeu de type "roue de la fortune" qui permet aux utilisateurs de gagner des réductions, des billets gratuits ou des améliorations VIP. C'est un outil de fidélisation et d'engagement qui récompense les utilisateurs actifs.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/ProfileController.php` |
| Contrôleur | `src/Controller/GameController.php` |
| Service | `src/Service/TicketChanceService.php` |
| Templates | `templates/profile/ticket_chance.html.twig` |
| Templates | `templates/game/ticket_chance.html.twig` |
| CSS | `public/vente-ticket/css/ticket-chance.css` |

---

## 🎯 Fonctionnalités

### 1. Roue de la fortune
- Animation de rotation fluide
- 8 segments avec différents prix
- Résultat aléatoire pondéré par probabilités

### 2. Prix disponibles
| Prix | Probabilité | Description |
|------|-------------|-------------|
| Réduction 5% | 25% | Code promo valable 30 jours |
| Réduction 10% | 15% | Code promo valable 30 jours |
| Réduction 15% | 10% | Code promo valable 30 jours |
| Réduction 20% | 5% | Code promo valable 30 jours |
| Réduction 50% | 2% | Code promo valable 30 jours |
| Billet gratuit | 3% | Pour le prochain événement |
| Upgrade VIP | 5% | Amélioration de billet |
| Partie bonus | 35% | Une chance de plus |

### 3. Règles du jeu
- **1 partie gratuite par semaine** (reset le lundi)
- **Parties bonus** : gagnées dans le jeu ou après achat
- **Validité des prix** : 30 jours
- **Limite anti-abus** : Maximum 5 parties par jour

---

## 🔄 Flux de jeu

```
┌─────────────────┐
│  Utilisateur    │
│ clique "Jouer"  │
└────────┬────────┘
         │ POST /api/ticket-chance/play
         ▼
┌─────────────────┐
│ ProfileController│
│ playTicketChance│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│TicketChance     │
│   Service       │
│    play()       │
└────────┬────────┘
         │
         ├──► Vérification éligibilité
         ├──► Tirage au sort pondéré
         ├──► Enregistrement résultat
         ├──► Application du prix (si applicable)
         │
         ▼
┌─────────────────┐
│   Animation     │
│   + résultat    │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Première partie de la semaine

1. **Mamy** accède à `/profile/ticket-chance`
2. Elle voit le message "1 partie gratuite disponible"
3. Elle clique sur "Lancer la roue"
4. La roue tourne pendant 3 secondes
5. Elle s'arrête sur "Réduction 10%"
6. Un popup de félicitations s'affiche
7. Le code promo "CHANCE-XXXXX" est généré
8. Elle peut l'utiliser sur son prochain achat

### Scénario 2 : Gagner une partie bonus

1. **Faly** lance la roue
2. Elle s'arrête sur "🎁 Partie bonus"
3. Un message indique : "Vous avez gagné une partie bonus !"
4. Le bouton "Jouer" redevient actif
5. Il peut relancer immédiatement la roue
6. Cette fois, il gagne une réduction de 15%

### Scénario 3 : Plus de parties disponibles

1. **Lova** a déjà joué sa partie gratuite
2. Elle voit "Prochaine partie gratuite dans 5 jours"
3. Le bouton "Jouer" est désactivé
4. Elle peut voir l'historique de ses gains passés
5. Un message suggère : "Achetez un billet pour gagner des parties bonus !"

### Scénario 4 : Utilisation d'un prix

1. **Hery** a gagné une réduction de 20%
2. Il accède à la page de paiement
3. Il entre le code "CHANCE-ABC123"
4. Le prix est réduit de 20%
5. Après utilisation, le code devient invalide

---

## 🛠️ Points techniques

### Vérification d'éligibilité

```php
public function canUserPlay(int $userId): array
{
    // Vérifier la partie gratuite hebdomadaire
    $lastFreePlay = $this->getLastFreePlay($userId);
    $canPlayFree = $lastFreePlay === null || 
                   $lastFreePlay < $this->getWeekStart();
    
    // Vérifier les parties bonus
    $bonusPlays = $this->getRemainingBonusPlays($userId);
    
    // Vérifier la limite quotidienne
    $todayPlays = $this->getTodayPlaysCount($userId);
    $underDailyLimit = $todayPlays < 5;
    
    return [
        'can_play' => ($canPlayFree || $bonusPlays > 0) && $underDailyLimit,
        'free_play_available' => $canPlayFree,
        'bonus_plays' => $bonusPlays,
        'today_plays' => $todayPlays,
        'next_free_play' => $this->getNextFreePlayDate(),
    ];
}
```

### Tirage au sort pondéré

```php
public function play(int $userId): array
{
    // Récupérer les prix avec leurs probabilités
    $prizes = $this->getAvailablePrizes();
    
    // Tirage pondéré
    $rand = mt_rand(1, 100);
    $cumulative = 0;
    $selectedPrize = null;
    
    foreach ($prizes as $prize) {
        $cumulative += $prize['probability'];
        if ($rand <= $cumulative) {
            $selectedPrize = $prize;
            break;
        }
    }
    
    // Enregistrer l'entrée
    $entryId = $this->recordEntry($userId, $selectedPrize);
    
    // Appliquer le prix
    if ($selectedPrize['type'] !== 'extra_play') {
        $this->applyPrize($userId, $selectedPrize, $entryId);
    }
    
    return [
        'prize' => $selectedPrize,
        'entry_id' => $entryId,
    ];
}
```

### Application du prix (création code promo)

```php
private function applyPrize(int $userId, array $prize, int $entryId): void
{
    if (in_array($prize['type'], ['percent', 'amount'])) {
        // Générer un code promo unique
        $code = 'CHANCE-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Insérer dans la table promo_codes
        $this->connection->insert('aiolia.promo_codes', [
            'code' => $code,
            'discount_type' => $prize['type'],
            'discount_value' => $prize['value'],
            'user_id' => $userId,
            'valid_until' => (new \DateTime('+30 days'))->format('Y-m-d'),
            'ticket_chance_entry_id' => $entryId,
        ]);
    }
}
```

### Animation JavaScript (Frontend)

```javascript
function spinWheel() {
    fetch('/api/ticket-chance/play', {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Calculer l'angle de rotation
            const prizeIndex = getPrizeIndex(data.prize.type);
            const baseRotation = 360 * 5; // 5 tours complets
            const prizeAngle = prizeIndex * (360 / 8);
            const finalRotation = baseRotation + (360 - prizeAngle);
            
            // Appliquer l'animation CSS
            wheel.style.transform = `rotate(${finalRotation}deg)`;
            
            // Afficher le résultat après l'animation
            setTimeout(() => {
                showPrizePopup(data.prize, data.message);
            }, 3000);
        }
    });
}
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/profile/ticket-chance` | GET | Page du jeu |
| `/ticket-chance` | GET | Page alternative |
| `/api/ticket-chance/play` | POST | Lancer la roue |
| `/api/ticket-chance/status` | GET | Statut et éligibilité |

---

## 🎨 Éléments d'interface

### Page du jeu

| Élément | Description |
|---------|-------------|
| Roue | SVG/Canvas avec 8 segments colorés |
| Bouton central | "Jouer" (vert) ou "Indisponible" (gris) |
| Compteur | "1 partie gratuite" / "3 parties bonus" |
| Timer | "Prochaine partie dans X jours" |
| Règles | Encadré avec les règles du jeu |
| Historique | Tableau des gains récents |

### Popup résultat

| Élément | Description |
|---------|-------------|
| Animation | Confettis ou étoiles |
| Icône | 🎉 pour victoire |
| Titre | "Félicitations !" |
| Prix | Nom du prix gagné |
| Code | Code promo (si applicable) |
| Bouton | "Fermer" ou "Rejouer" (si bonus) |

### Segments de la roue

| Segment | Couleur | Texte |
|---------|---------|-------|
| 5% | Vert clair | "-5%" |
| 10% | Vert | "-10%" |
| 15% | Bleu | "-15%" |
| 20% | Violet | "-20%" |
| 50% | Or | "-50%" |
| Billet | Rouge | "🎟️" |
| VIP | Rose | "⭐ VIP" |
| Bonus | Orange | "🎁" |

---

## 📊 Structure de données

### Table `ticket_chance_entries`

```sql
CREATE TABLE aiolia.ticket_chance_entries (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES aiolia.users(id),
    play_type VARCHAR(50), -- 'free', 'bonus', 'purchase'
    prize_type VARCHAR(50), -- 'percent', 'amount', 'free_ticket', 'upgrade', 'extra_play'
    prize_value DECIMAL(10,2),
    prize_code VARCHAR(100),
    promo_code VARCHAR(50),
    metadata JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### Table `ticket_chance_prizes`

```sql
CREATE TABLE aiolia.ticket_chance_prizes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    type VARCHAR(50),
    value DECIMAL(10,2),
    probability INTEGER, -- Pourcentage (1-100)
    is_active BOOLEAN DEFAULT TRUE
);
```

---

## ⚙️ Configuration Sécurité

La route `/api/ticket-chance` utilise l'authentification par session (pas JWT) :

```yaml
# config/packages/security.yaml
firewalls:
    ticket_chance:
        pattern: ^/api/ticket-chance
        security: false  # Session-based auth handled in controller
```

---

## 🔗 Dépendances

- **TicketChanceService** : Logique du jeu
- **ProfileController** : Routes API
- **Session Symfony** : Authentification
- **DBAL** : Accès base de données

