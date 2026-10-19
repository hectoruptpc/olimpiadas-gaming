<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/brackets.php
$pageTitle = "Árbol de Eliminatorias";
require_once __DIR__ . '/includes/header.php';

// Consultar llaves de la categoría
$stmtBracket = $pdo->prepare("SELECT * FROM bracket_matches WHERE category = ? ORDER BY round_order ASC, match_number ASC");
$stmtBracket->execute([$category]);
$bracketMatches = $stmtBracket->fetchAll();

// Mapa de participantes
$stmtP = $pdo->prepare("SELECT * FROM participants WHERE category = ?");
$stmtP->execute([$category]);
$allParticipants = $stmtP->fetchAll();
$playersMap = [];
foreach ($allParticipants as $p) {
    $playersMap[$p['id']] = $p;
}

// Agrupar por rondas y verificar si hay campeón
$rounds = [];
$champion = null;
$finalMatch = null;
foreach ($bracketMatches as $m) {
    $rounds[$m['round_order']]['name'] = $m['round_name'];
    $rounds[$m['round_order']]['matches'][] = $m;

    if (empty($m['next_match_id'])) {
        $finalMatch = $m;
        if ($m['status'] === 'completed' && !empty($m['winner_id'])) {
            $champion = $playersMap[$m['winner_id']] ?? null;
        }
    }
}

// Comprobar si ambos finalistas ya llegaron a la Gran Final
$finalistsArrived = (!empty($finalMatch) && !empty($finalMatch['player1_id']) && !empty($finalMatch['player2_id']));
$finalist1 = $finalistsArrived ? ($playersMap[$finalMatch['player1_id']] ?? null) : null;
$finalist2 = $finalistsArrived ? ($playersMap[$finalMatch['player2_id']] ?? null) : null;
$finalCompleted = ($finalistsArrived && $finalMatch['status'] === 'completed');
$scoreLabel = $isFC26 ? 'Goles Marcados' : 'Rounds Ganados (0 a 2)';
?>

<!-- BANNER DE ENCABEZADO DE BRACKETS -->
<div class="light-card p-4 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <div class="d-flex align-items-center gap-2 mb-2">
        <img src="assets/img/uptpc.png" alt="UPTPC" style="height: 28px; width: auto;" class="me-1">
        <span class="badge <?php echo $isFC26 ? 'bg-primary' : 'bg-danger'; ?> text-white text-uppercase px-3 py-1 font-monospace">
          Fase Eliminatoria &bull; Playoffs
        </span>
        <span class="badge bg-light text-secondary border font-monospace">
          Cruces: 1 vs Último &bull; 2 vs Penúltimo
        </span>
      </div>

      <h1 class="h3 fw-bold text-dark mb-1">
        Árbol de Eliminatorias &bull; <?php echo htmlspecialchars($catTitle); ?>
      </h1>
      <p class="text-secondary mb-0">
        Haz clic en <strong>"Cargar Marcador"</strong> en cada partido para registrar los <?php echo strtolower($scoreUnit); ?> 
        y confirmar manualmente quién avanza a la siguiente ronda.
      </p>
    </div>

    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
        <a href="fase_aleatoria.php?category=<?php echo $category; ?>" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Volver a Fase Aleatoria
        </a>

        <!-- Dropdown para Regenerar Llaves si es necesario -->
        <div class="dropdown">
          <button class="btn btn-game-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerar Llaves
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0">
            <li><h6 class="dropdown-header text-uppercase font-monospace">Opciones de Llave</h6></li>
            <li>
              <button class="dropdown-item btn-generate-bracket" data-category="<?php echo $category; ?>" data-size="auto">
                ⚡ Automático (Según puntajes)
              </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item btn-generate-bracket" data-category="<?php echo $category; ?>" data-size="16" <?php echo (count($allParticipants) < 16) ? 'disabled' : ''; ?>>
                🏆 Octavos de Final (Top 16)
              </button>
            </li>
            <li>
              <button class="dropdown-item btn-generate-bracket" data-category="<?php echo $category; ?>" data-size="8" <?php echo (count($allParticipants) < 8) ? 'disabled' : ''; ?>>
                🔥 Cuartos de Final (Top 8)
              </button>
            </li>
            <li>
              <button class="dropdown-item btn-generate-bracket" data-category="<?php echo $category; ?>" data-size="4" <?php echo (count($allParticipants) < 4) ? 'disabled' : ''; ?>>
                ⚔️ Semifinales (Top 4)
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ====================================================================
     SHOWDOWN Y REPRODUCTOR DE AUDIO DE LA GRAN FINAL
     (SE ACTIVA CUANDO AMBOS FINALISTAS LLEGAN A LA FINAL)
     ==================================================================== -->
<?php if ($finalistsArrived): ?>
  <div class="card border-0 text-white mb-4 shadow-lg overflow-hidden position-relative animate__animated animate__fadeIn <?php echo $isFC26 ? 'grand-final-banner-fc26' : 'grand-final-banner-mk1'; ?>">
    <div class="card-body p-4 position-relative" style="z-index: 2;">
      <div class="row align-items-center g-3">
        
        <div class="col-lg-7">
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span class="badge <?php echo $isFC26 ? 'bg-primary' : 'bg-danger'; ?> text-white text-uppercase px-3 py-1 font-monospace">
              <?php echo $isFC26 ? '⭐ ¡GRAN FINAL UEFA CHAMPIONS LEAGUE!' : '🐉 ¡GRAN FINAL DE MORTAL KOMBAT 1!'; ?>
            </span>
            <span class="badge <?php echo $isFC26 ? 'bg-info text-dark' : 'bg-warning text-dark'; ?> font-monospace" id="badgeAudioState">
              <i class="bi bi-music-note-beamed"></i> Audio Oficial Listo
            </span>
          </div>

          <h2 class="h3 fw-bold mb-1 text-white">
            <span class="<?php echo $isFC26 ? 'text-info' : 'text-warning'; ?>"><?php echo htmlspecialchars($finalist1['name']); ?></span>
            <?php if (!empty($finalist1['alias'])): ?>
              <small class="fs-6 opacity-75 font-monospace">(<?php echo htmlspecialchars($finalist1['alias']); ?>)</small>
            <?php endif; ?>
            <span class="<?php echo $isFC26 ? 'text-light' : 'text-danger'; ?> mx-2 fs-4 fw-black">VS</span>
            <span class="<?php echo $isFC26 ? 'text-info' : 'text-warning'; ?>"><?php echo htmlspecialchars($finalist2['name']); ?></span>
            <?php if (!empty($finalist2['alias'])): ?>
              <small class="fs-6 opacity-75 font-monospace">(<?php echo htmlspecialchars($finalist2['alias']); ?>)</small>
            <?php endif; ?>
          </h2>

          <p class="mb-0 text-light opacity-90 small">
            <?php if ($isFC26): ?>
              ¡Ambos finalistas han alcanzado la Gran Final! Suena en bucle el <strong>Himno Oficial UEFA Champions League</strong>.
            <?php else: ?>
              ¡Ambos finalistas han alcanzado la Gran Final! Suena primero <strong>"Finish Him"</strong> y seguidamente en bucle <strong>"Techno Syndrome (Mortal Kombat)"</strong>.
            <?php endif; ?>
          </p>
        </div>

        <div class="col-lg-5 text-lg-end">
          <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
            
            <!-- Botón para Abrir el Modal Épico de la Final -->
            <button class="btn <?php echo $isFC26 ? 'btn-light text-primary' : 'btn-warning text-dark'; ?> fw-bold px-3 py-2 shadow btn-open-match-modal"
              data-match-id="<?php echo $finalMatch['id']; ?>"
              data-category="<?php echo $category; ?>"
              data-round-name="Gran Final"
              data-match-number="<?php echo $finalMatch['match_number']; ?>"
              data-p1-id="<?php echo $finalist1['id']; ?>"
              data-p1-name="<?php echo htmlspecialchars($finalist1['name']); ?>"
              data-p1-alias="<?php echo htmlspecialchars($finalist1['alias'] ?? ''); ?>"
              data-p1-score="<?php echo $finalMatch['score1']; ?>"
              data-p2-id="<?php echo $finalist2['id']; ?>"
              data-p2-name="<?php echo htmlspecialchars($finalist2['name']); ?>"
              data-p2-alias="<?php echo htmlspecialchars($finalist2['alias'] ?? ''); ?>"
              data-p2-score="<?php echo $finalMatch['score2']; ?>"
              data-winner-id="<?php echo $finalMatch['winner_id']; ?>"
              data-is-final="1"
            >
              <i class="bi bi-trophy-fill me-1"></i> <?php echo $finalCompleted ? 'Modificar Final' : ($isFC26 ? '¡Jugar la Gran Final!' : '¡Iniciar Combate Final!'); ?>
            </button>

            <!-- Controles de Audio -->
            <button id="btnToggleFinalAudio" class="btn btn-outline-light fw-bold px-3 py-2 shadow-sm">
              <i class="bi bi-pause-fill" id="iconPlayPause"></i> <span id="textPlayPause">Pausar</span>
            </button>
            <button id="btnRestartFinalAudio" class="btn btn-outline-light px-2 py-2 shadow-sm" title="Reiniciar audio desde el principio">
              <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            <button id="btnMuteFinalAudio" class="btn btn-outline-light px-2 py-2 shadow-sm" title="Silenciar / Activar sonido">
              <i class="bi bi-volume-up-fill" id="iconMute"></i>
            </button>
          </div>
          
          <div class="mt-2 <?php echo $isFC26 ? 'text-info' : 'text-warning'; ?> font-monospace small d-flex align-items-center justify-content-lg-end gap-2" id="audioStatusContainer">
            <div class="audio-equalizer" id="equalizerAnim">
              <span></span><span></span><span></span><span></span>
            </div>
            <span id="audioStatusText">🔊 Iniciando audio...</span>
          </div>
        </div>

      </div>
    </div>
  </div>
