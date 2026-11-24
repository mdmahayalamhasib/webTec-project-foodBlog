-- ============================================================
-- FoodBlog - single consolidated schema + demo seed data
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < config/schema.sql
-- (The app also imports this file automatically on first run
--  if the database does not exist yet.)
-- ============================================================

CREATE DATABASE IF NOT EXISTS food_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_blog;

CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(255)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('admin','member') NOT NULL DEFAULT 'member',
    profile_picture VARCHAR(255)  DEFAULT NULL,
    remember_token  VARCHAR(255)  DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS restaurants (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(150) NOT NULL,
    location          VARCHAR(150) NOT NULL,
    area              VARCHAR(100) NOT NULL,
    short_background  TEXT,
    goals             TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name          VARCHAR(150)  NOT NULL,
    description   TEXT,
    price         DECIMAL(10,2) NOT NULL,
    image_path    VARCHAR(255)  DEFAULT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    user_id      INT NOT NULL,
    comment      TEXT NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS food_experience_posts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    title         VARCHAR(255) NOT NULL,
    content       TEXT NOT NULL,
    post_type     ENUM('restaurant','food','both') NOT NULL DEFAULT 'both',
    restaurant_id INT DEFAULT NULL,
    menu_item_id  INT DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL,
    FOREIGN KEY (menu_item_id)  REFERENCES menu_items(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS food_experience_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES food_experience_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)                 ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Demo accounts
--   admin@foodblog.com   / Admin@1234
--   member@foodblog.com  / Member@1234
-- ------------------------------------------------------------
INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES
(1, 'Admin',       'admin@foodblog.com',  '$2y$10$xVtERYjRURZXEtX9DWGEPOtw72xUE1eyyAhG1er2PR17.ldPVhvdS',  'admin'),
(2, 'Demo Member', 'member@foodblog.com', '$2y$10$HhEMJgm.3lCFEyZu.a1vEODZF6ej43JDccUXbWAA3EzE6CKV6q8Oa', 'member');

-- ------------------------------------------------------------
-- Demo content
-- ------------------------------------------------------------
INSERT IGNORE INTO restaurants (id, name, location, area, short_background, goals) VALUES
(1, 'Star Kabab & Restaurant', 'Dhaka', 'Dhanmondi',
    'A long-standing Dhanmondi favourite known for charcoal-grilled kababs and traditional Bangladeshi platters.',
    'Serve authentic, affordable grilled food with consistent quality for families and students.'),
(2, 'Pasta Corner', 'Dhaka', 'Banani',
    'A cosy Banani cafe serving freshly made pasta, wood-fired pizza and desserts.',
    'Bring simple Italian comfort food to Dhaka with fresh local ingredients.'),
(3, 'Cox Seafood House', 'Cox''s Bazar', 'Kolatoli',
    'Beachside seafood restaurant serving the daily catch grilled, fried or curried.',
    'Offer the freshest seafood experience on the Cox''s Bazar coast.');

INSERT IGNORE INTO menu_items (id, restaurant_id, name, description, price) VALUES
(1, 1, 'Chicken Seekh Kabab',  'Minced chicken kababs grilled over charcoal, served with naan and salad.', 320.00),
(2, 1, 'Beef Tehari',          'Fragrant mustard-oil tehari with tender beef and green chilli.',            280.00),
(3, 2, 'Creamy Alfredo Pasta', 'Fettuccine tossed in a rich parmesan cream sauce with grilled chicken.',    450.00),
(4, 2, 'Margherita Pizza',     'Wood-fired pizza with tomato, mozzarella and fresh basil.',                 520.00),
(5, 3, 'Grilled Lobster',      'Whole local lobster grilled with garlic butter and lemon.',                1800.00),
(6, 3, 'Fish Fry Platter',     'Crispy fried Red Snapper with fries and tartar sauce.',                     650.00);

INSERT IGNORE INTO reviews (id, menu_item_id, user_id, comment) VALUES
(1, 1, 2, 'Perfectly smoky and juicy. The naan was fresh and hot too!'),
(2, 3, 2, 'Very creamy, generous portion. Would order again.');

INSERT IGNORE INTO food_experience_posts (id, user_id, title, content, post_type, restaurant_id, menu_item_id) VALUES
(1, 2, 'A perfect Friday dinner in Dhanmondi',
    'We went to Star Kabab on a busy Friday night. Despite the crowd the service was quick, and the chicken seekh kabab was the highlight. Highly recommended for groups.',
    'both', 1, 1);

INSERT IGNORE INTO food_experience_comments (id, post_id, user_id, comment) VALUES
(1, 1, 1, 'Thanks for sharing, Demo Member! Glad you enjoyed it.');
