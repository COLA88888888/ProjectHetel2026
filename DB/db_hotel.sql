-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 02:46 PM
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
-- Database: `db_hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Booked',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `passport_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guest_count` int(11) DEFAULT 1,
  `deposit_amount` decimal(15,2) DEFAULT 0.00,
  `food_charge` decimal(15,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount_received` decimal(15,2) DEFAULT NULL,
  `change_amount` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `room_id`, `customer_name`, `customer_phone`, `check_in_date`, `check_out_date`, `total_price`, `status`, `created_at`, `passport_number`, `address`, `guest_count`, `deposit_amount`, `food_charge`, `payment_method`, `amount_received`, `change_amount`) VALUES
(1, 2, 'ໂຄລ່າ ສີນົນທາ', '207735433', '2026-04-28', '2026-04-29', 500000.00, 'Completed', '2026-04-28 15:23:17', '045048405', 'ໂພນໝີ', 2, 0.00, 0.00, NULL, NULL, NULL),
(2, 1, 'ຈັນສະໄໝ', '9999999', '2026-04-29', '2026-04-30', 300000.00, 'Checked In', '2026-04-29 03:39:54', '4t4tq4tr', 'vvczvz', 1, 0.00, 0.00, NULL, NULL, NULL),
(3, 2, 'Somsak', '020 99887766', '2026-04-29', '2026-04-30', 500000.00, 'Completed', '2026-04-29 03:44:57', '', '', 2, 100000.00, 0.00, NULL, NULL, NULL),
(4, 3, 'Somsak', '020 99887766', '2026-04-29', '2026-04-30', 300000.00, 'Cancelled', '2026-04-29 03:49:08', '663434', 'ssss', 1, 100000.00, 0.00, NULL, NULL, NULL),
(5, 3, 'aaaa', '33333322', '2026-05-10', '2026-04-30', 300000.00, 'Completed', '2026-04-29 03:55:53', '54r3e3', 'sds', 1, 0.00, 0.00, 'ເງິນສົດ', 300000.00, 0.00),
(6, 3, 'ໂຄລ່າ ສີນົນທາ', '207735433', '2026-05-10', '2026-05-11', 300000.00, 'Completed', '2026-05-10 01:54:50', '098886', 'ໂພນໝີ', 2, 100000.00, 0.00, 'ເງິນສົດ', 200000.00, 0.00),
(7, 6, 'Cola Synontha', '02077354334', '2026-05-10', '2026-05-11', 600000.00, 'Completed', '2026-05-10 02:00:37', '454667', 'Loas\r\nLaos', 1, 0.00, 70000.00, 'ເງິນສົດ', 670000.00, 0.00),
(8, 14, 'ໂຄລ່າ ສີນົນທາ', '207735433', '2026-05-10', '2026-05-11', 250000.00, 'Completed', '2026-05-10 11:44:27', '098886', 'ໂພນໝີ', 1, 250000.00, 0.00, 'ເງິນສົດ', 0.00, 0.00),
(9, 10, 'Cola Synontha', '02077354334', '2026-05-10', '2026-05-12', 600000.00, 'Completed', '2026-05-10 12:14:59', '098886', 'Loas\r\nLaos', 1, 300000.00, 15000.00, 'ເງິນສົດ', 315000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `currency`
--

CREATE TABLE `currency` (
  `id` int(11) NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `exchange_rate` decimal(15,2) NOT NULL,
  `symbol` varchar(10) DEFAULT '',
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `currency`
--

INSERT INTO `currency` (`id`, `currency_code`, `currency_name`, `exchange_rate`, `symbol`, `is_default`) VALUES
(1, 'LAK', 'ກີບ', 1.00, '₭', 1),
(2, 'THB', 'ໄທ', 680.00, '฿', 0),
(3, 'USD', 'ໂດລາ', 22000.00, '$', 0),
(4, 'CNY', 'ຢວນ', 3000.00, '¥', 0);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_title`, `amount`, `expense_date`, `created_at`) VALUES
(1, 'ຄ່ານ້ຳ-ຄ່າໄຟຟ້າ', 1200000.00, '2026-01-15', '2026-04-29 03:54:10'),
(2, 'ຄ່າຊື້ເຄື່ອງອຸປະໂພກ', 800000.00, '2026-01-20', '2026-04-29 03:54:10'),
(3, 'ຄ່ານ້ຳ-ຄ່າໄຟຟ້າ', 1350000.00, '2026-02-15', '2026-04-29 03:54:10'),
(4, 'ຄ່າສ້ອມແປງແອ', 450000.00, '2026-02-22', '2026-04-29 03:54:10'),
(5, 'ຄ່ານ້ຳ-ຄ່າໄຟຟ້າ', 1100000.00, '2026-03-15', '2026-04-29 03:54:10'),
(6, 'ຄ່າຊື້ເຄື່ອງອຸປະໂພກ', 900000.00, '2026-03-25', '2026-04-29 03:54:10'),
(7, 'ຄ່ານ້ຳ-ຄ່າໄຟຟ້າ', 1400000.00, '2026-04-10', '2026-04-29 03:54:10'),
(8, 'ເງິນເດືອນພະນັກງານ', 3500000.00, '2026-04-25', '2026-04-29 03:54:10');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT 'Food',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock_qty` int(11) DEFAULT 50,
  `item_type` enum('Inventory','Service') DEFAULT 'Inventory'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `item_name`, `price`, `category`, `created_at`, `stock_qty`, `item_type`) VALUES
(1, 'ເຝີ (Pho)', 35000.00, 'Food', '2026-04-29 03:39:00', 50, 'Service'),
(2, 'ເຂົ້າຜັດ (Fried Rice)', 30000.00, 'Food', '2026-04-29 03:39:00', 49, 'Inventory'),
(3, 'ຕຳໝາກຮຸ່ງ (Papaya Salad)', 25000.00, 'Food', '2026-04-29 03:39:00', 50, 'Inventory'),
(4, 'ເບຍລາວ (Beerlao Large)', 18000.00, 'Drinks', '2026-04-29 03:39:00', 50, 'Inventory'),
(5, 'ກາເຟເຢັນ (Iced Coffee)', 20000.00, 'Drinks', '2026-04-29 03:39:00', 50, 'Service'),
(6, 'ນ້ຳດື່ມ (Water)', 5000.00, 'Drinks', '2026-04-29 03:39:00', 50, 'Inventory');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `o_qty` int(11) DEFAULT 0,
  `amount` decimal(15,2) DEFAULT 0.00,
  `o_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `prod_id` int(11) NOT NULL,
  `prod_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `bprice` decimal(15,2) DEFAULT 0.00,
  `sprice` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`prod_id`, `prod_name`, `category`, `image`, `qty`, `bprice`, `sprice`, `created_at`) VALUES