<?php endif; ?>

<!-- PODIO DEL CAMPEÓN (SI CONCLUYÓ LA FINAL) -->
<?php if ($champion): ?>
  <div class="champion-podium mb-4 animate__animated animate__fadeIn">
    <div class="display-3 mb-2">🏆</div>
    <h3 class="text-uppercase">¡CAMPEÓN DE <?php echo strtoupper($catTitle); ?>!</h3>
    <h2 class="display-6"><?php echo htmlspecialchars($champion['name']); ?></h2>
    <?php if (!empty($champion['alias'])): ?>
      <span class="badge bg-warning text-dark fs-6 px-4 py-2 rounded-pill font-monospace shadow-sm">
        GamerTag: <?php echo htmlspecialchars($champion['alias']); ?>
      </span>
    <?php endif; ?>
    <p class="text-secondary mt-2 mb-2">¡Felicitaciones al campeón absoluto de las Olimpiadas Gaming UPTPC 2026!</p>
    
    <div class="mt-3">
      <button type="button" class="btn btn-warning btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm" id="btnShowFireworksPodium">
        <i class="bi bi-stars me-1"></i> 🎆 Ver Celebración y Fuegos Artificiales 🏆
      </button>
    </div>
  </div>
<?php endif; ?>

<!-- CONTENEDOR DEL ÁRBOL DE ELIMINATORIAS (FULL WIDTH) -->
<div class="light-card p-4">

  <?php if (empty($rounds)): ?>
    <div class="text-center py-5">
      <div class="display-4 text-muted mb-3">⚔️</div>
      <h3 class="h5 fw-bold text-dark">No se han generado las eliminatorias</h3>
      <p class="text-muted mb-4 max-w-md mx-auto" style="max-width: 480px;">
        Primero debes registrar los puntos de grupo en la tabla de clasificación para que el sistema empareje a los mejores clasificados.
      </p>
      <div class="d-flex justify-content-center gap-2">
        <a href="participantes.php?category=<?php echo $category; ?>" class="btn btn-outline-secondary">
          <i class="bi bi-people-fill me-1"></i> Ir a Clasificación
        </a>
        <button class="btn btn-game-primary btn-generate-bracket" data-category="<?php echo $category; ?>" data-size="auto" <?php echo (count($allParticipants) < 2) ? 'disabled' : ''; ?>>
          ⚡ Generar Llaves Ahora
        </button>
      </div>
    </div>
  <?php else: ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1 font-monospace">
          <i class="bi bi-shield-check"></i> Llaves Activas
        </span>
        <small class="text-muted">Total de rondas: <?php echo count($rounds); ?></small>
      </div>
      <small class="text-muted font-monospace">
        El ganador de cada partido avanza automáticamente a la siguiente ronda
      </small>
    </div>

    <!-- Árbol Horizontal de Llaves -->
    <div class="bracket-viewport">
      <div class="bracket-tree">
        
        <?php foreach ($rounds as $order => $rData): ?>
          <div class="bracket-column">
            
            <div class="bracket-col-title">
              <?php echo htmlspecialchars($rData['name']); ?>
            </div>

            <?php foreach ($rData['matches'] as $m): 
              $p1 = $playersMap[$m['player1_id']] ?? null;
              $p2 = $playersMap[$m['player2_id']] ?? null;
              $isCompleted = ($m['status'] === 'completed');
              $winnerId = $m['winner_id'];
              $canRecord = ($p1 && $p2);
              $isFinalMatch = empty($m['next_match_id']);
            ?>
              <div class="match-card-light <?php echo $isCompleted ? 'is-completed' : ''; ?> <?php echo ($isFinalMatch && $finalistsArrived) ? 'is-grand-final-showdown' : ''; ?>">
                
                <!-- Encabezado del partido -->
                <div class="match-card-header">
                  <div class="d-flex align-items-center gap-1">
                    <span>Partido #<?php echo $m['match_number']; ?></span>
                    <?php if ($order === 1 && !empty($m['player1_seed']) && !empty($m['player2_seed'])): ?>
                      <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.62rem;">
                        Seed #<?php echo $m['player1_seed']; ?> vs #<?php echo $m['player2_seed']; ?>
                      </span>
                    <?php elseif ($isFinalMatch): ?>
                      <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.62rem;">
                        👑 GRAN FINAL <?php echo $finalistsArrived ? '• 🎵 Audio Activo' : ''; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if ($isCompleted): ?>
                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Finalizado</span>
                  <?php elseif ($canRecord): ?>
                    <span class="text-primary"><i class="bi bi-play-circle-fill"></i> Por Jugar</span>
                  <?php else: ?>
                    <span class="text-muted"><i class="bi bi-hourglass-split"></i> Esperando</span>
                  <?php endif; ?>
                </div>

                <!-- Jugador 1 -->
                <div class="match-player-row <?php echo ($isCompleted && $winnerId == $m['player1_id']) ? 'winner-row' : ''; ?>">
                  <div class="player-meta">
                    <?php if (!empty($m['player1_seed'])): 
                      $isRep1 = (!empty($m['player1_group']) && stripos($m['player1_group'], 'Repesca') !== false);
                    ?>
                      <span class="badge <?php echo $isRep1 ? 'bg-info text-dark' : 'bg-dark text-white'; ?> px-1 py-0 font-monospace" style="font-size: 0.68rem;" title="<?php echo htmlspecialchars($m['player1_group'] ?? ('Seed #' . $m['player1_seed'])); ?>">
                        #<?php echo $m['player1_seed']; ?><?php echo $isRep1 ? ' Rep' : ''; ?>
                      </span>
                    <?php endif; ?>
                    <span class="text-truncate" style="max-width: 175px;">
                      <?php 
                      if ($p1) {
                        echo htmlspecialchars($p1['name']);
                        if (!empty($p1['alias'])) echo ' <small class="text-muted">(' . htmlspecialchars($p1['alias']) . ')</small>';
                      } else {
                        echo '<em class="text-muted">Por definir</em>';
                      }
                      ?>
                    </span>
                    <?php if ($isCompleted && $winnerId == $m['player1_id']): ?>
                      <span class="text-warning ms-1" title="Ganador">👑</span>
                    <?php endif; ?>
                  </div>
                  <div class="player-score-badge">
                    <?php echo $isCompleted ? $m['score1'] : '-'; ?>
                  </div>
                </div>

                <!-- Jugador 2 -->
                <div class="match-player-row <?php echo ($isCompleted && $winnerId == $m['player2_id']) ? 'winner-row' : ''; ?>">
                  <div class="player-meta">
                    <?php if (!empty($m['player2_seed'])): 
                      $isRep2 = (!empty($m['player2_group']) && stripos($m['player2_group'], 'Repesca') !== false);
                    ?>
                      <span class="badge <?php echo $isRep2 ? 'bg-info text-dark' : 'bg-dark text-white'; ?> px-1 py-0 font-monospace" style="font-size: 0.68rem;" title="<?php echo htmlspecialchars($m['player2_group'] ?? ('Seed #' . $m['player2_seed'])); ?>">
                        #<?php echo $m['player2_seed']; ?><?php echo $isRep2 ? ' Rep' : ''; ?>
                      </span>
                    <?php endif; ?>
                    <span class="text-truncate" style="max-width: 175px;">
                      <?php 
                      if ($p2) {
                        echo htmlspecialchars($p2['name']);
                        if (!empty($p2['alias'])) echo ' <small class="text-muted">(' . htmlspecialchars($p2['alias']) . ')</small>';
                      } else {
                        echo '<em class="text-muted">Por definir</em>';
                      }
                      ?>
                    </span>
                    <?php if ($isCompleted && $winnerId == $m['player2_id']): ?>
                      <span class="text-warning ms-1" title="Ganador">👑</span>
                    <?php endif; ?>
                  </div>
                  <div class="player-score-badge">
                    <?php echo $isCompleted ? $m['score2'] : '-'; ?>
                  </div>
                </div>

                <!-- Botón de Acción -->
                <div class="match-footer-action">
                  <?php if ($canRecord): ?>
                    <button 
                      class="btn btn-sm <?php echo $isCompleted ? 'btn-outline-secondary' : 'btn-game-primary'; ?> w-100 btn-open-match-modal py-1"
                      data-match-id="<?php echo $m['id']; ?>"
                      data-category="<?php echo $category; ?>"
                      data-round-name="<?php echo htmlspecialchars($rData['name']); ?>"
                      data-match-number="<?php echo $m['match_number']; ?>"
                      data-p1-id="<?php echo $p1['id']; ?>"
                      data-p1-name="<?php echo htmlspecialchars($p1['name']); ?>"
                      data-p1-alias="<?php echo htmlspecialchars($p1['alias'] ?? ''); ?>"
                      data-p1-score="<?php echo $m['score1']; ?>"
                      data-p2-id="<?php echo $p2['id']; ?>"
                      data-p2-name="<?php echo htmlspecialchars($p2['name']); ?>"
                      data-p2-alias="<?php echo htmlspecialchars($p2['alias'] ?? ''); ?>"
                      data-p2-score="<?php echo $m['score2']; ?>"
                      data-winner-id="<?php echo $m['winner_id']; ?>"
                      data-is-final="<?php echo $isFinalMatch ? '1' : '0'; ?>"
                    >
                      <?php if ($isCompleted): ?>
                        <i class="bi bi-pencil-square me-1"></i> Modificar Marcador
                      <?php else: ?>
                        <i class="bi bi-trophy-fill me-1"></i> <?php echo $isFinalMatch ? '¡Jugar Gran Final!' : 'Cargar Marcador y Avanzar'; ?>
                      <?php endif; ?>
                    </button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-light w-100 text-muted py-1" disabled>
                      <i class="bi bi-lock-fill me-1"></i> Esperando Rivales
                    </button>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>

          </div>
        <?php endforeach; ?>

      </div>
    </div>

  <?php endif; ?>

