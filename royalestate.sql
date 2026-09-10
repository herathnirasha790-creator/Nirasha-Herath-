-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2026 at 03:31 PM
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
-- Database: `royalestate`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `user_id`, `user_name`, `user_email`, `phone`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, NULL, 'Nuwan Perera', 'nuwan@example.com', '0771234567', 'Room availability', 'Is the pool open in December?', 1, '2026-05-24 12:43:01'),
(2, NULL, 'Amali Silva', 'amali@example.com', '0761234567', 'Wedding package', 'Can we customize the menu?', 1, '2026-05-24 12:43:01'),
(3, 9, 'Chamika Dinizuru', 'chamikadinisuru2@gmail.com', '0774532864', 'package', 'test', 1, '2026-07-22 07:58:51');

-- --------------------------------------------------------

--
-- Table structure for table `event_bookings`
--

CREATE TABLE `event_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hall_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `guests` int(11) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `estimated_price` decimal(10,2) NOT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `booking_type` enum('event_hall','package') DEFAULT 'event_hall',
  `whatsapp_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_bookings`
--

INSERT INTO `event_bookings` (`id`, `user_id`, `hall_id`, `package_id`, `event_date`, `guests`, `special_requests`, `estimated_price`, `final_price`, `status`, `payment_status`, `stripe_payment_intent_id`, `booking_type`, `whatsapp_link`, `created_at`, `updated_at`) VALUES
(9, 8, 5, 2, '2026-07-22', 150, 'aaa', 1514250.00, NULL, 'pending', 'paid', 'pi_3TvcqNRGiYQbuvnv0HHaAToz', 'package', NULL, '2026-07-21 12:35:47', '2026-07-21 12:35:47'),
(10, 8, 6, 5, '2026-07-23', 24, '', 403800.00, NULL, 'pending', 'paid', 'pi_3Tvd06RGiYQbuvnv1onx3Cnj', 'package', NULL, '2026-07-21 12:42:46', '2026-07-21 12:42:46'),
(11, 8, 2, 1, '2026-07-22', 150, 'test', 841250.00, NULL, 'pending', 'paid', 'pi_3TvdM2RGiYQbuvnv1AV61Wca', 'package', NULL, '2026-07-21 13:05:30', '2026-07-21 13:05:30'),
(12, 8, 3, 1, '2026-07-29', 1, 'test', 841250.00, NULL, 'pending', 'paid', 'pi_3TvdWdRGiYQbuvnv1xvQyvf5', 'event_hall', NULL, '2026-07-21 13:16:19', '2026-07-21 13:16:19'),
(13, 8, 2, 2, '2026-07-23', 1, '', 1514250.00, NULL, 'pending', 'paid', 'pi_3Tvdk9RGiYQbuvnv1Tmvo4Kc', 'package', NULL, '2026-07-21 13:30:22', '2026-07-21 13:30:22'),
(14, 8, 6, 4, '2026-07-24', 1, '', 269200.00, NULL, 'pending', 'paid', 'pi_3Tve20RGiYQbuvnv1YoJAiK7', 'event_hall', NULL, '2026-07-21 13:48:43', '2026-07-21 13:48:43'),
(15, 8, 4, 7, '2026-07-31', 1, '', 336500.00, NULL, 'pending', 'paid', 'pi_3Tve2dRGiYQbuvnv0Z5sPXIm', 'package', NULL, '2026-07-21 13:49:20', '2026-07-21 13:49:20'),
(16, 9, 5, 3, '2026-07-23', 140, 'test', 2523750.00, NULL, 'pending', 'paid', 'pi_3TveyKRGiYQbuvnv0xDWUPFM', 'event_hall', NULL, '2026-07-21 14:49:04', '2026-07-21 14:49:04'),
(17, 9, 3, 5, '2026-07-30', 1, '', 403800.00, NULL, 'pending', 'paid', 'pi_3Tvhh3RGiYQbuvnv0JiNKOmk', 'event_hall', NULL, '2026-07-21 17:43:31', '2026-07-21 17:43:31'),
(18, 9, 6, 3, '2026-07-28', 1, '', 2523750.00, NULL, 'pending', 'paid', 'pi_3TvhikRGiYQbuvnv135TMlrK', 'package', NULL, '2026-07-21 17:45:16', '2026-07-21 17:45:16'),
(19, 9, 5, 7, '2026-07-24', 100, '', 336500.00, NULL, 'pending', 'paid', 'pi_3TvylyRGiYQbuvnv0rymFVwj', 'event_hall', NULL, '2026-07-22 11:57:36', '2026-07-22 11:57:36'),
(20, 9, 5, 1, '2026-07-26', 100, '', 841250.00, NULL, 'pending', 'paid', 'pi_3TvymlRGiYQbuvnv0i4TJ1Oj', 'package', NULL, '2026-07-22 11:58:25', '2026-07-22 11:58:25');

-- --------------------------------------------------------

--
-- Table structure for table `event_halls`
--

CREATE TABLE `event_halls` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('wedding','party','conference') DEFAULT 'wedding',
  `capacity` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `badge` varchar(100) DEFAULT 'Premium',
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_halls`
--

