-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2025 at 06:12 PM
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
-- Database: `gaming_store`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddFlashSale` (IN `p_product_id` INT, IN `p_discount` INT, IN `p_stock` INT, IN `p_start_time` DATETIME, IN `p_end_time` DATETIME)   BEGIN
    DECLARE product_exists INT;
    DECLARE overlapping_sale INT;
    
    SELECT COUNT(*) INTO product_exists
    FROM products WHERE id = p_product_id;
    
    IF product_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Product does not exist';
    END IF;
    
    IF p_discount < 0 OR p_discount > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Discount must be between 0 and 100';
    END IF;
    
    IF p_stock < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock cannot be negative';
    END IF;
    
    IF p_end_time <= p_start_time THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'End time must be after start time';
    END IF;
    
    SELECT COUNT(*) INTO overlapping_sale
    FROM flash_sales
    WHERE product_id = p_product_id
    AND ((p_start_time <= end_time AND p_end_time >= start_time)
         OR (start_time <= p_end_time AND end_time >= p_start_time));
    
    IF overlapping_sale > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Overlapping flash sale exists';
    END IF;
    
    INSERT INTO flash_sales (product_id, discount, stock, start_time, end_time)
    VALUES (p_product_id, p_discount, p_stock, p_start_time, p_end_time);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddProduct` (IN `p_seller_id` INT, IN `p_name` VARCHAR(255), IN `p_category` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` INT, IN `p_description` TEXT, IN `p_image_path` VARCHAR(255), OUT `p_product_id` INT)   BEGIN
    INSERT INTO Products (seller_id, name, category, price, discount, description, image_path)
    VALUES (p_seller_id, p_name, p_category, p_price, p_discount, p_description, p_image_path);
    SET p_product_id = LAST_INSERT_ID();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateOrder` (IN `p_user_id` INT, IN `p_name` VARCHAR(100), IN `p_address` TEXT, IN `p_phone` VARCHAR(20), IN `p_payment_method` INT, IN `p_total_amount` DECIMAL(10,2))   BEGIN
    DECLARE v_order_id INT;
    
    -- Mulai transaksi
    START TRANSACTION;
    
    -- Buat order
    INSERT INTO orders (user_id, name, address, phone, payment_method, total_amount, status, created_at)
    VALUES (p_user_id, p_name, p_address, p_phone, p_payment_method, p_total_amount, 'pending', NOW());
    
    SET v_order_id = LAST_INSERT_ID();
    
    -- Pindahkan item dari cart ke order_details
    INSERT INTO order_details (order_id, product_id, quantity, price)
    SELECT v_order_id, c.product_id, c.quantity, p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = p_user_id;
    
    -- Kosongkan cart
    DELETE FROM cart WHERE user_id = p_user_id;
    
    -- Commit transaksi
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `product_id`, `image_path`, `created_at`) VALUES
(1, 2, '67e182307f528.jpg', '2025-03-24 16:02:56');

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