</div>

<!-- ====================================================================
     MODAL ESTÁNDAR DE REGISTRO MANUAL DE RESULTADOS (RONDAS PREVIAS)
     ==================================================================== -->
<div class="modal fade" id="modalRecordScore" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <form id="formRecordScore">
        <input type="hidden" name="match_id" id="modalMatchId">
        
        <div class="modal-header border-bottom bg-light rounded-top-4">
          <div>
            <h5 class="modal-title fw-bold text-dark mb-0">Cargar Marcador Oficial</h5>
            <small class="text-muted font-monospace" id="modalMatchSubtitle">Eliminatorias</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body p-4">
          
          <div class="row align-items-center text-center g-3">
            
            <!-- Jugador 1 -->
            <div class="col-5">
              <div class="p-3 bg-light rounded-3 border">
                <div class="fw-bold text-dark mb-2 text-truncate" id="modalP1Name">Jugador 1</div>
                <label class="text-muted small font-monospace d-block mb-1"><?php echo htmlspecialchars($scoreLabel); ?></label>
                <input type="number" name="score1" id="matchInputScore1" class="form-control form-control-lg text-center fw-bold text-primary" min="0" value="0">
              </div>
            </div>

            <!-- VS -->
            <div class="col-2">
              <span class="badge bg-secondary px-2 py-1 font-monospace">VS</span>
            </div>

            <!-- Jugador 2 -->
            <div class="col-5">
              <div class="p-3 bg-light rounded-3 border">
                <div class="fw-bold text-dark mb-2 text-truncate" id="modalP2Name">Jugador 2</div>
                <label class="text-muted small font-monospace d-block mb-1"><?php echo htmlspecialchars($scoreLabel); ?></label>
                <input type="number" name="score2" id="matchInputScore2" class="form-control form-control-lg text-center fw-bold text-primary" min="0" value="0">
              </div>
            </div>

          </div>

          <!-- Selector de quién avanza -->
          <div class="mt-4 p-3 bg-light rounded-3 border">
            <label class="form-label fw-bold text-dark font-monospace d-block mb-1">
              👑 ¿Quién avanza a la siguiente ronda? (Manual o Automático)
            </label>
            <select name="winner_id" id="matchSelectWinner" class="form-select border-primary fw-bold py-2">
              <!-- Se llena con JS -->
            </select>
            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
              El jugador seleccionado avanzará inmediatamente al casillero de la próxima ronda.
            </small>
          </div>

        </div>

        <div class="modal-footer border-top bg-light rounded-bottom-4 justify-content-between">
          <button type="button" class="btn btn-outline-danger btn-sm btn-reset-match" id="btnModalResetMatch" data-match-id="">
            <i class="bi bi-arrow-counterclockwise"></i> Reabrir Partido
          </button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-game-primary">
              <i class="bi bi-check2-circle me-1"></i> Guardar y Avanzar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (!$isFC26): ?>
