<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/participantes.php
$pageTitle = "1. Participantes Inscritos";
require_once __DIR__ . '/includes/header.php';

// Consultar participantes registrados en la categoría
$stmt = $pdo->prepare("SELECT * FROM participants WHERE category = ? ORDER BY id DESC");
$stmt->execute([$category]);
$participants = $stmt->fetchAll();
$totalCount = count($participants);

// Consultar cantidad de partidos generados en la fase aleatoria
$stmtMatches = $pdo->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN status='completed' THEN 1 END) as completed FROM preliminary_matches WHERE category = ?");
$stmtMatches->execute([$category]);
$matchStats = $stmtMatches->fetch() ?: ['total' => 0, 'completed' => 0];
?>

<!-- BANNER HERO DEL PASO 1: PARTICIPANTES -->
<div class="light-card p-4 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <div class="d-flex align-items-center gap-2 mb-2">
        <img src="assets/img/uptpc.png" alt="UPTPC" style="height: 28px; width: auto;" class="me-1">
        <span class="badge <?php echo $isFC26 ? 'bg-primary' : 'bg-danger'; ?> text-white text-uppercase px-3 py-1 font-monospace">
          PASO 1 &bull; REGISTRO DE COMPETIDORES
        </span>
        <span class="badge bg-light text-secondary border font-monospace">
          Capacidad Ilimitada
        </span>
      </div>

      <h1 class="h3 fw-bold text-dark mb-2">
        Participantes Inscritos &bull; <?php echo htmlspecialchars($catTitle); ?>
      </h1>
      <p class="text-secondary mb-0" style="max-width: 680px;">
        Inscribe a todos los estudiantes y participantes sin límite. Una vez cerrada la inscripción, pulsa en <strong>"Ir a Fase Aleatoria"</strong> para que el sistema genere automáticamente los enfrentamientos.
      </p>
    </div>

    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <div class="d-flex flex-column gap-2 justify-content-lg-end">
        <a href="fase_aleatoria.php?category=<?php echo $category; ?>" class="btn btn-game-primary py-2 fw-bold shadow-sm">
          <i class="bi bi-shuffle me-1"></i> Ir a Fase Aleatoria ➔
        </a>

        <div class="d-flex gap-2 justify-content-lg-end">
          <button class="btn btn-outline-dark btn-sm rounded-3 flex-fill" data-bs-toggle="modal" data-bs-target="#modalAddParticipant">
            <i class="bi bi-person-plus-fill me-1"></i> Inscribir Jugador
          </button>
          <button class="btn btn-outline-secondary btn-sm rounded-3" id="btnSeedDemo" data-category="<?php echo $category; ?>" title="Cargar participantes de prueba">
            <i class="bi bi-magic"></i> Demo
          </button>
          <?php if (!empty($participants)): ?>
            <button class="btn btn-outline-danger btn-sm rounded-3" id="btnClearParticipants" data-category="<?php echo $category; ?>" title="Eliminar todos los participantes">
              <i class="bi bi-trash3"></i> Vaciar
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MÉTRICAS RÁPIDAS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm">👥</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Total Inscritos</small>
        <div class="h4 fw-bold mb-0 text-dark"><?php echo $totalCount; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm"><?php echo $catIcon; ?></div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Disciplina</small>
        <div class="h6 fw-bold mb-0 text-dark"><?php echo $isFC26 ? 'EA FC 26' : 'Mortal Kombat 1'; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm">🎲</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Partidos Sorteados</small>
        <div class="h4 fw-bold mb-0 text-primary"><?php echo $matchStats['total']; ?></div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="light-card p-3 d-flex align-items-center gap-3">
      <div class="brand-orb fs-4 shadow-sm">⚡</div>
      <div>
        <small class="text-muted text-uppercase font-monospace" style="font-size: 0.72rem;">Siguiente Paso</small>
        <a href="fase_aleatoria.php?category=<?php echo $category; ?>" class="small fw-bold text-decoration-none d-block text-truncate">
          Fase Aleatoria &raquo;
        </a>
      </div>
    </div>
  </div>
</div>

<!-- LISTA DE PARTICIPANTES -->
<div class="light-card p-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <h2 class="h5 fw-bold text-dark mb-1">Listado Oficial de Competidores</h2>
      <p class="text-muted small mb-0">
        Revisa o elimina participantes antes del sorteo de cruces.
      </p>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-game-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAddParticipant">
        <i class="bi bi-person-plus-fill me-1"></i> Inscribir Nuevo
      </button>
      <a href="fase_aleatoria.php?category=<?php echo $category; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
        <i class="bi bi-shuffle me-1"></i> Ir a Fase Aleatoria ➔
      </a>
    </div>
  </div>

  <?php if (empty($participants)): ?>
    <div class="text-center py-5">
      <div class="display-5 text-muted mb-3"><?php echo $catIcon; ?></div>
      <h3 class="h5 fw-bold text-dark">No hay participantes registrados</h3>
      <p class="text-secondary mb-4">Inscribe al primer jugador manualmente o carga los datos de demostración para comenzar.</p>
      <div class="d-flex justify-content-center gap-2">
        <button class="btn btn-game-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAddParticipant">
          <i class="bi bi-person-plus-fill me-1"></i> Inscribir Participante
        </button>
        <button class="btn btn-outline-secondary rounded-pill px-4" id="btnSeedDemo" data-category="<?php echo $category; ?>">
          <i class="bi bi-magic me-1"></i> Cargar Demo
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-light-custom align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 70px;">#</th>
            <th>Nombre Completo</th>
            <th>GamerTag / Apodo</th>
            <th class="text-center" style="width: 140px;">Fecha Registro</th>
            <th class="text-center" style="width: 130px;">Estado</th>
            <th class="text-end" style="width: 80px;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $idx = 1;
          foreach ($participants as $p): 
          ?>
            <tr>
              <td class="text-muted font-monospace fw-bold">
                #<?php echo $idx++; ?>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-circle shadow-sm" style="width: 34px; height: 34px; font-size: 0.85rem;">
                    <?php echo mb_substr($p['name'], 0, 1); ?>
                  </div>
                  <div>
                    <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($p['name']); ?></span>
                    <small class="text-muted font-monospace" style="font-size: 0.72rem;">ID: #<?php echo $p['id']; ?></small>
                  </div>
                </div>
              </td>
              <td>
                <?php if (!empty($p['alias'])): ?>
                  <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                    🎮 <?php echo htmlspecialchars($p['alias']); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted fst-italic small">Sin apodo</span>
                <?php endif; ?>
              </td>
              <td class="text-center small text-muted font-monospace">
                <?php echo date('d/m/Y', strtotime($p['created_at'])); ?>
              </td>
              <td class="text-center">
                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
                  <i class="bi bi-check2-circle me-1"></i> Habilitado
                </span>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-danger p-1 rounded-circle btn-delete-participant" data-id="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" title="Eliminar a <?php echo htmlspecialchars($p['name']); ?>" aria-label="Eliminar participante">
                  <i class="bi bi-trash3" style="pointer-events: none;"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
