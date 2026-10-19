-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 19-10-2026 a las 18:30:37
-- Versión del servidor: 12.3.3-MariaDB
-- Versión de PHP: 8.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `olimpiadas_gaming`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bracket_matches`
--

CREATE TABLE `bracket_matches` (
  `id` int(11) NOT NULL,
  `category` enum('fc26','mk1') NOT NULL,
  `round_name` varchar(50) NOT NULL,
  `round_order` int(11) NOT NULL,
  `match_number` int(11) NOT NULL,
  `player1_id` int(11) DEFAULT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `player1_seed` int(11) DEFAULT NULL,
  `player2_seed` int(11) DEFAULT NULL,
  `score1` int(11) DEFAULT 0,
  `score2` int(11) DEFAULT 0,
  `winner_id` int(11) DEFAULT NULL,
  `mk_result_type` varchar(10) DEFAULT NULL,
  `mk_time_seconds` int(11) DEFAULT 0,
  `penalties_score1` int(11) DEFAULT NULL,
  `penalties_score2` int(11) DEFAULT NULL,
  `next_match_id` int(11) DEFAULT NULL,
  `next_match_slot` int(11) DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `player1_group` varchar(20) DEFAULT NULL,
  `player2_group` varchar(20) DEFAULT NULL,
  `player1_group_seed` int(11) DEFAULT NULL,
  `player2_group_seed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `bracket_matches`
--