<!-- ====================================================================
     MODAL DE GRAN FINAL SUPREMA: MORTAL KOMBAT 1 (HUD DE PELEA)
     ==================================================================== -->
<div class="modal fade modal-final-mk1" id="modalFinalMK1" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="formFinalMK1">
        <input type="hidden" name="match_id" id="mk1FinalMatchId">
        <input type="hidden" name="winner_id" id="mk1FinalWinnerId">
        <input type="hidden" name="score1" id="mk1FinalScore1" value="0">
        <input type="hidden" name="score2" id="mk1FinalScore2" value="0">
        <input type="hidden" name="mk_result_type" id="mk1FinalResultType" value="">

        <div class="modal-header d-flex justify-content-between align-items-center py-3">
          <div class="d-flex align-items-center gap-2">
            <span class="fs-2">🐉</span>
            <div>
              <h5 class="modal-title fw-black text-warning text-uppercase mb-0 font-monospace">
                ⚔️ GRAN FINAL SUPREMA &bull; MORTAL KOMBAT 1
              </h5>
              <small class="text-danger font-monospace">REGISTRA LOS PUNTOS / ROUNDS (0 A 2) &bull; RACHA 2-0 = 2 PTS | 2-1 = 1 PT</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4 position-relative">
          
          <!-- Top HUD: Barras de Vida, Medallones de Rounds y Marcador -->
          <div class="row align-items-center g-3 mb-4">
            
            <!-- Luchador 1 -->
            <div class="col-5">
              <div class="fighter-card-box text-center fighter-box-1" id="boxMk1P1">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-danger text-uppercase font-monospace px-2 py-0">Luchador 1</span>
                  <div class="mk1-medallions" title="Medallones de rounds ganados">
                    <span class="mk1-medallion" id="mk1MedallionP1_1"></span>
                    <span class="mk1-medallion" id="mk1MedallionP1_2"></span>
                  </div>
                </div>
                
                <div class="mk1-healthbar mb-2">
                  <div class="mk1-healthbar-fill-p1"></div>
                </div>

                <div class="avatar-circle mx-auto mb-2 border border-2 border-danger shadow" style="width: 54px; height: 54px; font-size: 1.3rem; background: #991b1b; color: white;">
                  <span id="mk1Avatar1">P1</span>
                </div>
                <h5 class="fw-bold text-white mb-0 text-truncate" id="mk1FinalP1Name">Luchador 1</h5>
                <small class="text-warning font-monospace d-block text-truncate mb-3" id="mk1FinalP1Alias">@Alias1</small>

                <!-- Selector de Puntos / Rounds Luchador 1 -->
                <label class="small text-uppercase font-monospace text-muted d-block mb-1">Rounds Ganados</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                  <button type="button" class="btn mk1-score-btn" id="btnMkDec1">-</button>
                  <span class="mk1-score-display" id="mk1ScoreDisp1">0</span>
                  <button type="button" class="btn mk1-score-btn" id="btnMkInc1">+</button>
                </div>

                <!-- Chips rápidos -->
                <div class="d-flex justify-content-center gap-1">
                  <span class="mk1-quick-chip" data-target="1" data-val="1">+1 Round</span>
                  <span class="mk1-quick-chip" data-target="1" data-val="2">👑 Ganar 2-0</span>
                </div>
              </div>
            </div>

            <!-- VS Central Flame & Score -->
            <div class="col-2 text-center py-2">
              <div class="mk1-vs-badge mb-1">VS</div>
              <div class="badge bg-dark border border-danger text-warning font-monospace px-2 py-1 fs-6" id="mk1RoundsBadge">
                <span id="mk1RoundsDisplay">0 - 0</span>
              </div>
              <small class="d-block text-muted font-monospace mt-1" style="font-size: 0.65rem;">ROUNDS</small>
            </div>

            <!-- Luchador 2 -->
            <div class="col-5">
              <div class="fighter-card-box text-center fighter-box-2" id="boxMk1P2">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="mk1-medallions" title="Medallones de rounds ganados">
                    <span class="mk1-medallion" id="mk1MedallionP2_1"></span>
                    <span class="mk1-medallion" id="mk1MedallionP2_2"></span>
                  </div>
                  <span class="badge bg-primary text-uppercase font-monospace px-2 py-0">Luchador 2</span>
                </div>

                <div class="mk1-healthbar mb-2">
                  <div class="mk1-healthbar-fill-p2"></div>
                </div>

                <div class="avatar-circle mx-auto mb-2 border border-2 border-info shadow" style="width: 54px; height: 54px; font-size: 1.3rem; background: #1e3a8a; color: white;">
                  <span id="mk1Avatar2">P2</span>
                </div>
                <h5 class="fw-bold text-white mb-0 text-truncate" id="mk1FinalP2Name">Luchador 2</h5>
                <small class="text-info font-monospace d-block text-truncate mb-3" id="mk1FinalP2Alias">@Alias2</small>

                <!-- Selector de Puntos / Rounds Luchador 2 -->
                <label class="small text-uppercase font-monospace text-muted d-block mb-1">Rounds Ganados</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                  <button type="button" class="btn mk1-score-btn btn-p2" id="btnMkDec2">-</button>
                  <span class="mk1-score-display player2" id="mk1ScoreDisp2">0</span>
                  <button type="button" class="btn mk1-score-btn btn-p2" id="btnMkInc2">+</button>
                </div>

                <!-- Chips rápidos -->
                <div class="d-flex justify-content-center gap-1">
                  <span class="mk1-quick-chip chip-p2" data-target="2" data-val="1">+1 Round</span>
                  <span class="mk1-quick-chip chip-p2" data-target="2" data-val="2">👑 Ganar 2-0</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Banner de Puntos y Racha Otorgada -->
          <div class="p-3 rounded-4 mb-2 border" id="mk1PointsBanner" style="background: rgba(22, 3, 3, 0.75); border: 2px solid rgba(239, 68, 68, 0.5) !important; backdrop-filter: blur(8px); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);">
            <div class="row align-items-center">
              <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                  <span class="badge bg-warning text-dark font-monospace fw-bold fs-6 px-3 py-1" id="mk1PointsBadge">⭐ 0 PUNTOS</span>
                  <span class="badge bg-danger text-uppercase font-monospace" id="mk1StreakBadge">COMBATE POR COMENZAR</span>
                </div>
                <div class="small text-light font-monospace" id="mk1PointsExplanation">
                  Sube los puntos con + y - a medida que ganen los rounds (2-0 otorga 2 pts de racha; 2-1 otorga 1 pt).
                </div>
              </div>
              <div class="col-md-5 text-md-end mt-2 mt-md-0">
                <span class="text-muted small font-monospace d-block">Campeón Proyectado:</span>
                <span class="fw-bold text-warning font-monospace fs-6" id="mk1AnnounceWinnerName">Por definir (0 - 0)</span>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer border-top border-danger border-opacity-25 bg-black bg-opacity-70 justify-content-between p-3">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-mk1-action py-2 px-4 fs-6">
            <i class="bi bi-fire me-1"></i> ¡CORONAR CAMPEÓN DE MORTAL KOMBAT 1! 🏆
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
<?php else: ?>
<!-- ====================================================================
     MODAL DE GRAN FINAL: EA SPORTS FC 26 (STADIUM BROADCAST & GOALS)
     ==================================================================== -->
