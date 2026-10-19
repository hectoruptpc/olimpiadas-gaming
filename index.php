<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/index.php
$pageTitle = "Panel Principal";
require_once __DIR__ . '/config/db.php';

// Estadísticas de FC26
$stmtFC = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(score), 0) as total_goals FROM participants WHERE category='fc26'");
$statFC = $stmtFC->fetch();
$stmtFCPrelim = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status='completed' THEN 1 END) as completed FROM preliminary_matches WHERE category='fc26'");
$statFCPrelim = $stmtFCPrelim->fetch();
$stmtFCBracket = $pdo->query("SELECT COUNT(*) as matches, COUNT(CASE WHEN status='completed' THEN 1 END) as completed FROM bracket_matches WHERE category='fc26'");
$statFCBracket = $stmtFCBracket->fetch();

// Estadísticas de MK1
$stmtMK = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(score), 0) as total_fights FROM participants WHERE category='mk1'");
$statMK = $stmtMK->fetch();
$stmtMKPrelim = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status='completed' THEN 1 END) as completed FROM preliminary_matches WHERE category='mk1'");
$statMKPrelim = $stmtMKPrelim->fetch();
$stmtMKBracket = $pdo->query("SELECT COUNT(*) as matches, COUNT(CASE WHEN status='completed' THEN 1 END) as completed FROM bracket_matches WHERE category='mk1'");
$statMKBracket = $stmtMKBracket->fetch();

// Categoría seleccionada por defecto si viene parámetro
$category = $_GET['category'] ?? 'fc26';
require_once __DIR__ . '/includes/header.php';
?>

<!-- BANNER DE BIENVENIDA CON LOGO DE LA UNIVERSIDAD -->
<div class="light-card p-4 p-md-5 mb-4 text-center position-relative overflow-hidden">
  <div class="mb-3 d-inline-flex align-items-center gap-3 p-2 px-3 bg-light rounded-pill border shadow-sm">
    <img src="assets/img/uptpc.png" alt="Logo UPTPC" style="height: 38px; width: auto;">
    <span class="fw-bold text-dark font-monospace small">UPTPC &bull; SEDE CENTRAL DE DEPORTES ELECTRÓNICOS</span>
  </div>
  <h1 class="display-6 fw-bold text-dark mb-2">
    OLIMPIADAS GAMING UPTPC 2026
  </h1>
  <p class="text-secondary max-w-lg mx-auto mb-0" style="max-width: 680px;">
    Universidad Politécnica Territorial de Puerto Cabello. Sistema de tres fases: <strong>1. Inscripción de Participantes</strong>, <strong>2. Fase Aleatoria con Sorteo de Partidos</strong> y <strong>3. Árbol de Eliminatorias Directas</strong> con cruce Mejor contra Peor (Grupo A vs Grupo B).
  </p>
</div>

