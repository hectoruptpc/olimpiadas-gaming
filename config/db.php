<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/config/db.php

$host = '127.0.0.1';
$user = 'root';
$pass = '123456';
$dbname = 'olimpiadas_gaming';

try {
    // Primero conectamos al servidor para asegurar que la base de datos exista
    $pdoServer = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Conectamos a la base de datos específica
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Crear tabla de participantes con métricas de goles y kombate
    $pdo->exec("CREATE TABLE IF NOT EXISTS `participants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `alias` VARCHAR(100) NULL,
        `category` ENUM('fc26', 'mk1') NOT NULL,
        `score` INT DEFAULT 0,
        `matches_played` INT DEFAULT 0,
        `group_name` VARCHAR(50) DEFAULT 'Grupo Único',
        `goals_for` INT DEFAULT 0,
        `goals_against` INT DEFAULT 0,
        `goal_diff` INT DEFAULT 0,
        `wins` INT DEFAULT 0,
        `losses` INT DEFAULT 0,
        `mk_wins_2_0` INT DEFAULT 0,
        `mk_wins_2_1` INT DEFAULT 0,
        `mk_points` INT DEFAULT 0,
        `match_time_seconds` INT DEFAULT 0,
        `assigned_group` VARCHAR(20) DEFAULT 'none',
        `group_seed` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Asegurar columnas existentes si la tabla ya existía
    $pdo->exec("ALTER TABLE `participants` 
        ADD COLUMN IF NOT EXISTS `goals_for` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `goals_against` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `goal_diff` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `wins` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `losses` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `mk_wins_2_0` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `mk_wins_2_1` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `mk_points` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `match_time_seconds` INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `assigned_group` VARCHAR(20) DEFAULT 'none',
        ADD COLUMN IF NOT EXISTS `group_seed` INT DEFAULT NULL;");

    // Crear tabla de llaves / eliminatorias (brackets)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bracket_matches` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` ENUM('fc26', 'mk1') NOT NULL,
        `round_name` VARCHAR(50) NOT NULL,
        `round_order` INT NOT NULL,
        `match_number` INT NOT NULL,
        `player1_id` INT NULL,
        `player2_id` INT NULL,
        `player1_seed` INT NULL,
        `player2_seed` INT NULL,
        `player1_group` VARCHAR(20) DEFAULT NULL,
        `player2_group` VARCHAR(20) DEFAULT NULL,
        `player1_group_seed` INT DEFAULT NULL,
        `player2_group_seed` INT DEFAULT NULL,
        `score1` INT DEFAULT 0,
        `score2` INT DEFAULT 0,
        `winner_id` INT NULL,
        `next_match_id` INT NULL,
        `next_match_slot` INT NULL,
        `status` ENUM('pending', 'completed') DEFAULT 'pending',
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`category`),
        INDEX (`round_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("ALTER TABLE `bracket_matches`
        ADD COLUMN IF NOT EXISTS `player1_group` VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `player2_group` VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `player1_group_seed` INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `player2_group_seed` INT DEFAULT NULL;");

    // Crear tabla de partidos de fase preliminar / aleatoria
    $pdo->exec("CREATE TABLE IF NOT EXISTS `preliminary_matches` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` ENUM('fc26', 'mk1') NOT NULL,
        `match_number` INT NOT NULL,
        `round_number` INT DEFAULT 1,
        `player1_id` INT NOT NULL,
        `player2_id` INT NOT NULL,
        `score1` INT DEFAULT 0,
        `score2` INT DEFAULT 0,
        `winner_id` INT DEFAULT NULL,
        `mk_result_type` VARCHAR(10) DEFAULT NULL,
        `mk_time_seconds` INT DEFAULT 0,
        `status` ENUM('pending', 'completed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`category`),
        INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (PDOException $e) {
    die("Error crítico de base de datos: " . $e->getMessage());
}
