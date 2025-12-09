# 🔍 Guide : Trouver les informations MVola manquantes

## ✅ Ce que vous avez déjà

D'après ce que vous voyez dans l'interface MVola :
- ✅ **Consumer Key** : Vous l'avez
- ✅ **Consumer Secret** : Vous l'avez  
- ✅ **Token Endpoint** : `https://developer.mvola.mg/oauth2/token`

## ❓ Ce qu'il vous faut encore

### 1. Partner MSISDN (Numéro de téléphone marchand)

**Où le trouver :**

#### Option A : Dans l'interface MVola
1. Retournez à la page principale de votre application
2. Cliquez sur **"View Application"** ou **"Application Details"**
3. Cherchez dans les sections :
   - **"Merchant Information"**
   - **"Account Details"**
   - **"Sandbox Configuration"**
   - Un champ nommé **"MSISDN"**, **"Phone Number"** ou **"Merchant Number"**

#### Option B : Utiliser votre numéro MVola
Pour le **sandbox**, vous pouvez utiliser :
- Votre propre numéro MVola (celui avec lequel vous vous êtes inscrit au portail)
- Format : `03412345678` (sans espaces)

#### Option C : Contacter le support
Si vous ne trouvez pas, contactez le support MVola pour obtenir le **Partner MSISDN** officiel.

---

### 2. Partner Name (Nom du partenaire)

**Où le trouver :**

#### Option A : Dans l'interface MVola
1. Dans la page de détails de votre application
2. Cherchez :
   - **"Application Name"**
   - **"Company Name"**
   - **"Partner Name"**
   - **"Organization Name"**

#### Option B : Utiliser le nom de votre application
Pour le **sandbox**, vous pouvez utiliser :
- Le nom de votre application (celui que vous avez donné lors de la création)
- Ou un nom simple comme : `AioliaEvent` ou `Aiolia`

#### Option C : Contacter le support
Demandez le **Partner Name** officiel au support MVola.

---

### 3. Callback URL (À configurer dans l'interface)

**Ce que vous devez faire :**

1. Dans l'interface MVola (section que vous voyez actuellement)
2. Trouvez le champ **"Callback URL"**
3. Remplacez `http://url-to-webapp` par :
   ```
   http://localhost:8000/api/mvola/callback
   ```
   (Pour le développement local)

4. **Sauvegardez** les modifications

**Note :** Pour la production, vous devrez mettre votre vrai domaine :
```
https://votre-domaine.com/api/mvola/callback
```

---

## 📋 Configuration minimale pour tester

En attendant de trouver ces informations, vous pouvez utiliser des valeurs de test pour le **sandbox** :

```bash
# Dans votre .env.local
MVOLA_CONSUMER_KEY=votre_consumer_key
MVOLA_CONSUMER_SECRET=votre_consumer_secret

# Valeurs de test (à remplacer quand vous les trouverez)
MVOLA_PARTNER_MSISDN=03412345678  # Votre numéro MVola personnel
MVOLA_PARTNER_NAME=AioliaEvent     # Nom de votre application

# URLs
MVOLA_BASE_URL=https://devapi.mvola.mg
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
```

---

## 🆘 Si vous ne trouvez toujours pas

### Contactez le support MVola :

- **Email** : support@mvola.mg ou developer@mvola.mg
- **Demandez** :
  - Le **Partner MSISDN** pour votre application sandbox
  - Le **Partner Name** officiel
  - Confirmation des URLs à utiliser

---

## ✅ Checklist

- [ ] Consumer Key obtenu ✅
- [ ] Consumer Secret obtenu ✅
- [ ] Token Endpoint noté : `https://developer.mvola.mg/oauth2/token` ✅
- [ ] Partner MSISDN trouvé ou valeur de test utilisée
- [ ] Partner Name trouvé ou valeur de test utilisée
- [ ] Callback URL configuré dans l'interface MVola
- [ ] Fichier `.env.local` créé avec toutes les variables