<!-- CUADRÍCULA DE LAS DOS DISCIPLINAS PRINCIPALES (FC26 Y MORTAL KOMBAT 1) -->
<div class="row g-4 mb-4">
  
  <!-- TARJETA 1: EA SPORTS FC 26 -->
  <div class="col-lg-6">
    <div class="light-card h-100 p-4 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-top: 4px solid var(--fc-primary);">
      
      <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1 font-monospace">
            ⚽ Fútbol Digital
          </span>
          <span class="badge bg-light text-secondary border font-monospace">
            Puntos por Diferencia de Goles
          </span>
        </div>

        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="brand-orb fs-2" style="background: #e0f2fe; color: #0284c7;">
            ⚽
          </div>
          <div>
            <h2 class="h4 fw-bold text-dark mb-0">EA Sports FC 26</h2>
            <small class="text-muted">Torneo de fútbol virtual en PlayStation y PC</small>
          </div>
        </div>

        <p class="text-secondary small mb-4">
          Inscribe participantes ilimitados, sortea los partidos preliminares automáticamente y obtén los puntos por Diferencia de Goles (con desempate por goles a favor) para clasificar al Top en Grupo A y B.
        </p>

        <!-- Métricas Rápidas -->
        <div class="row g-2 mb-4 text-center">
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Inscritos</small>
              <strong class="text-dark fs-5"><?php echo $statFC['total']; ?></strong>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Fase Aleatoria</small>
              <strong class="text-primary fs-5"><?php echo $statFCPrelim['completed']; ?>/<?php echo $statFCPrelim['total']; ?></strong>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Playoffs</small>
              <strong class="text-success fs-5"><?php echo $statFCBracket['completed']; ?>/<?php echo $statFCBracket['matches']; ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones en 3 Pasos -->
      <div class="d-flex flex-column gap-2 pt-2 border-top">
        <div class="d-flex gap-2">
          <a href="participantes.php?category=fc26" class="btn btn-outline-secondary flex-fill fw-bold py-2">
            <i class="bi bi-people-fill me-1"></i> 1. Participantes
          </a>
          <a href="fase_aleatoria.php?category=fc26" class="btn btn-outline-primary flex-fill fw-bold py-2">
            <i class="bi bi-shuffle me-1"></i> 2. Fase Aleatoria
          </a>
        </div>
        <a href="brackets.php?category=fc26" class="btn btn-primary fw-bold py-2" style="background: #0284c7; border-color: #0284c7;">
          <i class="bi bi-diagram-3-fill me-1"></i> 3. Árbol de Eliminatorias
        </a>
      </div>

    </div>
  </div>

  <!-- TARJETA 2: MORTAL KOMBAT 1 -->
  <div class="col-lg-6">
    <div class="light-card h-100 p-4 d-flex flex-column justify-content-between position-relative overflow-hidden" style="border-top: 4px solid var(--mk-primary);">
      
      <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 font-monospace">
            🐉 Kombat & Lucha
          </span>
          <span class="badge bg-light text-secondary border font-monospace">
            Puntos por Rounds y Tiempo
          </span>
        </div>

        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="brand-orb fs-2" style="background: #fee2e2; color: #dc2626;">
            🐉
          </div>
          <div>
            <h2 class="h4 fw-bold text-dark mb-0">Mortal Kombat 1</h2>
            <small class="text-muted">Torneo de lucha y kameos en eliminación directa</small>
          </div>
        </div>

        <p class="text-secondary small mb-4">
          La puntuación se define por victorias 2-0 (+2 pts) o 2-1 (+1 pt) con desempate por menor tiempo. Los clasificados entran a las llaves cruzando al mejor contra el peor.
        </p>

        <!-- Métricas Rápidas -->
        <div class="row g-2 mb-4 text-center">
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Inscritos</small>
              <strong class="text-dark fs-5"><?php echo $statMK['total']; ?></strong>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Fase Aleatoria</small>
              <strong class="text-danger fs-5"><?php echo $statMKPrelim['completed']; ?>/<?php echo $statMKPrelim['total']; ?></strong>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded-3 border">
              <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">Playoffs</small>
              <strong class="text-success fs-5"><?php echo $statMKBracket['completed']; ?>/<?php echo $statMKBracket['matches']; ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones en 3 Pasos -->
      <div class="d-flex flex-column gap-2 pt-2 border-top">
        <div class="d-flex gap-2">
          <a href="participantes.php?category=mk1" class="btn btn-outline-secondary flex-fill fw-bold py-2">
            <i class="bi bi-people-fill me-1"></i> 1. Participantes
          </a>
          <a href="fase_aleatoria.php?category=mk1" class="btn btn-outline-danger flex-fill fw-bold py-2">
            <i class="bi bi-shuffle me-1"></i> 2. Fase Aleatoria
          </a>
        </div>
        <a href="brackets.php?category=mk1" class="btn btn-danger fw-bold py-2" style="background: #dc2626; border-color: #dc2626;">
          <i class="bi bi-diagram-3-fill me-1"></i> 3. Árbol de Eliminatorias
        </a>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
