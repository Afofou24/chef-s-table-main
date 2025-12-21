-- =====================================================
-- SCRIPT SQL MYSQL COMPLET - GESTION DE RESTAURANT
-- Compatible Laravel - PHP 8+
-- =====================================================

-- Supprimer les tables existantes (dans l'ordre des dépendances)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS stock_items;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS backups;
DROP TABLE IF EXISTS activity_logs;

-- =====================================================
-- TABLE: roles
-- Description: Définition des rôles du système
-- =====================================================
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des rôles par défaut
INSERT INTO roles (name, display_name, description) VALUES
('admin', 'Administrateur', 'Contrôle total du système, gestion des utilisateurs, paramètres et sauvegardes'),
('manager', 'Gérant', 'Gestion du menu, stocks, consultation des paiements et rapports'),
('cashier', 'Caissier', 'Encaissement des commandes et historique des paiements'),
('server', 'Serveur', 'Gestion des tables, création et suivi des commandes'),
('cook', 'Cuisinier', 'Visualisation et mise à jour du statut des commandes en cuisine');

-- =====================================================
-- TABLE: users
-- Description: Utilisateurs du système
-- =====================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_users_email (email),
    INDEX idx_users_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_roles
-- Description: Association utilisateurs-rôles (many-to-many)
-- IMPORTANT: Les rôles sont stockés séparément pour la sécurité
-- =====================================================
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED NULL,
    
    UNIQUE KEY unique_user_role (user_id, role_id),
    
    CONSTRAINT fk_user_roles_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role 
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_assigned_by 
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: categories
-- Description: Catégories du menu (Entrées, Plats, Desserts, etc.)
-- =====================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_categories_active (is_active),
    INDEX idx_categories_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des catégories par défaut
INSERT INTO categories (name, description, display_order) VALUES
('Entrées', 'Plats servis en début de repas', 1),
('Plats principaux', 'Plats de résistance', 2),
('Desserts', 'Douceurs et pâtisseries', 3),
('Boissons', 'Boissons chaudes et froides', 4),
('Apéritifs', 'Boissons et amuse-bouches', 5);

-- =====================================================
-- TABLE: menu_items
-- Description: Plats et articles du menu
-- =====================================================
CREATE TABLE menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) NULL,
    preparation_time INT DEFAULT 15 COMMENT 'Temps de préparation en minutes',
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    allergens VARCHAR(255) NULL COMMENT 'Liste des allergènes séparés par virgule',
    calories INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_menu_items_category (category_id),
    INDEX idx_menu_items_available (is_available),
    INDEX idx_menu_items_featured (is_featured),
    
    CONSTRAINT fk_menu_items_category 
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: restaurant_tables
-- Description: Tables du restaurant
-- =====================================================
CREATE TABLE restaurant_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 4,
    location VARCHAR(50) NULL COMMENT 'Emplacement: terrasse, intérieur, etc.',
    status ENUM('available', 'occupied', 'reserved', 'unavailable') DEFAULT 'available',
    current_order_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tables_status (status),
    INDEX idx_tables_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des tables par défaut
INSERT INTO restaurant_tables (table_number, capacity, location) VALUES
('T01', 2, 'intérieur'),
('T02', 4, 'intérieur'),
('T03', 4, 'intérieur'),
('T04', 6, 'intérieur'),
('T05', 8, 'intérieur'),
('T06', 2, 'terrasse'),
('T07', 4, 'terrasse'),
('T08', 4, 'terrasse'),
('T09', 6, 'VIP'),
('T10', 10, 'VIP');