INSERT INTO `event_halls` (`id`, `name`, `category`, `capacity`, `base_price`, `description`, `amenities`, `badge`, `image`, `images`, `status`, `created_at`) VALUES
(1, 'Royal Wedding Hall', 'wedding', 500, 673000.00, 'Elegant wedding hall with stunning chandeliers, traditional decor, and state-of-the-art sound system.', '[]', 'Premium', 'uploads/halls/royal-wedding-hall/1783454078_6a4d597ec840a.avif', '[\"uploads\\/halls\\/royal-wedding-hall\\/1783454078_6a4d597ec840a.avif\"]', 'active', '2026-05-24 12:43:01'),
(2, 'Garden Party Hall', 'wedding', 200, 269200.00, 'Beautiful open-air venue surrounded by lush gardens, perfect for birthday parties and engagements.', '[]', 'Premium', 'uploads/halls/garden-party-hall/1783454067_6a4d59730321b.avif', '[\"uploads\\/halls\\/garden-party-hall\\/1783454067_6a4d59730321b.avif\"]', 'active', '2026-05-24 12:43:01'),
(3, 'Grand Conference Hall', 'wedding', 300, 168250.00, 'Professional venue equipped with modern AV technology, perfect for corporate events and seminars.', '[]', 'Premium', 'uploads/halls/grand-conference-hall/1783454057_6a4d5969d919a.avif', '[\"uploads\\/halls\\/grand-conference-hall\\/1783454057_6a4d5969d919a.avif\"]', 'active', '2026-05-24 12:43:01'),
(4, 'Crystal Ballroom', 'wedding', 350, 504750.00, 'Stunning ballroom with crystal chandeliers, luxury decor, and premium sound & lighting systems.', '[]', 'Premium', 'uploads/halls/crystal-ballroom/1783454048_6a4d5960b0704.jpg', '[\"uploads\\/halls\\/crystal-ballroom\\/1783454048_6a4d5960b0704.jpg\"]', 'active', '2026-05-24 12:43:01'),
(5, 'Poolside Party Area', 'wedding', 150, 201900.00, 'Exclusive poolside venue perfect for summer parties, cocktail events, and intimate gatherings.', '[]', 'Premium', 'uploads/halls/poolside-party-area/1783454041_6a4d595920ad1.jpg', '[\"uploads\\/halls\\/poolside-party-area\\/1783454041_6a4d595920ad1.jpg\"]', 'active', '2026-05-24 12:43:01'),
(6, 'Executive Boardroom', 'wedding', 50, 67300.00, 'Private executive boardroom for small meetings, interviews, and business discussions.', '[]', 'Premium', 'uploads/halls/executive-boardroom/1783454030_6a4d594e71cf7.avif', '[\"uploads\\/halls\\/executive-boardroom\\/1783454030_6a4d594e71cf7.avif\"]', 'active', '2026-05-24 12:43:01');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('wedding','party','corporate') DEFAULT 'wedding',
  `description` text DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `price`, `category`, `description`, `inclusions`, `image`, `images`, `status`, `created_at`) VALUES
