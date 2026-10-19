<?php
// filepath: /home/hector/proyectos/olimpiadas_gaming/includes/footer.php
?>
  </div><!-- /container-fluid -->

  <!-- Modal Global: Añadir Participante -->
  <div class="modal fade" id="modalAddParticipant" tabindex="-1" aria-labelledby="modalAddParticipantLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <form id="formAddParticipant">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold text-dark" id="modalAddParticipantLabel">
              <i class="bi bi-person-plus-fill me-2 text-primary"></i> Inscribir Participante
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-bold text-secondary font-monospace">Nombre Completo *</label>
              <input type="text" name="name" class="form-control" required placeholder="Ej: Héctor Martínez">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold text-secondary font-monospace">GamerTag / Apodo</label>
              <input type="text" name="alias" class="form-control" placeholder="Ej: ScorpionKing / D10S">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold text-secondary font-monospace">Categoría del Torneo *</label>
              <select name="category" class="form-select">
                <option value="fc26" <?php echo $isFC26 ? 'selected' : ''; ?>>⚽ EA Sports FC 26</option>
                <option value="mk1" <?php echo !$isFC26 ? 'selected' : ''; ?>>🐉 Mortal Kombat 1</option>
              </select>
            </div>

            <div class="alert alert-light border small text-muted mb-0">
              <i class="bi bi-info-circle me-1 text-primary"></i>
              La división (Grupo A o B) y la clasificación se determinarán automáticamente en la <strong>Fase Aleatoria</strong> según los resultados obtenidos.
            </div>
          </div>
          <div class="modal-footer border-top bg-light rounded-bottom-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-game-primary">
              <i class="bi bi-check-lg"></i> Inscribir Jugador
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Tournament Interactive Script -->
  <script src="assets/js/tournament.js?v=<?php echo time(); ?>"></script>

</body>
</html>
