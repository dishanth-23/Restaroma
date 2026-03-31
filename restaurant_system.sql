-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 30, 2026 at 08:15 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$BZi6m.NoBdUKRQBQCqHeUeM3hujztWrvaTd4oUEPr1sxv9TOzio4W');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `image`, `created_at`, `category`, `quantity`) VALUES
(1, 'Margherita Pizza', 'Classic pizza with fresh tomatoes, mozzarella, and basil.', 2500.00, 'margherita.jpg', '2026-03-03 04:34:19', 'Main Course', 50),
(2, 'Grilled Chicken', 'Served with seasonal veggies and mashed potatoes.', 2800.00, 'grilled_chicken.jpg', '2026-03-03 04:34:19', 'Main Course', 50),
(3, 'Caesar Salad', 'Crisp romaine with Caesar dressing and croutons.', 650.00, 'caesar_salad.jpg', '2026-03-03 04:34:19', 'Salads', 50),
(4, 'Spaghetti Bolognese', 'Traditional Italian pasta with rich beef tomato sauce.', 1700.00, 'spaghetti_bolognese.jpg', '2026-03-03 05:35:18', 'Main Course', 50),
(5, 'Cheeseburger Deluxe', 'Juicy beef burger with cheddar, lettuce, tomato, and fries.', 1200.00, 'cheeseburger_deluxe.jpg', '2026-03-03 05:35:18', 'Fast Food', 50),
(6, 'Vegetable Stir Fry', 'Mixed seasonal vegetables sautéed in a savory soy sauce.', 800.00, 'vegetable_stir_fry.jpg', '2026-03-03 05:35:18', 'Salads', 49),
(7, 'Crispy Chicken Burger', 'Crispy fried chicken fillet with lettuce, mayonnaise, and tomato in a toasted bun.', 1100.00, 'chicken-burger.jpg', '2026-03-03 05:35:18', 'Fast Food', 50),
(8, 'Fish and Chips', 'Crispy battered fish served with fries and tartar sauce.', 1600.00, 'fish_and_chips.jpg', '2026-03-03 05:35:18', 'Fast Food', 50),
(9, 'Chocolate Lava Cake', 'Warm chocolate cake with a gooey molten center.', 550.00, 'chocolate_lava_cake.jpg', '2026-03-03 05:35:18', 'Dessert', 50),
(10, 'Shrimp Alfredo Pasta', 'Creamy Alfredo sauce tossed with fettuccine and grilled shrimp.', 2200.00, 'shrimp_alfredo_pasta.jpg', '2026-03-03 05:36:17', 'Main Course', 50),
(11, 'Turkey Club Sandwich', 'Triple-layer sandwich with turkey, bacon, lettuce, and tomato.', 900.00, 'turkey_club_sandwich.jpg', '2026-03-03 05:36:17', 'Fast Food', 50),
(12, 'Greek Salad', 'Fresh cucumbers, tomatoes, olives, feta cheese, and vinaigrette.', 600.00, 'greek_salad.jpg', '2026-03-03 05:36:17', 'Salads', 50),
(13, 'Vanilla Ice Cream Sundae', 'Vanilla ice cream topped with chocolate sauce and nuts.', 450.00, 'vanilla-ice-cream-sundae.jpg', '2026-03-08 07:07:22', 'Dessert', 49),
(14, 'Avocado Garden Salad', 'Fresh avocado, cherry tomatoes, mixed greens, cucumber, and light lemon dressing.', 750.00, 'avocado-salad.jpg', '2026-03-08 07:09:45', 'Salads', 50),
(15, 'New York Cheesecake', 'Creamy baked cheesecake with a buttery biscuit base and strawberry topping.', 650.00, 'cheesecake.jpg', '2026-03-08 07:11:34', 'Dessert', 50),
(16, 'Classic Tiramisu', 'Italian dessert layered with coffee-soaked ladyfingers and mascarpone cream.', 700.00, 'tiramisu.jpg', '2026-03-08 07:12:34', 'Dessert', 50),
(17, 'Classic Iced Latte', 'Smooth espresso blended with chilled milk and ice for a refreshing coffee treat.', 900.00, 'classic-iced-latte.jpg', '2026-03-09 04:40:53', 'Drink', 50);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` enum('Unpaid','Paid','Refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unpaid',
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal_amount` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `created_at`, `payment_method`, `payment_status`, `payment_reference`, `promo_code`, `subtotal_amount`, `discount_amount`) VALUES
(43, 1, 800.00, 'Pending', '2026-03-29 17:01:46', 'Cash', 'Unpaid', NULL, NULL, NULL, NULL),
(44, 1, 800.00, 'Pending', '2026-03-29 17:16:04', 'Cash', 'Unpaid', NULL, NULL, NULL, NULL),
(45, 1, 800.00, 'Pending', '2026-03-29 17:23:48', 'Cash', 'Unpaid', NULL, NULL, NULL, NULL),
(46, 1, 800.00, 'Pending', '2026-03-29 17:32:23', 'Cash', 'Unpaid', NULL, NULL, 800.00, 0.00),
(47, 1, 800.00, 'Pending', '2026-03-29 17:34:50', 'Card', 'Paid', NULL, NULL, 800.00, 0.00),
(48, 1, 2550.00, 'Pending', '2026-03-29 17:45:56', 'Cash', 'Unpaid', NULL, NULL, 2550.00, 0.00),
(49, 1, 1250.00, 'Pending', '2026-03-29 17:46:30', 'Card', 'Paid', NULL, NULL, 1250.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_menu` (`menu_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price`) VALUES
(86, 43, 6, 1, 800.00),
(87, 44, 6, 1, 800.00),
(88, 45, 6, 1, 800.00),
(89, 46, 6, 1, 800.00),
(90, 47, 6, 1, 800.00),
(91, 48, 14, 1, 750.00),
(92, 48, 16, 1, 700.00),
(93, 48, 7, 1, 1100.00),
(94, 49, 13, 1, 450.00),
(95, 49, 6, 1, 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

DROP TABLE IF EXISTS `promos`;
CREATE TABLE IF NOT EXISTS `promos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_percent` int DEFAULT '0',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `images` json DEFAULT (_cp850'[]'),
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `featured` tinyint(1) DEFAULT '0',
  `schedule_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'range',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `weekly_days` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `daily_start_time` time DEFAULT NULL,
  `daily_end_time` time DEFAULT NULL,
  `promo_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `title`, `description`, `discount_percent`, `category`, `start_date`, `end_date`, `created_at`, `images`, `status`, `featured`, `schedule_type`, `valid_from`, `valid_to`, `weekly_days`, `daily_start_time`, `daily_end_time`, `promo_code`) VALUES
(14, 'Fast Food Friday', 'Get 15% off on all fast food items every Friday!', 15, 'General', '2026-03-20 11:00:00', '2026-03-27 23:00:00', '2026-03-19 06:20:25', '[\"69bb95a9e8cf3_cheeseburger_deluxe.jpg\"]', 'active', 0, 'weekly', '2026-03-20', '2026-03-27', '5', '11:00:00', '23:00:00', NULL),
(15, 'Welcome Offer', 'Get 10% off on your first order.', 10, 'General', '2026-03-19 12:30:00', '2026-03-20 23:00:00', '2026-03-19 06:54:00', '[\"69bb9d88e0f6f_chicken-burger.jpg\", \"69bb9d88e1925_grilled_chicken.jpg\", \"69bb9d88e2393_turkey_club_sandwich.jpg\"]', 'active', 0, 'weekly', '2026-03-19', '2026-03-20', '4,5', '12:30:00', '23:00:00', 'NEW01');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `guests` int NOT NULL,
  `status` enum('Pending','Approved','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `Message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`reservation_date`,`reservation_time`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `reservation_date`, `reservation_time`, `guests`, `status`, `Message`) VALUES
(49, 1, '2026-03-31', '01:25:00', 5, 'Pending', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Chandrasekar Dishanth', 'dishan5.ds@gmail.com', '$2y$10$Jt3YARcXNHMVxu.3DdYgZOQXR8/3g72OL4eG08jnUSLrsQ1M6ueFG', 'user', '2026-03-03 08:37:54');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