<div class="modal fade modal-final-fc26" id="modalFinalFC26" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="formFinalFC26">
        <input type="hidden" name="match_id" id="fc26FinalMatchId">
        <input type="hidden" name="winner_id" id="fc26FinalWinnerId">
        <input type="hidden" name="score1" id="fc26FinalScore1" value="3">
        <input type="hidden" name="score2" id="fc26FinalScore2" value="1">
        <input type="hidden" name="penalties_score1" id="fc26FinalPenalties1" value="">
        <input type="hidden" name="penalties_score2" id="fc26FinalPenalties2" value="">

        <div class="modal-header d-flex justify-content-between align-items-center py-3">
          <div class="d-flex align-items-center gap-2">
            <span class="fs-2">⚽</span>
            <div>
              <h5 class="modal-title fw-black text-info text-uppercase mb-0 font-monospace">
                ⚽ GRAN FINAL &bull; UEFA CHAMPIONS LEAGUE 2026
              </h5>
              <small class="text-light opacity-75 font-monospace">EA SPORTS FC 26 &bull; PUNTUACIÓN POR GOLES Y DIFERENCIA DE GOLES</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4 position-relative">
          
          <!-- Marcador Digital Estadio UEFA Champions League -->
          <div class="row align-items-center g-3 mb-4">
            
            <!-- Finalista 1 (Local) -->
            <div class="col-md-5">
              <div class="fc26-club-box text-center" id="boxFc26P1">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-primary text-uppercase font-monospace px-2 py-0">Local</span>
                  <span class="badge bg-dark border text-info font-monospace small" id="fc26BadgeDgP1">+2 DG</span>
                </div>

                <div class="avatar-circle mx-auto mb-2 border border-2 border-primary shadow" style="width: 54px; height: 54px; font-size: 1.3rem; background: #0369a1; color: white;">
                  <span id="fc26Avatar1">J1</span>
                </div>
                <h5 class="fw-bold text-white mb-0 text-truncate" id="fc26FinalP1Name">Finalista 1</h5>
                <small class="text-info font-monospace d-block text-truncate mb-3" id="fc26FinalP1Alias">@Alias1</small>
                
                <!-- Contador de Goles P1 -->
                <label class="small text-uppercase font-monospace text-light opacity-75 d-block mb-1">Goles Marcados</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                  <button type="button" class="fc26-goal-btn" id="btnFcDec1">-</button>
                  <span class="fc26-score-display" id="fc26ScoreDisp1">3</span>
                  <button type="button" class="fc26-goal-btn" id="btnFcInc1">+</button>
                </div>

                <!-- Chips de Goles Rápidos -->
                <div class="d-flex justify-content-center gap-1">
                  <span class="fc26-quick-chip" data-target="1" data-val="1">+1 Gol</span>
                  <span class="fc26-quick-chip" data-target="1" data-val="2">+2 Goles</span>
                  <span class="fc26-quick-chip" data-target="1" data-val="3">+3 Goles</span>
                </div>
              </div>
            </div>

            <!-- Centro: Starball & Emblema UCL -->
            <div class="col-md-2 text-center py-2">
              <div class="display-6 mb-1 text-warning">⭐</div>
              <div class="badge bg-dark border border-info text-info font-monospace px-2 py-1 mb-1">
                90 MIN
              </div>
              <span class="badge bg-primary text-uppercase px-2 py-0 font-monospace small" style="font-size: 0.65rem;">FINAL</span>
            </div>

            <!-- Finalista 2 (Visitante) -->
            <div class="col-md-5">
              <div class="fc26-club-box text-center" id="boxFc26P2">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="badge bg-dark border text-info font-monospace small" id="fc26BadgeDgP2">-2 DG</span>
                  <span class="badge bg-info text-dark text-uppercase font-monospace px-2 py-0">Visitante</span>
                </div>

                <div class="avatar-circle mx-auto mb-2 border border-2 border-info shadow" style="width: 54px; height: 54px; font-size: 1.3rem; background: #0284c7; color: white;">
                  <span id="fc26Avatar2">J2</span>
                </div>
                <h5 class="fw-bold text-white mb-0 text-truncate" id="fc26FinalP2Name">Finalista 2</h5>
                <small class="text-info font-monospace d-block text-truncate mb-3" id="fc26FinalP2Alias">@Alias2</small>
                
                <!-- Contador de Goles P2 -->
                <label class="small text-uppercase font-monospace text-light opacity-75 d-block mb-1">Goles Marcados</label>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                  <button type="button" class="fc26-goal-btn" id="btnFcDec2">-</button>
                  <span class="fc26-score-display" id="fc26ScoreDisp2">1</span>
                  <button type="button" class="fc26-goal-btn" id="btnFcInc2">+</button>
                </div>

                <!-- Chips de Goles Rápidos -->
                <div class="d-flex justify-content-center gap-1">
                  <span class="fc26-quick-chip" data-target="2" data-val="1">+1 Gol</span>
                  <span class="fc26-quick-chip" data-target="2" data-val="2">+2 Goles</span>
                  <span class="fc26-quick-chip" data-target="2" data-val="3">+3 Goles</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Panel de Diferencia de Goles y Puntos de Campeonato -->
          <div class="fc26-diff-badge mb-3" id="fc26PointsDashboard">
            <div class="row align-items-center g-2">
              <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                  <span class="badge bg-success text-white font-monospace fs-6 px-3 py-1" id="fc26CalculatedPoints">
                    ⭐ +2 PUNTOS DE CAMPEONATO
                  </span>
                  <span class="badge bg-dark border border-info text-info font-monospace" id="fc26GoalDiffLabel">
                    DIFERENCIA: +2 GOLES (3 GF - 1 GC)
                  </span>
                </div>
                <small class="text-light opacity-90 font-monospace d-block" id="fc26RuleText">
                  Regla del Torneo: Los puntos se obtienen por la diferencia neta de goles (ej: 5 a 3 son 2 pts; 4 a 0 son 4 pts).
                </small>
              </div>
              <div class="col-md-5 text-md-end">
                <span class="text-info small font-monospace d-block">Campeón Proyectado:</span>
                <span class="h6 fw-black text-white font-monospace mb-0" id="fc26AnnounceChampion">Finalista 1</span>
              </div>
            </div>
          </div>

          <!-- Sección de Tanda de Penales (Solo si hay empate) -->
          <div class="fc26-penalties-section mb-2" id="fc26PenaltiesBox" style="display: none;">
            <div class="text-center mb-2">
              <span class="badge bg-warning text-dark font-monospace fw-bold px-3 py-1 text-uppercase">
                ⚽ ¡EMPATE REGISTRADO! DEFINICIÓN EN TANDA DE PENALTIS 🥅
              </span>
              <p class="small text-light opacity-80 mt-1 mb-2">
                Al haber empate en goles reglamentarios, define quién se corona campeón en los lanzamientos desde el punto penal:
              </p>
            </div>

            <div class="row align-items-center text-center g-3">
              <div class="col-5">
                <label class="small text-warning font-monospace d-block mb-1">Penales <span class="fc26-p1-label">J1</span></label>
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <button type="button" class="btn btn-sm btn-outline-warning" id="btnPenDec1">-</button>
                  <span class="h4 fw-bold text-white font-monospace mb-0" id="dispPen1">5</span>
                  <button type="button" class="btn btn-sm btn-outline-warning" id="btnPenInc1">+</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2 font-monospace fw-bold btn-select-pen-winner" data-slot="1">
                  👑 Ganó en Penales
                </button>
              </div>

              <div class="col-2">
                <span class="text-warning fw-bold font-monospace">PENALTIS</span>
              </div>

              <div class="col-5">
                <label class="small text-warning font-monospace d-block mb-1">Penales <span class="fc26-p2-label">J2</span></label>
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <button type="button" class="btn btn-sm btn-outline-warning" id="btnPenDec2">-</button>
                  <span class="h4 fw-bold text-white font-monospace mb-0" id="dispPen2">4</span>
                  <button type="button" class="btn btn-sm btn-outline-warning" id="btnPenInc2">+</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2 font-monospace fw-bold btn-select-pen-winner" data-slot="2">
                  👑 Ganó en Penales
                </button>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer border-top border-info border-opacity-25 bg-black bg-opacity-70 justify-content-between p-3">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-fc26-action py-2 px-4 fs-6">
            <i class="bi bi-trophy-fill me-1"></i> ¡LEVANTAR LA OREJONA Y CORONAR AL CAMPEÓN! 🏆
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ====================================================================
     MODAL DE CELEBRACIÓN DEL CAMPEÓN CON FUEGOS ARTIFICIALES
     ==================================================================== -->
