<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/includes/tournament_helpers.php

/**
 * Recalcula de forma atómica y consistente las estadísticas de todos los participantes
 * basándose en los partidos completados de la tabla preliminary_matches
 */
function recalculate_preliminary_stats(PDO $pdo, string $category) {
    $stmt = $pdo->prepare("SELECT id FROM participants WHERE category = ?");
    $stmt->execute([$category]);
    $pids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pids)) return;

    $stats = [];
    foreach ($pids as $pid) {
        $stats[$pid] = [
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'wins' => 0, 'losses' => 0, 'pj' => 0,
            'w20' => 0, 'w21' => 0, 'mk_pts' => 0, 'mk_time' => 0, 'score' => 0
        ];
    }

    $stmtM = $pdo->prepare("SELECT * FROM preliminary_matches WHERE category = ? AND status = 'completed' ORDER BY id ASC");
    $stmtM->execute([$category]);
    $matches = $stmtM->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matches as $m) {
        $p1 = $m['player1_id'];
        $p2 = $m['player2_id'];

        if (!isset($stats[$p1]) || !isset($stats[$p2])) continue;

        if ($category === 'fc26') {
            $g1 = intval($m['score1']);
            $g2 = intval($m['score2']);

            $stats[$p1]['gf'] += $g1;
            $stats[$p1]['gc'] += $g2;
            $stats[$p1]['pj'] += 1;

            $stats[$p2]['gf'] += $g2;
            $stats[$p2]['gc'] += $g1;
            $stats[$p2]['pj'] += 1;

            if ($g1 > $g2) {
                $stats[$p1]['wins'] += 1;
                $stats[$p2]['losses'] += 1;
            } elseif ($g2 > $g1) {
                $stats[$p2]['wins'] += 1;
                $stats[$p1]['losses'] += 1;
            }
        } else {
            // MK1
            $wId = intval($m['winner_id']);
            $lId = ($wId == $p1) ? $p2 : $p1;
            $rType = $m['mk_result_type'] ?? '2-0';
            $timeSec = intval($m['mk_time_seconds']);

            $stats[$p1]['pj'] += 1;
            $stats[$p2]['pj'] += 1;

            if ($wId == $p1 || $wId == $p2) {
                $stats[$wId]['wins'] += 1;
                $stats[$lId]['losses'] += 1;
                $stats[$wId]['mk_time'] += $timeSec;
                $stats[$lId]['mk_time'] += $timeSec; // Tiempo de combate para el derrotado

                if ($rType === '2-0' || $rType === '2_0') {
                    $stats[$wId]['w20'] += 1;
                } else {
                    $stats[$wId]['w21'] += 1;
                }
            }
        }
    }

    $updateStmt = $pdo->prepare("UPDATE participants SET 
        goals_for = ?, goals_against = ?, goal_diff = ?, 
        wins = ?, losses = ?, matches_played = ?,
        mk_wins_2_0 = ?, mk_wins_2_1 = ?, mk_points = ?, match_time_seconds = ?,
        score = ?
        WHERE id = ?");

    foreach ($stats as $pid => $st) {
        $st['dg'] = $st['gf'] - $st['gc'];
        $st['mk_pts'] = (2 * $st['w20']) + (1 * $st['w21']);
        $score = ($category === 'fc26') ? $st['dg'] : $st['mk_pts'];

        $updateStmt->execute([
            $st['gf'], $st['gc'], $st['dg'],
            $st['wins'], $st['losses'], $st['pj'],
            $st['w20'], $st['w21'], $st['mk_pts'], $st['mk_time'],
            $score,
            $pid
        ]);
    }
}

/**
 * Retorna la clasificación de la fase preliminar dividida en Ganadores, Perdedores y No Jugados:
 * - MK1 Ganadores: 1º Racha (2-0 > 2-1), 2º Menor tiempo para ganar (Segundos ASC)
 * - MK1 Perdedores: 1º Mayor tiempo en ser derrotados (Segundos DESC), 2º Racha/rounds (1-2 > 0-2)
 * - FC26 Ganadores: 1º Diferencia de Goles (DG), 2º Goles a Favor (GF)
 * - FC26 Perdedores: 1º Menor desventaja en DG (-1 > -2), 2º Goles a Favor (GF)
 */
