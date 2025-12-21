# 🔧 CORRECTIONS DES MODÈLES - RÉCAPITULATIF

## ✅ CORRECTIONS DÉJÀ EFFECTUÉES

1. ✅ **User Model** - Corrigé pour utiliser `name` au lieu de `username`/`first_name`/`last_name`
2. ✅ **User Model** - Relations corrigées : `waiter_id` et `cashier_id`
3. ✅ **Order Model** - Corrigé pour utiliser `waiter_id` et `total_amount`
4. ✅ **Payment Model** - Corrigé pour utiliser `cashier_id`

## 🔴 CORRECTIONS RESTANTES À FAIRE

### 1. RestaurantTable Model
**Problème** : Le modèle utilise `is_active` mais la migration n'a pas cette colonne
**Solution** : Retirer `is_active` du fillable et casts, utiliser uniquement `status`

### 2. Reservation Model
**Problème** : Le modèle utilise `party_size` mais la migration utilise `guests_count`
**Solution** : Remplacer `party_size` par `guests_count` dans le fillable

### 3. StockItem Model
**Problème** : Plusieurs colonnes différentes
- Modèle : `description`, `minimum_quantity`, `cost_per_unit`, `last_restocked_at`
- Migration : `min_quantity`, `unit_cost` (pas de description, pas de last_restocked_at)
**Solution** : Aligner le modèle avec la migration

### 4. StockMovement Model
**Problème** : Structure complètement différente
- Modèle : `unit_cost`, `total_cost`, `notes`
- Migration : `quantity_before`, `quantity_after`, `reason`, `reference` (pas de unit_cost, total_cost, notes)
**Solution** : Aligner le modèle avec la migration

### 5. ActivityLog Model
**Problème** : 
- Modèle : `entity_type`, `entity_id`, `public $timestamps = false;`
- Migration : `model_type`, `model_id`, timestamps activés
**Solution** : Aligner le modèle avec la migration

### 6. Backup Model
**Problème** :
- Modèle : `filepath`, `completed_at`
- Migration : `path` (pas `filepath`), pas de `completed_at`
**Solution** : Aligner le modèle avec la migration

### 7. StockItem Model - Scope manquant
**Problème** : Le contrôleur utilise `expiringSoon()` scope qui n'existe pas
**Solution** : Ajouter le scope dans le modèle

---

## 📝 INSTRUCTIONS POUR APPLIQUER LES CORRECTIONS

Ces corrections doivent être appliquées AVANT d'exécuter les migrations.

Voir les fichiers modèles individuels pour les corrections détaillées.

