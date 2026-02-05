-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3307
-- Tiempo de generación: 04-02-2026 a las 19:28:10
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `chaty`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `es_general` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invitaciones`
--

CREATE TABLE `invitaciones` (
  `id` int(11) NOT NULL,
  `fecha_invitacion` datetime NOT NULL,
  `id_usuario_remitente_id` int(11) NOT NULL,
  `id_usuario_receptor_id` int(11) NOT NULL,
  `chat_id_id` int(11) NOT NULL,
  `mensaje` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `contenido` longtext NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `nombre_usuario_id` int(11) NOT NULL,
  `chat_perteneciente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `latitud` double NOT NULL,
  `longitud` double NOT NULL,
  `baneado` tinyint(4) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `biografia` longtext DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `activo` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `nombre`, `token`, `latitud`, `longitud`, `baneado`, `avatar`, `biografia`, `fecha_creacion`, `activo`) VALUES
(1, 'hugo@gmail.com', '[]', '$2y$13$SLJ4vDZjMrZ8N0s6KDXoQujscgk40qDgBNcLEeDNdbaOsbjAKzjCO', 'hugo', '8477fc3cf57c30cf76d80b28d76cc42e054744fbd1215423f8f797bd2835abd4', 0, 0, 0, NULL, 'Me llamo hugo y esto es una prueba', '2026-01-30 17:54:02', 1),
(2, 'mocholi@gmail.com', '[]', '$2y$13$PhKoyMUZ/Yzd10UYzwYId.rz3l5iUi0IVXSzii46EI/l9GNlQLJtG', 'mocholi', '2d4654e1c9eaeee1dbad77ab1a495075596b668dcda6c94b0b3d51515584e053', 0, 0, 0, NULL, 'Me llamo hugo y esto es una prueba', '2026-01-31 14:17:28', 1),
(3, 'luis@gmail.com', '[]', '$2y$13$FFWBkv3QUWVNeTv8EE3uy.RG95Q8ZAldPJrWk49AU7qeZ1HXKk/sy', 'luis', '38c4e61fc32f699c6778583eeb4beffe8301c09aec972805b6ec7e2f250c087f', 0, 0, 0, NULL, NULL, '2026-02-03 17:59:23', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_chats`
--

CREATE TABLE `user_chats` (
  `user_id` int(11) NOT NULL,
  `chats_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_user`
--

CREATE TABLE `user_user` (
  `user_source` int(11) NOT NULL,
  `user_target` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_2808E1001AF6E02E` (`id_usuario_remitente_id`),
  ADD KEY `IDX_2808E10046ACE344` (`id_usuario_receptor_id`),
  ADD KEY `IDX_2808E1007E3973CC` (`chat_id_id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6C929C8026AB182E` (`nombre_usuario_id`),
  ADD KEY `IDX_6C929C809B498984` (`chat_perteneciente_id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- Indices de la tabla `user_chats`
--
ALTER TABLE `user_chats`
  ADD PRIMARY KEY (`user_id`,`chats_id`),
  ADD KEY `IDX_CFAAE357A76ED395` (`user_id`),
  ADD KEY `IDX_CFAAE357AC6FF313` (`chats_id`);

--
-- Indices de la tabla `user_user`
--
ALTER TABLE `user_user`
  ADD PRIMARY KEY (`user_source`,`user_target`),
  ADD KEY `IDX_F7129A803AD8644E` (`user_source`),
  ADD KEY `IDX_F7129A80233D34C1` (`user_target`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  ADD CONSTRAINT `FK_2808E1001AF6E02E` FOREIGN KEY (`id_usuario_remitente_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_2808E10046ACE344` FOREIGN KEY (`id_usuario_receptor_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_2808E1007E3973CC` FOREIGN KEY (`chat_id_id`) REFERENCES `chats` (`id`);

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `FK_6C929C8026AB182E` FOREIGN KEY (`nombre_usuario_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_6C929C809B498984` FOREIGN KEY (`chat_perteneciente_id`) REFERENCES `chats` (`id`);

--
-- Filtros para la tabla `user_chats`
--
ALTER TABLE `user_chats`
  ADD CONSTRAINT `FK_CFAAE357A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_CFAAE357AC6FF313` FOREIGN KEY (`chats_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_user`
--
ALTER TABLE `user_user`
  ADD CONSTRAINT `FK_F7129A80233D34C1` FOREIGN KEY (`user_target`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_F7129A803AD8644E` FOREIGN KEY (`user_source`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