function get_preliminary_standings(PDO $pdo, string $category): array {
    $stmt = $pdo->prepare("SELECT * FROM participants WHERE category = ?");
    $stmt->execute([$category]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtM = $pdo->prepare("SELECT * FROM preliminary_matches WHERE category = ? ORDER BY round_number ASC, match_number ASC");
    $stmtM->execute([$category]);
    $matches = $stmtM->fetchAll(PDO::FETCH_ASSOC);

    $playerData = [];
    foreach ($all as $p) {
        $playerData[$p['id']] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'alias' => $p['alias'],
            'category' => $p['category'],
            'played' => 0,
            'wins' => 0,
            'losses' => 0,
            'is_winner' => false,
            'is_loser' => false,
            'gf' => 0,
            'gc' => 0,
            'dg' => 0,
            'racha' => 0,
            'racha_label' => '-',
            'seconds' => 0,
            'opponent_name' => '',
            'match_id' => null,
            'rank_in_group' => 0,
            'status_label' => '',
            'status_class' => ''
        ];
    }

    $playerNames = array_column($all, 'name', 'id');

    foreach ($matches as $m) {
        if ($m['status'] !== 'completed') continue;

        $p1 = $m['player1_id'];
        $p2 = $m['player2_id'];
        $wId = $m['winner_id'];
        $lId = ($wId == $p1) ? $p2 : $p1;

        if (isset($playerData[$p1])) $playerData[$p1]['played']++;
        if (isset($playerData[$p2])) $playerData[$p2]['played']++;

        if ($category === 'fc26') {
            $s1 = intval($m['score1']);
            $s2 = intval($m['score2']);

            if (isset($playerData[$p1])) {
                $playerData[$p1]['gf'] += $s1;
                $playerData[$p1]['gc'] += $s2;
                $playerData[$p1]['dg'] = $playerData[$p1]['gf'] - $playerData[$p1]['gc'];
                $playerData[$p1]['opponent_name'] = $playerNames[$p2] ?? '';
                $playerData[$p1]['match_id'] = $m['id'];
                if ($wId == $p1) {
                    $playerData[$p1]['is_winner'] = true;
                    $playerData[$p1]['wins']++;
                } elseif ($wId == $p2) {
                    $playerData[$p1]['is_loser'] = true;
                    $playerData[$p1]['losses']++;
                }
            }
            if (isset($playerData[$p2])) {
                $playerData[$p2]['gf'] += $s2;
                $playerData[$p2]['gc'] += $s1;
                $playerData[$p2]['dg'] = $playerData[$p2]['gf'] - $playerData[$p2]['gc'];
                $playerData[$p2]['opponent_name'] = $playerNames[$p1] ?? '';
                $playerData[$p2]['match_id'] = $m['id'];
                if ($wId == $p2) {
                    $playerData[$p2]['is_winner'] = true;
                    $playerData[$p2]['wins']++;
                } elseif ($wId == $p1) {
                    $playerData[$p2]['is_loser'] = true;
                    $playerData[$p2]['losses']++;
                }
            }
        } else {
            // MK1
            $rType = $m['mk_result_type'] ?? '2-0';
            $sec = intval($m['mk_time_seconds']);

            if ($wId && isset($playerData[$wId])) {
                $playerData[$wId]['is_winner'] = true;
                $playerData[$wId]['wins']++;
                $playerData[$wId]['seconds'] = $sec;
                $playerData[$wId]['opponent_name'] = $playerNames[$lId] ?? '';
                $playerData[$wId]['match_id'] = $m['id'];
                if ($rType === '2-0' || $rType === '2_0') {
                    $playerData[$wId]['racha'] = 2;
                    $playerData[$wId]['mk_pts'] = 2;
                    $playerData[$wId]['racha_label'] = '2-0 (+2 pts)';
                } else {
                    $playerData[$wId]['racha'] = 1;
                    $playerData[$wId]['mk_pts'] = 1;
                    $playerData[$wId]['racha_label'] = '2-1 (+1 pt)';
                }
            }

            if ($lId && isset($playerData[$lId])) {
                $playerData[$lId]['is_loser'] = true;
                $playerData[$lId]['losses']++;
                $playerData[$lId]['seconds'] = $sec;
                $playerData[$lId]['opponent_name'] = $playerNames[$wId] ?? '';
                $playerData[$lId]['match_id'] = $m['id'];
                if ($rType === '2-1' || $rType === '2_1') {
                    $playerData[$lId]['racha'] = 1; // Ganó 1 round en derrota 2-1
                    $playerData[$lId]['rounds_won'] = 1;
                    $playerData[$lId]['mk_pts'] = 0;
                    $playerData[$lId]['racha_label'] = '1-2 (1 round)';
                } else {
                    $playerData[$lId]['racha'] = 0; // Ganó 0 rounds en derrota 2-0
                    $playerData[$lId]['rounds_won'] = 0;
                    $playerData[$lId]['mk_pts'] = 0;
                    $playerData[$lId]['racha_label'] = '0-2 (0 rounds)';
                }
            }
        }
    }

    $winners = [];
    $losers = [];
    $unplayed = [];

    foreach ($playerData as $pd) {
        if ($pd['is_winner']) {
            $winners[] = $pd;
        } elseif ($pd['is_loser']) {
            $losers[] = $pd;
        } else {
            $unplayed[] = $pd;
        }
    }

    if ($category === 'mk1') {
        // Ganadores: 1º Racha pts DESC (2-0 [+2 pts] supera a 2-1 [+1 pt]), 2º Segundos ASC (ganar en menos tiempo)
        usort($winners, function($a, $b) {
            if ($a['racha'] !== $b['racha']) return $b['racha'] <=> $a['racha'];
            if ($a['seconds'] !== $b['seconds']) return $a['seconds'] <=> $b['seconds'];
            return $a['id'] <=> $b['id'];
        });

        // Perdedores: 1º Haber ganado al menos 1 round (2-1 / 1-2 es superior a 2-0 / 0-2 aunque tenga más tiempo)
        //             2º En igualdad de rounds (ambos 2-1 o ambos 2-0), mayor tiempo en ser derrotados (Segundos DESC)
        usort($losers, function($a, $b) {
            if ($a['racha'] !== $b['racha']) return $b['racha'] <=> $a['racha'];
            if ($a['seconds'] !== $b['seconds']) return $b['seconds'] <=> $a['seconds'];
            return $a['id'] <=> $b['id'];
        });
    } else {
        // FC26 Ganadores: 1º DG DESC, 2º GF DESC
        usort($winners, function($a, $b) {
            if ($a['dg'] !== $b['dg']) return $b['dg'] <=> $a['dg'];
            if ($a['gf'] !== $b['gf']) return $b['gf'] <=> $a['gf'];
            if ($a['gc'] !== $b['gc']) return $a['gc'] <=> $b['gc'];
            return $a['id'] <=> $b['id'];
        });

        // FC26 Perdedores: 1º DG DESC (menos desventaja), 2º GF DESC
        usort($losers, function($a, $b) {
            if ($a['dg'] !== $b['dg']) return $b['dg'] <=> $a['dg'];
            if ($a['gf'] !== $b['gf']) return $b['gf'] <=> $a['gf'];
            return $a['id'] <=> $b['id'];
        });
    }

    // Enumerar posiciones de grupo
    foreach ($winners as $idx => &$w) {
        $w['rank_in_group'] = $idx + 1;
        $w['status_label'] = 'Ganador #' . ($idx + 1);
        $w['status_class'] = 'bg-success text-white';
    }
    unset($w);

    foreach ($losers as $idx => &$l) {
        $l['rank_in_group'] = $idx + 1;
        $l['status_label'] = 'Perdedor #' . ($idx + 1);
        $l['status_class'] = 'bg-secondary text-white';
    }
    unset($l);

    return [
        'winners' => $winners,
        'losers' => $losers,
        'unplayed' => $unplayed,
        'all_ranked' => array_merge($winners, $losers, $unplayed)
    ];
}