(1, 'Silver Wedding Package', 841250.00, 'wedding', '', 'Welcome drink for all guests, Premium dessert counter, Food plates (5 varieties), Basic decorations, Sound system', 'uploads/packages/silver-wedding-package/1783454821_6a4d5c6523b7e.jpg', '[\"uploads\\/packages\\/silver-wedding-package\\/1783454821_6a4d5c6523b7e.jpg\"]', 'active', '2026-05-24 12:43:01'),
(2, 'Gold Wedding Package', 1514250.00, 'wedding', '', 'Welcome drink + Champagne toast, Premium dessert + cake, Food plates (8 varieties), Full decorations + flowers, DJ + professional sound, Professional photography', 'uploads/packages/gold-wedding-package/1783454643_6a4d5bb39f66a.avif', '[\"uploads\\/packages\\/gold-wedding-package\\/1783454643_6a4d5bb39f66a.avif\"]', 'active', '2026-05-24 12:43:01'),
(3, 'Platinum Wedding Package', 2523750.00, 'wedding', '', 'Welcome drink + premium bar, Gourmet dessert + 3-tier cake, Food plates (12 varieties), Luxury decor + lighting, Live band + DJ, Photo + video package', 'uploads/packages/platinum-wedding-package/1783454778_6a4d5c3a5d877.jpg', '[\"uploads\\/packages\\/platinum-wedding-package\\/1783454778_6a4d5c3a5d877.jpg\"]', 'active', '2026-05-24 12:43:01'),
(4, 'Birthday Bash Package', 269200.00, 'party', '', 'Welcome drink, Birthday cake, Food plates (4 varieties), Balloon decorations, Basic sound system', 'uploads/packages/birthday-bash-package/1783454787_6a4d5c4380fc7.avif', '[\"uploads\\/packages\\/birthday-bash-package\\/1783454787_6a4d5c4380fc7.avif\"]', 'active', '2026-05-24 12:43:01'),
(5, 'Anniversary Celebration', 403800.00, 'party', '', 'Champagne toast, Special anniversary cake, 5-course dinner, Romantic decorations, Live music, Photography', 'uploads/packages/anniversary-celebration/1783454611_6a4d5b9366491.webp', '[\"uploads\\/packages\\/anniversary-celebration\\/1783454611_6a4d5b9366491.webp\"]', 'active', '2026-05-24 12:43:01'),
(6, 'Engagement Ceremony', 504750.00, 'party', '', 'Welcome drinks, Engagement cake, Food plates (6 varieties), Floral decorations, Sound + lighting', 'uploads/packages/engagement-ceremony/1783454467_6a4d5b0384f0e.avif', '[\"uploads\\/packages\\/engagement-ceremony\\/1783454467_6a4d5b0384f0e.avif\"]', 'active', '2026-05-24 12:43:01'),
(7, 'Conference Package', 336500.00, 'corporate', '', 'Conference hall rental\r\nAV equipment + projector\r\nSound system + microphones\r\nTea/coffee breaks\r\nLunch buffet\r\nWiFi + printing', 'uploads/packages/conference-package/1783454401_6a4d5ac14a2e1.avif', '[\"uploads\\/packages\\/conference-package\\/1783454401_6a4d5ac14a2e1.avif\"]', 'active', '2026-05-24 12:43:01'),
(8, 'Seminar Package', 235550.00, 'corporate', '', 'Seminar hall\r\nProjector + screen\r\nPA system\r\nNotepad + pens\r\nRefreshments', 'uploads/packages/seminar-package/1783454621_6a4d5b9dc858b.jpg', '[\"uploads\\/packages\\/seminar-package\\/1783454621_6a4d5b9dc858b.jpg\"]', 'active', '2026-05-24 12:43:01');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_type` enum('room','event_hall','package') NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'usd',
  `status` enum('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `booking_type`, `booking_id`, `stripe_payment_intent_id`, `amount`, `currency`, `status`, `created_at`, `updated_at`) VALUES
(1, 8, 'event_hall', NULL, 'pi_3TvaemRGiYQbuvnv1l41obpj', 841250.00, 'lkr', 'pending', '2026-07-21 10:12:09', '2026-07-21 10:12:09'),
(2, 8, 'room', NULL, 'pi_3TvasfRGiYQbuvnv1REOOLoS', 168250.00, 'lkr', 'pending', '2026-07-21 10:26:30', '2026-07-21 10:26:30'),
(3, 8, 'room', NULL, 'pi_3Tvbu3RGiYQbuvnv04VHfpdw', 673000.00, 'lkr', 'pending', '2026-07-21 11:32:03', '2026-07-21 11:32:03'),
(4, 8, 'room', NULL, 'pi_3Tvbu4RGiYQbuvnv19xNbJwC', 673000.00, 'lkr', 'pending', '2026-07-21 11:32:04', '2026-07-21 11:32:04'),
(5, 8, 'event_hall', NULL, 'pi_3TvbvaRGiYQbuvnv0egYqAyM', 841250.00, 'lkr', 'pending', '2026-07-21 11:33:38', '2026-07-21 11:33:38'),
(6, 8, 'event_hall', NULL, 'pi_3TvbvcRGiYQbuvnv0v23NlWC', 841250.00, 'lkr', 'pending', '2026-07-21 11:33:40', '2026-07-21 11:33:40'),
(7, 8, 'event_hall', NULL, 'pi_3TvbveRGiYQbuvnv1qGumFwW', 841250.00, 'lkr', 'pending', '2026-07-21 11:33:42', '2026-07-21 11:33:42'),
(8, 8, 'room', NULL, 'pi_3TvbzORGiYQbuvnv05Hp7kFN', 168250.00, 'lkr', 'pending', '2026-07-21 11:37:35', '2026-07-21 11:37:35'),
(9, 8, 'room', NULL, 'pi_3Tvc6KRGiYQbuvnv1QTgOmu6', 235550.00, 'lkr', 'pending', '2026-07-21 11:44:44', '2026-07-21 11:44:44'),
(10, 8, 'room', NULL, 'pi_3TvcFpRGiYQbuvnv0gPsPkjw', 100950.00, 'lkr', 'pending', '2026-07-21 11:54:33', '2026-07-21 11:54:33'),
(11, 8, 'room', NULL, 'pi_3TvcIaRGiYQbuvnv0UlSFZGM', 168250.00, 'lkr', 'pending', '2026-07-21 11:57:24', '2026-07-21 11:57:24'),
(12, 8, 'room', 7, 'pi_3TvcOTRGiYQbuvnv05jXDNh1', 134600.00, 'lkr', 'succeeded', '2026-07-21 12:03:29', '2026-07-21 12:07:00'),
(13, 8, 'room', NULL, 'pi_3TvcQgRGiYQbuvnv0lQYfd7g', 100950.00, 'lkr', 'pending', '2026-07-21 12:05:46', '2026-07-21 12:05:46'),
(14, 8, 'event_hall', 9, 'pi_3TvcqNRGiYQbuvnv0HHaAToz', 1514250.00, 'lkr', 'succeeded', '2026-07-21 12:32:20', '2026-07-21 12:35:47'),
(15, 8, 'event_hall', NULL, 'pi_3TvczjRGiYQbuvnv1mTXLg0v', 403800.00, 'lkr', 'pending', '2026-07-21 12:41:59', '2026-07-21 12:41:59'),
(16, 8, 'event_hall', 10, 'pi_3Tvd06RGiYQbuvnv1onx3Cnj', 403800.00, 'lkr', 'succeeded', '2026-07-21 12:42:23', '2026-07-21 12:42:46'),
(17, 8, 'event_hall', NULL, 'pi_3TvdLrRGiYQbuvnv1erU6Ifu', 841250.00, 'lkr', 'pending', '2026-07-21 13:04:52', '2026-07-21 13:04:52'),
(18, 8, 'event_hall', 11, 'pi_3TvdM2RGiYQbuvnv1AV61Wca', 841250.00, 'lkr', 'succeeded', '2026-07-21 13:05:03', '2026-07-21 13:05:30'),
(19, 8, 'event_hall', NULL, 'pi_3TvdVRRGiYQbuvnv1fJU580Z', 841250.00, 'lkr', 'pending', '2026-07-21 13:14:46', '2026-07-21 13:14:46'),
(20, 8, 'event_hall', 12, 'pi_3TvdWdRGiYQbuvnv1xvQyvf5', 841250.00, 'lkr', 'succeeded', '2026-07-21 13:15:59', '2026-07-21 13:16:19'),
(21, 8, 'package', 13, 'pi_3Tvdk9RGiYQbuvnv1Tmvo4Kc', 1514250.00, 'lkr', 'succeeded', '2026-07-21 13:29:57', '2026-07-21 13:30:22'),
(22, 8, 'room', 8, 'pi_3Tve1MRGiYQbuvnv00DL7YZ8', 168250.00, 'lkr', 'succeeded', '2026-07-21 13:47:44', '2026-07-21 13:48:08'),
(23, 8, 'event_hall', 14, 'pi_3Tve20RGiYQbuvnv1YoJAiK7', 269200.00, 'lkr', 'succeeded', '2026-07-21 13:48:24', '2026-07-21 13:48:43'),
(24, 8, 'package', 15, 'pi_3Tve2dRGiYQbuvnv0Z5sPXIm', 336500.00, 'lkr', 'succeeded', '2026-07-21 13:49:03', '2026-07-21 13:49:20'),
(25, 9, 'event_hall', 16, 'pi_3TveyKRGiYQbuvnv0xDWUPFM', 2523750.00, 'lkr', 'succeeded', '2026-07-21 14:48:40', '2026-07-21 14:49:04'),
(26, 9, 'room', 9, 'pi_3TvfyoRGiYQbuvnv1MROIqtZ', 168250.00, 'lkr', 'succeeded', '2026-07-21 15:53:15', '2026-07-21 15:54:58'),
(27, 9, 'event_hall', 17, 'pi_3Tvhh3RGiYQbuvnv0JiNKOmk', 403800.00, 'lkr', 'succeeded', '2026-07-21 17:43:01', '2026-07-21 17:43:31'),
(28, 9, 'package', 18, 'pi_3TvhikRGiYQbuvnv135TMlrK', 2523750.00, 'lkr', 'succeeded', '2026-07-21 17:44:47', '2026-07-21 17:45:16'),
(29, 9, 'room', NULL, 'pi_3Tvj98RGiYQbuvnv1URH6acB', 168250.00, 'lkr', 'pending', '2026-07-21 19:16:06', '2026-07-21 19:16:06'),
(30, 9, 'room', 10, 'pi_3TvyklRGiYQbuvnv1MUjTm5t', 168250.00, 'lkr', 'succeeded', '2026-07-22 11:55:59', '2026-07-22 11:56:40'),
(31, 9, 'event_hall', 19, 'pi_3TvylyRGiYQbuvnv0rymFVwj', 336500.00, 'lkr', 'succeeded', '2026-07-22 11:57:14', '2026-07-22 11:57:36'),
(32, 9, 'package', 20, 'pi_3TvymlRGiYQbuvnv0i4TJ1Oj', 841250.00, 'lkr', 'succeeded', '2026-07-22 11:58:03', '2026-07-22 11:58:25');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_avatar` varchar(255) DEFAULT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `title` varchar(200) DEFAULT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `user_name`, `user_avatar`, `rating`, `title`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'Chamika Dinizuru', NULL, 5, 'Amazing Experience!', 'The rooms were luxurious and the staff was incredibly helpful. Best hotel in Kurunegala!', 'approved', '2026-07-22 04:57:21', '2026-07-22 04:57:21'),
(2, 4, 'Nirasha Herath', NULL, 5, 'Perfect Wedding Venue', 'We had our wedding at Royal Estate. The event team made everything perfect. Highly recommended!', 'approved', '2026-07-20 04:57:21', '2026-07-22 04:57:21'),
(3, 4, 'Saman Perera', NULL, 4, 'Great Corporate Event', 'Great place for corporate events. Professional service and excellent facilities.', 'approved', '2026-07-17 04:57:21', '2026-07-22 04:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_guests` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `badge` varchar(100) DEFAULT 'Premium',
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `price`, `max_guests`, `description`, `features`, `badge`, `image`, `images`, `status`, `created_at`) VALUES
(1, 'Deluxe King Room', 40380.00, 2, 'Spacious room with king-size bed, modern amenities, and city views.', '[]', 'Premium', 'uploads/rooms/deluxe-king-room/1783453891_6a4d58c32522f.jpg', '[\"uploads\\/rooms\\/deluxe-king-room\\/1783453891_6a4d58c32522f.jpg\"]', 'active', '2026-05-24 12:43:01'),
(2, 'Executive Suite', 67300.00, 2, 'Luxury suite with separate living area, jacuzzi, and executive lounge access.', '[]', 'Premium', 'uploads/rooms/executive-suite/1783453884_6a4d58bc83ff6.jpg', '[\"uploads\\/rooms\\/executive-suite\\/1783453884_6a4d58bc83ff6.jpg\"]', 'active', '2026-05-24 12:43:01'),
(3, 'Presidential Suite', 117775.00, 4, 'The ultimate luxury with panoramic views, private terrace, and butler service.', '[]', 'Premium', 'uploads/rooms/presidential-suite/1783453873_6a4d58b1bca23.jpg', '[\"uploads\\/rooms\\/presidential-suite\\/1783453873_6a4d58b1bca23.jpg\"]', 'active', '2026-05-24 12:43:01'),
(4, 'Family Suite', 60570.00, 4, 'Two connecting bedrooms, kid-friendly amenities, kitchenette.', '[]', 'Premium', 'uploads/rooms/family-suite/1783453863_6a4d58a748d53.jpg', '[\"uploads\\/rooms\\/family-suite\\/1783453863_6a4d58a748d53.jpg\"]', 'active', '2026-05-24 12:43:01'),
(5, 'Ocean View Room', 50475.00, 2, 'Beautiful room with private balcony and breathtaking ocean views.', '[]', 'Premium', 'uploads/rooms/ocean-view-room/1783453845_6a4d58950d8ef.jpg', '[\"uploads\\/rooms\\/ocean-view-room\\/1783453845_6a4d58950d8ef.jpg\"]', 'active', '2026-05-24 12:43:01'),
(6, 'Honeymoon Suite', 84125.00, 2, 'Romantic suite with heart-shaped jacuzzi, rose petal decorations, and champagne.', '[]', 'Premium', 'uploads/rooms/honeymoon-suite/1783453792_6a4d5860c441b.webp', '[\"uploads\\/rooms\\/honeymoon-suite\\/1783453792_6a4d5860c441b.webp\"]', 'active', '2026-05-24 12:43:01');

