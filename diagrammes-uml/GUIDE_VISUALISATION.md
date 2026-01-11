# 🎨 Guide de Visualisation des Diagrammes UML

## 📊 Diagrammes Disponibles

Ce dossier contient deux diagrammes UML principaux pour l'application **Chef's Table** :

1. **`diagramme-classes.puml`** - Diagramme de classes (structure de la base de données)
2. **`diagramme-cas-utilisation.puml`** - Diagramme de cas d'utilisation (fonctionnalités par rôle)

---

## 🖼️ Méthodes de Visualisation

### Méthode 1 : PlantUML Online (Recommandé - Le Plus Simple)

**Étapes :**
1. Ouvrez [PlantUML Online Server](http://www.plantuml.com/plantuml/uml/)
2. Copiez le contenu d'un fichier `.puml` (diagramme-classes.puml ou diagramme-cas-utilisation.puml)
3. Collez-le dans l'éditeur en ligne
4. Le diagramme s'affiche automatiquement
5. Vous pouvez télécharger l'image en PNG, SVG ou PDF

**Avantages :**
- ✅ Aucune installation requise
- ✅ Fonctionne dans n'importe quel navigateur
- ✅ Export en plusieurs formats

---

### Méthode 2 : Extension VS Code

**Installation :**
1. Ouvrez VS Code
2. Allez dans Extensions (Ctrl+Shift+X)
3. Recherchez "PlantUML" par jebbs
4. Installez l'extension

**Utilisation :**
1. Ouvrez un fichier `.puml` dans VS Code
2. Appuyez sur `Alt+D` (ou `Cmd+D` sur Mac)
3. Le diagramme s'affiche dans un panneau à côté
4. Clic droit → "Export Current Diagram" pour sauvegarder en image

**Prérequis :**
- Java doit être installé sur votre système
- Ou utilisez le mode serveur PlantUML (pas besoin de Java)

---

### Méthode 3 : Outils en Ligne de Commande

**Installation Node.js :**
```bash
npm install -g node-plantuml
```

**Générer les images PNG :**
```bash
# Diagramme de classes
puml generate diagramme-classes.puml -o diagramme-classes.png

# Diagramme de cas d'utilisation
puml generate diagramme-cas-utilisation.puml -o diagramme-cas-utilisation.png
```

**Installation Java (alternative) :**
```bash
# Télécharger PlantUML JAR
wget http://sourceforge.net/projects/plantuml/files/plantuml.jar/download -O plantuml.jar

# Générer les images
java -jar plantuml.jar diagramme-classes.puml
java -jar plantuml.jar diagramme-cas-utilisation.puml
```

---

### Méthode 4 : IntelliJ IDEA / PHPStorm

**Installation :**
1. Ouvrez IntelliJ IDEA ou PHPStorm
2. Allez dans Settings → Plugins
3. Recherchez "PlantUML integration"
4. Installez le plugin

**Utilisation :**
1. Ouvrez un fichier `.puml`
2. Le diagramme s'affiche automatiquement dans le panneau de droite
3. Clic droit → "Export to File" pour sauvegarder

---

### Méthode 5 : Sites Web de Visualisation

**PlantText :**
- URL : https://www.planttext.com/
- Collez le code PlantUML
- Visualisez et téléchargez

**PlantUML QEditor :**
- URL : https://plantuml-editor.kkeisuke.com/
- Interface moderne
- Export en plusieurs formats

---

## 📋 Contenu des Diagrammes

### Diagramme de Classes (`diagramme-classes.puml`)

**Entités Modélisées :**
- User (Utilisateurs)
- Role (Rôles)
- Category (Catégories de menu)
- MenuItem (Plats du menu)
- RestaurantTable (Tables du restaurant)
- Order (Commandes)
- OrderItem (Articles de commande)
- Payment (Paiements)
- Reservation (Réservations)
- StockItem (Articles en stock)
- StockMovement (Mouvements de stock)
- Setting (Paramètres système)
- Backup (Sauvegardes)
- ActivityLog (Logs d'activité)

**Relations Principales :**
- User ↔ Role (Many-to-Many via user_roles)
- Category → MenuItem (One-to-Many)
- RestaurantTable → Order (One-to-Many)
- Order → OrderItem (One-to-Many)
- Order → Payment (One-to-One)
- StockItem → StockMovement (One-to-Many)

---

### Diagramme de Cas d'Utilisation (`diagramme-cas-utilisation.puml`)

**Acteurs :**
1. **Administrateur** - Accès complet
2. **Gérant** - Gestion opérationnelle
3. **Caissier** - Gestion des paiements
4. **Serveur** - Gestion des commandes et tables
5. **Cuisinier** - Gestion de la cuisine
6. **Client** - Consultation et réservation

**Packages Fonctionnels :**
1. Authentification et Gestion des Utilisateurs
2. Gestion du Menu
3. Gestion des Tables
4. Gestion des Commandes
5. Gestion des Paiements
6. Gestion des Stocks
7. Rapports et Statistiques
8. Paramètres et Maintenance

---

## 🎯 Exemples d'Utilisation

### Visualiser rapidement en ligne

**Pour le diagramme de classes :**
1. Ouvrez http://www.plantuml.com/plantuml/uml/
2. Copiez tout le contenu de `diagramme-classes.puml`
3. Collez dans l'éditeur
4. Cliquez sur "Submit"

**Pour le diagramme de cas d'utilisation :**
1. Même processus avec `diagramme-cas-utilisation.puml`

### Exporter en haute qualité

**Format SVG (vectoriel, recommandé pour documents) :**
- Sur PlantUML Online : Cliquez sur "SVG" en haut
- Téléchargez le fichier SVG

**Format PNG (image, recommandé pour présentations) :**
- Sur PlantUML Online : Cliquez sur "PNG" en haut
- Téléchargez le fichier PNG

**Format PDF (recommandé pour impression) :**
- Utilisez la ligne de commande avec l'option `-tpdf`

---

## 🔧 Personnalisation

Les diagrammes utilisent PlantUML avec des styles personnalisés :

**Couleurs :**
- Entités : Bleu clair (#E1F5FF)
- Bordures : Bleu (#0066CC)
- Acteurs : Rouge clair (#FFE1E1)

**Modifier les couleurs :**
Éditez les lignes `skinparam` au début de chaque fichier `.puml`

---

## 📱 Visualisation Mobile

**Applications recommandées :**
- **Android** : PlantUML Viewer
- **iOS** : PlantUML Viewer

**Alternative :**
- Utilisez PlantUML Online dans votre navigateur mobile

---

## ❓ Dépannage

### Le diagramme ne s'affiche pas
- Vérifiez que vous avez copié **tout** le contenu du fichier
- Assurez-vous que la syntaxe PlantUML est correcte
- Essayez un autre outil de visualisation

### L'image est trop grande
- Utilisez le zoom de votre navigateur (Ctrl + molette)
- Exportez en SVG pour un zoom sans perte de qualité
- Modifiez le paramètre `scale` dans le fichier `.puml`

### Java n'est pas installé (pour VS Code)
- Utilisez le mode serveur PlantUML dans les paramètres de l'extension
- Ou installez Java : https://www.java.com/fr/download/

---

## 📚 Ressources Supplémentaires

**Documentation PlantUML :**
- Site officiel : https://plantuml.com/
- Guide de syntaxe : https://plantuml.com/guide
- Exemples : https://real-world-plantuml.com/

**Tutoriels :**
- Diagrammes de classes : https://plantuml.com/class-diagram
- Diagrammes de cas d'utilisation : https://plantuml.com/use-case-diagram

---

## 🎨 Aperçu Rapide

Pour une visualisation rapide sans outils :

**Diagramme de Classes - Structure Simplifiée :**
```
User ←→ Role (many-to-many)
  ↓
Order → OrderItem → MenuItem → Category
  ↓
Payment
  ↓
RestaurantTable → Reservation

StockItem → StockMovement
```

**Cas d'Utilisation - Permissions par Rôle :**
```
Admin      : Tout
Manager    : Menu, Stocks, Rapports
Cashier    : Paiements, Commandes (lecture)
Waiter     : Commandes, Tables, Réservations
Cook       : Cuisine (commandes en préparation)
Client     : Menu (lecture), Réservations
```

---

**Dernière mise à jour :** Janvier 2026
**Version des diagrammes :** 2.0
