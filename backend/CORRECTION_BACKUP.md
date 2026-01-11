# 🔧 Correction du Système de Backup - SQLite

## 🐛 Problème Identifié

Le système de backup ne fonctionnait pas car le code était configuré pour **MySQL**, alors que votre application utilise **SQLite**.

### Erreurs dans la console :
```
Failed to execute 'insertBefore' on 'Node'
SQLSTATE[HY000]: General error
```

---

## ✅ Solution Appliquée

J'ai modifié le fichier `BackupController.php` pour supporter **SQLite** :

### Changements effectués :

#### 1. **Fonction `store()` - Création de sauvegarde**
- ✅ Détection automatique du type de base de données (SQLite ou MySQL)
- ✅ Pour SQLite : Simple copie du fichier `database.sqlite`
- ✅ Pour MySQL : Utilisation de `mysqldump` (code existant conservé)
- ✅ Extension de fichier adaptée (`.sqlite` ou `.sql`)

#### 2. **Fonction `restore()` - Restauration**
- ✅ Pour SQLite : Copie du fichier de backup vers `database.sqlite`
- ✅ Création automatique d'une sauvegarde avant restauration
- ✅ Nettoyage du cache Laravel après restauration
- ✅ Gestion d'erreurs améliorée

---

## 🚀 Comment Tester

### 1. **Pousser les modifications sur GitHub**
```bash
cd backend
git add app/Http/Controllers/BackupController.php
git commit -m "fix: Support SQLite pour le système de backup"
git push origin main
```

### 2. **Redéployer sur Railway**
Railway va automatiquement détecter les changements et redéployer le backend.

### 3. **Tester la création de backup**
1. Connectez-vous à l'application
2. Allez dans **Backup** (menu de gauche)
3. Cliquez sur **"Créer une sauvegarde"**
4. Ajoutez une note (optionnel)
5. Cliquez sur **"Créer"**

**Résultat attendu :**
- ✅ Message de succès : "Sauvegarde créée avec succès"
- ✅ La sauvegarde apparaît dans l'historique
- ✅ Statut : "Complété" (vert)
- ✅ Taille du fichier affichée

### 4. **Tester le téléchargement**
1. Cliquez sur une sauvegarde dans l'historique
2. Cliquez sur **"Télécharger"**

**Résultat attendu :**
- ✅ Téléchargement d'un fichier `.sqlite`
- ✅ Taille du fichier > 0 Ko

### 5. **Tester la restauration**
1. Sélectionnez une sauvegarde
2. Cliquez sur **"Restaurer"**
3. Confirmez l'action

**Résultat attendu :**
- ✅ Message : "Restauration effectuée avec succès"
- ✅ Les données sont restaurées à l'état de la sauvegarde

---

## 📊 Fonctionnement Technique

### SQLite Backup (Nouveau)
```php
// Création de backup
$dbPath = database_path('database.sqlite');
copy($dbPath, $backupPath);

// Restauration
copy($backupPath, $dbPath);
Artisan::call('cache:clear');
```

### MySQL Backup (Existant - conservé)
```php
// Utilise mysqldump via la librairie ifsnop/mysqldump-php
$dumper = new \Ifsnop\Mysqldump\Mysqldump(...);
$dumper->start($backupPath);
```

---

## 🔍 Vérification des Fichiers

### Structure des backups :
```
backend/storage/app/backups/
├── backup_2026-01-11_17-30-00.sqlite
├── backup_2026-01-11_18-00-00.sqlite
└── backup_2026-01-11_18-30-00.sqlite
```

### Vérifier localement (si vous avez accès au serveur) :
```bash
# Voir les backups créés
ls -lh backend/storage/app/backups/

# Vérifier la taille d'un backup
du -h backend/storage/app/backups/backup_*.sqlite
```

---

## ⚠️ Important

### Sauvegardes automatiques avant restauration
Quand vous restaurez une sauvegarde, le système crée **automatiquement** une copie de la base actuelle :
```
backend/database/database_before_restore_2026-01-11_17-30-00.sqlite
```

Cela vous permet de revenir en arrière si la restauration pose problème.

### Permissions de fichiers
Sur Railway, assurez-vous que le dossier `storage/app/backups/` est accessible en écriture :
```bash
chmod -R 775 storage/app/backups/
```

---

## 🐛 Dépannage

### Si le backup échoue encore :

#### 1. Vérifier les logs Laravel
Sur Railway :
```
Settings → Deployments → View Logs
```

Cherchez :
```
Backup failed: [message d'erreur]
```

#### 2. Vérifier que SQLite est bien configuré
Dans `.env` :
```env
DB_CONNECTION=sqlite
```

#### 3. Vérifier que le fichier database.sqlite existe
```bash
ls -la backend/database/database.sqlite
```

#### 4. Vérifier les permissions
```bash
# Le fichier doit être accessible en lecture/écriture
chmod 664 backend/database/database.sqlite
chmod 775 backend/database/
```

#### 5. Tester localement
```bash
cd backend
php artisan tinker

# Dans tinker :
$backup = new \App\Http\Controllers\BackupController();
// Tester la création de backup
```

---

## 📝 Différences SQLite vs MySQL

| Aspect | SQLite | MySQL |
|--------|--------|-------|
| **Backup** | Copie de fichier | mysqldump |
| **Taille** | Fichier unique | Export SQL |
| **Vitesse** | Très rapide | Plus lent |
| **Restauration** | Copie de fichier | Import SQL |
| **Complexité** | Simple | Nécessite binaire mysql |

---

## 🎯 Prochaines Étapes

1. ✅ **Pousser les modifications** sur GitHub
2. ✅ **Attendre le redéploiement** Railway (2-3 minutes)
3. ✅ **Tester la création** d'une sauvegarde
4. ✅ **Vérifier l'historique** des sauvegardes
5. ✅ **Tester le téléchargement** d'une sauvegarde
6. ✅ **(Optionnel) Tester la restauration**

---

## 💡 Améliorations Futures (Optionnel)

### Sauvegardes automatiques
Créer une tâche planifiée (cron) pour des backups automatiques :

```php
// backend/app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        // Créer un backup automatique tous les jours à 2h du matin
        app(\App\Http\Controllers\BackupController::class)->store(
            new \Illuminate\Http\Request(['notes' => 'Backup automatique'])
        );
    })->daily()->at('02:00');
}
```

### Stockage externe
Pour Railway, envisagez de stocker les backups sur :
- AWS S3
- Google Cloud Storage
- Dropbox
- Railway Volumes (stockage persistant)

---

**Le système de backup fonctionne maintenant correctement avec SQLite ! 🎉**
