# 📊 Diagrammes UML - Chef's Table

Ce dossier contient les diagrammes UML modélisant l'architecture et les fonctionnalités de l'application **Chef's Table**.

## 📁 Fichiers

### 1. Diagramme de Classes (`diagramme-classes.puml`)
Modélise la structure des entités de l'application et leurs relations :
- **14 classes principales** : User, Role, Category, MenuItem, Order, OrderItem, Payment, Reservation, RestaurantTable, StockItem, StockMovement, Setting, Backup, ActivityLog
- **Relations** : associations, compositions, héritages
- **Attributs** : propriétés de chaque classe
- **Méthodes** : opérations principales de chaque classe

### 2. Diagramme de Cas d'Utilisation (`diagramme-cas-utilisation.puml`)
Modélise les fonctionnalités de l'application selon les différents acteurs :
- **6 acteurs** : Administrateur, Gérant, Caissier, Serveur, Cuisinier, Client
- **8 packages fonctionnels** :
  - Authentification et Gestion des Utilisateurs
  - Gestion du Menu
  - Gestion des Tables
  - Gestion des Commandes
  - Gestion des Paiements
  - Gestion des Stocks
  - Rapports et Statistiques
  - Paramètres et Maintenance
- **Relations** : inclusions, extensions entre cas d'utilisation

## 🛠️ Outils pour Visualiser

### PlantUML (Recommandé)
Les fichiers `.puml` peuvent être visualisés avec :

1. **En ligne** : [PlantUML Online Server](http://www.plantuml.com/plantuml/uml/)
   - Copiez le contenu du fichier `.puml` et collez-le dans l'éditeur

2. **VS Code Extension** :
   - Installez l'extension "PlantUML" de Markiewich
   - Ouvrez un fichier `.puml` et appuyez sur `Alt+D` pour prévisualiser

3. **IntelliJ IDEA / PHPStorm** :
   - Installez le plugin PlantUML
   - Ouvrez un fichier `.puml` et utilisez la prévisualisation intégrée

4. **En ligne de commande** :
   ```bash
   # Installer PlantUML
   npm install -g node-plantuml
   
   # Générer une image PNG
   puml generate diagramme-classes.puml -o diagramme-classes.png
   ```

### Visualisation Alternative
Des versions en format Mermaid sont également disponibles dans les fichiers `.md` pour une visualisation directe dans GitHub ou les éditeurs Markdown.

## 📋 Structure des Diagrammes

### Diagramme de Classes

#### Entités Principales
- **User** : Utilisateurs du système avec gestion des rôles
- **Role** : Rôles du système (admin, manager, cashier, waiter, cook)
- **Category** : Catégories de plats
- **MenuItem** : Articles du menu
- **RestaurantTable** : Tables du restaurant
- **Order** : Commandes clients
- **OrderItem** : Articles d'une commande
- **Payment** : Paiements
- **Reservation** : Réservations de tables
- **StockItem** : Articles en stock
- **StockMovement** : Mouvements de stock
- **Setting** : Paramètres système
- **Backup** : Sauvegardes
- **ActivityLog** : Logs d'activité

#### Relations Clés
- User ↔ Role : Relation many-to-many (via user_roles)
- Category → MenuItem : One-to-many
- MenuItem → OrderItem : One-to-many
- RestaurantTable → Order : One-to-many
- RestaurantTable → Reservation : One-to-many
- Order → OrderItem : One-to-many
- Order → Payment : One-to-one
- StockItem → StockMovement : One-to-many

### Diagramme de Cas d'Utilisation

#### Acteurs et Permissions

| Acteur | Permissions Principales |
|--------|------------------------|
| **Administrateur** | Accès complet à toutes les fonctionnalités |
| **Gérant** | Gestion du menu, stocks, consultation des paiements et rapports |
| **Caissier** | Encaissement des commandes, gestion des paiements |
| **Serveur** | Gestion des tables, création et suivi des commandes |
| **Cuisinier** | Visualisation et mise à jour du statut des commandes en cuisine |
| **Client** | Consultation du menu, réservation de table |

#### Packages Fonctionnels

1. **Authentification et Gestion des Utilisateurs**
   - Connexion/Déconnexion
   - Gestion des utilisateurs et rôles
   - Logs d'activité

2. **Gestion du Menu**
   - Gestion des catégories et plats
   - Disponibilité et mise en vedette

3. **Gestion des Tables**
   - Gestion des tables et réservations
   - Statut des tables

4. **Gestion des Commandes**
   - Création et suivi des commandes
   - Gestion des articles de commande
   - Interface cuisine

5. **Gestion des Paiements**
   - Encaissement
   - Remboursements
   - Rapports quotidiens

6. **Gestion des Stocks**
   - Gestion des articles
   - Alertes de stock faible
   - Mouvements de stock

7. **Rapports et Statistiques**
   - Tableau de bord
   - Rapports de ventes et stocks

8. **Paramètres et Maintenance**
   - Paramètres système
   - Sauvegardes et restaurations

## 🔄 Mise à Jour des Diagrammes

Pour mettre à jour les diagrammes après des modifications du code :

1. **Diagramme de Classes** : Vérifiez les modèles dans `laravel-models/`
2. **Diagramme de Cas d'Utilisation** : Vérifiez les routes dans `laravel-controllers/routes/api.php` et les contrôleurs

## 📝 Notes

- Les diagrammes sont basés sur l'analyse du code source actuel
- Les relations reflètent la structure de la base de données Laravel
- Les cas d'utilisation sont dérivés des routes API et des contrôleurs
- Les permissions des acteurs sont basées sur la structure des rôles définie dans l'application