-- --------------------------------------------------------

--
-- Table structure for table `room_bookings`
--

CREATE TABLE `room_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_bookings`
--

INSERT INTO `room_bookings` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `guests`, `total_price`, `special_requests`, `status`, `payment_status`, `stripe_payment_intent_id`, `created_at`) VALUES
(7, 8, 2, '2026-07-22', '2026-07-24', 1, 134600.00, '', 'pending', 'paid', 'pi_3TvcOTRGiYQbuvnv05jXDNh1', '2026-07-21 12:07:00'),
(8, 8, 6, '2026-07-22', '2026-07-24', 1, 168250.00, '', 'pending', 'paid', 'pi_3Tve1MRGiYQbuvnv00DL7YZ8', '2026-07-21 13:48:08'),
(9, 9, 6, '2026-07-29', '2026-07-31', 2, 168250.00, 'test', 'pending', 'paid', 'pi_3TvfyoRGiYQbuvnv1MROIqtZ', '2026-07-21 15:54:58'),
(10, 9, 6, '2026-08-02', '2026-08-04', 1, 168250.00, '', 'pending', 'paid', 'pi_3TvyklRGiYQbuvnv1MUjTm5t', '2026-07-22 11:56:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','owner') DEFAULT 'user',
  `avatar` varchar(255) DEFAULT 'https://randomuser.me/api/portraits/men/32.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `created_at`) VALUES