<canvas id="canvasCelebrationFireworks"></canvas>

<div class="modal fade modal-champion-celebration" id="modalChampionCelebration" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content text-center p-4">
      
      <!-- Trofeo Flotante 3D -->
      <div class="celebration-trophy-anim mb-2">🏆</div>
      
      <div class="mb-2">
        <span class="badge bg-warning text-dark px-3 py-1 font-monospace text-uppercase" id="celebTopBadge">
          ¡CAMPEÓN ABSOLUTO UPTPC 2026!
        </span>
      </div>

      <h4 class="text-uppercase fw-bold text-light mb-1 font-monospace" id="celebGameTitle">
        <?php echo $isFC26 ? 'UEFA CHAMPIONS LEAGUE • EA FC 26' : 'MORTAL KOMBAT 1'; ?>
      </h4>

      <h1 class="display-3 glow-champion-name mb-2" id="celebChampionName">
        <?php echo htmlspecialchars($champion['name'] ?? 'CAMPEÓN'); ?>
      </h1>

      <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
        <span class="badge bg-danger fs-6 px-3 py-1 rounded-pill font-monospace shadow" id="celebChampionAlias">
          <?php echo !empty($champion['alias']) ? 'GamerTag: ' . htmlspecialchars($champion['alias']) : 'GamerTag Oficial'; ?>
        </span>
        <span class="badge bg-dark border text-light fs-6 px-3 py-1 font-monospace" id="celebScoreText">
          <?php echo !empty($finalMatch) ? "Marcador: {$finalMatch['score1']} - {$finalMatch['score2']}" : "Victoria de Campeonato"; ?>
        </span>
      </div>

      <p class="text-light opacity-90 mx-auto mb-4" style="max-width: 580px;" id="celebSubtitle">
        <?php if ($isFC26): ?>
          ¡Ha levantado la Orejona y se corona con la Gloria Eterna en las Olimpiadas Gaming UPTPC 2026!
        <?php else: ?>
          ¡Ha conquistado el torneo supremo con Flawless Victory en las Olimpiadas Gaming UPTPC 2026!
        <?php endif; ?>
      </p>

      <!-- Botones de Acción de la Celebración -->
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <button type="button" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow" id="btnLaunchMoreFireworks">
          🎆 ¡Lanzar Más Fuegos Artificiales! 🎉
        </button>
        <button type="button" class="btn btn-outline-light px-4 py-2 rounded-pill shadow" data-bs-dismiss="modal" id="btnCloseCelebration">
          🏆 Ver Podio y Cuadro Completo
        </button>
      </div>

      <!-- Barra de Música en la Celebración -->
      <div class="mt-4 pt-3 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center text-start flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 text-warning small font-monospace">
          <div class="audio-equalizer">
            <span></span><span></span><span></span><span></span>
          </div>
          <span id="celebMusicStatus">🔊 Sonando la música oficial del torneo a todo volumen</span>
        </div>

        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" id="btnToggleCelebMusic">
          <i class="bi bi-pause-fill"></i> Pausar
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ====================================================================
     CONTROLADOR DE AUDIO EN VIVO Y CELEBRACIÓN DE LA FINAL
     ==================================================================== -->
<script>
// Motor de Fuegos Artificiales y Confetti en Canvas Puro
class CelebrationFireworks {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.particles = [];
    this.rockets = [];
    this.confetti = [];
    this.running = false;
    this.animId = null;
    this.themeColors = ['#f59e0b', '#ef4444', '#38bdf8', '#10b981', '#f43f5e', '#a855f7', '#fbbf24'];

