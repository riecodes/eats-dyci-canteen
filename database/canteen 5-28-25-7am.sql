-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 01:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `canteen`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('promotion','emergency','info') NOT NULL,
  `seller_id` int(11) NOT NULL,
  `stall_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `type`, `seller_id`, `stall_id`, `created_at`) VALUES
(1, '50% Off on All Noodles!', 'Limited time offer for lunch hours.', 'promotion', 1, 1, '2025-05-14 03:51:29'),
(2, 'Closed Today', 'Due to maintenance, our stall is closed today.', 'emergency', 1, 2, '2025-05-14 03:51:29'),
(3, 'New Menu Items!', 'We have added new vegan options to our menu.', 'info', 1, 1, '2025-05-14 03:51:29'),
(4, 'new', 'Check my new announcement', 'info', 1, 1, '2025-05-20 06:10:58'),
(5, 'Fresh new BreakFast', 'Check our new Bread with sausages', 'promotion', 1, 1, '2025-05-20 06:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_images`
--

CREATE TABLE `announcement_images` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `image_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_images`
--

INSERT INTO `announcement_images` (`id`, `announcement_id`, `image_url`) VALUES
(1, 1, 'https://example.com/images/noodles-1.jpg'),
(2, 1, 'https://example.com/images/noodles-2.jpg'),
(3, 2, 'https://example.com/images/closed-sign.jpg'),
(4, 3, 'https://example.com/images/vegan1.jpg'),
(5, 3, 'https://example.com/images/vegan2.jpg'),
(6, 4, ''),
(7, 4, '');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` text DEFAULT NULL,
  `stall_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `stall_id`) VALUES