(2, 'Admin User', 'admin@royalestate.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'https://randomuser.me/api/portraits/men/2.jpg', '2026-05-24 12:43:01'),
(5, 'Nirasha', 'nirasha@gmail.com', '$2y$10$UF5AVNO8PjqJcPs3ngLWLe7vuZtQMflz9/KtopI70W8sLVrStgQwq', 'user', 'https://randomuser.me/api/portraits/men/32.jpg', '2026-05-24 13:00:17'),
(6, 'Nuwan', 'nuwan@gmail.com', '$2y$10$ShmRVGtNhWIC..Hzy2X38uuB9JwA2iPCCE3o/3ufipvRlqQ/nX1/G', 'user', 'https://randomuser.me/api/portraits/men/32.jpg', '2026-05-24 13:08:34'),
(8, 'Nirasha Herath', 'nirashaherath@gmail.com', '$2y$10$UAtqsc9Pxt3MlPbeJKdFvOpaR0NlQRAnH67mcT4UBqVSGAZo4jZOa', 'user', 'https://randomuser.me/api/portraits/men/32.jpg', '2026-07-21 11:41:51'),
(9, 'Chamika Dinizuru', 'chamikadinisuru2@gmail.com', '$2y$10$DL2uQpALW2WknFNDQ/EJHumfYUvz4P0XEE5Aos732hhs7UuVw12KS', 'user', 'uploads/avatars/user_9_1784658216.jpg', '2026-07-21 13:58:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hall_id` (`hall_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `event_halls`
--
ALTER TABLE `event_halls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_intent` (`stripe_payment_intent_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_bookings`
--
ALTER TABLE `event_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `event_halls`
--
ALTER TABLE `event_halls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `room_bookings`
--
ALTER TABLE `room_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD CONSTRAINT `event_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_bookings_ibfk_2` FOREIGN KEY (`hall_id`) REFERENCES `event_halls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_bookings_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD CONSTRAINT `room_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
