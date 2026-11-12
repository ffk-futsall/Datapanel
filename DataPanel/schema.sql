-- Schema only: data rows have been omitted.
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Янв 01 2026 г., 11:17
-- Версия сервера: 10.11.6-MariaDB-0+deb12u1
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `iptv_standig`
--

-- --------------------------------------------------------

--
-- Структура таблицы `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `blocked_at` datetime NOT NULL,
  `blocked_until` datetime NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `attempts_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `login_attempts`
--


-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `logs`
--


-- --------------------------------------------------------

--
-- Структура таблицы `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `security_logs`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tv_clients`
--

CREATE TABLE `tv_clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `subscription_date` date NOT NULL,
  `months` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `device_count` int(11) NOT NULL,
  `viewing_program` varchar(255) NOT NULL,
  `paid` decimal(10,2) DEFAULT 0.00,
  `provider_cost` decimal(10,2) DEFAULT 0.00,
  `earned` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_clients`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tv_events`
--

CREATE TABLE `tv_events` (
  `id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `months` int(11) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `user` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_events`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tv_payments`
--

CREATE TABLE `tv_payments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `paid` decimal(10,2) NOT NULL,
  `provider_cost` decimal(10,2) NOT NULL,
  `earned` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_payments`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tv_providers`
--

CREATE TABLE `tv_providers` (
  `id` int(11) NOT NULL,
  `operator` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `referral_link` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_providers`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--


--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `ip_address_2` (`ip_address`),
  ADD KEY `blocked_until` (`blocked_until`);

--
-- Индексы таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `severity` (`severity`);

--
-- Индексы таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`);

--
-- Индексы таблицы `tv_events`
--
ALTER TABLE `tv_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `type` (`type`),
  ADD KEY `created_at` (`created_at`);

--
-- Индексы таблицы `tv_payments`
--
ALTER TABLE `tv_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `year` (`year`);

--
-- Индексы таблицы `tv_providers`
--
ALTER TABLE `tv_providers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `tv_events`
--
ALTER TABLE `tv_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `tv_payments`
--
ALTER TABLE `tv_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `tv_providers`
--
ALTER TABLE `tv_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  ADD CONSTRAINT `tv_clients_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `tv_providers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