('cat1', 'Breakfast', 'Morning meal to kickstart your day', 'https://th.bing.com/th/id/OIP.HpXVsKWAhylyyuRdetS4cQHaE8?w=258&h=180&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 1),
('cat2', 'Lunch', 'Midday meal to refuel your energy', 'https://th.bing.com/th/id/OIP.G5MYdVvHkGg5KsLQadllAwHaHa?w=183&h=185&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 1),
('cat3', 'Afternoon Snack', 'Small bite between lunch and dinner', 'https://th.bing.com/th/id/OIP.6WJcqOIYlLX3DAbG9i5PwwHaE8?w=273&h=182&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 2),
('cat4', 'Dinner', 'Evening meal to end the day', 'https://th.bing.com/th?id=OIF.okT4wLt%2fhxoI4e%2bb8x%2bmPQ&w=242&h=180&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 2),
('cat5', 'Supper', 'Late-night light meal for a quick bite', 'https://th.bing.com/th/id/OIP.cjnIEPYRlnM31OSCaFMHvwHaE9?w=193&h=180&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 1),
('cat6', 'Break', 'Quick snack or drink during your work breaks', 'https://th.bing.com/th/id/OIP.af1gjkGH5oWp9VD2pf2uawHaE8?w=258&h=180&c=7&r=0&o=7&cb=iwp2&pid=1.7&rm=3', 2);

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` double NOT NULL,
  `image` text DEFAULT NULL,
  `category_id` varchar(10) DEFAULT NULL,
  `stall_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `name`, `description`, `price`, `image`, `category_id`, `stall_id`) VALUES
(1, 'Veggie Burger', 'Delicious plant-based burger with lettuce and tomato', 99.99, 'https://th.bing.com/th/id/OIP.sVvjZiDL2RKdLoRmv_gAcAHaEK?cb=iwc2&rs=1&pid=ImgDetMain', 'cat3', 2),
(2, 'Veggie Soup', 'Warm and comforting veggie soup', 345.99, 'https://www.missinthekitchen.com/wp-content/uploads/2023/09/Hamburger-Vegetable-Soup-Recipe-photo-F-735x735.jpg', 'cat3', 2),
(3, 'Iced Tea', 'Chilled lemon-flavored iced tea', 25, 'https://th.bing.com/th/id/R.065e9aa3e35a3038d605f9cf60a026f5?rik=y9SQ2nscNpz0Rw&pid=ImgRaw&r=0', 'cat2', 1),
(4, 'Fries', 'Crispy golden fries', 45.5, 'https://th.bing.com/th/id/OIP.s7z7jIuWfwE4o_rEiNa1dQHaE7?cb=iwc2&rs=1&pid=ImgDetMain', 'cat1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderRef` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_price` double DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderRef`, `user_id`, `total_price`, `created_at`, `status`) VALUES
('ORD-20250421-001', 2, 170.49, '2025-05-14 03:51:29', 'pending'),
('ORD-20250421-002', 3, 95.99, '2025-05-14 03:51:29', 'pending'),
('ORD-20250421-003', 1, 240, '2025-05-14 03:51:29', 'pending'),
('ORD-20250521-26D506', 6, 445.98, '2025-05-20 19:28:59', 'Processing');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `food_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `food_id`, `quantity`) VALUES
(1, 'ORD-20250421-001', 1, 2),
(2, 'ORD-20250421-001', 2, 3),
(3, 'ORD-20250421-001', 3, 1),
(4, 'ORD-20250421-002', 1, 2),
(5, 'ORD-20250421-002', 2, 3),
(6, 'ORD-20250421-002', 3, 1),
(7, 'ORD-20250521-26D506', 1, 1),
(8, 'ORD-20250521-26D506', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `food_id`, `user_id`, `parent_id`, `comment`, `rating`, `created_at`) VALUES
(1, 1, 2, NULL, 'Loved the veggie burger! Very juicy and flavorful.', 5, '2025-05-14 03:51:29'),
(2, 1, 2, NULL, 'Good but a bit salty.', 3, '2025-05-14 03:51:29'),
(3, 2, 3, NULL, 'Refreshing drink, perfect with fries!', 4, '2025-05-14 03:51:29');

-- --------------------------------------------------------

--
-- Table structure for table `stalls`
--

CREATE TABLE `stalls` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stalls`
--

INSERT INTO `stalls` (`id`, `name`, `description`, `owner_id`) VALUES
(1, 'Grill House', 'Tasty grilled meals and BBQ delights', 1),
(2, 'Veggie Delight', 'Fresh vegetarian and vegan options', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','seller','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Alice Seller', 'alice@canteen.com', 'hashedpassword1', 'seller', '2025-05-14 03:51:29'),
(2, 'Bob Buyer', 'bob@canteen.com', 'hashedpassword2', 'buyer', '2025-05-14 03:51:29'),
(3, 'Charlie Buyer', 'charlie@canteen.com', 'hashedpassword3', 'buyer', '2025-05-14 03:51:29'),
(4, 'Yabs Mullo', 'admin@gmail.com', '$2b$10$S0GfgLd5cYOrrvjilqkpnOAZewMe.uxP2jFGPOdQuSb9N0L9H8Iv.', 'admin', '2025-05-14 03:51:59'),
(5, 'Alex Tuchi', 'alextuchi0@gmail.com', '$2b$10$z55wzNp/KOQ9q15rAn1ndufd4gHxtTZY4CGLIw5vAcHNm3PiJhjme', 'seller', '2025-05-14 04:09:50'),
(6, 'Sijui', 'sijui@gmail.com', '$2b$10$3whmQQ1q/ZU.sT3tp3OqgOnPVd7tGpvHlidHPW.Ft5yLoGQOI7YRG', 'buyer', '2025-05-20 03:17:34'),
(7, 'Wayne Pandwa', 'wayne@gmail.com', '$2b$10$CWUk55AiD4sHJ3Qs2Qhpye1i7EZvp4/L/c29kzZzAqnmrzvjb4fyC', 'admin', '2025-05-20 03:54:43'),
(8, 'test', 'test@gmail.com', '$2b$10$bYaf6QKQQGPBQTawmbHhbeqLQr4PWZcWLS1H7yG3iEffHV2wrm4bC', 'buyer', '2025-05-24 21:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `user_announcement_views`
--

CREATE TABLE `user_announcement_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_announcement_views`
--

INSERT INTO `user_announcement_views` (`id`, `user_id`, `announcement_id`, `viewed_at`) VALUES
(1, 8, 3, '2025-05-24 21:38:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `stall_id` (`stall_id`);

--
-- Indexes for table `announcement_images`
--
ALTER TABLE `announcement_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stall_id` (`stall_id`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `stall_id` (`stall_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderRef`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stalls`
--
ALTER TABLE `stalls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_announcement` (`user_id`,`announcement_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcement_images`
--
ALTER TABLE `announcement_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stalls`
--
ALTER TABLE `stalls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`);

--
-- Constraints for table `announcement_images`
--
ALTER TABLE `announcement_images`
  ADD CONSTRAINT `announcement_images_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`);

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `foods_ibfk_2` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`orderRef`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `reviews` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stalls`
--
ALTER TABLE `stalls`
  ADD CONSTRAINT `stalls_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_announcement_views`
--
ALTER TABLE `user_announcement_views`
  ADD CONSTRAINT `user_announcement_views_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`),
  ADD CONSTRAINT `user_announcement_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
