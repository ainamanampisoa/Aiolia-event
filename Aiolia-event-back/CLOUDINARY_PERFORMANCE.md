# 🚀 Cloudinary - Performance et Limitations

## 📊 Plan Gratuit de Cloudinary - Limitations

### Limites du Plan Free (Gratuit)

| Ressource | Limite Mensuelle | Détails |
|-----------|------------------|---------|
| **Stockage** | 25 GB | Espace total pour toutes vos images/vidéos |
| **Bandwidth** | 25 GB/mois | Bande passante pour les téléchargements/upload |
| **Transformations** | Illimitées | Pas de limite sur les transformations d'images |
| **Support** | Communautaire | Forum et documentation uniquement |
| **Taille max upload** | 10 MB (images), 100 MB (vidéos) | Par fichier |

### ⚠️ Pourquoi les uploads sont lents ?

1. **Plan gratuit = Priorité réduite**
   - Les comptes gratuits ont une **priorité plus basse** que les comptes payants
   - En période de forte charge, les uploads gratuits sont ralentis

2. **Bandwidth limité (25 GB/mois)**
   - Si vous dépassez la limite, Cloudinary peut **ralentir ou bloquer** les uploads
   - Vérifiez votre usage sur : https://console.cloudinary.com/console

3. **Taille des fichiers**
   - Des images > 2-3 MB sont **trop lourdes**
   - Uploads longs + consommation de bandwidth

4. **Pas d'optimisation avant upload**
   - Les images ne sont pas compressées/redimensionnées avant envoi
   - Tout le poids est envoyé à Cloudinary

5. **Upload synchrone**
   - Les uploads bloquent la réponse HTTP
   - L'utilisateur attend la fin de l'upload

## ✅ Solutions et Optimisations

### 1. Optimiser les images avant upload (RECOMMANDÉ)

**Avant l'upload, réduire :**
- La taille du fichier (compresser)
- Les dimensions (redimensionner si nécessaire)
- Le format (utiliser WebP si possible)

### 2. Configurer des Upload Presets

Les presets permettent de définir des transformations automatiques à l'upload.

### 3. Upload Asynchrone (Queue)

Utiliser Symfony Messenger pour uploader en arrière-plan.

### 4. Limiter la taille des fichiers côté client

Ajouter des validations JavaScript pour bloquer les fichiers trop lourds.

### 5. Optimiser les options Cloudinary

Utiliser les bonnes options dans `uploadImage()` :
- `eager` : Générer les formats optimisés à l'upload
- `quality: 'auto'` : Compression automatique
- `fetch_format: 'auto'` : Format optimal selon le navigateur

## 📈 Plans Payants Cloudinary (si besoin)

| Plan | Prix/mois | Storage | Bandwidth | Support |
|------|-----------|---------|-----------|---------|
| **Plus** | $89 | 25 GB | 25 GB | Email |
| **Advanced** | $224 | 60 GB | 60 GB | Email + Phone |
| **Enterprise** | Custom | Illimité | Illimité | Support 24/7 |

## 🔧 Actions Recommandées IMMÉDIATES

1. ✅ **Vérifier votre usage** sur https://console.cloudinary.com/console
2. ✅ **Compresser les images** avant upload (ajouter dans le code)
3. ✅ **Limiter la taille max** à 2-3 MB pour les images
4. ✅ **Utiliser WebP** si possible
5. ✅ **Redimensionner** les images trop grandes (ex: max 2000px)

## 📝 Code d'Optimisation à Ajouter

Voir `CloudinaryService.php` pour les optimisations implémentées :
- Compression automatique
- Redimensionnement optionnel
- Validation de taille
- Upload presets