    this.resize = this.resize.bind(this);
    this.loop = this.loop.bind(this);
    window.addEventListener('resize', this.resize);
    this.resize();
  }

  resize() {
    if (!this.canvas) return;
    this.width = this.canvas.width = window.innerWidth;
    this.height = this.canvas.height = window.innerHeight;
  }

  start(category = 'mk1') {
    if (!this.canvas) return;
    this.running = true;
    this.resize();
    this.particles = [];
    this.rockets = [];
    this.confetti = [];

    if (category === 'mk1') {
      this.themeColors = ['#ef4444', '#dc2626', '#f59e0b', '#fbbf24', '#ffedd5', '#f97316'];
    } else {
      this.themeColors = ['#38bdf8', '#0284c7', '#3b82f6', '#facc15', '#ffffff', '#60a5fa'];
    }

    // Oleada inicial de confetti
    for (let i = 0; i < 90; i++) {
      this.spawnConfetti();
    }

    // Salva inicial de fuegos artificiales
    for (let i = 0; i < 6; i++) {
      this.spawnRocket(this.width * (0.15 + 0.7 * Math.random()));
    }

    if (!this.animId) {
      this.loop();
    }
  }

  stop() {
    this.running = false;
    if (this.animId) {
      cancelAnimationFrame(this.animId);
      this.animId = null;
    }
    if (this.ctx) {
      this.ctx.clearRect(0, 0, this.width, this.height);
    }
  }

  spawnRocket(targetX = null) {
    const x = targetX ?? (this.width * (0.15 + 0.7 * Math.random()));
    const y = this.height;
    const targetY = this.height * (0.15 + 0.35 * Math.random());
    const speed = 12 + Math.random() * 4;
    const angle = -Math.PI / 2 + (Math.random() - 0.5) * 0.25;
    const color = this.themeColors[Math.floor(Math.random() * this.themeColors.length)];

    this.rockets.push({
      x, y,
      vx: Math.cos(angle) * speed,
      vy: Math.sin(angle) * speed,
      targetY,
      color,
      trail: []
    });
  }

  explodeRocket(rocket) {
    const count = 70 + Math.floor(Math.random() * 40);
    for (let i = 0; i < count; i++) {
      const angle = Math.random() * Math.PI * 2;
      const speed = 2 + Math.random() * 7;
      const color = this.themeColors[Math.floor(Math.random() * this.themeColors.length)];
      this.particles.push({
        x: rocket.x,
        y: rocket.y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        alpha: 1,
        color: color,
        size: 2 + Math.random() * 3,
        decay: 0.012 + Math.random() * 0.018,
        gravity: 0.12
      });
    }
  }

  spawnConfetti() {
    this.confetti.push({
      x: Math.random() * this.width,
      y: -20 - Math.random() * 100,
      w: 8 + Math.random() * 8,
      h: 5 + Math.random() * 6,
      vx: (Math.random() - 0.5) * 2,
      vy: 2 + Math.random() * 3,
      angle: Math.random() * 360,
      vAngle: (Math.random() - 0.5) * 10,
      color: this.themeColors[Math.floor(Math.random() * this.themeColors.length)]
    });
  }

  burstMultiple(num = 4) {
    for (let i = 0; i < num; i++) {
      this.spawnRocket(this.width * (0.1 + 0.8 * Math.random()));
    }
    for (let i = 0; i < 30; i++) {
      this.spawnConfetti();
    }
  }

  loop() {
    if (!this.running && this.particles.length === 0 && this.rockets.length === 0 && this.confetti.length === 0) {
      this.animId = null;
      return;
    }

    this.ctx.clearRect(0, 0, this.width, this.height);

    // Generar cohetes aleatorios mientras esté activo
    if (this.running && Math.random() < 0.07 && this.rockets.length < 6) {
      this.spawnRocket();
    }
    if (this.running && Math.random() < 0.3 && this.confetti.length < 120) {
      this.spawnConfetti();
    }

    // Actualizar y dibujar cohetes
    for (let i = this.rockets.length - 1; i >= 0; i--) {
      const r = this.rockets[i];
      r.x += r.vx;
      r.y += r.vy;
      r.trail.push({ x: r.x, y: r.y });
      if (r.trail.length > 5) r.trail.shift();

      this.ctx.strokeStyle = r.color;
      this.ctx.lineWidth = 3;
      this.ctx.beginPath();
      for (let t = 0; t < r.trail.length; t++) {
        if (t === 0) this.ctx.moveTo(r.trail[t].x, r.trail[t].y);
        else this.ctx.lineTo(r.trail[t].x, r.trail[t].y);
      }
      this.ctx.stroke();

      if (r.vy >= 0 || r.y <= r.targetY) {
        this.explodeRocket(r);
        this.rockets.splice(i, 1);
      }
    }

    // Actualizar y dibujar chispas de fuegos artificiales
    for (let i = this.particles.length - 1; i >= 0; i--) {
      const p = this.particles[i];
      p.x += p.vx;
      p.y += p.vy;
      p.vy += p.gravity;
      p.vx *= 0.98;
      p.alpha -= p.decay;

      if (p.alpha <= 0) {
        this.particles.splice(i, 1);
        continue;
      }

      this.ctx.save();
      this.ctx.globalAlpha = Math.max(0, p.alpha);
      this.ctx.fillStyle = p.color;
      this.ctx.beginPath();
      this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      this.ctx.fill();
      this.ctx.restore();
    }

    // Actualizar y dibujar confetti en caída
    for (let i = this.confetti.length - 1; i >= 0; i--) {
      const c = this.confetti[i];
      c.x += c.vx;
      c.y += c.vy;
      c.angle += c.vAngle;

      if (c.y > this.height + 20) {
        if (this.running) {
          c.y = -20;
          c.x = Math.random() * this.width;
        } else {
          this.confetti.splice(i, 1);
          continue;
        }
      }

      this.ctx.save();
      this.ctx.translate(c.x, c.y);
      this.ctx.rotate((c.angle * Math.PI) / 180);
      this.ctx.fillStyle = c.color;
      this.ctx.fillRect(-c.w / 2, -c.h / 2, c.w, c.h);
      this.ctx.restore();
    }

    this.animId = requestAnimationFrame(this.loop);
  }
}

