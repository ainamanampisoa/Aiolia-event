# 📝 Note sur les Données de Test

## 🎯 Objectif

Ce fichier explique la configuration des données de test pour la présentation au jury.

---

## 🖼️ Images des Événements

### Modification Apportée

**Toutes les images d'événements** dans `mydata.sql` utilisent désormais **uniquement 2 images de test** :

- ✅ **`img1.png`** - Image de test 1
- ✅ **`img2.png`** - Image de test 2

### Raison

Pour la présentation au jury, nous utilisons des **données de démonstration simplifiées** :
- Plus facile à gérer et à reproduire
- Pas besoin de créer des dizaines d'images uniques
- Permet de se concentrer sur les fonctionnalités plutôt que sur le contenu visuel
- Réduit la taille du projet et facilite le déploiement

### Fichiers Concernés

```
Base/mydata.sql
```

### Sections Modifiées

1. **Insertion des événements principaux** (lignes ~790-1490)
   - Tous les événements utilisent alternativement `img1.png` et `img2.png`

2. **Médias des événements (event_media)** (lignes ~1739-1770)
   - CASE WHEN avec tous les slugs d'événements → `img1.png` ou `img2.png`

3. **Événements supplémentaires**
   - Festival Famille & Enfants → `img1.png`
   - Séminaire Professionnel Adultes → `img2.png`
   - Concert Premium VIP → `img1.png`
   - Festival Sportif → `img2.png`

---

## 📂 Emplacement des Images

Les images doivent être placées dans :

```
~/Documents/MyProject/Aiolia-event/Aiolia-event-front/public/vente-ticket/images/
├── img1.png
└── img2.png
```

**Chemin absolu** :
```
/home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/public/vente-ticket/images/
```

### ✅ Images Existantes

Les images sont **déjà présentes** dans le répertoire :

```bash
-rw-rw-r-- 1 aina aina 128K img1.png
-rw-rw-r-- 1 aina aina 116K img2.png
```

**Format** : PNG  
**Taille** : 
- `img1.png` : 128 Ko
- `img2.png` : 116 Ko

✅ **Aucune action requise** - Les images sont prêtes à l'emploi !

---

## 🔄 Réimportation

Après modification de `mydata.sql`, pour réimporter les données :

### Option 1 : Réimportation Complète

```bash
cd /home/aina/Documents/MyProject/Aiolia-event
sudo -u postgres psql << EOF
DROP DATABASE IF EXISTS aiolia_event;
DROP ROLE IF EXISTS aiolia_user;
\q
EOF
sudo -u postgres psql -f Base/schema.sql
sudo -u postgres psql -d aiolia_event -f Base/logic.sql
sudo -u postgres psql -d aiolia_event -f Base/mydata.sql
```

### Option 2 : Mise à Jour Incrémentale (si données existent)

```bash
cd /home/aina/Documents/MyProject/Aiolia-event

# Mise à jour des cover_image_url dans la table events
sudo -u postgres psql -d aiolia_event << EOF
-- Mettre à jour toutes les images d'événements pour utiliser img1.png ou img2.png
UPDATE aiolia.events 
SET cover_image_url = CASE 
    WHEN MOD(id::integer, 2) = 0 THEN 'vente-ticket/images/img2.png'
    ELSE 'vente-ticket/images/img1.png'
END
WHERE cover_image_url IS NOT NULL;

-- Mettre à jour les event_media
UPDATE aiolia.event_media 
SET url = CASE 
    WHEN MOD(event_id::integer, 2) = 0 THEN 'vente-ticket/images/img2.png'
    ELSE 'vente-ticket/images/img1.png'
END
WHERE media_type = 'image' AND url LIKE '%vente-ticket/images/%';
EOF
```

---

## ✅ Vérification

Pour vérifier que toutes les images utilisent bien img1.png ou img2.png :

```bash
# Dans la base de données
sudo -u postgres psql -d aiolia_event << EOF
-- Compter les images par type
SELECT 
    cover_image_url, 
    COUNT(*) as nb_events
FROM aiolia.events 
WHERE cover_image_url IS NOT NULL
GROUP BY cover_image_url
ORDER BY cover_image_url;
EOF
```

**Résultat attendu** :
```
        cover_image_url         | nb_events 
--------------------------------+-----------
 vente-ticket/images/img1.png  |    XX
 vente-ticket/images/img2.png  |    XX
```

---

## 📊 Statistiques

### Avant Modification
- ❌ ~30 images différentes avec noms spécifiques
- ❌ Besoin de créer toutes les images
- ❌ Gestion complexe

### Après Modification
- ✅ 2 images seulement : `img1.png` et `img2.png`
- ✅ Facile à gérer
- ✅ **Parfait pour une démonstration au jury**

---

## 💡 Pour la Production

**Note importante** : Dans un environnement de production réel, vous voudrez :
- Utiliser des images uniques et attrayantes pour chaque événement
- Optimiser les images (compression, WebP, lazy loading)
- Utiliser un CDN pour servir les images
- Implémenter un système d'upload d'images pour les organisateurs

---

## 🎓 Message pour le Jury

> *"Les images des événements utilisent actuellement des fichiers de démonstration (`img1.png` et `img2.png`) pour faciliter la présentation. Dans un contexte de production, chaque événement aurait son image unique, optimisée et hébergée sur un CDN pour des performances optimales."*

---

**Date de modification** : 5 Janvier 2026  
**Fichier modifié** : `Base/mydata.sql`  
**Changements** : Toutes les images d'événements → `img1.png` ou `img2.png`