(2, 'ເບຍລາວ', 'ເຄື່ອງດື່ມ', '', 1000, 15000.00, 20000.00, '2026-05-10 02:32:15');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`) VALUES
(6, 'ເຄື່ອງດື່ມ'),
(7, 'ອາຫານ'),
(8, 'ເຂົ້າໜົມ');

-- --------------------------------------------------------

--
-- Table structure for table `receives`
--

CREATE TABLE `receives` (
  `rec_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT 0,
  `amount` decimal(15,2) DEFAULT 0.00,
  `rec_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_orders`
--

CREATE TABLE `restaurant_orders` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT 'Guest',
  `total_price` decimal(10,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid','Charged to Room') DEFAULT 'Paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `restaurant_orders`
--

INSERT INTO `restaurant_orders` (`id`, `room_id`, `customer_name`, `total_price`, `payment_status`, `created_at`) VALUES
(1, NULL, 'Guest', 65000.00, 'Paid', '2026-04-29 03:42:45');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_order_details`
--

CREATE TABLE `restaurant_order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `restaurant_order_details`
--

INSERT INTO `restaurant_order_details` (`id`, `order_id`, `item_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 30000.00),
(2, 1, 1, 1, 35000.00);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `room_type` varchar(100) NOT NULL,
  `bed_type` varchar(50) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Available',
  `housekeeping_status` varchar(50) NOT NULL DEFAULT 'Clean',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `bed_type`, `price`, `status`, `housekeeping_status`, `created_at`) VALUES
(1, '101', 'Standard', 'ຕຽງດ່ຽວ', 300000.00, 'Booked', 'Ready', '2026-04-28 15:08:41'),
(2, '102', 'VIP', 'ຕຽງຄູ່', 500000.00, 'Available', 'Cleaning', '2026-04-28 15:08:42'),
(3, '201', 'Standard', 'ຕຽງດ່ຽວ', 300000.00, 'Available', 'Cleaning', '2026-04-28 15:08:42'),
(4, '103', 'ຫ້ອງຕຽງດ່ຽວ', 'ຕຽງດ່ຽວ', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(5, '104', 'ຫ້ອງຕຽງດ່ຽວ', 'ຕຽງຄູ່', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(6, '105', 'ຫ້ອງຕຽງດ່ຽວ', 'ຕຽງດ່ຽວ', 600000.00, 'Available', 'Cleaning', '2026-04-29 03:38:06'),
(7, '106', 'ຫ້ອງຕຽງດ່ຽວ', 'ຕຽງຄູ່', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(8, '202', 'ຫ້ອງຕຽງຄູ່', 'ຕຽງຄູ່', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(9, '203', 'ຫ້ອງຕຽງຄູ່', 'ຕຽງດ່ຽວ', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(10, '204', 'ຫ້ອງຕຽງຄູ່', 'ຕຽງຄູ່', 600000.00, 'Available', 'Cleaning', '2026-04-29 03:38:06'),
(11, '205', 'ຫ້ອງຕຽງຄູ່', 'ຕຽງດ່ຽວ', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(12, '206', 'ຫ້ອງຕຽງຄູ່', 'ຕຽງຄູ່', 600000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(13, '301', 'Standard', 'ຕຽງດ່ຽວ', 250000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(14, '302', 'Standard', 'ຕຽງຄູ່', 250000.00, 'Available', 'Cleaning', '2026-04-29 03:38:06'),
(15, '303', 'Standard', 'ຕຽງດ່ຽວ', 250000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(16, '304', 'Standard', 'ຕຽງຄູ່', 250000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(17, '305', 'Standard', 'ຕຽງດ່ຽວ', 250000.00, 'Available', 'Ready', '2026-04-29 03:38:06'),
(18, '306', 'Standard', 'ຕຽງຄູ່', 250000.00, 'Available', 'Ready', '2026-04-29 03:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `room_services`
--

CREATE TABLE `room_services` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `room_services`
--

INSERT INTO `room_services` (`id`, `booking_id`, `item_name`, `price`, `qty`, `total_price`, `created_at`) VALUES
(1, 7, 'ນ້ຳດື່ມ', 20000.00, 2, 40000.00, '2026-05-10 02:01:13'),
(2, 7, 'ເຂົ້າໜົມ', 30000.00, 1, 30000.00, '2026-05-10 02:01:41'),
(3, 9, 'ນ້ຳດື່ມ', 15000.00, 1, 15000.00, '2026-05-10 12:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `room_type_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `room_type_name`, `price`, `description`, `created_at`) VALUES
(3, 'Standard', 0.00, 'ຫ້ອງທຳມະດາ\r\n', '2026-04-28 15:06:23'),
(4, 'ຫ້ອງຕຽງດ່ຽວ', 0.00, '', '2026-04-28 15:07:02'),
(5, 'ຫ້ອງຕຽງຄູ່', 0.00, '', '2026-04-28 15:07:16'),
(6, 'Standard', 0.00, 'ຫ້ອງມາດຕະຖານ', '2026-04-28 15:08:41'),
(7, 'VIP', 0.00, 'ຫ້ອງ VIP', '2026-04-28 15:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'hotel_name', 'ລະບົບໂຮງແຮມຫຼູຫຼາ'),
(2, 'hotel_phone', '020 12345678'),
(3, 'currency', 'LAK'),
(10, 'hotel_address', 'ນະຄອນຫຼວງວຽງຈັນ'),
(11, 'receipt_footer', 'ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການ!'),
(12, 'hotel_logo', 'logo_1778414164.webp');

-- --------------------------------------------------------

--
-- Table structure for table `type_root`
--

CREATE TABLE `type_root` (
  `Id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ພະນັກງານ',
  `permissions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_img` varchar(255) DEFAULT 'default_avatar.png',
  `phone` varchar(20) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `fname`, `lname`, `status`, `permissions`, `created_at`, `profile_img`, `phone`, `email`, `address`) VALUES
(1, 'admin', '$2y$10$GaWgig.QorHUair6y0fmduW2vUfpFdOBvBvsFSFMdWwTfZ0zdWNbC', 'Cola', 'Synontha', 'ຜູ້ບໍລິຫານ', '[\"products_type\",\"products_info\",\"receives\",\"orders\",\"locations\",\"users\"]', '2026-04-29 03:00:04', 'user_1778414522.jpeg', '02077354334', 'admin@gmail.com', 'Loas\r\nLaos'),
(2, 'khola', '$2y$10$Slyi3x2N4pGflkvKzGWkjeFOROS.qEoHA3z99KmtoLYGgDgqA5Ljq', 'khola', 'synontha', 'ພະນັກງານ', '[\"room_types\",\"bookings\",\"reports\"]', '2026-04-29 03:36:47', 'user_1778413233.jpeg', '020 95321484', 'khola@gmail.com', 'ບ້ານ ໂນນສະຫວ່າງ ເມືອງ ວຽງຄຳ ແຂວງ ວຽງຈັນ');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`prod_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receives`
--
ALTER TABLE `receives`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `restaurant_orders`
--
ALTER TABLE `restaurant_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_order_details`
--
ALTER TABLE `restaurant_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_services`
--
ALTER TABLE `room_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `type_root`
--
ALTER TABLE `type_root`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `currency`
--
ALTER TABLE `currency`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `receives`
--
ALTER TABLE `receives`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_orders`
--
ALTER TABLE `restaurant_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurant_order_details`
--
ALTER TABLE `restaurant_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `room_services`
--
ALTER TABLE `room_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `type_root`
--
ALTER TABLE `type_root`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `restaurant_order_details`
--
ALTER TABLE `restaurant_order_details`
  ADD CONSTRAINT `restaurant_order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `restaurant_orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