CREATE TABLE `buyers` (
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(7, 4, 3, 1, '2025-04-28 01:41:20'),
(8, 4, 2, 1, '2025-04-28 01:41:26'),
(9, 4, 9, 1, '2025-04-28 01:45:01');

--
-- Triggers `cart`
--
DELIMITER $$
CREATE TRIGGER `after_cart_insert` AFTER INSERT ON `cart` FOR EACH ROW BEGIN
    DECLARE flash_sale_id INT;
    DECLARE current_stock INT;
    
    -- Cek apakah produk dalam flash sale
    SELECT id, stock INTO flash_sale_id, current_stock
    FROM flash_sales
    WHERE product_id = NEW.product_id
    AND NOW() BETWEEN start_time AND end_time
    LIMIT 1;
    
    IF flash_sale_id IS NOT NULL THEN
        IF current_stock >= NEW.quantity THEN
            UPDATE flash_sales
            SET stock = stock - NEW.quantity
            WHERE id = flash_sale_id;
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Insufficient stock for flash sale';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flash_sales`
--

CREATE TABLE `flash_sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `discount` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `flash_sales`
--

INSERT INTO `flash_sales` (`id`, `product_id`, `discount`, `start_time`, `end_time`, `created_at`, `stock`) VALUES
(1, 1, 80, '2025-03-24 00:00:00', '2025-03-24 00:10:00', '2025-03-24 16:02:32', 0),
(2, 6, 90, '2025-03-24 23:10:00', '2025-03-25 00:10:00', '2025-03-24 16:10:49', 0),
(3, 5, 89, '2025-03-24 23:17:00', '2025-03-25 23:17:00', '2025-03-24 16:17:17', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'Your product with ID 1 has been purchased by a buyer (Order ID: 3).', 0, '2025-03-24 03:39:28'),
(2, 1, 'Your order (Order ID: 3) has been placed successfully.', 0, '2025-03-24 03:39:28'),
(3, 2, 'Your product with ID 8 has been purchased by a buyer (Order ID: 4).', 0, '2025-03-24 15:31:22'),
(4, 1, 'Your order (Order ID: 4) has been placed successfully.', 0, '2025-03-24 15:31:22'),
(5, 2, 'Your product with ID 5 has been purchased by a buyer (Order ID: 5).', 0, '2025-03-24 16:20:48'),
(6, 2, 'Your product with ID 6 has been purchased by a buyer (Order ID: 5).', 0, '2025-03-24 16:20:48'),
(7, 1, 'Your order (Order ID: 5) has been placed successfully.', 0, '2025-03-24 16:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_method` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `address`, `phone`, `payment_method`, `total_amount`, `status`, `created_at`) VALUES
(1, 1, 'Wildan Daffa abdillah', 'Kp. Malang mengah Desa sukasari Rt/Rw 001/001 Kec. cipanas Kab. Lebak Prov. Banten', '089505845227', 1, 15000000.00, 'pending', '2025-03-24 03:26:52'),
(2, 1, 'kaila shafa', 'Kp. Malang mengah Desa sukasari Rt/Rw 001/001 Kec. cipanas Kab. Lebak Prov. Banten', '081398121908', 3, 15000000.00, 'pending', '2025-03-24 03:37:37'),
(3, 1, 'kaila shafa', 'Kp. Malang mengah Desa sukasari Rt/Rw 001/001 Kec. cipanas Kab. Lebak Prov. Banten', '081398121908', 3, 15000000.00, 'pending', '2025-03-24 03:39:28'),
(4, 1, 'kaila shafa', 'al', '081398121908', 3, 67000000.00, 'pending', '2025-03-24 15:31:22'),
(5, 1, 'Wildan Daffa abdillah', 'Kp. Malang mengah Desa sukasari Rt/Rw 001/001 Kec. cipanas Kab. Lebak Prov. Banten', '089505845227', 1, 41000000.00, 'pending', '2025-03-24 16:20:48');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE seller_id INT;
    DECLARE product_id INT;
    DECLARE product_name VARCHAR(100);
    
    -- Ambil product_id dari order_details
    SELECT product_id INTO product_id
    FROM order_details
    WHERE order_id = NEW.id
    LIMIT 1;
    
    -- Ambil seller_id dan nama produk
    SELECT seller_id, name INTO seller_id, product_name
    FROM products
    WHERE id = product_id;
    
    -- Notifikasi untuk pembeli
    INSERT INTO notifications (user_id, message, created_at)
    VALUES (NEW.user_id, CONCAT('Your order (Order ID: ', NEW.id, ') has been placed successfully.'), NOW());
    
    -- Notifikasi untuk penjual
    INSERT INTO notifications (user_id, message, created_at)
    VALUES (seller_id, CONCAT('Your product ', product_name, ' has been purchased by a buyer (Order ID: ', NEW.id, ').'), NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_order_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO order_logs (order_id, status, changed_at)
        VALUES (NEW.id, NEW.status, NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 15000000.00),
(2, 2, 1, 1, 15000000.00),
(3, 3, 1, 1, 15000000.00),
(4, 4, 8, 1, 67000000.00),
(5, 5, 5, 2, 18000000.00),
(6, 5, 6, 1, 5000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_logs`
--

CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`) VALUES
(1, 'bank_transfer'),
(2, 'credit_card'),
(3, 'cod');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('computer','gaming_chair','vga','cpu','laptop','monitor','other') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `category`, `price`, `description`, `created_at`, `discount`) VALUES
(1, 2, 'RTX 4090', '', 15000000.00, '', '2025-03-24 02:26:46', 80),
(2, 2, 'Kursi gaming RGC101', 'gaming_chair', 7000000.00, '', '2025-03-24 15:22:54', 0),
(3, 2, 'Kursi gaming EOC 5029', 'gaming_chair', 6500000.00, '', '2025-03-24 15:23:46', 0),
(4, 2, 'Huawei matebook D15', 'laptop', 11000000.00, '', '2025-03-24 15:25:18', 0),
(5, 2, 'ASUS_ROG GL502VM', 'laptop', 18000000.00, '', '2025-03-24 15:26:23', 0),
(6, 2, 'CPU_INTEL_I5 10400F', 'cpu', 5000000.00, '', '2025-03-24 15:27:29', 0),
(7, 2, 'CPU_RYZEN_7 5700X3D', 'cpu', 7200000.00, '', '2025-03-24 15:28:04', 0),
(8, 2, 'MONITOR_ASUS_VG275Q', 'monitor', 6700000.00, '', '2025-03-24 15:29:22', 0),
(9, 2, 'MONITOR_XIAOMI_CURVED GAMING', 'monitor', 7000000.00, '', '2025-03-24 15:30:12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `created_at`) VALUES
(1, 1, 'VGA_RTX_4090.jpg', '2025-03-24 03:26:19'),
(2, 2, 'Chair_RGC101.jpg', '2025-03-24 15:22:54'),
(3, 3, 'Chait_EOC 5029C.jpg', '2025-03-24 15:23:46'),
(4, 4, 'LAPTOP_HUAWEI_MATEBOOK_D15 I7-1195G7.jpg', '2025-03-24 15:25:18'),
(5, 5, 'LAPTO[_ASUS_ROG GL502VM.jpg', '2025-03-24 15:26:23'),
(6, 6, 'CPU_INTEL_I5 10400F.jpg', '2025-03-24 15:27:29'),
(7, 7, 'CPU_RYZEN_7 5700X3D.jpg', '2025-03-24 15:28:04'),
(8, 8, 'MONITOR_ASUS_VG275Q.jpg', '2025-03-24 15:29:35'),
(9, 9, 'MONITOR_XIAOMI_CURVED GAMING.jpg', '2025-03-24 15:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `discount_percentage` int(11) NOT NULL CHECK (`discount_percentage` between 1 and 100),
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `product_id`, `user_id`, `rating`, `review`, `created_at`) VALUES
(1, 1, 1, 5, 'greet', '2025-03-24 03:40:26'),
(2, 1, 1, 5, 'greet', '2025-03-24 03:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_details`
--

CREATE TABLE `seller_details` (
  `seller_id` int(11) NOT NULL,
  `store_name` varchar(100) DEFAULT NULL,
  `store_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_details`
--

INSERT INTO `seller_details` (`seller_id`, `store_name`, `store_address`) VALUES
(2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','seller') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'wildan', '$2y$10$74PgMD6Y4IVq1ofznsOxkuS19ChCfsYCbEd58W7e2T5qMlZJrjbvq', 'buyer', '2025-03-24 02:25:27'),
(2, 'jeremi', '$2y$10$2IQ0NFcO7Q21wR9ydLKRD.9eJB.F3.njnikTU1VU/ChZVmbGGa3li', 'seller', '2025-03-24 02:25:54'),
(3, 'daffa', '$2y$10$btNMmY7WJf31PjOqCfNAsOUA6Y.CxCP57gvUPTdHQ2hN6alszgFw6', 'buyer', '2025-04-06 13:32:45'),
(4, 'danu', '$2y$10$2t.kYg4sqJbVP5qBU8WgRuy1JNQ5ENm9IremcyYF11oEEFaAPp1xS', 'buyer', '2025-04-28 01:41:03');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `buyers`
--
ALTER TABLE `buyers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_method` (`payment_method`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_order_details_product_id` (`product_id`);

--
-- Indexes for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `seller_details`
--
ALTER TABLE `seller_details`
  ADD PRIMARY KEY (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ads`
--
ALTER TABLE `ads`
  ADD CONSTRAINT `ads_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `buyers`
--
ALTER TABLE `buyers`
  ADD CONSTRAINT `buyers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD CONSTRAINT `flash_sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `promos`
--
ALTER TABLE `promos`
  ADD CONSTRAINT `promos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_details`
--
ALTER TABLE `seller_details`
  ADD CONSTRAINT `seller_details_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