INSERT INTO `bracket_matches` (`id`, `category`, `round_name`, `round_order`, `match_number`, `player1_id`, `player2_id`, `player1_seed`, `player2_seed`, `score1`, `score2`, `winner_id`, `mk_result_type`, `mk_time_seconds`, `penalties_score1`, `penalties_score2`, `next_match_id`, `next_match_slot`, `status`, `updated_at`, `player1_group`, `player2_group`, `player1_group_seed`, `player2_group_seed`) VALUES
(221, 'mk1', 'Octavos de Final', 1, 1, 383, 392, 1, 16, 1, 2, 392, NULL, 0, NULL, NULL, 229, 1, 'completed', '2026-10-19 18:15:47', 'Ganador #1', 'Repesca (110s)', 1, 16),
(222, 'mk1', 'Octavos de Final', 1, 2, 388, 386, 8, 9, 2, 1, 388, NULL, 0, NULL, NULL, 229, 2, 'completed', '2026-10-19 18:16:05', 'Ganador #8', 'Ganador #9', 8, 9),
(223, 'mk1', 'Octavos de Final', 1, 3, 395, 403, 4, 13, 0, 2, 403, NULL, 0, NULL, NULL, 230, 1, 'completed', '2026-10-19 18:15:57', 'Ganador #4', 'Ganador #13', 4, 13),
(224, 'mk1', 'Octavos de Final', 1, 4, 377, 394, 5, 12, 2, 1, 377, NULL, 0, NULL, NULL, 230, 2, 'completed', '2026-10-19 18:16:21', 'Ganador #5', 'Ganador #12', 5, 12),
(225, 'mk1', 'Octavos de Final', 1, 5, 373, 376, 2, 15, 2, 0, 373, NULL, 0, NULL, NULL, 231, 1, 'completed', '2026-10-19 18:16:35', 'Ganador #2', 'Ganador #15', 2, 15),
(226, 'mk1', 'Octavos de Final', 1, 6, 374, 393, 7, 10, 2, 1, 374, NULL, 0, NULL, NULL, 231, 2, 'completed', '2026-10-19 18:16:52', 'Ganador #7', 'Ganador #10', 7, 10),
(227, 'mk1', 'Octavos de Final', 1, 7, 375, 387, 3, 14, 0, 2, 387, NULL, 0, NULL, NULL, 232, 1, 'completed', '2026-10-19 18:17:03', 'Ganador #3', 'Ganador #14', 3, 14),
(228, 'mk1', 'Octavos de Final', 1, 8, 380, 384, 6, 11, 2, 0, 380, NULL, 0, NULL, NULL, 232, 2, 'completed', '2026-10-19 18:17:12', 'Ganador #6', 'Ganador #11', 6, 11),
(229, 'mk1', 'Cuartos de Final', 2, 1, 392, 388, NULL, NULL, 2, 0, 392, NULL, 0, NULL, NULL, 233, 1, 'completed', '2026-10-19 18:17:22', NULL, NULL, NULL, NULL),
(230, 'mk1', 'Cuartos de Final', 2, 2, 403, 377, NULL, NULL, 1, 2, 377, NULL, 0, NULL, NULL, 233, 2, 'completed', '2026-10-19 18:17:32', NULL, NULL, NULL, NULL),
(231, 'mk1', 'Cuartos de Final', 2, 3, 373, 374, NULL, NULL, 1, 2, 374, NULL, 0, NULL, NULL, 234, 1, 'completed', '2026-10-19 18:17:50', NULL, NULL, NULL, NULL),
(232, 'mk1', 'Cuartos de Final', 2, 4, 387, 380, NULL, NULL, 2, 0, 387, NULL, 0, NULL, NULL, 234, 2, 'completed', '2026-10-19 18:18:03', NULL, NULL, NULL, NULL),
(233, 'mk1', 'Semifinales', 3, 1, 392, 377, NULL, NULL, 2, 1, 392, NULL, 0, NULL, NULL, 235, 1, 'completed', '2026-10-19 18:18:16', NULL, NULL, NULL, NULL),
(234, 'mk1', 'Semifinales', 3, 2, 374, 387, NULL, NULL, 0, 2, 387, NULL, 0, NULL, NULL, 235, 2, 'completed', '2026-10-19 18:18:27', NULL, NULL, NULL, NULL),
(235, 'mk1', 'Gran Final', 4, 1, 392, 387, NULL, NULL, 2, 1, 392, '2-1', 0, NULL, NULL, NULL, NULL, 'completed', '2026-10-19 18:19:16', NULL, NULL, NULL, NULL),
(236, 'fc26', 'Cuartos de Final', 1, 1, 347, 352, 1, 8, 1, 3, 352, NULL, 0, NULL, NULL, 240, 1, 'completed', '2026-10-19 18:22:50', 'Ganador #1', 'Ganador #8', 1, 8),
(237, 'fc26', 'Cuartos de Final', 1, 2, 349, 339, 4, 5, 2, 0, 349, NULL, 0, NULL, NULL, 240, 2, 'completed', '2026-10-19 18:22:54', 'Ganador #4', 'Ganador #5', 4, 5),
(238, 'fc26', 'Cuartos de Final', 1, 3, 341, 337, 2, 7, 0, 4, 337, NULL, 0, NULL, NULL, 241, 1, 'completed', '2026-10-19 18:22:59', 'Ganador #2', 'Ganador #7', 2, 7),
(239, 'fc26', 'Cuartos de Final', 1, 4, 343, 346, 3, 6, 4, 2, 343, NULL, 0, NULL, NULL, 241, 2, 'completed', '2026-10-19 18:23:05', 'Ganador #3', 'Ganador #6', 3, 6),
(240, 'fc26', 'Semifinales', 2, 1, 352, 349, NULL, NULL, 2, 0, 352, NULL, 0, NULL, NULL, 242, 1, 'completed', '2026-10-19 18:23:14', NULL, NULL, NULL, NULL),
(241, 'fc26', 'Semifinales', 2, 2, 337, 343, NULL, NULL, 0, 1, 343, NULL, 0, NULL, NULL, 242, 2, 'completed', '2026-10-19 18:23:17', NULL, NULL, NULL, NULL),
(242, 'fc26', 'Gran Final', 3, 1, 352, 343, NULL, NULL, 2, 2, 352, NULL, 0, 5, 4, NULL, NULL, 'completed', '2026-10-19 18:24:27', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `category` enum('fc26','mk1') NOT NULL,
  `score` int(11) DEFAULT 0,
  `matches_played` int(11) DEFAULT 0,
  `group_name` varchar(50) DEFAULT 'Grupo Único',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `goals_for` int(11) DEFAULT 0,
  `goals_against` int(11) DEFAULT 0,
  `goal_diff` int(11) DEFAULT 0,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `mk_wins_2_0` int(11) DEFAULT 0,
  `mk_wins_2_1` int(11) DEFAULT 0,
  `mk_points` int(11) DEFAULT 0,
  `match_time_seconds` int(11) DEFAULT 0,
  `assigned_group` varchar(20) DEFAULT 'none',
  `group_seed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `participants`
--

INSERT INTO `participants` (`id`, `name`, `alias`, `category`, `score`, `matches_played`, `group_name`, `created_at`, `goals_for`, `goals_against`, `goal_diff`, `wins`, `losses`, `mk_wins_2_0`, `mk_wins_2_1`, `mk_points`, `match_time_seconds`, `assigned_group`, `group_seed`) VALUES
(337, 'Carlos Mendoza', 'ElMatador_99', 'fc26', 1, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 3, 2, 1, 1, 0, 0, 0, 0, 0, 'Ganador #7', 7),
(338, 'Manuel Rangel', 'ManuPlay', 'fc26', -2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 1, 3, -2, 0, 1, 0, 0, 0, 0, 'none', NULL),
(339, 'Alejandro Ramos', 'AlexSniper', 'fc26', 2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 2, 0, 2, 1, 0, 0, 0, 0, 0, 'Ganador #5', 5),
(340, 'Brayan Gómez', 'BG_Striker', 'fc26', 1, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 2, 1, 1, 1, 0, 0, 0, 0, 0, 'none', NULL),
(341, 'Diego Fernández', 'D10S_FC', 'fc26', 2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 4, 2, 2, 1, 0, 0, 0, 0, 0, 'Ganador #2', 2),
(342, 'Daniel Fuentes', 'DaniRocket', 'fc26', -1, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 2, 3, -1, 0, 1, 0, 0, 0, 0, 'none', NULL),
(343, 'Gabriel Torres', 'GaboTitan', 'fc26', 2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 3, 1, 2, 1, 0, 0, 0, 0, 0, 'Ganador #3', 3),
(344, 'Kevin Paredes', 'KevWall', 'fc26', -1, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 1, 2, -1, 0, 1, 0, 0, 0, 0, 'none', NULL),
(345, 'Mateo Silva', 'TikiTaka_Pro', 'fc26', -1, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 2, 3, -1, 0, 1, 0, 0, 0, 0, 'none', NULL),
(346, 'Tomás Navarro', 'TommyGol', 'fc26', 2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 2, 0, 2, 1, 0, 0, 0, 0, 0, 'Ganador #6', 6),
(347, 'Lucas Herrera', 'LuquitasFC', 'fc26', 4, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 4, 0, 4, 1, 0, 0, 0, 0, 0, 'Ganador #1', 1),
(348, 'Andrés Castillo', 'Castillo_Wall', 'fc26', -2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 0, 2, -2, 0, 1, 0, 0, 0, 0, 'none', NULL),
(349, 'Rodrigo Castro', 'RodriCR7', 'fc26', 2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 3, 1, 2, 1, 0, 0, 0, 0, 0, 'Ganador #4', 4),
(350, 'Nicolás Rivas', 'NicoSpeed', 'fc26', -4, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 0, 4, -4, 0, 1, 0, 0, 0, 0, 'none', NULL),
(351, 'Sebastián Mora', 'SebaGol_6a4', 'fc26', -2, 1, 'Fase Preliminar', '2026-10-19 16:24:44', 0, 2, -2, 0, 1, 0, 0, 0, 0, 'none', NULL),
(352, 'Emilio Vega', 'EmiTackle', 'fc26', 1, 1, 'Fase Preliminar', '2026-10-19 16:24:45', 3, 2, 1, 1, 0, 0, 0, 0, 0, 'Ganador #8', 8),
(353, 'Javier Morales', 'JaviPress_5a3', 'fc26', -2, 1, 'Fase Preliminar', '2026-10-19 16:24:45', 2, 4, -2, 0, 1, 0, 0, 0, 0, 'none', NULL),
(354, 'Samuel Ortiz', 'SamuDribble', 'fc26', -2, 1, 'Fase Preliminar', '2026-10-19 16:24:45', 1, 3, -2, 0, 1, 0, 0, 0, 0, 'none', NULL),
(373, 'Héctor Martínez', 'ScorpionKing', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 1, 0, 1, 0, 2, 57, 'Ganador #2', 2),
(374, 'Luis Cordero', 'ReikoWarrior', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 1, 0, 0, 1, 1, 69, 'Ganador #7', 7),
(375, 'Víctor Salazar', 'SubZero_Freeze', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 1, 0, 1, 0, 2, 57, 'Ganador #3', 3),
(376, 'Pedro Santana', 'NitaraBlood', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 1, 0, 0, 1, 1, 110, 'Ganador #15', 15),
(377, 'Raúl Benítez', 'SmokeKombat', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 1, 0, 1, 0, 2, 62, 'Ganador #5', 5),
(378, 'Oscar León', 'HavocChaos', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:33', 0, 0, 0, 0, 1, 0, 0, 0, 100, 'none', NULL),
(379, 'Fernando Gómez', 'RaidenThunder', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 57, 'none', NULL),
(380, 'Manuel Gil', 'RainWaterGod', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 1, 0, 2, 82, 'Ganador #6', 6),
(381, 'Marcos Suárez', 'BarakaFangs', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 96, 'none', NULL),
(382, 'Cristóbal Paz', 'ShangTsungSoul', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 57, 'none', NULL),
(383, 'Adrián Peña', 'ReptileAcid', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 1, 0, 2, 40, 'Ganador #1', 1),
(384, 'Brayan Rojas', 'MileenaChomp', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 0, 1, 1, 96, 'Ganador #11', 11),
(385, 'Ignacio Ruiz', 'FireGodLiu', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 105, 'none', NULL),
(386, 'David Romero', 'GeneralShao', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 0, 1, 1, 78, 'Ganador #9', 9),
(387, 'Camilo López', 'JohnnyCage_Star', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 0, 1, 1, 105, 'Ganador #14', 14),
(388, 'Esteban Cruz', 'KenshiBlade', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 1, 0, 0, 1, 1, 69, 'Ganador #8', 8),
(389, 'Felipe Navarro', 'KungLaoSpin', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 62, 'none', NULL),
(390, 'Ángel Medina', 'GerasTime', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:03:34', 0, 0, 0, 0, 1, 0, 0, 0, 69, 'none', NULL),
(392, 'lol', 'olol', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:08:36', 0, 0, 0, 0, 1, 0, 0, 0, 110, 'Repesca (110s)', 16),
(393, 'rtyrty', 'etert', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:08:45', 0, 0, 0, 1, 0, 0, 1, 1, 91, 'Ganador #10', 10),
(394, 'wrwrw', 'tfdgd', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:08:55', 0, 0, 0, 1, 0, 0, 1, 1, 100, 'Ganador #12', 12),
(395, 'u6uy6y', 'yy5yt5', 'mk1', 2, 1, 'Fase Preliminar', '2026-10-19 18:09:04', 0, 0, 0, 1, 0, 1, 0, 2, 61, 'Ganador #4', 4),
(396, 'cvcvc', 'cxcxcx', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:09:30', 0, 0, 0, 0, 1, 0, 0, 0, 102, 'none', NULL),
(397, 'asas', 'sdsd', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:09:36', 0, 0, 0, 0, 1, 0, 0, 0, 69, 'none', NULL),
(398, 'yjyj', 'tgtgt', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:09:42', 0, 0, 0, 0, 1, 0, 0, 0, 61, 'none', NULL),
(399, 'jujuyjy', 'hgtgrg', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:10:37', 0, 0, 0, 0, 1, 0, 0, 0, 82, 'none', NULL),
(400, 'jhnhgn', 'vdfv', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:10:47', 0, 0, 0, 0, 1, 0, 0, 0, 78, 'none', NULL),
(401, 'sfsfsd', 'fgfdgfd', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:10:53', 0, 0, 0, 0, 1, 0, 0, 0, 40, 'none', NULL),
(402, 'ththt', 'ggfgz', 'mk1', 0, 1, 'Fase Preliminar', '2026-10-19 18:11:07', 0, 0, 0, 0, 1, 0, 0, 0, 91, 'none', NULL),
(403, 'rrtryty', 'hyjyj', 'mk1', 1, 1, 'Fase Preliminar', '2026-10-19 18:11:14', 0, 0, 0, 1, 0, 0, 1, 1, 102, 'Ganador #13', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preliminary_matches`
--

CREATE TABLE `preliminary_matches` (
  `id` int(11) NOT NULL,
  `category` enum('fc26','mk1') NOT NULL,
  `match_number` int(11) NOT NULL,
  `round_number` int(11) DEFAULT 1,
  `player1_id` int(11) NOT NULL,
  `player2_id` int(11) NOT NULL,
  `score1` int(11) DEFAULT 0,
  `score2` int(11) DEFAULT 0,
  `winner_id` int(11) DEFAULT NULL,
  `mk_result_type` varchar(10) DEFAULT NULL,
  `mk_time_seconds` int(11) DEFAULT 0,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `preliminary_matches`
--

INSERT INTO `preliminary_matches` (`id`, `category`, `match_number`, `round_number`, `player1_id`, `player2_id`, `score1`, `score2`, `winner_id`, `mk_result_type`, `mk_time_seconds`, `status`, `created_at`, `updated_at`) VALUES
(176, 'mk1', 1, 1, 380, 399, 2, 0, 380, '2-0', 82, 'completed', '2026-10-19 18:11:24', '2026-10-19 18:12:04'),
(177, 'mk1', 2, 1, 388, 397, 2, 1, 388, '2-1', 69, 'completed', '2026-10-19 18:11:24', '2026-10-19 18:12:13'),
(178, 'mk1', 3, 1, 394, 378, 2, 1, 394, '2-1', 100, 'completed', '2026-10-19 18:11:24', '2026-10-19 18:12:19'),
(179, 'mk1', 4, 1, 374, 390, 2, 1, 374, '2-1', 69, 'completed', '2026-10-19 18:11:24', '2026-10-19 18:12:25'),
(180, 'mk1', 5, 1, 395, 398, 2, 0, 395, '2-0', 61, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:12:33'),
(181, 'mk1', 6, 1, 377, 389, 2, 0, 377, '2-0', 62, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:12:42'),
(182, 'mk1', 7, 1, 403, 396, 2, 1, 403, '2-1', 102, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:12:50'),
(183, 'mk1', 8, 1, 386, 400, 2, 1, 386, '2-1', 78, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:12:56'),
(184, 'mk1', 9, 1, 373, 379, 2, 0, 373, '2-0', 57, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:17'),
(185, 'mk1', 10, 1, 384, 381, 2, 1, 384, '2-1', 96, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:27'),
(186, 'mk1', 11, 1, 387, 385, 2, 1, 387, '2-1', 105, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:34'),
(187, 'mk1', 12, 1, 382, 375, 0, 2, 375, '2-0', 57, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:41'),
(188, 'mk1', 13, 1, 393, 402, 2, 1, 393, '2-1', 91, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:47'),
(189, 'mk1', 14, 1, 383, 401, 2, 0, 383, '2-0', 40, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:13:55'),
(190, 'mk1', 15, 1, 376, 392, 2, 1, 376, '2-1', 110, 'completed', '2026-10-19 18:11:25', '2026-10-19 18:14:03'),
(191, 'fc26', 1, 1, 349, 354, 3, 1, 349, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:20:55'),
(192, 'fc26', 2, 1, 348, 346, 0, 2, 346, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:21:03'),
(193, 'fc26', 3, 1, 347, 350, 4, 0, 347, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:21:17'),
(194, 'fc26', 4, 1, 342, 337, 2, 3, 337, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:21:24'),
(195, 'fc26', 5, 1, 339, 351, 2, 0, 339, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:21:29'),
(196, 'fc26', 6, 1, 344, 340, 1, 2, 340, NULL, 0, 'completed', '2026-10-19 18:20:42', '2026-10-19 18:21:40'),
(197, 'fc26', 7, 1, 353, 341, 2, 4, 341, NULL, 0, 'completed', '2026-10-19 18:20:43', '2026-10-19 18:21:51'),
(198, 'fc26', 8, 1, 343, 338, 3, 1, 343, NULL, 0, 'completed', '2026-10-19 18:20:43', '2026-10-19 18:21:58'),
(199, 'fc26', 9, 1, 345, 352, 2, 3, 352, NULL, 0, 'completed', '2026-10-19 18:20:43', '2026-10-19 18:22:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bracket_matches`
--
ALTER TABLE `bracket_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `round_order` (`round_order`);

--
-- Indices de la tabla `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `preliminary_matches`
--
ALTER TABLE `preliminary_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bracket_matches`
--
ALTER TABLE `bracket_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT de la tabla `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=404;

--
-- AUTO_INCREMENT de la tabla `preliminary_matches`
--
ALTER TABLE `preliminary_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
