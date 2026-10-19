<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/api/ajax.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/tournament_helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    switch ($action) {

        // ==============================================================
        // 1. PARTICIPANTES: REGISTRO Y GESTIÓN
        // ==============================================================
        case 'add_participant':
            $name = trim($_POST['name'] ?? '');
            $alias = trim($_POST['alias'] ?? '');
            $category = $_POST['category'] ?? 'fc26';
            $group_name = trim($_POST['group_name'] ?? '');
            if (empty($group_name)) {
                $group_name = 'Fase Preliminar';
            }

            if (empty($name)) {
                jsonResponse(false, 'El nombre del participante es obligatorio.');
            }
            if (!in_array($category, ['fc26', 'mk1'])) {
                jsonResponse(false, 'Categoría no válida.');
            }

            if ($category === 'fc26') {
                $gf = intval($_POST['goals_for'] ?? 0);
                $gc = intval($_POST['goals_against'] ?? 0);
                $dg = $gf - $gc;
                $wins = intval($_POST['wins'] ?? 0);
                $losses = intval($_POST['losses'] ?? 0);
                $pj = intval($_POST['matches_played'] ?? ($wins + $losses));

                $stmt = $pdo->prepare("INSERT INTO participants 
                    (name, alias, category, group_name, goals_for, goals_against, goal_diff, wins, losses, matches_played, score) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $alias, $category, $group_name, $gf, $gc, $dg, $wins, $losses, $pj, $dg]);
            } else {
                $w20 = intval($_POST['mk_wins_2_0'] ?? 0);
                $w21 = intval($_POST['mk_wins_2_1'] ?? 0);
                $pts = ($w20 * 2) + ($w21 * 1);
                $timeSec = intval($_POST['match_time_seconds'] ?? 0);
                $losses = intval($_POST['losses'] ?? 0);
                $pj = intval($_POST['matches_played'] ?? ($w20 + $w21 + $losses));

                $stmt = $pdo->prepare("INSERT INTO participants 
                    (name, alias, category, group_name, mk_wins_2_0, mk_wins_2_1, mk_points, match_time_seconds, wins, losses, matches_played, score) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $alias, $category, $group_name, $w20, $w21, $pts, $timeSec, ($w20 + $w21), $losses, $pj, $pts]);
            }

            $newId = $pdo->lastInsertId();
            jsonResponse(true, 'Participante registrado exitosamente en la fase preliminar.', ['id' => $newId]);
            break;

        case 'delete_participant':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) jsonResponse(false, 'ID de participante no válido.');

            // Obtener categoría del participante
            $stmtCat = $pdo->prepare("SELECT category FROM participants WHERE id = ?");
            $stmtCat->execute([$id]);
            $cat = $stmtCat->fetchColumn();

            // Desvincular de llaves si ya estaba asignado
            $pdo->prepare("UPDATE bracket_matches SET player1_id = NULL WHERE player1_id = ?")->execute([$id]);
            $pdo->prepare("UPDATE bracket_matches SET player2_id = NULL WHERE player2_id = ?")->execute([$id]);
            $pdo->prepare("UPDATE bracket_matches SET winner_id = NULL WHERE winner_id = ?")->execute([$id]);

            // Eliminar partidos preliminares donde participaba
            $pdo->prepare("DELETE FROM preliminary_matches WHERE player1_id = ? OR player2_id = ?")->execute([$id, $id]);

            $stmt = $pdo->prepare("DELETE FROM participants WHERE id = ?");
            $stmt->execute([$id]);

            if ($cat) {
                recalculate_preliminary_stats($pdo, $cat);
            }

            jsonResponse(true, 'Participante eliminado con éxito.');
            break;

        // ==============================================================
        // 2. FASE ALEATORIA: SORTEO AUTOMÁTICO Y PARTIDOS PRELIMINARES
        // ==============================================================
        case 'generate_random_matches':
            $category = $_POST['category'] ?? 'fc26';
            $numRounds = max(1, min(5, intval($_POST['rounds'] ?? 1)));

            $stmt = $pdo->prepare("SELECT id, name FROM participants WHERE category = ? ORDER BY id ASC");
            $stmt->execute([$category]);
            $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($players) < 2) {
                jsonResponse(false, 'Se necesitan al menos 2 participantes para sortear los partidos de la fase aleatoria.');
            }

            // Eliminar partidos preliminares existentes de esta categoría
            $pdo->prepare("DELETE FROM preliminary_matches WHERE category = ?")->execute([$category]);

            // Resetear estadísticas para recalcular desde cero
            recalculate_preliminary_stats($pdo, $category);

            $insertStmt = $pdo->prepare("INSERT INTO preliminary_matches 
                (category, match_number, round_number, player1_id, player2_id, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')");

            $matchNum = 1;
            for ($r = 1; $r <= $numRounds; $r++) {
                $pool = $players;
                shuffle($pool);

                while (count($pool) >= 2) {
                    $p1 = array_shift($pool);
                    $p2 = array_shift($pool);
                    $insertStmt->execute([$category, $matchNum++, $r, $p1['id'], $p2['id']]);
                }

                // Si queda 1 jugador suelto en una ronda con número impar
                if (count($pool) === 1) {
                    $leftover = array_shift($pool);
                    $opponents = array_filter($players, fn($p) => $p['id'] != $leftover['id']);
                    if (!empty($opponents)) {
                        $opp = $opponents[array_rand($opponents)];
                        $insertStmt->execute([$category, $matchNum++, $r, $leftover['id'], $opp['id']]);
                    }
                }
            }

            $totalCreated = $matchNum - 1;
            jsonResponse(true, "¡Se han sorteado $totalCreated enfrentamientos aleatorios para la Fase Preliminar exitosamente!", [
                'total_matches' => $totalCreated,
                'rounds' => $numRounds
            ]);
            break;

        case 'update_preliminary_match':
            $matchId = intval($_POST['match_id'] ?? 0);
            if ($matchId <= 0) jsonResponse(false, 'ID de partido no válido.');

            $stmt = $pdo->prepare("SELECT * FROM preliminary_matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$match) jsonResponse(false, 'Partido no encontrado.');

            $category = $match['category'];

            if ($category === 'fc26') {
                $score1 = intval($_POST['score1'] ?? 0);
                $score2 = intval($_POST['score2'] ?? 0);
                $winnerId = ($score1 > $score2) ? $match['player1_id'] : (($score2 > $score1) ? $match['player2_id'] : NULL);

                $updateStmt = $pdo->prepare("UPDATE preliminary_matches SET 
                    score1 = ?, score2 = ?, winner_id = ?, status = 'completed' 
                    WHERE id = ?");
                $updateStmt->execute([$score1, $score2, $winnerId, $matchId]);
            } else {
                $winnerId = intval($_POST['winner_id'] ?? 0);
                $resultType = in_array($_POST['result_type'] ?? '', ['2-0', '2-1']) ? $_POST['result_type'] : '2-0';
                $timeSec = max(0, intval($_POST['time_seconds'] ?? 0));

                if ($winnerId != $match['player1_id'] && $winnerId != $match['player2_id']) {
                    jsonResponse(false, 'Debes seleccionar al jugador ganador.');
                }

                $score1 = ($winnerId == $match['player1_id']) ? ($resultType === '2-0' ? 2 : 2) : ($resultType === '2-0' ? 0 : 1);
                $score2 = ($winnerId == $match['player2_id']) ? ($resultType === '2-0' ? 2 : 2) : ($resultType === '2-0' ? 0 : 1);

                $updateStmt = $pdo->prepare("UPDATE preliminary_matches SET 
                    score1 = ?, score2 = ?, winner_id = ?, mk_result_type = ?, mk_time_seconds = ?, status = 'completed' 
                    WHERE id = ?");
                $updateStmt->execute([$score1, $score2, $winnerId, $resultType, $timeSec, $matchId]);
            }

            // Recalcular estadísticas y puntos de la fase aleatoria
            recalculate_preliminary_stats($pdo, $category);

            jsonResponse(true, 'Marcador guardado exitosamente. La tabla de clasificación se ha actualizado.');
            break;

        case 'reset_preliminary_match':
            $matchId = intval($_POST['match_id'] ?? 0);
            if ($matchId <= 0) jsonResponse(false, 'ID de partido no válido.');

            $stmt = $pdo->prepare("SELECT category FROM preliminary_matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$match) jsonResponse(false, 'Partido no encontrado.');

            $pdo->prepare("UPDATE preliminary_matches SET 
                score1 = 0, score2 = 0, winner_id = NULL, mk_result_type = NULL, mk_time_seconds = 0, status = 'pending' 
                WHERE id = ?")->execute([$matchId]);

            recalculate_preliminary_stats($pdo, $match['category']);

            jsonResponse(true, 'Partido reabierto a pendiente con éxito.');
            break;

        case 'clear_preliminary_matches':
            $category = $_POST['category'] ?? 'fc26';
            $pdo->prepare("DELETE FROM preliminary_matches WHERE category = ?")->execute([$category]);
            recalculate_preliminary_stats($pdo, $category);
            jsonResponse(true, 'Todos los partidos de la fase aleatoria han sido reiniciados.');
            break;

        // ==============================================================
        // 2. REGISTRO DE PARTIDOS / COMBATES DE FASE PRELIMINAR
        // ==============================================================
        case 'record_group_match':
            $category = $_POST['category'] ?? 'fc26';
            $p1_id = intval($_POST['p1_id'] ?? 0);
            $p2_id = intval($_POST['p2_id'] ?? 0);

            if ($p1_id <= 0 || $p2_id <= 0 || $p1_id === $p2_id) {
                jsonResponse(false, 'Debes seleccionar dos jugadores distintos.');
            }

            if ($category === 'fc26') {
                $goals1 = intval($_POST['goals1'] ?? 0);
                $goals2 = intval($_POST['goals2'] ?? 0);

                // Jugador 1
                $win1 = ($goals1 > $goals2) ? 1 : 0;
                $loss1 = ($goals1 < $goals2) ? 1 : 0;
                $stmt1 = $pdo->prepare("UPDATE participants SET 
                    goals_for = goals_for + ?, 
                    goals_against = goals_against + ?, 
                    goal_diff = (goals_for + ?) - (goals_against + ?), 
                    score = (goals_for + ?) - (goals_against + ?), 
                    wins = wins + ?, 
                    losses = losses + ?, 
                    matches_played = matches_played + 1 
                    WHERE id = ?");
                $stmt1->execute([$goals1, $goals2, $goals1, $goals2, $goals1, $goals2, $win1, $loss1, $p1_id]);

                // Jugador 2
                $win2 = ($goals2 > $goals1) ? 1 : 0;
                $loss2 = ($goals2 < $goals1) ? 1 : 0;
                $stmt2 = $pdo->prepare("UPDATE participants SET 
                    goals_for = goals_for + ?, 
                    goals_against = goals_against + ?, 
                    goal_diff = (goals_for + ?) - (goals_against + ?), 
                    score = (goals_for + ?) - (goals_against + ?), 
                    wins = wins + ?, 
                    losses = losses + ?, 
                    matches_played = matches_played + 1 
                    WHERE id = ?");
                $stmt2->execute([$goals2, $goals1, $goals2, $goals1, $goals2, $goals1, $win2, $loss2, $p2_id]);

                $diff1 = $goals1 - $goals2;
                $diff2 = $goals2 - $goals1;
                jsonResponse(true, "Partido FC26 registrado: $goals1 - $goals2. (Diferencia de goles: " . ($diff1 > 0 ? "+$diff1" : "$diff1") . " / " . ($diff2 > 0 ? "+$diff2" : "$diff2") . ")");
            } else {
                // Mortal Kombat 1
                $winner_id = intval($_POST['winner_id'] ?? 0);
                $result_type = $_POST['result_type'] ?? '2_0'; // '2_0' = 2 pts, '2_1' = 1 pt
                $time_seconds = intval($_POST['time_seconds'] ?? 0);

                if ($winner_id != $p1_id && $winner_id != $p2_id) {
                    jsonResponse(false, 'Debes indicar quién fue el ganador del combate.');
                }

                $loser_id = ($winner_id == $p1_id) ? $p2_id : $p1_id;
                $is2_0 = ($result_type === '2_0') ? 1 : 0;
                $is2_1 = ($result_type === '2_1') ? 1 : 0;
                $ptsAdded = $is2_0 ? 2 : 1;

                // Ganador
                $stmtW = $pdo->prepare("UPDATE participants SET 
                    mk_wins_2_0 = mk_wins_2_0 + ?, 
                    mk_wins_2_1 = mk_wins_2_1 + ?, 
                    mk_points = mk_points + ?, 
                    score = score + ?, 
                    match_time_seconds = match_time_seconds + ?, 
                    wins = wins + 1, 
                    matches_played = matches_played + 1 
                    WHERE id = ?");
                $stmtW->execute([$is2_0, $is2_1, $ptsAdded, $ptsAdded, $time_seconds, $winner_id]);

                // Perdedor
                $stmtL = $pdo->prepare("UPDATE participants SET 
                    losses = losses + 1, 
                    matches_played = matches_played + 1 
                    WHERE id = ?");
                $stmtL->execute([$loser_id]);

                $detail = $is2_0 ? "2-0 consecutivos (+2 Pts)" : "2-1 reñido (+1 Pt)";
                jsonResponse(true, "Kombate registrado exitosamente: Victoria $detail en {$time_seconds}s.");
            }
            break;

        // Ajuste rápido de goles/puntos en la tabla
        case 'adjust_score':
            $id = intval($_POST['id'] ?? 0);
            $delta = intval($_POST['delta'] ?? 0);
            $category = $_POST['category'] ?? 'fc26';
            if ($id <= 0) jsonResponse(false, 'ID no válido.');

            if ($category === 'fc26') {
                $stmt = $pdo->prepare("UPDATE participants SET 
                    goals_for = GREATEST(0, goals_for + ?),
                    goal_diff = (goals_for) - goals_against,
                    score = (goals_for) - goals_against
                    WHERE id = ?");
                $stmt->execute([$delta, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE participants SET 
                    mk_points = GREATEST(0, mk_points + ?),
                    score = GREATEST(0, score + ?)
                    WHERE id = ?");
                $stmt->execute([$delta, $delta, $id]);
            }

            $stmt2 = $pdo->prepare("SELECT score, goal_diff, goals_for, mk_points FROM participants WHERE id = ?");
            $stmt2->execute([$id]);
            $row = $stmt2->fetch();

            jsonResponse(true, 'Puntuación modificada.', ['score' => $row['score'] ?? 0, 'data' => $row]);
            break;

        // ==============================================================
        // 3. CARGA DE DEMO COMPLETO CON CASOS EXACTOS DEL USUARIO
        // ==============================================================
        case 'seed_demo':
            $category = $_POST['category'] ?? 'both';

            // DEMO FC26: 18 participantes para 9 partidos preliminares
            // 9 Ganadores y 9 Perdedores
            $fcDemo = [
                ['name' => 'Carlos Mendoza',  'alias' => 'ElMatador_99'],
                ['name' => 'Manuel Rangel',   'alias' => 'ManuPlay'],
                ['name' => 'Alejandro Ramos', 'alias' => 'AlexSniper'],
                ['name' => 'Brayan Gómez',    'alias' => 'BG_Striker'],
                ['name' => 'Diego Fernández', 'alias' => 'D10S_FC'],
                ['name' => 'Daniel Fuentes',  'alias' => 'DaniRocket'],
                ['name' => 'Gabriel Torres',  'alias' => 'GaboTitan'],
                ['name' => 'Kevin Paredes',   'alias' => 'KevWall'],
                ['name' => 'Mateo Silva',     'alias' => 'TikiTaka_Pro'],
                ['name' => 'Tomás Navarro',   'alias' => 'TommyGol'],
                ['name' => 'Lucas Herrera',   'alias' => 'LuquitasFC'],
                ['name' => 'Andrés Castillo', 'alias' => 'Castillo_Wall'],
                ['name' => 'Rodrigo Castro',  'alias' => 'RodriCR7'],
                ['name' => 'Nicolás Rivas',   'alias' => 'NicoSpeed'],
                // Caso exacto: 6 a 4 (+2 DG, 6 GF) vs 5 a 3 (+2 DG, 5 GF)
                ['name' => 'Sebastián Mora',  'alias' => 'SebaGol_6a4'],
                ['name' => 'Emilio Vega',     'alias' => 'EmiTackle'],
                ['name' => 'Javier Morales',  'alias' => 'JaviPress_5a3'],
                ['name' => 'Samuel Ortiz',    'alias' => 'SamuDribble'],
            ];

            // DEMO MK1: 18 participantes para 9 combates preliminares
            // 9 Ganadores (por racha y menor tiempo) y 9 Perdedores (por mayor tiempo en ser derrotados)
            $mkDemo = [
                ['name' => 'Héctor Martínez', 'alias' => 'ScorpionKing'],
                ['name' => 'Luis Cordero',    'alias' => 'ReikoWarrior'],
                ['name' => 'Víctor Salazar',  'alias' => 'SubZero_Freeze'],
                ['name' => 'Pedro Santana',   'alias' => 'NitaraBlood'],
                ['name' => 'Raúl Benítez',    'alias' => 'SmokeKombat'],
                ['name' => 'Oscar León',      'alias' => 'HavocChaos'],
                ['name' => 'Fernando Gómez',  'alias' => 'RaidenThunder'],
                ['name' => 'Manuel Gil',      'alias' => 'RainWaterGod'],
                ['name' => 'Marcos Suárez',   'alias' => 'BarakaFangs'],
                ['name' => 'Cristóbal Paz',   'alias' => 'ShangTsungSoul'],
                ['name' => 'Adrián Peña',     'alias' => 'ReptileAcid'],
                ['name' => 'Brayan Rojas',    'alias' => 'MileenaChomp'],
                ['name' => 'Ignacio Ruiz',    'alias' => 'FireGodLiu'],
                ['name' => 'David Romero',    'alias' => 'GeneralShao'],
                ['name' => 'Camilo López',    'alias' => 'JohnnyCage_Star'],
                ['name' => 'Esteban Cruz',    'alias' => 'KenshiBlade'],
                ['name' => 'Felipe Navarro',  'alias' => 'KungLaoSpin'],
                ['name' => 'Ángel Medina',    'alias' => 'GerasTime'],
            ];

            if ($category === 'fc26' || $category === 'both') {
                $pdo->exec("DELETE FROM participants WHERE category = 'fc26'");
                $pdo->exec("DELETE FROM bracket_matches WHERE category = 'fc26'");
                $pdo->exec("DELETE FROM preliminary_matches WHERE category = 'fc26'");

                $stmt = $pdo->prepare("INSERT INTO participants (name, alias, category, group_name) VALUES (?, ?, 'fc26', 'Fase Preliminar')");
                $pIds = [];
                foreach ($fcDemo as $p) {
                    $stmt->execute([$p['name'], $p['alias']]);
                    $pIds[] = $pdo->lastInsertId();
                }

                // Generar los 9 partidos preliminares con marcadores exactos
                // Resultados:
                // Partido 1: Carlos Mendoza (5) vs Manuel Rangel (1) -> Carlos +4 DG
                // Partido 2: Alejandro Ramos (4) vs Brayan Gómez (0) -> Alejandro +4 DG
                // Partido 3: Diego Fernández (4) vs Daniel Fuentes (1) -> Diego +3 DG
                // Partido 4: Gabriel Torres (3) vs Kevin Paredes (0) -> Gabriel +3 DG
                // Partido 5: Mateo Silva (4) vs Tomás Navarro (2) -> Mateo +2 DG (4 GF)
                // Partido 6: Lucas Herrera (3) vs Andrés Castillo (1) -> Lucas +2 DG (3 GF)
                // Partido 7: Rodrigo Castro (2) vs Nicolás Rivas (0) -> Rodrigo +2 DG (2 GF)
                // Partido 8: Sebastián Mora (6) vs Emilio Vega (4) -> Sebastián +2 DG (6 GF!)
                // Partido 9: Javier Morales (5) vs Samuel Ortiz (3) -> Javier +2 DG (5 GF!)
                $insPre = $pdo->prepare("INSERT INTO preliminary_matches 
                    (category, match_number, round_number, player1_id, player2_id, score1, score2, winner_id, status) 
                    VALUES (?, ?, 1, ?, ?, ?, ?, ?, 'completed')");

                $fcScores = [
                    [5, 1], [4, 0], [4, 1], [3, 0],
                    [4, 2], [3, 1], [2, 0], [6, 4], [5, 3]
                ];

                for ($i = 0; $i < 9; $i++) {
                    $p1 = $pIds[$i * 2];
                    $p2 = $pIds[$i * 2 + 1];
                    $s1 = $fcScores[$i][0];
                    $s2 = $fcScores[$i][1];
                    $insPre->execute(['fc26', $i + 1, $p1, $p2, $s1, $s2, $p1]);
                }

                recalculate_preliminary_stats($pdo, 'fc26');
            }

            if ($category === 'mk1' || $category === 'both') {
                $pdo->exec("DELETE FROM participants WHERE category = 'mk1'");
                $pdo->exec("DELETE FROM bracket_matches WHERE category = 'mk1'");
                $pdo->exec("DELETE FROM preliminary_matches WHERE category = 'mk1'");

                $stmt = $pdo->prepare("INSERT INTO participants (name, alias, category, group_name) VALUES (?, ?, 'mk1', 'Fase Preliminar')");
                $pIds = [];
                foreach ($mkDemo as $p) {
                    $stmt->execute([$p['name'], $p['alias']]);
                    $pIds[] = $pdo->lastInsertId();
                }

                // Generar los 9 combates preliminares con marcadores exactos:
                // 9 Ganadores:
                // #1: Héctor Martínez (2-0, 80s)
                // #2: Víctor Salazar (2-0, 95s)
                // #3: Raúl Benítez (2-0, 110s)
                // #4: Fernando Gómez (2-0, 130s)
                // #5: Marcos Suárez (2-1, 85s)
                // #6: Adrián Peña (2-1, 100s)
                // #7: Ignacio Ruiz (2-1, 115s)
                // #8: Camilo López (2-1, 125s)
                // #9: Felipe Navarro (2-1, 140s) -> 9º GANADOR
                // 9 Perdedores (ordenados por mayor tiempo en ser derrotados):
                // Perdedor #1: Ángel Medina (1-2, 140s) -> MEJOR PERDEDOR (Mayor tiempo)
                // Perdedor #2: Esteban Cruz (1-2, 125s)
                // Perdedor #3: David Romero (1-2, 115s)
                // Perdedor #4: Brayan Rojas (1-2, 100s)
                // Perdedor #5: Cristóbal Paz (1-2, 85s)
                // Perdedor #6: Manuel Gil (0-2, 130s)
                // Perdedor #7: Oscar León (0-2, 110s)
                // Perdedor #8: Pedro Santana (0-2, 95s)
                // Perdedor #9: Luis Cordero (0-2, 80s)
                $insPre = $pdo->prepare("INSERT INTO preliminary_matches 
                    (category, match_number, round_number, player1_id, player2_id, score1, score2, winner_id, mk_result_type, mk_time_seconds, status) 
                    VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, 'completed')");

                $mkResults = [
                    ['2-0', 80],
                    ['2-0', 95],
                    ['2-0', 110],
                    ['2-0', 130],
                    ['2-1', 85],
                    ['2-1', 100],
                    ['2-1', 115],
                    ['2-1', 125],
                    ['2-1', 140],
                ];

                for ($i = 0; $i < 9; $i++) {
                    $p1 = $pIds[$i * 2];
                    $p2 = $pIds[$i * 2 + 1];
                    $rType = $mkResults[$i][0];
                    $tSec = $mkResults[$i][1];
                    $s1 = 2;
                    $s2 = ($rType === '2-1') ? 1 : 0;
                    $insPre->execute(['mk1', $i + 1, $p1, $p2, $s1, $s2, $p1, $rType, $tSec]);
                }

                recalculate_preliminary_stats($pdo, 'mk1');
            }

            jsonResponse(true, 'Participantes de prueba (18 competidores, 9 partidos) cargados con éxito.');
            break;

        case 'clear_participants':
            $category = $_POST['category'] ?? '';
            if (in_array($category, ['fc26', 'mk1'])) {
                $pdo->prepare("DELETE FROM participants WHERE category = ?")->execute([$category]);
                $pdo->prepare("DELETE FROM bracket_matches WHERE category = ?")->execute([$category]);
                $pdo->prepare("DELETE FROM preliminary_matches WHERE category = ?")->execute([$category]);
            } else {
                $pdo->exec("TRUNCATE TABLE participants");
                $pdo->exec("TRUNCATE TABLE bracket_matches");
                $pdo->exec("TRUNCATE TABLE preliminary_matches");
            }
            jsonResponse(true, 'Participantes, fase aleatoria y llaves eliminados con éxito.');
            break;

        // ==============================================================
        // 4. GENERACIÓN DE LLAVES: GANADORES, REPESCA Y "MEJOR CONTRA PEOR"
        // ==============================================================
        case 'generate_bracket':
            $category = $_POST['category'] ?? 'fc26';
            $sizeReq = $_POST['size'] ?? 'auto';
            $selectedWildcards = $_POST['wildcard_ids'] ?? [];
            if (!is_array($selectedWildcards)) {
                $selectedWildcards = !empty($selectedWildcards) ? explode(',', $selectedWildcards) : [];
            }
            $selectedWildcards = array_map('intval', array_filter($selectedWildcards));

            if (!in_array($category, ['fc26', 'mk1'])) {
                jsonResponse(false, 'Categoría no válida.');
            }

            // Obtener clasificación de la fase preliminar dividida en Ganadores y Perdedores
            $standings = get_preliminary_standings($pdo, $category);
            $winners = $standings['winners'];
            $losers = $standings['losers'];
            $unplayed = $standings['unplayed'];

            $winnerCount = count($winners);
            $loserCount = count($losers);
            $totalCount = $winnerCount + $loserCount + count($unplayed);

            if ($totalCount < 2) {
                jsonResponse(false, 'Se necesitan al menos 2 participantes para generar eliminatorias.');
            }

            // Determinar tamaño de la llave:
            if ($sizeReq === '16') {
                $bracketSize = 16;
            } elseif ($sizeReq === '8') {
                $bracketSize = 8;
            } elseif ($sizeReq === '4') {
                $bracketSize = 4;
            } elseif ($sizeReq === '2') {
                $bracketSize = 2;
            } else {
                // Auto:
                // Si hay >= 16 ganadores -> 16
                // Si hay entre 12 y 15 ganadores y total >= 16 -> 16 (con cupos repesca)
                // Si hay entre 8 y 11 ganadores -> 8 (top 8 ganadores, los demás quedan fuera como el 9º en 18 personas)
                // Si hay entre 6 y 7 ganadores y total >= 8 -> 8 (con repesca)
                // Si hay entre 4 y 5 ganadores -> 4
                // Else: 2
                if ($winnerCount >= 12 && $totalCount >= 16) {
                    $bracketSize = 16;
                } elseif ($winnerCount >= 8) {
                    $bracketSize = 8;
                } elseif ($winnerCount >= 6 && $totalCount >= 8) {
                    $bracketSize = 8;
                } elseif ($winnerCount >= 4) {
                    $bracketSize = 4;
                } else {
                    $bracketSize = min(4, $totalCount >= 4 ? 4 : 2);
                }
            }

            // Seleccionar los $bracketSize clasificados:
            $qualified = [];
            $leftOutWinners = [];
            $wildcardsUsed = [];

            if ($winnerCount >= $bracketSize) {
                // Entran los mejores $bracketSize ganadores
                // Los ganadores restantes (ej: el 9º en 18 personas) QUEDAN FUERA
                for ($i = 0; $i < $bracketSize; $i++) {
                    $w = $winners[$i];
                    $w['seed'] = $i + 1;
                    $w['seed_type'] = 'Ganador';
                    $w['seed_label'] = 'Ganador #' . ($i + 1);
                    $qualified[] = $w;
                }
                for ($i = $bracketSize; $i < $winnerCount; $i++) {
                    $leftOutWinners[] = $winners[$i];
                }
            } else {
                // Faltan cupos para completar la llave (ej: 15 ganadores para llave de 16, falta 1 cupo)
                // Todos los ganadores entran primero
                foreach ($winners as $idx => $w) {
                    $w['seed'] = $idx + 1;
                    $w['seed_type'] = 'Ganador';
                    $w['seed_label'] = 'Ganador #' . ($idx + 1);
                    $qualified[] = $w;
                }

                $missing = $bracketSize - count($qualified);
                $usedLoserIds = [];

                // 1. Si el usuario seleccionó perdedores específicos para los cupos de repesca:
                if (!empty($selectedWildcards)) {
                    foreach ($selectedWildcards as $wId) {
                        if ($missing <= 0) break;
                        foreach ($losers as $l) {
                            if ($l['id'] == $wId && !in_array($l['id'], $usedLoserIds)) {
                                $l['seed'] = count($qualified) + 1;
                                $l['seed_type'] = 'Repesca';
                                $l['seed_label'] = 'Repesca (Elegido)';
                                $qualified[] = $l;
                                $wildcardsUsed[] = $l;
                                $usedLoserIds[] = $l['id'];
                                $missing--;
                                break;
                            }
                        }
                    }
                }

                // 2. Si aún faltan cupos, se asignan automáticamente a los MEJORES PERDEDORES:
                // En MK1: Mayor tiempo en ser derrotados
                // En FC26: Menor diferencia negativa y más goles
                if ($missing > 0) {
                    foreach ($losers as $l) {
                        if ($missing <= 0) break;
                        if (in_array($l['id'], $usedLoserIds)) continue;

                        $l['seed'] = count($qualified) + 1;
                        $l['seed_type'] = 'Repesca';
                        $reason = ($category === 'mk1') 
                            ? "Repesca ({$l['seconds']}s)" 
                            : "Repesca (DG {$l['dg']})";
                        $l['seed_label'] = $reason;
                        $qualified[] = $l;
                        $wildcardsUsed[] = $l;
                        $usedLoserIds[] = $l['id'];
                        $missing--;
                    }
                }
            }

            // Si aún después de perdedores no alcanza (caso con muy pocos jugadores):
            if (count($qualified) < $bracketSize) {
                foreach ($unplayed as $u) {
                    if (count($qualified) >= $bracketSize) break;
                    $u['seed'] = count($qualified) + 1;
                    $u['seed_type'] = 'Participante';
                    $u['seed_label'] = 'Cupo #' . (count($qualified) + 1);
                    $qualified[] = $u;
                }
            }

            if (count($qualified) < $bracketSize) {
                if (count($qualified) >= 8) $bracketSize = 8;
                elseif (count($qualified) >= 4) $bracketSize = 4;
                else $bracketSize = 2;
                $qualified = array_slice($qualified, 0, $bracketSize);
            }

            // Actualizar seeds y etiquetas en base de datos
            $stmtReset = $pdo->prepare("UPDATE participants SET assigned_group = 'none', group_seed = NULL WHERE category = ?");
            $stmtReset->execute([$category]);

            $stmtUpdateP = $pdo->prepare("UPDATE participants SET assigned_group = ?, group_seed = ? WHERE id = ?");
            foreach ($qualified as $idx => $q) {
                $seedNum = $idx + 1;
                $stmtUpdateP->execute([$q['seed_label'], $seedNum, $q['id']]);
            }

            // Limpiar llaves anteriores de esta categoría
            $stmtDel = $pdo->prepare("DELETE FROM bracket_matches WHERE category = ?");
            $stmtDel->execute([$category]);

            // Parejas de cuadro: 1 vs Último, 2 vs Penúltimo, etc. (definido en tournament_helpers.php)
            $seedPairs = getGroupSeedingPairs($bracketSize);

            // Estructura de rondas
            $roundsConfig = [];
            if ($bracketSize == 16) {
                $roundsConfig = [
                    1 => ['name' => 'Octavos de Final', 'matches' => 8],
                    2 => ['name' => 'Cuartos de Final', 'matches' => 4],
                    3 => ['name' => 'Semifinales',       'matches' => 2],
                    4 => ['name' => 'Gran Final',         'matches' => 1]
                ];
            } elseif ($bracketSize == 8) {
                $roundsConfig = [
                    1 => ['name' => 'Cuartos de Final', 'matches' => 4],
                    2 => ['name' => 'Semifinales',       'matches' => 2],
                    3 => ['name' => 'Gran Final',         'matches' => 1]
                ];
            } elseif ($bracketSize == 4) {
                $roundsConfig = [
                    1 => ['name' => 'Semifinales', 'matches' => 2],
                    2 => ['name' => 'Gran Final',   'matches' => 1]
                ];
            } else {
                $roundsConfig = [
                    1 => ['name' => 'Gran Final', 'matches' => 1]
                ];
            }

            // Insertar partidos
            $insertedMatches = [];
            $stmtInsert = $pdo->prepare("INSERT INTO bracket_matches 
                (category, round_name, round_order, match_number, player1_id, player2_id, player1_seed, player2_seed, player1_group, player2_group, player1_group_seed, player2_group_seed, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($roundsConfig as $roundOrder => $rInfo) {
                $insertedMatches[$roundOrder] = [];
                for ($m = 1; $m <= $rInfo['matches']; $m++) {
                    $p1_id = null;
                    $p2_id = null;
                    $s1 = null;
                    $s2 = null;
                    $g1 = null;
                    $g2 = null;
                    $gs1 = null;
                    $gs2 = null;

                    if ($roundOrder === 1) {
                        $pair = $seedPairs[$m - 1];
                        $s1 = $pair['p1_seed'];
                        $s2 = $pair['p2_seed'];
                        $p1 = $qualified[$s1 - 1] ?? null;
                        $p2 = $qualified[$s2 - 1] ?? null;

                        $p1_id = $p1['id'] ?? null;
                        $p2_id = $p2['id'] ?? null;
                        $g1 = $p1['seed_label'] ?? "Seed #$s1";
                        $g2 = $p2['seed_label'] ?? "Seed #$s2";
                        $gs1 = $s1;
                        $gs2 = $s2;
                    }

                    $stmtInsert->execute([
                        $category,
                        $rInfo['name'],
                        $roundOrder,
                        $m,
                        $p1_id,
                        $p2_id,
                        $s1,
                        $s2,
                        $g1,
                        $g2,
                        $gs1,
                        $gs2,
                        'pending'
                    ]);
                    $insertedMatches[$roundOrder][$m] = $pdo->lastInsertId();
                }
            }

            // Vincular next_match_id y next_match_slot
            $stmtUpdateNext = $pdo->prepare("UPDATE bracket_matches SET next_match_id = ?, next_match_slot = ? WHERE id = ?");
            $totalRounds = count($roundsConfig);

            for ($r = 1; $r < $totalRounds; $r++) {
                $currentMatches = $roundsConfig[$r]['matches'];
                for ($m = 1; $m <= $currentMatches; $m++) {
                    $currentMatchId = $insertedMatches[$r][$m];
                    $nextMatchIndex = (int)ceil($m / 2);
                    $nextSlot = ($m % 2 !== 0) ? 1 : 2;
                    $nextMatchId = $insertedMatches[$r + 1][$nextMatchIndex] ?? null;

                    if ($nextMatchId) {
                        $stmtUpdateNext->execute([$nextMatchId, $nextSlot, $currentMatchId]);
                    }
                }
            }

            $msg = "¡Cuadro de Eliminatorias (Top $bracketSize) generado exitosamente!";
            if (!empty($wildcardsUsed)) {
                $wcNames = array_map(fn($w) => $w['name'], $wildcardsUsed);
                $msg .= " Se incluyeron $winnerCount ganadores y " . count($wildcardsUsed) . " cupo(s) de repesca otorgado(s) a los mejores perdedores (" . implode(', ', $wcNames) . ").";
            } elseif (!empty($leftOutWinners)) {
                $loNames = array_map(fn($w) => $w['name'], $leftOutWinners);
                $msg .= " Clasificaron los mejores $bracketSize ganadores. El/los ganador(es) restante(s) (" . implode(', ', $loNames) . ") quedaron fuera por variables de desempate.";
            }

            jsonResponse(true, $msg, [
                'bracket_size' => $bracketSize,
                'total_rounds' => $totalRounds,
                'winners_count' => count($qualified),
                'wildcards_count' => count($wildcardsUsed),
                'left_out_count' => count($leftOutWinners)
            ]);
            break;

        // ==============================================================
        // 5. ACTUALIZACIÓN MANUAL DE RESULTADOS DE ELIMINATORIAS Y AVANCE
        // ==============================================================
        case 'update_match':
            $match_id = intval($_POST['match_id'] ?? 0);
            $score1 = intval($_POST['score1'] ?? 0);
            $score2 = intval($_POST['score2'] ?? 0);
            $winner_id = intval($_POST['winner_id'] ?? 0);

            if ($match_id <= 0) jsonResponse(false, 'ID de partido no válido.');

            $stmt = $pdo->prepare("SELECT * FROM bracket_matches WHERE id = ?");
            $stmt->execute([$match_id]);
            $match = $stmt->fetch();

            if (!$match) jsonResponse(false, 'Partido no encontrado.');

            if (!$match['player1_id'] || !$match['player2_id']) {
                jsonResponse(false, 'Ambos jugadores deben estar definidos antes de registrar el resultado.');
            }

            if ($winner_id <= 0) {
                if ($score1 > $score2) {
                    $winner_id = $match['player1_id'];
                } elseif ($score2 > $score1) {
                    $winner_id = $match['player2_id'];
                } else {
                    jsonResponse(false, 'Hay un empate en el marcador. Selecciona explícitamente quién avanza en el desempate.');
                }
            }

            if ($winner_id != $match['player1_id'] && $winner_id != $match['player2_id']) {
                jsonResponse(false, 'El ganador debe ser uno de los dos competidores del partido.');
            }

            $mk_result_type = !empty($_POST['mk_result_type']) ? trim($_POST['mk_result_type']) : null;
            $time_seconds = intval($_POST['time_seconds'] ?? 0);
            $penalties1 = isset($_POST['penalties_score1']) && $_POST['penalties_score1'] !== '' ? intval($_POST['penalties_score1']) : null;
            $penalties2 = isset($_POST['penalties_score2']) && $_POST['penalties_score2'] !== '' ? intval($_POST['penalties_score2']) : null;

            $stmtUp = $pdo->prepare("UPDATE bracket_matches SET 
                score1 = ?, 
                score2 = ?, 
                winner_id = ?, 
                mk_result_type = ?, 
                mk_time_seconds = ?, 
                penalties_score1 = ?, 
                penalties_score2 = ?, 
                status = 'completed' 
                WHERE id = ?");
            $stmtUp->execute([$score1, $score2, $winner_id, $mk_result_type, $time_seconds, $penalties1, $penalties2, $match_id]);

            // Avanzar automáticamente el ganador
            $finalistsArrivedNow = false;
            $nextRoundName = '';
            if (!empty($match['next_match_id']) && !empty($match['next_match_slot'])) {
                $nextId = $match['next_match_id'];
                $slot = $match['next_match_slot'];

                if ($slot == 1) {
                    $stmtNext = $pdo->prepare("UPDATE bracket_matches SET player1_id = ? WHERE id = ?");
                } else {
                    $stmtNext = $pdo->prepare("UPDATE bracket_matches SET player2_id = ? WHERE id = ?");
                }
                $stmtNext->execute([$winner_id, $nextId]);

                // Comprobar la siguiente ronda
                $stmtNextMatch = $pdo->prepare("SELECT next_match_id, player1_id, player2_id, round_name FROM bracket_matches WHERE id = ?");
                $stmtNextMatch->execute([$nextId]);
                $targetMatch = $stmtNextMatch->fetch();
                if ($targetMatch) {
                    $nextRoundName = $targetMatch['round_name'] ?? '';
                    if (empty($targetMatch['next_match_id']) && !empty($targetMatch['player1_id']) && !empty($targetMatch['player2_id'])) {
                        $finalistsArrivedNow = true;
                    }
                }
            }

            // Consultar datos del ganador
            $stmtWinner = $pdo->prepare("SELECT id, name, alias FROM participants WHERE id = ?");
            $stmtWinner->execute([$winner_id]);
            $winnerData = $stmtWinner->fetch(PDO::FETCH_ASSOC);
            $winnerName = $winnerData ? $winnerData['name'] : 'El competidor';

            $category = $match['category'] ?? 'fc26';
            $isFinal = empty($match['next_match_id']);
            $championData = null;
            $msg = 'Marcador registrado y ganador avanzado exitosamente.';

            // Construir locución del narrador oficial
            $narrationText = '';
            if ($isFinal) {
                if ($winner_id > 0) {
                    $stmtChamp = $pdo->prepare("SELECT id, name, alias, category FROM participants WHERE id = ?");
                    $stmtChamp->execute([$winner_id]);
                    $championData = $stmtChamp->fetch(PDO::FETCH_ASSOC);

                    if ($category === 'mk1') {
                        $narrationText = "¡Flawless Victory! ¡{$winnerName} se corona como el Campeón Supremo de Mortal Kombat 1!";
                        $msg = "¡FLAWLESS VICTORY! ¡{$winnerName} ES EL CAMPEÓN DE MORTAL KOMBAT 1! 🐉";
                    } else {
                        $narrationText = "¡Gloria Eterna! ¡{$winnerName} se corona como el nuevo Campeón de la UEFA Champions League!";
                        $msg = "¡GLORIA ETERNA! ¡{$winnerName} ES EL CAMPEÓN DE LA UEFA CHAMPIONS LEAGUE! ⚽";
                    }
                }
            } else {
                $lowerNext = mb_strtolower($nextRoundName, 'UTF-8');
                $lowerCurrent = mb_strtolower($match['round_name'] ?? '', 'UTF-8');
                
                if (str_contains($lowerNext, 'cuarto') || str_contains($lowerCurrent, 'octavo')) {
                    $narrationText = "¡{$winnerName} avanza a cuartos de final!";
                } elseif (str_contains($lowerNext, 'semifinal') || str_contains($lowerCurrent, 'cuarto')) {
                    $narrationText = "¡{$winnerName} avanza a semifinales!";
                } elseif (str_contains($lowerNext, 'final') || str_contains($lowerCurrent, 'semifinal')) {
                    $narrationText = "¡{$winnerName} avanza a la gran final!";
                } else {
                    $narrationText = !empty($nextRoundName) 
                        ? "¡{$winnerName} avanza a {$nextRoundName}!" 
                        : "¡{$winnerName} avanza a la siguiente ronda!";
                }
            }

            jsonResponse(true, $msg, [
                'match_id' => $match_id,
                'winner_id' => $winner_id,
                'winner_name' => $winnerName,
                'is_final' => $isFinal,
                'finalists_arrived' => $finalistsArrivedNow,
                'champion' => $championData,
                'score1' => $score1,
                'score2' => $score2,
                'category' => $category,
                'next_round_name' => $nextRoundName,
                'narration_text' => $narrationText
            ]);
            break;

        case 'reset_match':
            $match_id = intval($_POST['match_id'] ?? 0);
            if ($match_id <= 0) jsonResponse(false, 'ID no válido.');

            $stmt = $pdo->prepare("SELECT * FROM bracket_matches WHERE id = ?");
            $stmt->execute([$match_id]);
            $match = $stmt->fetch();
            if (!$match) jsonResponse(false, 'Partido no encontrado.');

            if (!empty($match['next_match_id']) && !empty($match['next_match_slot'])) {
                if ($match['next_match_slot'] == 1) {
                    $pdo->prepare("UPDATE bracket_matches SET player1_id = NULL WHERE id = ?")->execute([$match['next_match_id']]);
                } else {
                    $pdo->prepare("UPDATE bracket_matches SET player2_id = NULL WHERE id = ?")->execute([$match['next_match_id']]);
                }
            }

            $stmtReset = $pdo->prepare("UPDATE bracket_matches SET score1 = 0, score2 = 0, winner_id = NULL, status = 'pending' WHERE id = ?");
            $stmtReset->execute([$match_id]);

            jsonResponse(true, 'Partido reabierto con éxito.');
            break;

        default:
            jsonResponse(false, 'Acción no reconocida.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error en el servidor: ' . $e->getMessage());
}