-- =====================================================
-- TABLE: orders
-- Description: Commandes des clients
-- =====================================================
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    table_id INT UNSIGNED NULL,
    server_id BIGINT UNSIGNED NULL COMMENT 'Serveur qui a pris la commande',
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'served', 'paid', 'cancelled') DEFAULT 'pending',
    order_type ENUM('dine_in', 'takeaway', 'delivery') DEFAULT 'dine_in',
    subtotal DECIMAL(10, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    customer_name VARCHAR(100) NULL,
    customer_phone VARCHAR(20) NULL,
    notes TEXT NULL,
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    estimated_ready_time TIMESTAMP NULL,
    actual_ready_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_orders_status (status),
    INDEX idx_orders_table (table_id),
    INDEX idx_orders_server (server_id),
    INDEX idx_orders_created (created_at),
    INDEX idx_orders_number (order_number),
    
    CONSTRAINT fk_orders_table 
        FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_server 
        FOREIGN KEY (server_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: order_items
-- Description: Articles d'une commande
-- =====================================================
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
    special_instructions TEXT NULL,
    prepared_by BIGINT UNSIGNED NULL COMMENT 'Cuisinier qui a préparé le plat',
    prepared_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_menu_item (menu_item_id),
    INDEX idx_order_items_status (status),
    
    CONSTRAINT fk_order_items_order 
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu_item 
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_prepared_by 
        FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: payments
-- Description: Paiements des commandes
-- =====================================================
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(20) NOT NULL UNIQUE,
    order_id BIGINT UNSIGNED NOT NULL,
    cashier_id BIGINT UNSIGNED NULL COMMENT 'Caissier qui a encaissé',
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'mobile', 'voucher', 'other') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded', 'partial') DEFAULT 'pending',
    reference VARCHAR(100) NULL COMMENT 'Référence de transaction externe',
    tip_amount DECIMAL(10, 2) DEFAULT 0.00,
    change_given DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_cashier (cashier_id),
    INDEX idx_payments_status (status),
    INDEX idx_payments_method (payment_method),
    INDEX idx_payments_paid_at (paid_at),
    
    CONSTRAINT fk_payments_order 
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_cashier 
        FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: stock_items
-- Description: Inventaire et gestion des stocks
-- =====================================================
CREATE TABLE stock_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(50) NULL UNIQUE COMMENT 'Stock Keeping Unit',
    category VARCHAR(50) NULL COMMENT 'Catégorie de stock: ingrédients, boissons, etc.',
    quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'unité' COMMENT 'kg, L, unité, etc.',
    min_quantity DECIMAL(10, 2) DEFAULT 0 COMMENT 'Seuil d\'alerte stock bas',
    max_quantity DECIMAL(10, 2) NULL COMMENT 'Capacité maximale de stockage',
    unit_cost DECIMAL(10, 2) DEFAULT 0.00,
    supplier VARCHAR(150) NULL,
    supplier_contact VARCHAR(100) NULL,
    location VARCHAR(100) NULL COMMENT 'Emplacement de stockage',
    expiry_date DATE NULL,
    last_restocked_at TIMESTAMP NULL,
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_stock_items_category (category),
    INDEX idx_stock_items_quantity (quantity),
    INDEX idx_stock_items_expiry (expiry_date),
    INDEX idx_stock_items_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: stock_movements
-- Description: Historique des mouvements de stock
-- =====================================================
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    movement_type ENUM('in', 'out', 'adjustment', 'waste', 'transfer') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    previous_quantity DECIMAL(10, 2) NOT NULL,
    new_quantity DECIMAL(10, 2) NOT NULL,
    reason TEXT NULL,
    reference VARCHAR(100) NULL COMMENT 'Numéro de bon de livraison, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_stock_movements_item (stock_item_id),
    INDEX idx_stock_movements_type (movement_type),
    INDEX idx_stock_movements_created (created_at),
    
    CONSTRAINT fk_stock_movements_item 
        FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_movements_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: settings
-- Description: Paramètres du restaurant
-- =====================================================
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_settings_updated_by 
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des paramètres par défaut
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('restaurant_name', 'Mon Restaurant', 'string', 'Nom du restaurant', TRUE),
('restaurant_address', '123 Rue de la Gastronomie, 75001 Paris', 'string', 'Adresse complète', TRUE),
('restaurant_phone', '+33 1 23 45 67 89', 'string', 'Numéro de téléphone', TRUE),
('restaurant_email', 'contact@monrestaurant.fr', 'string', 'Email de contact', TRUE),
('tax_rate', '20', 'number', 'Taux de TVA en pourcentage', FALSE),
('currency', 'EUR', 'string', 'Devise utilisée', FALSE),
('currency_symbol', '€', 'string', 'Symbole de la devise', TRUE),
('opening_hours', '{"monday":"11:00-23:00","tuesday":"11:00-23:00","wednesday":"11:00-23:00","thursday":"11:00-23:00","friday":"11:00-00:00","saturday":"11:00-00:00","sunday":"12:00-22:00"}', 'json', 'Horaires d\'ouverture', TRUE),
('receipt_footer', 'Merci de votre visite !', 'string', 'Message sur les reçus', FALSE),
('low_stock_threshold', '10', 'number', 'Seuil d\'alerte stock bas par défaut', FALSE);

-- =====================================================
-- TABLE: backups
-- Description: Historique des sauvegardes
-- =====================================================
CREATE TABLE backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NULL COMMENT 'Taille en octets',
    backup_type ENUM('manual', 'scheduled', 'pre_update') DEFAULT 'manual',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_backups_status (status),
    INDEX idx_backups_type (backup_type),
    INDEX idx_backups_created (created_at),
    
    CONSTRAINT fk_backups_created_by 
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: activity_logs
-- Description: Journal d'activité du système
-- =====================================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL COMMENT 'Type d\'entité: user, order, payment, etc.',
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_activity_logs_user (user_id),
    INDEX idx_activity_logs_action (action),
    INDEX idx_activity_logs_entity (entity_type, entity_id),
    INDEX idx_activity_logs_created (created_at),
    
    CONSTRAINT fk_activity_logs_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: reservations (BONUS)
-- Description: Réservations de tables
-- =====================================================
CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_id INT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255) NULL,
    party_size INT NOT NULL DEFAULT 2,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    duration_minutes INT DEFAULT 120,
    status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_reservations_date (reservation_date),
    INDEX idx_reservations_status (status),
    INDEX idx_reservations_table (table_id),
    
    CONSTRAINT fk_reservations_table 
        FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    CONSTRAINT fk_reservations_created_by 
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGER: Génération automatique du numéro de commande
-- =====================================================
DELIMITER //
CREATE TRIGGER before_order_insert 
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD((SELECT COALESCE(MAX(id), 0) + 1 FROM orders), 5, '0'));
    END IF;
