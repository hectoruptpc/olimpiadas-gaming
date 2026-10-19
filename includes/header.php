<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/tournament_helpers.php';

// Detectar categoría (fc26 o mk1)
$category = $_GET['category'] ?? 'fc26';
if (!in_array($category, ['fc26', 'mk1'])) {
    $category = 'fc26';
}

$isFC26 = ($category === 'fc26');
$catTitle = $isFC26 ? 'EA Sports FC 26' : 'Mortal Kombat 1';
$catIcon = $isFC26 ? '⚽' : '🐉';
$scoreUnit = $isFC26 ? 'Goles Anotados' : 'Peleas Ganadas';
$scoreShort = $isFC26 ? 'Goles' : 'Peleas';
$themeClass = $isFC26 ? 'theme-fc26' : 'theme-mk1';

// Detectar página actual
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$isParticipantesPage = ($scriptName === 'participantes.php');
$isFaseAleatoriaPage = ($scriptName === 'fase_aleatoria.php');
$isBracketsPage = ($scriptName === 'brackets.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Olimpiadas Gaming'); ?> // UPTPC</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'><?php echo $catIcon; ?></text></svg>">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Gaming Light CSS -->
  <link rel="stylesheet" href="assets/css/gaming.css?v=<?php echo time(); ?>">
</head>
<body class="<?php echo $themeClass; ?>">

  <!-- Toast Notification Container -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1200;">
    <div id="liveToast" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-white border-bottom">
        <strong class="me-auto" id="toastTitle">⚡ Notificación</strong>
        <small class="text-muted">Ahora</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body bg-white text-dark" id="toastMessage"></div>
    </div>
  </div>

  <div class="container-fluid px-lg-5 py-4">

    <!-- ====================================================================
         BARRA DE NAVEGACIÓN SUPERIOR (ORGANIZADA & LIMPIA)
         ==================================================================== -->
    <header class="navbar-gaming mb-4">
      <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
        
        <!-- Marca / Logo con Logo de la Universidad -->
        <div class="d-flex align-items-center gap-3">
          <a href="index.php" title="Universidad Politécnica Territorial de Puerto Cabello">
            <img src="assets/img/uptpc.png" alt="Logo UPTPC" class="univ-logo-img">
          </a>
          <div class="vr opacity-25 d-none d-sm-block" style="height: 38px;"></div>
          <div class="brand-orb shadow-sm">
            <?php echo $catIcon; ?>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <a href="index.php" class="text-decoration-none">
                <span class="h5 mb-0 fw-bold text-dark text-uppercase tracking-wider">OLIMPIADAS GAMING</span>
              </a>
              <span class="badge rounded-pill <?php echo $isFC26 ? 'bg-primary text-white' : 'bg-danger text-white'; ?> px-2 py-1" style="font-size: 0.75rem;">
                UPTPC 2026
              </span>
            </div>
            <small class="text-muted font-monospace" style="font-size: 0.72rem;">UNIVERSIDAD POLITÉCNICA TERRITORIAL DE PUERTO CABELLO</small>
          </div>
        </div>

        <!-- Secciones de Páginas Independientes (Flujo en 3 Pasos) -->
        <nav class="d-flex flex-wrap align-items-center gap-2">
          <a class="nav-link-gaming <?php echo $isParticipantesPage ? 'active' : ''; ?>" href="participantes.php?category=<?php echo $category; ?>" title="Registro y lista de participantes">
            <i class="bi bi-people-fill"></i> 1. Participantes
          </a>
          <a class="nav-link-gaming <?php echo $isFaseAleatoriaPage ? 'active' : ''; ?>" href="fase_aleatoria.php?category=<?php echo $category; ?>" title="Emparejamientos aleatorios y tabla de puntos">
            <i class="bi bi-shuffle"></i> 2. Fase Aleatoria
          </a>
          <a class="nav-link-gaming <?php echo $isBracketsPage ? 'active' : ''; ?>" href="brackets.php?category=<?php echo $category; ?>" title="Playoffs y árbol de eliminatorias">
            <i class="bi bi-diagram-3-fill"></i> 3. Eliminatorias
          </a>
        </nav>

        <!-- Switcher de Juego & Salida a Portal Principal -->
        <div class="d-flex align-items-center gap-2">
          <div class="p-1 bg-light rounded-pill border d-inline-flex gap-1">
            <a class="game-switch-btn fc26-btn <?php echo $isFC26 ? 'active' : ''; ?>" href="?category=fc26">
              <span>⚽</span> FC 26
            </a>
            <a class="game-switch-btn mk1-btn <?php echo !$isFC26 ? 'active' : ''; ?>" href="?category=mk1">
              <span>🐉</span> MK 1
            </a>
          </div>

          <a class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 ms-1" href="/index.html" title="Regresar al Portal Principal">
            <i class="bi bi-arrow-left"></i> Nexus
          </a>
        </div>

      </div>
    </header>
