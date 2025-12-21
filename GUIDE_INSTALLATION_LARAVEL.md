# 🚀 GUIDE D'INSTALLATION LARAVEL - RESTAURANT BACKEND

## 📋 PRÉREQUIS

- PHP >= 8.1
- Composer installé
- MySQL/MariaDB installé
- Extension PHP : pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json

---

## 🔧 ÉTAPE 1 : CRÉER LE PROJET LARAVEL

```bash
# Depuis le répertoire parent du projet
cd ..
composer create-project laravel/laravel restaurant-backend
cd restaurant-backend
```

**Si Composer n'est pas disponible**, vous pouvez télécharger Laravel manuellement ou utiliser un autre gestionnaire de paquets.

---

## 📁 ÉTAPE 2 : COPIER LES FICHIERS GÉNÉRÉS

### 2.1 Copier les Models

```bash
# Depuis le répertoire restaurant-backend
cp ../chef-s-table-main/laravel-models/*.php app/Models/
```

### 2.2 Copier les Controllers

```bash
cp ../chef-s-table-main/laravel-controllers/*.php app/Http/Controllers/
```

### 2.3 Copier les Routes

```bash
cp ../chef-s-table-main/laravel-controllers/routes/api.php routes/api.php
```

### 2.4 Copier les Migrations

```bash
cp ../chef-s-table-main/laravel-migrations/*.php database/migrations/
```

### 2.5 Créer les dossiers et copier les Form Requests

```bash
mkdir -p app/Http/Requests/{Auth,Role,User,Category,MenuItem,RestaurantTable,Order,OrderItem,Payment,StockItem,Reservation,Setting,Backup}

cp ../chef-s-table-main/laravel-requests/Auth/*.php app/Http/Requests/Auth/
cp ../chef-s-table-main/laravel-requests/Role/*.php app/Http/Requests/Role/
cp ../chef-s-table-main/laravel-requests/User/*.php app/Http/Requests/User/
cp ../chef-s-table-main/laravel-requests/Category/*.php app/Http/Requests/Category/
cp ../chef-s-table-main/laravel-requests/MenuItem/*.php app/Http/Requests/MenuItem/
cp ../chef-s-table-main/laravel-requests/RestaurantTable/*.php app/Http/Requests/RestaurantTable/
cp ../chef-s-table-main/laravel-requests/Order/*.php app/Http/Requests/Order/
cp ../chef-s-table-main/laravel-requests/OrderItem/*.php app/Http/Requests/OrderItem/
cp ../chef-s-table-main/laravel-requests/Payment/*.php app/Http/Requests/Payment/
cp ../chef-s-table-main/laravel-requests/StockItem/*.php app/Http/Requests/StockItem/
cp ../chef-s-table-main/laravel-requests/Reservation/*.php app/Http/Requests/Reservation/
cp ../chef-s-table-main/laravel-requests/Setting/*.php app/Http/Requests/Setting/
cp ../chef-s-table-main/laravel-requests/Backup/*.php app/Http/Requests/Backup/
```

### 2.6 Copier les Resources

```bash
mkdir -p app/Http/Resources/Collections
cp ../chef-s-table-main/laravel-resources/*.php app/Http/Resources/
cp ../chef-s-table-main/laravel-resources/Collections/*.php app/Http/Resources/Collections/
```

---

## 🔧 ÉTAPE 3 : CORRIGER LES PROBLÈMES CRITIQUES

### 3.1 Corriger les imports DB dans les migrations

**Fichiers à corriger :**
- `database/migrations/2024_01_01_000001_create_roles_table.php`
- `database/migrations/2024_01_01_000004_create_categories_table.php`
- `database/migrations/2024_01_01_000006_create_restaurant_tables_table.php`
- `database/migrations/2024_01_01_000013_create_settings_table.php`

**Ajouter après la ligne 5 :**
```php
use Illuminate\Support\Facades\DB;
```

### 3.2 Corriger les incohérences Modèles/Migrations

Voir le fichier `CORRECTIONS_MODELES.md` pour les détails complets.

### 3.3 Créer les routes d'authentification

Voir le fichier `ROUTES_AUTH.md` pour les routes à ajouter.

---

## ⚙️ ÉTAPE 4 : CONFIGURER L'ENVIRONNEMENT

### 4.1 Copier le fichier .env

```bash
cp .env.example .env
```

### 4.2 Générer la clé d'application

```bash
php artisan key:generate
```

### 4.3 Configurer .env

Éditer `.env` et configurer :

```env
APP_NAME="Restaurant Management"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_db
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

---

## 📦 ÉTAPE 5 : INSTALLER SANCTUM

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## 🗄️ ÉTAPE 6 : CRÉER LA BASE DE DONNÉES

```sql
CREATE DATABASE restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 🚀 ÉTAPE 7 : EXÉCUTER LES MIGRATIONS

```bash
php artisan migrate
```

**Si erreurs :** Vérifier que toutes les corrections ont été appliquées.

---

## 🌱 ÉTAPE 8 : CRÉER ET EXÉCUTER LES SEEDERS

Les données par défaut sont déjà dans les migrations, mais vous pouvez créer des seeders pour plus de flexibilité.

---

## 🧹 ÉTAPE 9 : NETTOYER LES CACHES

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## ✅ ÉTAPE 10 : VÉRIFIER LES ROUTES

```bash
php artisan route:list --path=api
```

---

## 🎯 ÉTAPE 11 : LANCER LE SERVEUR

```bash
php artisan serve
```

L'API sera accessible sur : **http://localhost:8000**

---

## 🔍 VÉRIFICATIONS FINALES

1. ✅ Migrations exécutées sans erreur
2. ✅ Routes API listées correctement
3. ✅ Test d'une route API (ex: GET /api/roles)
4. ✅ Test d'authentification (POST /api/auth/login)

---

## ⚠️ PROBLÈMES CONNUS ET SOLUTIONS

Voir le fichier `RAPPORT_ANALYSE_LARAVEL.md` pour la liste complète des problèmes identifiés et leurs solutions.