END//
DELIMITER ;

-- =====================================================
-- TRIGGER: Génération automatique du numéro de paiement
-- =====================================================
DELIMITER //
CREATE TRIGGER before_payment_insert 
BEFORE INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.payment_number IS NULL OR NEW.payment_number = '' THEN
        SET NEW.payment_number = CONCAT('PAY-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD((SELECT COALESCE(MAX(id), 0) + 1 FROM payments), 5, '0'));
    END IF;
END//
DELIMITER ;

-- =====================================================
-- TRIGGER: Mise à jour du total de commande
-- =====================================================
DELIMITER //
CREATE TRIGGER after_order_item_change
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (SELECT COALESCE(SUM(total_price), 0) FROM order_items WHERE order_id = NEW.order_id),
        total_amount = subtotal + tax_amount - discount_amount,
        updated_at = NOW()
    WHERE id = NEW.order_id;
END//
DELIMITER ;

-- =====================================================
-- FONCTION: Vérifier le rôle d'un utilisateur
-- =====================================================
DELIMITER //
CREATE FUNCTION has_role(p_user_id BIGINT UNSIGNED, p_role_name VARCHAR(50))
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE role_exists BOOLEAN DEFAULT FALSE;
    
    SELECT EXISTS(
        SELECT 1 
        FROM user_roles ur 
        JOIN roles r ON ur.role_id = r.id 
        WHERE ur.user_id = p_user_id AND r.name = p_role_name
    ) INTO role_exists;
    
    RETURN role_exists;
END//
DELIMITER ;

-- =====================================================
-- VUES UTILES
-- =====================================================

-- Vue: Commandes avec détails
CREATE VIEW v_orders_details AS
SELECT 
    o.id,
    o.order_number,
    o.status,
    o.order_type,
    o.total_amount,
    o.created_at,
    rt.table_number,
    CONCAT(u.first_name, ' ', u.last_name) AS server_name,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS items_count
FROM orders o
LEFT JOIN restaurant_tables rt ON o.table_id = rt.id
LEFT JOIN users u ON o.server_id = u.id;

-- Vue: Stock bas
CREATE VIEW v_low_stock AS
SELECT 
    id,
    name,
    sku,
    category,
    quantity,
    unit,
    min_quantity,
    supplier,
    (min_quantity - quantity) AS shortage
FROM stock_items
WHERE quantity <= min_quantity AND is_active = TRUE;

-- Vue: Revenus journaliers
CREATE VIEW v_daily_revenue AS
SELECT 
    DATE(p.paid_at) AS date,
    COUNT(DISTINCT p.order_id) AS orders_count,
    SUM(p.amount) AS total_revenue,
    SUM(p.tip_amount) AS total_tips,
    AVG(p.amount) AS average_order_value
FROM payments p
WHERE p.status = 'completed'
GROUP BY DATE(p.paid_at);

-- Vue: Plats populaires
CREATE VIEW v_popular_items AS
SELECT 
    mi.id,
    mi.name,
    mi.price,
    c.name AS category,
    COUNT(oi.id) AS times_ordered,
    SUM(oi.quantity) AS total_quantity,
    SUM(oi.total_price) AS total_revenue
FROM menu_items mi
JOIN order_items oi ON mi.id = oi.menu_item_id
JOIN categories c ON mi.category_id = c.id
GROUP BY mi.id, mi.name, mi.price, c.name
ORDER BY times_ordered DESC;

-- =====================================================
-- DONNÉES DE TEST (Optionnel - À supprimer en production)
-- =====================================================

-- Utilisateur admin par défaut (mot de passe: password123 - À changer!)
-- Le hash correspond à bcrypt('password123')
INSERT INTO users (first_name, last_name, email, password, is_active) VALUES
('Admin', 'Système', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Assigner le rôle admin
INSERT INTO user_roles (user_id, role_id) 
SELECT u.id, r.id FROM users u, roles r WHERE u.email = 'admin@restaurant.com' AND r.name = 'admin';

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

-- Pour exécuter ce script:
-- mysql -u root -p nom_base_de_donnees < restaurant_management.sql
--
-- Ou dans Laravel, créez des migrations basées sur ce schéma