/**
 * Parejas de cuadro para torneo de eliminación directa:
 * "El mejor contra el peor": 1 vs Último, 2 vs Penúltimo, etc.
 * Estructurados para que el #1 y #2 solo se encuentren en la Gran Final.
 */
function getGroupSeedingPairs(int $k): array {
    if ($k == 2) {
        return [
            ['p1_seed' => 1, 'p2_seed' => 2]
        ];
    }
    if ($k == 4) {
        return [
            ['p1_seed' => 1, 'p2_seed' => 4],
            ['p1_seed' => 2, 'p2_seed' => 3]
        ];
    }
    if ($k == 8) {
        return [
            ['p1_seed' => 1, 'p2_seed' => 8], // 1 vs Último
            ['p1_seed' => 4, 'p2_seed' => 5],
            ['p1_seed' => 2, 'p2_seed' => 7], // 2 vs Penúltimo
            ['p1_seed' => 3, 'p2_seed' => 6]
        ];
    }
    if ($k == 16) {
        return [
            ['p1_seed' => 1, 'p2_seed' => 16], // 1 vs Último
            ['p1_seed' => 8, 'p2_seed' => 9],
            ['p1_seed' => 4, 'p2_seed' => 13],
            ['p1_seed' => 5, 'p2_seed' => 12],
            ['p1_seed' => 2, 'p2_seed' => 15], // 2 vs Penúltimo
            ['p1_seed' => 7, 'p2_seed' => 10],
            ['p1_seed' => 3, 'p2_seed' => 14],
            ['p1_seed' => 6, 'p2_seed' => 11]
        ];
    }
    return [];
}