// Inicialización de Sonido y Fuegos Artificiales
document.addEventListener('DOMContentLoaded', () => {
  const category = '<?php echo $category; ?>';
  const finalistsArrived = <?php echo $finalistsArrived ? 'true' : 'false'; ?>;
  const isChampionPresent = <?php echo $champion ? 'true' : 'false'; ?>;

  const celebrationFireworks = new CelebrationFireworks('canvasCelebrationFireworks');
  window.celebrationFireworks = celebrationFireworks;

  // Instancia de audio global
  let audio1 = null;      // finish-him.mp3 (MK1)
  let audioLoop = null;   // mk_theme.mp3 (MK1) o champions_league.mp3 (FC26)
  let isPlaying = false;
  let isMuted = false;

  const btnToggle = document.getElementById('btnToggleFinalAudio');
  const btnRestart = document.getElementById('btnRestartFinalAudio');
  const btnMute = document.getElementById('btnMuteFinalAudio');
  const iconPlayPause = document.getElementById('iconPlayPause');
  const textPlayPause = document.getElementById('textPlayPause');
  const iconMute = document.getElementById('iconMute');
  const statusText = document.getElementById('audioStatusText');
  const badgeState = document.getElementById('badgeAudioState');
  const equalizer = document.getElementById('equalizerAnim');

  function updateEq(playing) {
    if (!equalizer) return;
    if (playing) equalizer.classList.remove('paused');
    else equalizer.classList.add('paused');
  }

  if (category === 'mk1') {
    audio1 = new Audio('assets/audio/finish-him.mp3');
    audioLoop = new Audio('assets/audio/mk_theme.mp3');
    audioLoop.loop = true;

    audio1.addEventListener('ended', () => {
      if (audioLoop) {
        audioLoop.currentTime = 0;
        audioLoop.play().then(() => {
          isPlaying = true;
          updateEq(true);
          if (iconPlayPause) iconPlayPause.className = 'bi bi-pause-fill';
          if (textPlayPause) textPlayPause.textContent = 'Pausar';
          if (statusText) statusText.textContent = '🔊 Sonando: Techno Syndrome (Mortal Kombat) [En Bucle]';
          if (badgeState) badgeState.innerHTML = '<i class="bi bi-disc text-warning"></i> Techno Syndrome en Vivo';
        }).catch(err => console.warn('Loop playback blocked:', err));
      }
    });
  } else {
    audioLoop = new Audio('assets/audio/champions_league.mp3');
    audioLoop.loop = true;
  }

  function startPlayback() {
    isPlaying = true;
    updateEq(true);
    if (iconPlayPause) iconPlayPause.className = 'bi bi-pause-fill';
    if (textPlayPause) textPlayPause.textContent = 'Pausar';

    if (category === 'mk1') {
      if (statusText) statusText.textContent = '🔊 Sonando: "Finish Him!"...';
      if (badgeState) badgeState.innerHTML = '<i class="bi bi-lightning-charge-fill text-danger"></i> ¡FINISH HIM!';
      
      const p = audio1.play();
      if (p !== undefined) {
        p.catch(err => {
          isPlaying = false;
          updateEq(false);
          if (iconPlayPause) iconPlayPause.className = 'bi bi-play-fill';
          if (textPlayPause) textPlayPause.textContent = '▶️ Iniciar';
          if (statusText) statusText.textContent = '⚠️ Haz clic para activar Finish Him y la música';
        });
      }
    } else {
      if (statusText) statusText.textContent = '🔊 Sonando: Himno Oficial UEFA Champions League [En Bucle]';
      if (badgeState) badgeState.innerHTML = '<i class="bi bi-trophy-fill text-warning"></i> Himno Champions en Vivo';

      const p = audioLoop.play();
      if (p !== undefined) {
        p.catch(err => {
          isPlaying = false;
          updateEq(false);
          if (iconPlayPause) iconPlayPause.className = 'bi bi-play-fill';
          if (textPlayPause) textPlayPause.textContent = '▶️ Iniciar';
          if (statusText) statusText.textContent = '⚠️ Haz clic para activar el Himno de Champions';
        });
      }
    }
  }

  function pausePlayback() {
    isPlaying = false;
    updateEq(false);
    if (audio1 && !audio1.paused) audio1.pause();
    if (audioLoop && !audioLoop.paused) audioLoop.pause();
    if (iconPlayPause) iconPlayPause.className = 'bi bi-play-fill';
    if (textPlayPause) textPlayPause.textContent = 'Reanudar';
    if (statusText) statusText.textContent = '⏸ Música en Pausa';
  }

  function restartPlayback() {
    if (audio1) { audio1.pause(); audio1.currentTime = 0; }
    if (audioLoop) { audioLoop.pause(); audioLoop.currentTime = 0; }
    startPlayback();
  }

  window.playTournamentAudio = startPlayback;
  window.pauseTournamentAudio = pausePlayback;

  if (btnToggle) {
    btnToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (isPlaying) pausePlayback();
      else {
        if (category === 'mk1' && audioLoop && audioLoop.currentTime > 0) {
          audioLoop.play();
          isPlaying = true;
          updateEq(true);
        } else {
          startPlayback();
        }
      }
    });
  }

  if (btnRestart) {
    btnRestart.addEventListener('click', (e) => {
      e.stopPropagation();
      restartPlayback();
    });
  }

  if (btnMute) {
    btnMute.addEventListener('click', (e) => {
      e.stopPropagation();
      isMuted = !isMuted;
      if (audio1) audio1.muted = isMuted;
      if (audioLoop) audioLoop.muted = isMuted;
      if (iconMute) {
        iconMute.className = isMuted ? 'bi bi-volume-mute-fill text-danger' : 'bi bi-volume-up-fill';
      }
    });
  }

  // Desbloqueo garantizado de audio por primer clic en la pantalla
  if (finalistsArrived) {
    const unlockAudioOnFirstClick = () => {
      if (!isPlaying) startPlayback();
      document.removeEventListener('click', unlockAudioOnFirstClick);
      document.removeEventListener('keydown', unlockAudioOnFirstClick);
    };
    document.addEventListener('click', unlockAudioOnFirstClick);
    document.addEventListener('keydown', unlockAudioOnFirstClick);
    startPlayback();
  }

  // Función global para disparar la Celebración del Campeón con Fuegos Artificiales
  window.triggerChampionCelebration = function(champion, cat, score1 = '', score2 = '') {
    if (!champion) return;
    
    // Asegurar que la música de fondo esté sonando a todo volumen
    if (audioLoop) {
      audioLoop.muted = false;
      audioLoop.play().catch(e => console.log(e));
    }

    const celebModalEl = document.getElementById('modalChampionCelebration');
    if (!celebModalEl) return;

    celebModalEl.classList.remove('celebration-mk1', 'celebration-fc26');
    celebModalEl.classList.add(cat === 'mk1' ? 'celebration-mk1' : 'celebration-fc26');

    // Actualizar datos del campeón
    document.getElementById('celebChampionName').textContent = champion.name;
    document.getElementById('celebChampionAlias').textContent = champion.alias ? 'GamerTag: ' + champion.alias : 'Campeón Oficial';
    if (score1 !== '' && score2 !== '') {
      document.getElementById('celebScoreText').textContent = `Marcador Final: ${score1} - ${score2}`;
    }

    if (cat === 'mk1') {
      document.getElementById('celebTopBadge').textContent = '🐉 ¡FLAWLESS VICTORY! 🐉';
      document.getElementById('celebGameTitle').textContent = 'CAMPEÓN SUPREMO DE MORTAL KOMBAT 1';
      document.getElementById('celebSubtitle').textContent = '¡Ha conquistado el torneo de Mortal Kombat 1 y reina victorioso en Earthrealm!';
      document.getElementById('celebMusicStatus').textContent = '🔊 Sonando a todo volumen: Techno Syndrome (Mortal Kombat)';
    } else {
      document.getElementById('celebTopBadge').textContent = '⭐ ¡GLORIA ETERNA UEFA CHAMPIONS LEAGUE! ⭐';
      document.getElementById('celebGameTitle').textContent = 'CAMPEÓN DE LA COPA EA SPORTS FC 26';
      document.getElementById('celebSubtitle').textContent = '¡Ha levantado la Orejona y entra en la historia grande del torneo UPTPC 2026!';
      document.getElementById('celebMusicStatus').textContent = '🔊 Sonando a todo volumen: Himno Oficial UEFA Champions League';
    }

    // Iniciar fuegos artificiales en el canvas
    celebrationFireworks.start(cat);

    // Abrir modal de celebración
    const celebModal = new bootstrap.Modal(celebModalEl);
    celebModal.show();
  };

  // Botón "Lanzar Más Fuegos Artificiales"
  const btnMoreFireworks = document.getElementById('btnLaunchMoreFireworks');
  if (btnMoreFireworks) {
    btnMoreFireworks.addEventListener('click', () => {
      celebrationFireworks.burstMultiple(5);
    });
  }

  // Al cerrar la celebración, detener fuegos artificiales y hacer scroll al podio
  const celebModalEl = document.getElementById('modalChampionCelebration');
  if (celebModalEl) {
    celebModalEl.addEventListener('hidden.bs.modal', () => {
      celebrationFireworks.stop();
      if (window.__finalMatchCompleted) {
        location.reload();
      } else {
        const podium = document.querySelector('.champion-podium');
        if (podium) {
          podium.scrollIntoView({ behavior: 'smooth' });
        }
      }
    });
  }

  // Botón en el podio existente para revivir la celebración con fuegos artificiales
  const btnShowFireworksPodium = document.getElementById('btnShowFireworksPodium');
  if (btnShowFireworksPodium) {
    btnShowFireworksPodium.addEventListener('click', () => {
      window.triggerChampionCelebration({
        name: '<?php echo htmlspecialchars($champion['name'] ?? ''); ?>',
        alias: '<?php echo htmlspecialchars($champion['alias'] ?? ''); ?>'
      }, category);
    });
  }

  // Botón para pausar música desde el modal de celebración
  const btnToggleCelebMusic = document.getElementById('btnToggleCelebMusic');
  if (btnToggleCelebMusic) {
    btnToggleCelebMusic.addEventListener('click', () => {
      if (audioLoop) {
        if (audioLoop.paused) {
          audioLoop.play();
          btnToggleCelebMusic.innerHTML = '<i class="bi bi-pause-fill"></i> Pausar';
        } else {
          audioLoop.pause();
          btnToggleCelebMusic.innerHTML = '<i class="bi bi-play-fill"></i> Reanudar';
        }
      }
    });
  }

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
