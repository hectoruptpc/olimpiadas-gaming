<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/fase_aleatoria.php
$pageTitle = "2. Fase Aleatoria y Puntos";
require_once __DIR__ . '/includes/header.php';

// Consultar partidos sorteados en preliminary_matches
$stmtMatches = $pdo->prepare("SELECT m.*, 
       p1.name as p1_name, p1.alias as p1_alias,
       p2.name as p2_name, p2.alias as p2_alias,
       w.name as winner_name
    FROM preliminary_matches m
    LEFT JOIN participants p1 ON m.player1_id = p1.id
    LEFT JOIN participants p2 ON m.player2_id = p2.id
    LEFT JOIN participants w ON m.winner_id = w.id
    WHERE m.category = ?
    ORDER BY m.round_number ASC, m.match_number ASC");
$stmtMatches->execute([$category]);
$matches = $stmtMatches->fetchAll();
$totalMatches = count($matches);

$completedMatches = 0;
foreach ($matches as $m) {
    if ($m['status'] === 'completed') $completedMatches++;
}
$pendingMatches = $totalMatches - $completedMatches;

// Obtener clasificación oficial de la fase preliminar dividida en Ganadores, Perdedores y No Jugados
$standingsData = get_preliminary_standings($pdo, $category);
$winners = $standingsData['winners'];
$losers = $standingsData['losers'];
$unplayed = $standingsData['unplayed'];
$allRanked = $standingsData['all_ranked'];
$totalParticipants = count($allRanked);
$winnerCount = count($winners);
$loserCount = count($losers);

// Determinar tamaño proyectado de llave de eliminación:
// - Si hay 12 o más ganadores (o ~15 como en 30 jugadores) y >= 16 total -> Llave de 16 (con cupo de repesca si faltan)
// - Si hay 8 a 11 ganadores (ej: 9 ganadores con 18 jugadores) -> Llave de 8 (los mejores 8 clasifican, el 9º queda fuera)
// - Si hay 6 a 7 ganadores y total >= 8 -> Llave de 8 (con cupo repesca)
// - Si hay 4 o 5 ganadores -> Llave de 4
// - Else -> Llave de 2
if ($winnerCount >= 12 && $totalParticipants >= 16) {
    $bracketSize = 16;
} elseif ($winnerCount >= 8) {
    $bracketSize = 8;
} elseif ($winnerCount >= 6 && $totalParticipants >= 8) {
    $bracketSize = 8;
} elseif ($winnerCount >= 4) {
    $bracketSize = 4;
} else {
    $bracketSize = ($totalParticipants >= 4) ? 4 : 2;
}
$halfGroup = (int)($bracketSize / 2);
?>

<!-- BANNER HERO DEL PASO 2: FASE ALEATORIA -->
<div class="light-card p-4 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <div class="d-flex align-items-center gap-2 mb-2">
        <img src="assets/img/uptpc.png" alt="UPTPC" style="height: 28px; width: auto;" class="me-1">
        <span class="badge <?php echo $isFC26 ? 'bg-primary' : 'bg-danger'; ?> text-white text-uppercase px-3 py-1 font-monospace">
          PASO 2 &bull; FASE ALEATORIA
        </span>
        <span class="badge bg-light text-secondary border font-monospace">
          Sorteo Automático de Partidos
        </span>
      </div>

      <h1 class="h3 fw-bold text-dark mb-2">
        Fase Aleatoria &bull; <?php echo htmlspecialchars($catTitle); ?>
      </h1>
      <p class="text-secondary mb-3" style="max-width: 720px;">
        El sistema genera aleatoriamente los enfrentamientos necesarios. Registra el resultado de cada partido y el sistema calculará en tiempo real los puntos y desempates oficiales para conformar el <strong>Grupo A (los mejores)</strong> y <strong>Grupo B (los que al menos entraron)</strong>.
      </p>

      <!-- Reglas Oficiales -->
      <?php if ($isFC26): ?>
        <div class="p-3 bg-light rounded-3 border mb-0" style="font-size: 0.85rem;">
          <div class="d-flex align-items-center gap-2 text-primary fw-bold mb-1">
            <span>⚽</span> REGLAS DE PUNTUACIÓN FC26:
          </div>
          <div class="text-secondary">
            <strong>Puntos = Diferencia de Goles (DG = GF - GC):</strong> Ej: 5 a 3 da <strong>+2 Pts</strong>, 4 a 0 da <strong>+4 Pts</strong>. Si empatan en DG (ej. 6-4 y 5-3 ambos tienen +2), <strong>va ganando quien tiene más goles a favor (6 GF > 5 GF)</strong>.
          </div>
        </div>
      <?php else: ?>
        <div class="p-3 bg-light rounded-3 border mb-0" style="font-size: 0.85rem;">
          <div class="d-flex align-items-center gap-2 text-danger fw-bold mb-1">
            <span>🐉</span> REGLAS DE PUNTUACIÓN MORTAL KOMBAT 1:
          </div>
          <div class="text-secondary">
            <strong>Ganadores:</strong> Se ordenan por <strong>Racha</strong> (2-0 consecutivo supera a 2-1) y <strong>menor tiempo de combate</strong> en segundos (victoria más rápida).<br>
            <strong>Perdedores:</strong> Para los que pierdan, <strong>van arriba los que hayan tenido mayor tiempo de combate en ser derrotados</strong> (mayor resistencia).
          </div>
        </div>
      <?php endif; ?>

    </div>

    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <div class="d-flex flex-column gap-2 justify-content-lg-end">
        
        <div class="dropdown">
          <button class="btn btn-game-primary w-100 py-2 fw-bold dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-shuffle me-1"></i> Sortear Partidos Automáticamente
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
            <li><h6 class="dropdown-header text-uppercase font-monospace small">Opciones de Sorteo</h6></li>
            <li>
              <button class="dropdown-item btn-generate-random fw-bold text-primary" data-category="<?php echo $category; ?>" data-rounds="1">
                🎲 1 Ronda de Partidos (1 partido por jugador)
              </button>
            </li>
            <li>
              <button class="dropdown-item btn-generate-random" data-category="<?php echo $category; ?>" data-rounds="2">
                🎲 2 Rondas de Partidos (2 partidos por jugador)
              </button>
            </li>
            <li>
              <button class="dropdown-item btn-generate-random" data-category="<?php echo $category; ?>" data-rounds="3">
                🎲 3 Rondas de Partidos (3 partidos por jugador)
              </button>
            </li>
          </ul>
        </div>

        <div class="d-flex gap-2 justify-content-lg-end">
          <a href="participantes.php?category=<?php echo $category; ?>" class="btn btn-outline-secondary btn-sm rounded-3 flex-fill">
            <i class="bi bi-arrow-left"></i> Inscritos (<?php echo $totalParticipants; ?>)
          </a>
          <button class="btn btn-outline-primary btn-sm rounded-3 flex-fill fw-bold" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
            <i class="bi bi-diagram-3-fill me-1"></i> Armar Llaves ➔
          </button>
        </div>

        <?php if (!empty($matches)): ?>
          <button class="btn btn-outline-danger btn-sm rounded-3 w-100" id="btnClearPrelimMatches" data-category="<?php echo $category; ?>">
            <i class="bi bi-trash3 me-1"></i> Reiniciar Sorteo de Partidos
          </button>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<!-- TARJETAS DE ESTADÍSTICAS RÁPIDAS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm">🎲</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Total Partidos</small>
        <div class="h4 fw-bold mb-0 text-dark"><?php echo $totalMatches; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm text-success">✅</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Jugados</small>
        <div class="h4 fw-bold mb-0 text-success"><?php echo $completedMatches; ?> / <?php echo $totalMatches; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm text-warning">⏳</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Pendientes</small>
        <div class="h4 fw-bold mb-0 text-warning"><?php echo $pendingMatches; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm text-primary">🏆</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Corte Proyectado</small>
        <div class="h5 fw-bold mb-0 text-primary">
          Top <?php echo $bracketSize; ?> (<?php echo $halfGroup; ?>A vs <?php echo $halfGroup; ?>B)
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MENSAJES INFORMATIVOS DE CLASIFICACIÓN / REPESCA / 9º GANADOR -->
<?php if ($winnerCount == 9 && $bracketSize == 8): ?>
  <div class="alert alert-warning border-warning shadow-sm rounded-4 p-3 mb-4 d-flex align-items-start gap-3">
    <div class="fs-3 lh-1">⚠️</div>
    <div>
      <h6 class="fw-bold mb-1">Caso de 18 Participantes (9 Partidos &bull; 9 Ganadores):</h6>
      <p class="mb-0 small text-dark">
        Para la llave de <strong>8 competidores (Cuartos de final)</strong> entran los <strong>8 mejores ganadores</strong> por desempate.
        El <strong>9º ganador</strong> queda fuera por variables de desempate (menor racha o mayor tiempo). 
        <em>Si prefieres que jueguen 16 competidores, puedes generar la llave de 16 y el sistema otorgará cupos de repesca a los mejores perdedores.</em>
      </p>
    </div>
  </div>
<?php elseif ($winnerCount > 0 && $winnerCount < 16 && $totalParticipants >= 16): ?>
  <div class="alert alert-info border-info shadow-sm rounded-4 p-3 mb-4 d-flex align-items-start gap-3">
    <div class="fs-3 lh-1">💡</div>
    <div>
      <h6 class="fw-bold mb-1">Cupos de Repesca para Llave de 16 (Octavos de Final):</h6>
      <p class="mb-0 small text-dark">
        Hay <strong><?php echo $winnerCount; ?> ganadores</strong>. Para armar una llave de 16 faltan <strong><?php echo (16 - $winnerCount); ?> cupo(s)</strong>.
        El sistema asignará el cupo de repesca al <strong>Mejor Perdedor</strong> (en MK1: <strong>mayor tiempo de combate</strong>; en FC26: <strong>mejor DG</strong>), o puedes elegir quién recibe el cupo en el modal de llaves.
      </p>
    </div>
  </div>
<?php endif; ?>

<!-- DISTRIBUCIÓN EN 2 SECCIONES: PARTIDOS SORTEADOS Y TABLA DE POSICIONES -->
<div class="row g-4">

  <!-- ====================================================================
       SECCIÓN IZQUIERDA: PARTIDOS SORTEADOS POR EL SISTEMA
       ==================================================================== -->
  <div class="col-lg-6">
    <div class="light-card p-4 h-100">
      
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold text-dark mb-1">Enfrentamientos Sorteados</h2>
          <p class="text-muted small mb-0">Partidos generados automáticamente por el sistema.</p>
        </div>

        <?php if (!empty($matches)): ?>
          <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
            <?php echo $completedMatches; ?> de <?php echo $totalMatches; ?> completados
          </span>
        <?php endif; ?>
      </div>

      <?php if (empty($matches)): ?>
        <div class="text-center py-5">
          <div class="display-4 text-muted mb-3">🎲</div>
          <h3 class="h5 fw-bold text-dark">No hay partidos sorteados aún</h3>
          <p class="text-secondary mb-4" style="max-width: 480px; margin: 0 auto;">
            Presiona el botón de abajo para que el sistema empareje automáticamente a los participantes inscritos en los partidos preliminares.
          </p>
          <button class="btn btn-game-primary rounded-pill px-4 py-2 fw-bold btn-generate-random" data-category="<?php echo $category; ?>" data-rounds="1">
            <i class="bi bi-shuffle me-1"></i> Generar Partidos Aleatorios Ahora
          </button>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($matches as $m): 
            $isDone = ($m['status'] === 'completed');
            $p1Win = ($isDone && $m['winner_id'] == $m['player1_id']);
            $p2Win = ($isDone && $m['winner_id'] == $m['player2_id']);
          ?>
            <div class="p-3 rounded-4 border <?php echo $isDone ? 'bg-white shadow-sm' : 'bg-light'; ?> position-relative" style="border-color: <?php echo $isDone ? '#e2e8f0' : '#cbd5e1'; ?>;">
              
              <!-- Cabecera del Partido -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-secondary text-white font-monospace" style="font-size: 0.72rem;">
                    Partido #<?php echo $m['match_number']; ?>
                  </span>
                  <?php if ($m['round_number'] > 1): ?>
                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                      Ronda <?php echo $m['round_number']; ?>
                    </span>
                  <?php endif; ?>
                </div>

                <div>
                  <?php if ($isDone): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1" style="font-size: 0.75rem;">
                      <i class="bi bi-check-circle-fill me-1"></i> Finalizado
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-1" style="font-size: 0.75rem;">
                      <i class="bi bi-hourglass-split me-1"></i> Pendiente
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Jugador 1 vs Jugador 2 -->
              <div class="row align-items-center g-2 mb-3">
                
                <!-- Jugador 1 -->
                <div class="col-5">
                  <div class="d-flex align-items-center gap-2 <?php echo $p1Win ? 'fw-bold text-success' : 'text-dark'; ?>">
                    <div class="avatar-circle shadow-sm <?php echo $p1Win ? 'border border-success' : ''; ?>" style="width: 36px; height: 36px; font-size: 0.85rem;">
                      <?php echo mb_substr($m['p1_name'], 0, 1); ?>
                    </div>
                    <div class="text-truncate">
                      <span class="d-block text-truncate"><?php echo htmlspecialchars($m['p1_name']); ?></span>
                      <?php if (!empty($m['p1_alias'])): ?>
                        <small class="text-muted font-monospace d-block text-truncate" style="font-size: 0.72rem;">
                          🎮 <?php echo htmlspecialchars($m['p1_alias']); ?>
                        </small>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Marcador Central -->
                <div class="col-2 text-center">
                  <?php if ($isDone): ?>
                    <div class="h4 fw-bold mb-0 font-monospace text-dark">
                      <?php echo $m['score1']; ?> - <?php echo $m['score2']; ?>
                    </div>
                  <?php else: ?>
                    <span class="badge bg-light text-muted border px-2 py-1 font-monospace">VS</span>
                  <?php endif; ?>
                </div>

                <!-- Jugador 2 -->
                <div class="col-5 text-end">
                  <div class="d-flex align-items-center justify-content-end gap-2 <?php echo $p2Win ? 'fw-bold text-success' : 'text-dark'; ?>">
                    <div class="text-truncate text-end">
                      <span class="d-block text-truncate"><?php echo htmlspecialchars($m['p2_name']); ?></span>
                      <?php if (!empty($m['p2_alias'])): ?>
                        <small class="text-muted font-monospace d-block text-truncate" style="font-size: 0.72rem;">
                          🎮 <?php echo htmlspecialchars($m['p2_alias']); ?>
                        </small>
                      <?php endif; ?>
                    </div>
                    <div class="avatar-circle shadow-sm <?php echo $p2Win ? 'border border-success' : ''; ?>" style="width: 36px; height: 36px; font-size: 0.85rem;">
                      <?php echo mb_substr($m['p2_name'], 0, 1); ?>
                    </div>
                  </div>
                </div>

              </div>

              <!-- Formulario de Marcador o Acciones de Partido -->
              <?php if (!$isDone): ?>
                <?php if ($isFC26): ?>
                  <!-- Formulario Rápido FC26 -->
                  <form class="form-save-prelim-match border-top pt-2">
                    <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                      <span class="small text-muted font-monospace">Goles:</span>
                      <input type="number" name="score1" class="form-control form-control-sm text-center fw-bold" style="width: 60px;" min="0" value="0" required placeholder="J1">
                      <span class="text-muted fw-bold">-</span>
                      <input type="number" name="score2" class="form-control form-control-sm text-center fw-bold" style="width: 60px;" min="0" value="0" required placeholder="J2">
                      <button type="submit" class="btn btn-game-primary btn-sm px-3 fw-bold">
                        <i class="bi bi-check-lg"></i> Guardar
                      </button>
                    </div>
                  </form>
                <?php else: ?>
                  <!-- Formulario Rápido MK1 -->
                  <form class="form-save-prelim-match border-top pt-2">
                    <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                    <div class="row g-2 align-items-center">
                      <div class="col-12 col-sm-5">
                        <select name="winner_id" class="form-select form-select-sm" required>
                          <option value="">-- Ganador del Combate --</option>
                          <option value="<?php echo $m['player1_id']; ?>">👑 <?php echo htmlspecialchars($m['p1_name']); ?></option>
                          <option value="<?php echo $m['player2_id']; ?>">👑 <?php echo htmlspecialchars($m['p2_name']); ?></option>
                        </select>
                      </div>
                      <div class="col-6 col-sm-3">
                        <select name="result_type" class="form-select form-select-sm" required>
                          <option value="2-0">2-0 (+2 Pts)</option>
                          <option value="2-1">2-1 (+1 Pt)</option>
                        </select>
                      </div>
                      <div class="col-6 col-sm-2">
                        <input type="number" name="time_seconds" class="form-control form-control-sm text-center" min="1" placeholder="Seg" value="90" title="Tiempo de combate en segundos">
                      </div>
                      <div class="col-12 col-sm-2 text-end">
                        <button type="submit" class="btn btn-game-primary btn-sm w-100 fw-bold">
                          <i class="bi bi-check-lg"></i> OK
                        </button>
                      </div>
                    </div>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <!-- Partido Completado: Detalles y Reabrir -->
                <div class="d-flex justify-content-between align-items-center border-top pt-2 small text-muted">
                  <div>
                    <?php if ($isFC26): 
                      $dg1 = $m['score1'] - $m['score2'];
                      $dg2 = $m['score2'] - $m['score1'];
                    ?>
                      <span class="font-monospace">
                        <?php echo htmlspecialchars($m['p1_name']); ?>: <strong><?php echo $dg1 > 0 ? "+$dg1" : "$dg1"; ?> DG</strong> &bull; 
                        <?php echo htmlspecialchars($m['p2_name']); ?>: <strong><?php echo $dg2 > 0 ? "+$dg2" : "$dg2"; ?> DG</strong>
                      </span>
                    <?php else: ?>
                      <span class="font-monospace">
                        👑 Ganó <strong><?php echo htmlspecialchars($m['winner_name']); ?></strong> por 
                        <strong><?php echo htmlspecialchars($m['mk_result_type'] ?? '2-0'); ?></strong> en 
                        <strong><?php echo $m['mk_time_seconds']; ?>s</strong>
                      </span>
                    <?php endif; ?>
                  </div>

                  <button class="btn btn-link btn-sm text-secondary text-decoration-none p-0 btn-reset-prelim-match" data-match-id="<?php echo $m['id']; ?>" title="Reabrir partido para corregir marcador">
                    <i class="bi bi-arrow-counterclockwise"></i> Reabrir
                  </button>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- ====================================================================
       SECCIÓN DERECHA: TABLA OFICIAL DE POSICIONES EN TIEMPO REAL
       ==================================================================== -->
  <div class="col-lg-6">
    <div class="light-card p-4 h-100 position-sticky" style="top: 20px;">
      
      <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
          <h2 class="h5 fw-bold text-dark mb-1">Clasificación de Fase Aleatoria</h2>
          <small class="text-muted">
            <?php if ($isFC26): ?>
              Orden oficial: Diferencia de Goles (DG) &bull; Goles a Favor (GF)
            <?php else: ?>
              Ganadores: Racha &bull; Menor Tiempo | Perdedores: Mayor tiempo de combate
            <?php endif; ?>
          </small>
        </div>
        <button class="btn btn-game-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
          <i class="bi bi-diagram-3-fill me-1"></i> Armar Llaves ➔
        </button>
      </div>

      <!-- Leyenda de Grupos A, B, Fuera de Corte y Candidato a Repesca -->
      <div class="d-flex flex-wrap gap-2 mb-3 small">
        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
          🟢 Grupo A (#1 a #<?php echo $halfGroup; ?>)
        </span>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-2 py-1">
          🟡 Grupo B (#<?php echo $halfGroup + 1; ?> a #<?php echo $bracketSize; ?>)
        </span>
        <?php if ($winnerCount > $bracketSize): ?>
          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
            ⚠️ Ganador Fuera (Corte #<?php echo $bracketSize; ?>)
          </span>
        <?php endif; ?>
        <?php if (!empty($losers)): ?>
          <span class="badge bg-info bg-opacity-10 text-primary border border-info px-2 py-1">
            🔵 Mejor Perdedor (Candidato Repesca)
          </span>
        <?php endif; ?>
      </div>

      <?php if (empty($allRanked)): ?>
        <p class="text-muted text-center py-4">No hay participantes registrados.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-light-custom align-middle mb-0 font-monospace" style="font-size: 0.80rem;">
            <thead>
              <tr>
                <th style="width: 45px;">Pos</th>
                <th>Competidor</th>
                <?php if ($isFC26): ?>
                  <th class="text-center" style="width: 40px;" title="Partidos Jugados">PJ</th>
                  <th class="text-center" style="width: 70px;" title="Goles a Favor - Goles en Contra">Goles</th>
                  <th class="text-center" style="width: 55px;" title="Puntos = Diferencia de Goles">DG</th>
                  <th class="text-center" style="width: 140px;">Estado</th>
                <?php else: ?>
                  <!-- MK1 EXACTO: Racha y Segundos (Sin PJ, Sin 2-0 suelto, Sin PTS) -->
                  <th class="text-center" style="width: 85px;" title="Racha de Rounds: 2-0 supera a 2-1">Racha</th>
                  <th class="text-center" style="width: 80px;" title="Tiempo de Combate en Segundos">Segundos</th>
                  <th class="text-center" style="width: 150px;">Estado</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php 
              $pos = 1;
              foreach ($allRanked as $p): 
                $isWinner = $p['is_winner'];
                $isLoser = $p['is_loser'];

                // Clasificación en llaves:
                $isGroupA = ($isWinner && $pos <= $halfGroup);
                $isGroupB = ($isWinner && $pos > $halfGroup && $pos <= $bracketSize);
                $isWinnerLeftOut = ($isWinner && $pos > $bracketSize);
                $isBestLoser = ($isLoser && $p['rank_in_group'] === 1);

                $rowBg = '';
                if ($isGroupA) {
                    $rowBg = 'style="background-color: #f0fdf4;"';
                } elseif ($isGroupB) {
                    $rowBg = 'style="background-color: #fefce8;"';
                } elseif ($isWinnerLeftOut) {
                    $rowBg = 'style="background-color: #fff7ed;"';
                } elseif ($isBestLoser) {
                    $rowBg = 'style="background-color: #f0f9ff;"';
                }
              ?>
                <tr <?php echo $rowBg; ?>>
                  
                  <!-- Posición -->
                  <td class="fw-bold">
                    <?php if ($isGroupA): ?>
                      <span class="badge bg-success text-white px-1 py-0" style="font-size: 0.7rem;">A#<?php echo $pos; ?></span>
                    <?php elseif ($isGroupB): ?>
                      <span class="badge bg-warning text-dark px-1 py-0" style="font-size: 0.7rem;">B#<?php echo $pos - $halfGroup; ?></span>
                    <?php elseif ($isWinnerLeftOut): ?>
                      <span class="badge bg-danger text-white px-1 py-0" style="font-size: 0.7rem;">#<?php echo $pos; ?></span>
                    <?php elseif ($isBestLoser): ?>
                      <span class="badge bg-info text-dark px-1 py-0" style="font-size: 0.7rem;">#<?php echo $pos; ?></span>
                    <?php else: ?>
                      <span class="text-muted">#<?php echo $pos; ?></span>
                    <?php endif; ?>
                  </td>

                  <!-- Competidor -->
                  <td>
                    <span class="fw-bold text-dark d-block text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($p['name']); ?>">
                      <?php echo htmlspecialchars($p['name']); ?>
                    </span>
                    <?php if (!empty($p['alias'])): ?>
                      <small class="text-muted d-block text-truncate" style="font-size: 0.68rem; max-width: 140px;">
                        🎮 <?php echo htmlspecialchars($p['alias']); ?>
                      </small>
                    <?php endif; ?>
                  </td>

                  <?php if ($isFC26): ?>
                    <!-- FC26: PJ, Goles (GF - GC), DG, Estado -->
                    <td class="text-center text-muted"><?php echo $p['played']; ?></td>
                    <td class="text-center font-monospace"><?php echo $p['gf']; ?> - <?php echo $p['gc']; ?></td>
                    <td class="text-center fw-bold <?php echo $p['dg'] > 0 ? 'text-primary' : ($p['dg'] < 0 ? 'text-danger' : 'text-secondary'); ?>">
                      <?php echo $p['dg'] > 0 ? "+{$p['dg']}" : $p['dg']; ?>
                    </td>
                    <td class="text-center">
                      <?php if ($isGroupA): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size: 0.7rem;">
                          Top A &bull; Clasificado
                        </span>
                      <?php elseif ($isGroupB): ?>
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning" style="font-size: 0.7rem;">
                          Top B &bull; Clasificado
                        </span>
                      <?php elseif ($isWinnerLeftOut): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size: 0.68rem;" title="Ganó pero queda fuera del corte por desempate">
                          Fuera por Desempate
                        </span>
                      <?php elseif ($isBestLoser): ?>
                        <span class="badge bg-info bg-opacity-15 text-primary border border-info" style="font-size: 0.68rem;" title="1º Candidato a Cupo de Repesca">
                          ⭐ Mejor Perdedor
                        </span>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">
                          Perdedor #<?php echo $p['rank_in_group']; ?>
                        </span>
                      <?php endif; ?>
                    </td>

                  <?php else: ?>
                    <!-- MORTAL KOMBAT 1: Racha, Segundos, Estado (Sin PJ, Sin 2-0, Sin PTS) -->
                    <td class="text-center font-monospace">
                      <?php if ($isWinner): ?>
                        <span class="fw-bold text-success"><?php echo htmlspecialchars($p['racha_label']); ?></span>
                      <?php elseif ($isLoser): ?>
                        <span class="text-muted"><?php echo htmlspecialchars($p['racha_label']); ?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <td class="text-center font-monospace fw-bold <?php echo $isWinner ? 'text-dark' : 'text-secondary'; ?>">
                      <?php echo $p['seconds'] > 0 ? "{$p['seconds']}s" : "-"; ?>
                    </td>

                    <td class="text-center">
                      <?php if ($isGroupA): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size: 0.7rem;">
                          Top A &bull; Clasificado
                        </span>
                      <?php elseif ($isGroupB): ?>
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning" style="font-size: 0.7rem;">
                          Top B &bull; Clasificado
                        </span>
                      <?php elseif ($isWinnerLeftOut): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size: 0.68rem;" title="Ganó pero queda fuera del corte por desempate">
                          <?php echo $p['rank_in_group']; ?>º Ganador (Fuera)
                        </span>
                      <?php elseif ($isBestLoser): ?>
                        <span class="badge bg-info bg-opacity-15 text-primary border border-info" style="font-size: 0.68rem;" title="Mayor tiempo de combate: 1º candidato a repesca">
                          ⭐ Mejor Perdedor (<?php echo $p['seconds']; ?>s)
                        </span>
                      <?php elseif ($isLoser): ?>
                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">
                          Perdedor #<?php echo $p['rank_in_group']; ?>
                        </span>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Pendiente</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>

                </tr>
              <?php 
                $pos++;
              endforeach; 
              ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 pt-3 border-top text-center">
          <p class="small text-secondary mb-2">
            ¿Completaste los partidos preliminares? Pasa a la siguiente fase para armar las eliminatorias.
          </p>
          <button class="btn btn-game-primary w-100 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGenerateBracket">
            <i class="bi bi-diagram-3-fill me-1"></i> Armar Llave de Eliminatorias (Playoffs) ➔
          </button>
        </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<!-- ====================================================================
     MODAL PARA GENERAR LLAVES DE ELIMINATORIAS (CON REPESCA Y CORTE)
     ==================================================================== -->
<div class="modal fade" id="modalGenerateBracket" tabindex="-1" aria-labelledby="modalGenerateBracketLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <form id="formGenerateBracketModal">
        <input type="hidden" name="category" value="<?php echo $category; ?>">
        
        <div class="modal-header border-bottom pb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="fs-4">🏆</span>
            <div>
              <h5 class="modal-title fw-bold mb-0" id="modalGenerateBracketLabel">Armar Llave de Eliminatorias (Playoffs)</h5>
              <small class="text-muted"><?php echo htmlspecialchars($catTitle); ?> &bull; Sorteo de Cuadro</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body p-4">
          
          <!-- Resumen de Ganadores y Perdedores -->
          <div class="row g-3 mb-4">
            <div class="col-sm-4">
              <div class="p-3 rounded-3 bg-light border text-center">
                <small class="text-muted text-uppercase font-monospace d-block" style="font-size: 0.72rem;">Ganadores Preliminares</small>
                <div class="h3 fw-bold text-success mb-0"><?php echo $winnerCount; ?></div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 rounded-3 bg-light border text-center">
                <small class="text-muted text-uppercase font-monospace d-block" style="font-size: 0.72rem;">Perdedores Preliminares</small>
                <div class="h3 fw-bold text-secondary mb-0"><?php echo $loserCount; ?></div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 rounded-3 bg-light border text-center">
                <small class="text-muted text-uppercase font-monospace d-block" style="font-size: 0.72rem;">Total Participantes</small>
                <div class="h3 fw-bold text-primary mb-0"><?php echo $totalParticipants; ?></div>
              </div>
            </div>
          </div>

          <!-- Selección de Tamaño de Llave -->
          <label class="form-label fw-bold mb-2">1. Selecciona el tamaño de la Llave de Eliminatorias:</label>
          <div class="d-flex flex-column gap-2 mb-4">
            
            <label class="d-flex align-items-center gap-3 p-3 rounded-3 border cursor-pointer hover-bg-light">
              <input type="radio" name="size" value="8" <?php echo ($bracketSize == 8) ? 'checked' : ''; ?> class="form-check-input mt-0 radio-bracket-size">
              <div>
                <strong>Cuartos de Final (Llave de 8)</strong>
                <div class="small text-muted">
                  Recomendado para 8 o 9 ganadores. Si hay 9 ganadores (ej. 18 participantes), <strong>el 9º ganador queda fuera</strong> por desempate de racha y tiempo.
                </div>
              </div>
            </label>

            <label class="d-flex align-items-center gap-3 p-3 rounded-3 border cursor-pointer hover-bg-light">
              <input type="radio" name="size" value="16" <?php echo ($bracketSize == 16) ? 'checked' : ''; ?> class="form-check-input mt-0 radio-bracket-size">
              <div>
                <strong>Octavos de Final (Llave de 16)</strong>
                <div class="small text-muted">
                  Recomendado para 15 o 16 ganadores (ej. 30 participantes). Si faltan cupos para llegar a 16, <strong>se otorgan cupos de repesca</strong> a los mejores perdedores.
                </div>
              </div>
            </label>

            <label class="d-flex align-items-center gap-3 p-3 rounded-3 border cursor-pointer hover-bg-light">
              <input type="radio" name="size" value="4" <?php echo ($bracketSize == 4) ? 'checked' : ''; ?> class="form-check-input mt-0 radio-bracket-size">
              <div>
                <strong>Semifinales (Llave de 4)</strong>
                <div class="small text-muted">Entran únicamente los 4 mejores clasificados.</div>
              </div>
            </label>

          </div>

          <!-- Sección de Cupos de Repesca (Wildcards) si faltan cupos -->
          <?php if (!empty($losers)): ?>
            <div id="sectionWildcardOptions" class="p-3 bg-light rounded-3 border mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="fw-bold text-dark mb-0">
                  <i class="bi bi-star-fill text-warning me-1"></i> Asignación de Cupos de Repesca (Wildcards):
                </label>
                <span class="badge bg-primary">Opcional</span>
              </div>
              <p class="small text-secondary mb-3">
                Si la llave elegida requiere más competidores que los ganadores disponibles (ej. 15 ganadores para llave de 16, falta 1 cupo), el sistema puede otorgar el cupo automáticamente al <strong>Mejor Perdedor</strong> (en MK1: <strong>mayor tiempo de combate</strong>; en FC26: <strong>mejor DG</strong>), o puedes seleccionar quién recibe el cupo:
              </p>

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="wildcard_mode" id="wcModeAuto" value="auto" checked>
                  <label class="form-check-label fw-bold" for="wcModeAuto">
                    ⚡ Automático: Asignar cupo al Mejor Perdedor por estadísticas oficiales
                    <?php if (!empty($losers[0])): ?>
                      <span class="badge bg-info text-dark ms-1">
                        Recomendado: <?php echo htmlspecialchars($losers[0]['name']); ?> 
                        (<?php echo $isFC26 ? 'DG ' . $losers[0]['dg'] : $losers[0]['seconds'] . 's de combate'; ?>)
                      </span>
                    <?php endif; ?>
                  </label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="radio" name="wildcard_mode" id="wcModeManual" value="manual">
                  <label class="form-check-label" for="wcModeManual">
                    ✍️ Manual: Elegir participante(s) específico(s) para la repesca:
                  </label>
                </div>
              </div>

              <div id="manualWildcardsList" class="ps-3 border-start border-2 border-primary" style="display: none;">
                <small class="text-muted d-block mb-2">Marca el/los perdedor(es) que deseas repescar para completar los cupos:</small>
                <div class="row g-2" style="max-height: 180px; overflow-y: auto;">
                  <?php foreach ($losers as $l): ?>
                    <div class="col-12 col-sm-6">
                      <div class="form-check">
                        <input class="form-check-input chk-wildcard" type="checkbox" name="wildcard_ids[]" value="<?php echo $l['id']; ?>" id="wc_<?php echo $l['id']; ?>">
                        <label class="form-check-label small" for="wc_<?php echo $l['id']; ?>">
                          <strong><?php echo htmlspecialchars($l['name']); ?></strong>
                          <span class="text-muted font-monospace">
                            (<?php echo $isFC26 ? 'DG: ' . $l['dg'] : $l['seconds'] . 's'; ?>)
                          </span>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Explicación del Cruce: Mejor vs Peor -->
          <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <small>
              <strong>Cruce "El Mejor contra el Peor":</strong> El Seed #1 jugará contra el Último clasificado, el #2 contra el Penúltimo, y así sucesivamente. El 1º y el 2º solo podrán enfrentarse en la Gran Final.
            </small>
          </div>

        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-game-primary px-4 fw-bold">
            <i class="bi bi-diagram-3-fill me-1"></i> Confirmar y Generar Llaves ➔
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
