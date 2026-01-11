# 🚀 Guide de Déploiement sur Railway - Chef's Table

## 🔧 Problème Résolu : Écran Noir lors de la Navigation

### Cause du Problème
Votre application React utilise **React Router** avec `BrowserRouter`. Quand vous naviguez vers `/dashboard` ou `/users`, Railway ne sait pas comment gérer ces routes côté serveur, ce qui cause l'écran noir.

### Solution Implémentée
J'ai créé 3 fichiers de configuration pour résoudre ce problème :

---

## 📁 Fichiers Créés

### 1. `public/_redirects`
```
/* /index.html 200
```
**Rôle :** Redirige toutes les routes vers `index.html` pour que React Router puisse gérer la navigation.

### 2. `railway.json`
Configuration Railway pour le build et le déploiement.

### 3. `nixpacks.toml`
Configuration Nixpacks pour Railway (système de build).

### 4. `package.json` (modifié)
Ajout du script `start` pour servir l'application en production.

---

## 🚀 Étapes de Déploiement

### Option 1 : Redéploiement Automatique (Recommandé)

1. **Commitez les nouveaux fichiers sur GitHub :**
   ```bash
   git add .
   git commit -m "fix: Ajouter configuration Railway pour SPA routing"
   git push origin main
   ```

2. **Railway redéploiera automatiquement** votre application avec la nouvelle configuration.

3. **Attendez 2-3 minutes** que le build se termine.

4. **Testez votre application** :
   - Ouvrez l'URL Railway
   - Connectez-vous
   - Naviguez entre les pages
   - ✅ Plus d'écran noir !

---

### Option 2 : Redéploiement Manuel sur Railway

Si le redéploiement automatique ne fonctionne pas :

1. **Allez sur Railway Dashboard** : https://railway.app/

2. **Sélectionnez votre projet** "chef-s-table"

3. **Cliquez sur votre service** (frontend)

4. **Allez dans Settings**

5. **Trouvez "Build Command"** et vérifiez :
   ```
   npm install && npm run build
   ```

6. **Trouvez "Start Command"** et mettez :
   ```
   npx serve dist -s -p $PORT
   ```

7. **Cliquez sur "Deploy"** en haut à droite

8. **Attendez le build** (2-3 minutes)

---

## 🔍 Vérification

### Tester que ça fonctionne :

1. ✅ **Page d'accueil** : https://votre-app.railway.app/
2. ✅ **Connexion** : Se connecter avec un compte
3. ✅ **Navigation** : Cliquer sur "Dashboard", "Users", "Menu", etc.
4. ✅ **Actualisation** : Appuyer sur F5 sur n'importe quelle page
5. ✅ **URL directe** : Aller directement sur https://votre-app.railway.app/dashboard

**Résultat attendu :** Toutes les pages doivent s'afficher correctement, sans écran noir.

---

## 🐛 Dépannage

### Si l'écran noir persiste :

#### 1. Vérifier les logs Railway
```bash
# Dans Railway Dashboard
Settings → Deployments → Cliquer sur le dernier déploiement → View Logs
```

Cherchez les erreurs comme :
- `Cannot GET /dashboard`
- `404 Not Found`
- `ENOENT: no such file or directory`

#### 2. Vérifier que les fichiers sont bien présents
Dans votre repo GitHub, vérifiez que ces fichiers existent :
- ✅ `public/_redirects`
- ✅ `railway.json`
- ✅ `nixpacks.toml`

#### 3. Vérifier la configuration Railway

**Build Command doit être :**
```
npm install && npm run build
```

**Start Command doit être :**
```
npx serve dist -s -p $PORT
```

**Root Directory :** (vide ou `/`)

#### 4. Forcer un nouveau build
```bash
# Dans Railway Dashboard
Settings → Redeploy
```

---

## 🌐 Configuration CORS (Si nécessaire)

Si vous avez des erreurs CORS dans la console :

### Backend Laravel (si hébergé séparément)

Modifiez `config/cors.php` :
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [
    'https://votre-app.railway.app',
    'http://localhost:8080',
],
'supports_credentials' => true,
```

### Variable d'environnement Frontend

Dans Railway, ajoutez :
```
VITE_API_URL=https://votre-backend.railway.app/api
```

---

## 📊 Architecture de Déploiement

```
┌─────────────────────────────────────┐
│     Railway (Frontend React)        │
│  https://chef-table.railway.app     │
│                                     │
│  - Vite Build (dist/)               │
│  - Serve avec SPA fallback          │
│  - Toutes routes → index.html       │
└─────────────────────────────────────┘
              ↓ API Calls
┌─────────────────────────────────────┐
│     Railway (Backend Laravel)        │
│  https://api-chef-table.railway.app │
│                                     │
│  - Laravel API                      │
│  - MySQL/PostgreSQL                 │
│  - Sanctum Auth                     │
└─────────────────────────────────────┘
```

---

## 🎯 Explication Technique

### Pourquoi ça ne marchait pas avant ?

1. **React Router utilise BrowserRouter** qui crée des URLs comme `/dashboard`, `/users`
2. **Railway servait les fichiers statiques** directement
3. **Quand vous naviguiez vers `/dashboard`**, Railway cherchait un fichier `dashboard.html`
4. **Le fichier n'existait pas** → 404 → Écran noir

### Comment ça marche maintenant ?

1. **Le fichier `_redirects`** dit à Railway : "Pour toute URL, sers `index.html`"
2. **`serve` avec l'option `-s`** (single-page app) fait la même chose
3. **React Router reçoit toutes les requêtes** et gère la navigation côté client
4. **Plus d'écran noir** ! 🎉

---

## 📝 Commandes Utiles

### Tester localement avant de déployer
```bash
# Build l'application
npm run build

# Servir en mode production (comme Railway)
npx serve dist -s -p 8080

# Ouvrir dans le navigateur
# http://localhost:8080
```

### Vérifier que le build fonctionne
```bash
# Le dossier dist/ doit contenir :
ls dist/
# → index.html
# → assets/
# → vite.svg
```

---

## ✅ Checklist de Déploiement

Avant de pousser sur GitHub :

- [ ] Fichier `public/_redirects` créé
- [ ] Fichier `railway.json` créé
- [ ] Fichier `nixpacks.toml` créé
- [ ] Script `start` ajouté dans `package.json`
- [ ] Build local réussi (`npm run build`)
- [ ] Test local avec serve (`npx serve dist -s`)
- [ ] Commit et push sur GitHub
- [ ] Railway redéploie automatiquement
- [ ] Test sur l'URL Railway

---

## 🆘 Support

Si le problème persiste après avoir suivi ce guide :

1. **Vérifiez les logs Railway** pour voir les erreurs exactes
2. **Testez localement** avec `npx serve dist -s`
3. **Vérifiez la console du navigateur** (F12) pour les erreurs JavaScript
4. **Contactez le support Railway** si c'est un problème de plateforme

---

## 🎉 Résultat Final

Après le déploiement, votre application devrait :
- ✅ S'ouvrir correctement sur la page de connexion
- ✅ Permettre la navigation entre toutes les pages
- ✅ Supporter l'actualisation (F5) sur n'importe quelle page
- ✅ Permettre l'accès direct via URL (ex: `/dashboard`)
- ✅ Ne plus afficher d'écran noir

**Bon déploiement ! 🚀**
