-- ============================================================
-- Stripe payment integration — run this ONCE against your
-- existing `royalestate` database (e.g. via phpMyAdmin > SQL tab).
-- It only ADDS columns/tables, nothing existing is removed.
-- ============================================================

ALTER TABLE `room_bookings`
  ADD COLUMN `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER `status`,
  ADD COLUMN `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL AFTER `payment_status`;

ALTER TABLE `event_bookings`
  ADD COLUMN `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER `status`,
  ADD COLUMN `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL AFTER `payment_status`;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `booking_type` ENUM('room','event_hall','package') NOT NULL,
  `booking_id` INT(11) DEFAULT NULL,
  `stripe_payment_intent_id` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'usd',
  `status` ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_intent` (`stripe_payment_intent_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
