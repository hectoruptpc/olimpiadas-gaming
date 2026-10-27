// filepath: /home/hector/proyectos/olimpiadas_gaming/assets/js/tournament.js

document.addEventListener('DOMContentLoaded', () => {

  // ============================================================================
  // NOTIFICACIONES TOAST (Bootstrap 5)
  // ============================================================================
  function showToast(message, type = 'success') {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    const toastTitle = document.getElementById('toastTitle');
    if (!toastEl || !toastBody) {
      alert(message);
      return;
    }

    toastBody.textContent = message;
    toastTitle.textContent = type === 'success' ? '⚡ Éxito' : '⚠️ Atención';
    toastEl.className = `toast align-items-center text-bg-${type === 'success' ? 'dark border-success' : 'danger'} border-0`;

    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
  }

  // ============================================================================
  // 1. GESTIÓN DE PARTICIPANTES (AJAX)
  // ============================================================================
  const formAddParticipant = document.getElementById('formAddParticipant');
  if (formAddParticipant) {
    formAddParticipant.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(formAddParticipant);
      formData.append('action', 'add_participant');

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error de conexión con el servidor', 'danger');
      }
    });
  }

  // Ajuste rápido de puntuación (+ / -)
  document.querySelectorAll('.btn-score-adjust').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-id');
      const delta = parseInt(btn.getAttribute('data-delta'));
      const category = btn.getAttribute('data-category') || 'fc26';

      const formData = new FormData();
      formData.append('action', 'adjust_score');
      formData.append('id', id);
      formData.append('delta', delta);
      formData.append('category', category);

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 500);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al modificar puntuación', 'danger');
      }
    });
  });

  // Eliminar participante (usando delegación de eventos en document para máxima fiabilidad)
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-delete-participant');
    if (!btn) return;

    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name') || 'este participante';

    if (!confirm(`¿Eliminar a "${name}" del torneo?`)) return;

    const formData = new FormData();
    formData.append('action', 'delete_participant');
    formData.append('id', id);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al eliminar participante', 'danger');
    }
  });

  // Vaciar todos los participantes de la categoría
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#btnClearParticipants');
    if (!btn) return;

    e.preventDefault();
    const category = btn.getAttribute('data-category');
    if (!confirm('¿Estás seguro de que deseas eliminar TODOS los participantes de esta categoría? Se reiniciarán también las llaves generadas.')) return;

    const formData = new FormData();
    formData.append('action', 'clear_participants');
    formData.append('category', category);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al vaciar participantes', 'danger');
    }
  });

  // Cargar datos de prueba (Demo)
  const btnSeedDemo = document.getElementById('btnSeedDemo');
  if (btnSeedDemo) {
    btnSeedDemo.addEventListener('click', async () => {
      const category = btnSeedDemo.getAttribute('data-category') || 'both';
      if (!confirm(`¿Cargar participantes de prueba para la categoría actual?`)) return;

      const formData = new FormData();
      formData.append('action', 'seed_demo');
      formData.append('category', category);

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al cargar datos de prueba', 'danger');
      }
    });
  }

  // ============================================================================
  // 2. GENERACIÓN AUTOMÁTICA DE LLAVES (BRACKETS)
  // ============================================================================
  const formGenerateBracketModal = document.getElementById('formGenerateBracketModal');
  const wcModeAuto = document.getElementById('wcModeAuto');
  const wcModeManual = document.getElementById('wcModeManual');
  const manualWildcardsList = document.getElementById('manualWildcardsList');

  if (wcModeAuto && wcModeManual && manualWildcardsList) {
    wcModeAuto.addEventListener('change', () => {
      manualWildcardsList.style.display = 'none';
    });
    wcModeManual.addEventListener('change', () => {
      manualWildcardsList.style.display = 'block';
    });
  }

  if (formGenerateBracketModal) {
    formGenerateBracketModal.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(formGenerateBracketModal);
      formData.append('action', 'generate_bracket');

      const isManual = wcModeManual && wcModeManual.checked;
      if (!isManual) {
        // En modo auto, dejamos que el backend seleccione automáticamente a los mejores perdedores
        formData.delete('wildcard_ids[]');
      }

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          const cat = formData.get('category') || 'fc26';
          const modalEl = document.getElementById('modalGenerateBracket');
          if (modalEl) {
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
          }
          setTimeout(() => {
            window.location.href = 'brackets.php?category=' + cat;
          }, 800);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al generar las eliminatorias', 'danger');
      }
    });
  }

  document.querySelectorAll('.btn-generate-bracket').forEach(btn => {
    btn.addEventListener('click', async () => {
      const category = btn.getAttribute('data-category');
      const size = btn.getAttribute('data-size') || 'auto';

      if (!confirm(`¿Generar cuadro de eliminatorias (${size === 'auto' ? 'Automático' : 'Top ' + size}) para los mejores clasificados? Se reiniciarán las llaves existentes.`)) return;

      const formData = new FormData();
      formData.append('action', 'generate_bracket');
      formData.append('category', category);
      formData.append('size', size);

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al generar las eliminatorias', 'danger');
      }
    });
  });

  // ============================================================================
  // SISTEMA DE NARRACIÓN POR VOZ OFICIAL (WEB SPEECH SYNTHESIS)
  // ============================================================================
  let speechVoices = [];
  let preferredSpanishVoice = null;

  function loadSpeechVoices() {
    if (!('speechSynthesis' in window)) return;
    try {
      speechVoices = window.speechSynthesis.getVoices() || [];
      if (speechVoices.length > 0) {
        preferredSpanishVoice = 
          speechVoices.find(v => v.lang === 'es-419') ||
          speechVoices.find(v => v.lang === 'es-MX') ||
          speechVoices.find(v => v.lang === 'es-US') ||
          speechVoices.find(v => v.lang.startsWith('es') && (v.name.includes('Google') || v.name.includes('Natural') || v.name.includes('Sabina') || v.name.includes('Alvaro') || v.name.includes('Jorge'))) ||
          speechVoices.find(v => v.lang.startsWith('es')) ||
          null;
      }
    } catch (e) {
      console.warn('Error loading speech voices:', e);
    }
  }

  loadSpeechVoices();
  if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = loadSpeechVoices;
  }

  let narrationTimer = null;
  function showLiveNarrationBanner(text) {
    const banner = document.getElementById('liveNarrationBanner');
    const labelText = document.getElementById('liveNarrationText');
    if (!banner || !labelText) return;

    labelText.textContent = text;
    banner.style.display = 'block';
    banner.classList.remove('animate__fadeOutDown');
    banner.classList.add('animate__fadeInUp');

    if (narrationTimer) clearTimeout(narrationTimer);
  }

  function hideLiveNarrationBanner(delay = 1800) {
    const banner = document.getElementById('liveNarrationBanner');
    if (!banner) return;
    if (narrationTimer) clearTimeout(narrationTimer);
    narrationTimer = setTimeout(() => {
      banner.classList.remove('animate__fadeInUp');
      banner.classList.add('animate__fadeOutDown');
      setTimeout(() => {
        banner.style.display = 'none';
      }, 500);
    }, delay);
  }

  let isNarratorMuted = localStorage.getItem('olimpiadas_narrator_muted') === '1';

  function updateNarratorUI() {
    const btnMute = document.getElementById('btnToggleVoiceMute');
    const iconMute = document.getElementById('iconVoiceMute');
    const textMute = document.getElementById('textVoiceMute');
    const badge = document.getElementById('badgeNarrator');

    if (iconMute) {
      iconMute.className = isNarratorMuted ? 'bi bi-bell-slash-fill text-danger me-1' : 'bi bi-bell-fill me-1';
    }
    if (textMute) {
      textMute.textContent = isNarratorMuted ? 'Activar Voz' : 'Silenciar';
    }
    if (badge) {
      if (isNarratorMuted) {
        badge.className = 'badge bg-dark border border-secondary text-secondary px-3 py-2 font-monospace d-flex align-items-center gap-2';
        badge.innerHTML = '<i class="bi bi-mic-mute-fill text-secondary fs-6"></i><span>🎙️ Narrador Oficial: <strong class="text-secondary">Silenciado</strong></span>';
      } else {
        badge.className = 'badge bg-dark border border-warning text-warning px-3 py-2 font-monospace d-flex align-items-center gap-2';
        badge.innerHTML = '<i class="bi bi-mic-fill text-danger fs-6" id="iconNarratorMic"></i><span>🎙️ Narrador Oficial en Vivo: <strong class="text-white">Activado</strong></span>';
      }
    }
  }

  updateNarratorUI();

  const btnToggleVoiceMute = document.getElementById('btnToggleVoiceMute');
  if (btnToggleVoiceMute) {
    btnToggleVoiceMute.addEventListener('click', () => {
      isNarratorMuted = !isNarratorMuted;
      localStorage.setItem('olimpiadas_narrator_muted', isNarratorMuted ? '1' : '0');
      updateNarratorUI();
      showToast(isNarratorMuted ? 'Narrador de eliminatorias silenciado' : 'Narrador de eliminatorias activado', isNarratorMuted ? 'danger' : 'success');
      if (isNarratorMuted && 'speechSynthesis' in window) {
        window.speechSynthesis.cancel();
      }
    });
  }

  function speakTournamentNarration(text, onComplete) {
    if (!text) {
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    showLiveNarrationBanner(text);

    if (isNarratorMuted || !('speechSynthesis' in window)) {
      hideLiveNarrationBanner(2500);
      if (typeof onComplete === 'function') {
        setTimeout(onComplete, 800);
      }
      return;
    }

    try {
      window.speechSynthesis.cancel();
    } catch (e) {}

    // Atenuar música de fondo para que la voz se escuche clara
    if (typeof window.duckTournamentAudio === 'function') {
      window.duckTournamentAudio(true);
    }

    const utterance = new SpeechSynthesisUtterance(text);
    if (!preferredSpanishVoice) {
      loadSpeechVoices();
    }
    if (preferredSpanishVoice) {
      utterance.voice = preferredSpanishVoice;
    }
    utterance.lang = preferredSpanishVoice ? preferredSpanishVoice.lang : 'es-ES';
    utterance.pitch = 1.05;
    utterance.rate = 1.02;
    utterance.volume = 1.0;

    let callbackCalled = false;
    let safetyTimeout = null;

    const finishSpeech = () => {
      if (callbackCalled) return;
      callbackCalled = true;
      if (safetyTimeout) clearTimeout(safetyTimeout);
      if (typeof window.duckTournamentAudio === 'function') {
        window.duckTournamentAudio(false);
      }
      hideLiveNarrationBanner(1200);
      if (typeof onComplete === 'function') {
        onComplete();
      }
    };

    utterance.onend = finishSpeech;
    utterance.onerror = (err) => {
      console.warn('SpeechSynthesis event error:', err);
      finishSpeech();
    };

    safetyTimeout = setTimeout(finishSpeech, 6000);

    try {
      window.speechSynthesis.speak(utterance);
    } catch (err) {
      console.warn('SpeechSynthesis speak failed:', err);
      finishSpeech();
    }
  }

  window.speakTournamentNarration = speakTournamentNarration;

  const btnTestVoice = document.getElementById('btnTestVoice');
  if (btnTestVoice) {
    btnTestVoice.addEventListener('click', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const cat = urlParams.get('category') || 'fc26';
      const sampleText = (cat === 'mk1')
        ? '¡Héctor Martínez avanza a semifinales en Mortal Kombat 1!'
        : '¡Carlos Mendoza avanza a la gran final de la UEFA Champions League!';
      
      showToast('Reproduciendo locución de prueba...', 'success');
      speakTournamentNarration(sampleText, null);
    });
  }

  // Delegación para botón de reproducir locución en las tarjetas (.btn-speak-advance)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-speak-advance');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const narrationText = btn.getAttribute('data-narration');
    if (narrationText) {
      speakTournamentNarration(narrationText, null);
    }
  });

  // ============================================================================
  // 3. REGISTRO MANUAL DE RESULTADOS Y AVANCE DE GANADOR (RONDAS PREVIAS Y FINAL)
  // ============================================================================
  const modalMatchEl = document.getElementById('modalRecordScore');
  let modalMatch = modalMatchEl ? new bootstrap.Modal(modalMatchEl) : null;

  const modalFinalMK1El = document.getElementById('modalFinalMK1');
  let modalFinalMK1 = modalFinalMK1El ? new bootstrap.Modal(modalFinalMK1El) : null;

  const modalFinalFC26El = document.getElementById('modalFinalFC26');
  let modalFinalFC26 = modalFinalFC26El ? new bootstrap.Modal(modalFinalFC26El) : null;

  const formRecordScore = document.getElementById('formRecordScore');
  const inputScore1 = document.getElementById('matchInputScore1');
  const inputScore2 = document.getElementById('matchInputScore2');
  const selectWinner = document.getElementById('matchSelectWinner');

  // ============================================================================
  // LÓGICA MODAL GRAN FINAL MORTAL KOMBAT 1 (PUNTOS Y ROUNDS DESDE 0 A 0)
  // Reglas: Solo 2-0 o 2-1, máximo 2 rounds ganados, sin 2-2 ni 3 o más.
  // ============================================================================
  let mk1P1Data = { id: '', name: '', alias: '' };
  let mk1P2Data = { id: '', name: '', alias: '' };
  let mk1Score1 = 0;
  let mk1Score2 = 0;

  function updateMk1FightState() {
    const s1 = mk1Score1;
    const s2 = mk1Score2;

    const disp1 = document.getElementById('mk1ScoreDisp1');
    const disp2 = document.getElementById('mk1ScoreDisp2');
    const dispRounds = document.getElementById('mk1RoundsDisplay');
    const inpScore1 = document.getElementById('mk1FinalScore1');
    const inpScore2 = document.getElementById('mk1FinalScore2');
    const inpResultType = document.getElementById('mk1FinalResultType');
    const inpWinner = document.getElementById('mk1FinalWinnerId');

    if (disp1) disp1.textContent = s1;
    if (disp2) disp2.textContent = s2;
    if (dispRounds) dispRounds.textContent = `${s1} - ${s2}`;
    if (inpScore1) inpScore1.value = s1;
    if (inpScore2) inpScore2.value = s2;

    // Medallones dragón (0, 1 o 2 activos)
    const medP1_1 = document.getElementById('mk1MedallionP1_1');
    const medP1_2 = document.getElementById('mk1MedallionP1_2');
    const medP2_1 = document.getElementById('mk1MedallionP2_1');
    const medP2_2 = document.getElementById('mk1MedallionP2_2');

    if (medP1_1) medP1_1.className = 'mk1-medallion' + (s1 >= 1 ? ' active-p1' : '');
    if (medP1_2) medP1_2.className = 'mk1-medallion' + (s1 >= 2 ? ' active-p1' : '');
    if (medP2_1) medP2_1.className = 'mk1-medallion' + (s2 >= 1 ? ' active-p2' : '');
    if (medP2_2) medP2_2.className = 'mk1-medallion' + (s2 >= 2 ? ' active-p2' : '');

    const box1 = document.getElementById('boxMk1P1');
    const box2 = document.getElementById('boxMk1P2');
    const badgePts = document.getElementById('mk1PointsBadge');
    const badgeStreak = document.getElementById('mk1StreakBadge');
    const txtExpl = document.getElementById('mk1PointsExplanation');
    const txtWinner = document.getElementById('mk1AnnounceWinnerName');
    const btnSubmitText = document.getElementById('btnSubmitFinalMK1Text');

    if (s1 === 0 && s2 === 0) {
      if (inpResultType) inpResultType.value = '';
      if (inpWinner) inpWinner.value = '';
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = '⭐ 0 PUNTOS';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-secondary text-uppercase font-monospace';
        badgeStreak.textContent = 'COMBATE POR COMENZAR';
      }
      if (txtExpl) txtExpl.textContent = 'Combate 0 a 0. Sube los rounds con + / - o usa los botones rápidos de victoria 2-0 / 2-1.';
      if (txtWinner) txtWinner.textContent = 'Por definir (0 - 0)';
      if (btnSubmitText) btnSubmitText.textContent = '¡CORONAR CAMPEÓN DE MORTAL KOMBAT 1!';
    } else if (s1 === 2 && s2 === 0) {
      if (inpResultType) inpResultType.value = '2-0';
      if (inpWinner) inpWinner.value = mk1P1Data.id;
      if (box1) box1.classList.add('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = '⭐ +2 PUNTOS DE CAMPEONATO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
        badgeStreak.textContent = '🐉 FLAWLESS RACHA 2-0';
      }
      if (txtExpl) txtExpl.textContent = `¡Victoria Imbatible de ${mk1P1Data.name}! 2 rounds a 0 otorgan 2 puntos de campeonato.`;
      if (txtWinner) txtWinner.textContent = `${mk1P1Data.name} (Ganador 2 - 0)`;
      if (btnSubmitText) btnSubmitText.textContent = `¡CORONAR A ${mk1P1Data.name.toUpperCase()} (2-0)!`;
    } else if (s1 === 2 && s2 === 1) {
      if (inpResultType) inpResultType.value = '2-1';
      if (inpWinner) inpWinner.value = mk1P1Data.id;
      if (box1) box1.classList.add('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = '⭐ +1 PUNTO DE CAMPEONATO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
        badgeStreak.textContent = '⚡ VICTORIA AJUSTADA 2-1';
      }
      if (txtExpl) txtExpl.textContent = `¡Victoria en Round Decisivo de ${mk1P1Data.name}! 2 rounds a 1 otorgan 1 punto de campeonato.`;
      if (txtWinner) txtWinner.textContent = `${mk1P1Data.name} (Ganador 2 - 1)`;
      if (btnSubmitText) btnSubmitText.textContent = `¡CORONAR A ${mk1P1Data.name.toUpperCase()} (2-1)!`;
    } else if (s2 === 2 && s1 === 0) {
      if (inpResultType) inpResultType.value = '2-0';
      if (inpWinner) inpWinner.value = mk1P2Data.id;
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.add('selected-winner');
      if (badgePts) badgePts.textContent = '⭐ +2 PUNTOS DE CAMPEONATO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
        badgeStreak.textContent = '🐉 FLAWLESS RACHA 2-0';
      }
      if (txtExpl) txtExpl.textContent = `¡Victoria Imbatible de ${mk1P2Data.name}! 2 rounds a 0 otorgan 2 puntos de campeonato.`;
      if (txtWinner) txtWinner.textContent = `${mk1P2Data.name} (Ganador 2 - 0)`;
      if (btnSubmitText) btnSubmitText.textContent = `¡CORONAR A ${mk1P2Data.name.toUpperCase()} (2-0)!`;
    } else if (s2 === 2 && s1 === 1) {
      if (inpResultType) inpResultType.value = '2-1';
      if (inpWinner) inpWinner.value = mk1P2Data.id;
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.add('selected-winner');
      if (badgePts) badgePts.textContent = '⭐ +1 PUNTO DE CAMPEONATO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
        badgeStreak.textContent = '⚡ VICTORIA AJUSTADA 2-1';
      }
      if (txtExpl) txtExpl.textContent = `¡Victoria en Round Decisivo de ${mk1P2Data.name}! 2 rounds a 1 otorgan 1 punto de campeonato.`;
      if (txtWinner) txtWinner.textContent = `${mk1P2Data.name} (Ganador 2 - 1)`;
      if (btnSubmitText) btnSubmitText.textContent = `¡CORONAR A ${mk1P2Data.name.toUpperCase()} (2-1)!`;
    } else if (s1 === 1 && s2 === 1) {
      if (inpResultType) inpResultType.value = '1-1';
      if (inpWinner) inpWinner.value = '';
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = '⚔️ EMPATE 1-1';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
        badgeStreak.textContent = 'ROUND 3 DECISIVO';
      }
      if (txtExpl) txtExpl.textContent = 'Empate a 1 round. Sube a 2 al luchador que gane el round decisivo (el marcador final será 2-1).';
      if (txtWinner) txtWinner.textContent = 'Empate 1-1 (Falta definir el round final)';
      if (btnSubmitText) btnSubmitText.textContent = '¡Falta definir el round decisivo (2-1)!';
    } else if (s1 === 1 && s2 === 0) {
      if (inpResultType) inpResultType.value = '1-0';
      if (inpWinner) inpWinner.value = '';
      if (box1) box1.classList.add('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = 'COMBATE EN CURSO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
        badgeStreak.textContent = '1 ROUND GANADO';
      }
      if (txtExpl) txtExpl.textContent = `${mk1P1Data.name} va ganando 1-0. Debe llegar a 2 rounds para ganar (2-0 o 2-1).`;
      if (txtWinner) txtWinner.textContent = `${mk1P1Data.name} va ganando 1-0`;
      if (btnSubmitText) btnSubmitText.textContent = '¡Falta 1 round para definir (2-0 o 2-1)!';
    } else if (s2 === 1 && s1 === 0) {
      if (inpResultType) inpResultType.value = '0-1';
      if (inpWinner) inpWinner.value = '';
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.add('selected-winner');
      if (badgePts) badgePts.textContent = 'COMBATE EN CURSO';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-primary text-uppercase font-monospace';
        badgeStreak.textContent = '1 ROUND GANADO';
      }
      if (txtExpl) txtExpl.textContent = `${mk1P2Data.name} va ganando 1-0. Debe llegar a 2 rounds para ganar (2-0 o 2-1).`;
      if (txtWinner) txtWinner.textContent = `${mk1P2Data.name} va ganando 1-0`;
      if (btnSubmitText) btnSubmitText.textContent = '¡Falta 1 round para definir (2-0 o 2-1)!';
    }
  }

  function openModalFinalMK1(matchId, p1Id, p1Name, p1Alias, p1Score, p2Id, p2Name, p2Alias, p2Score, currentWinner) {
    mk1P1Data = { id: p1Id, name: p1Name, alias: p1Alias };
    mk1P2Data = { id: p2Id, name: p2Name, alias: p2Alias };

    const inpMatch = document.getElementById('mk1FinalMatchId');
    if (inpMatch) inpMatch.value = matchId;

    const elP1Name = document.getElementById('mk1FinalP1Name');
    const elP1Alias = document.getElementById('mk1FinalP1Alias');
    const elAvatar1 = document.getElementById('mk1Avatar1');
    if (elP1Name) elP1Name.textContent = p1Name;
    if (elP1Alias) elP1Alias.textContent = p1Alias ? '@' + p1Alias : '@Luchador1';
    if (elAvatar1) elAvatar1.textContent = p1Name.substring(0, 2).toUpperCase();

    const elP2Name = document.getElementById('mk1FinalP2Name');
    const elP2Alias = document.getElementById('mk1FinalP2Alias');
    const elAvatar2 = document.getElementById('mk1Avatar2');
    if (elP2Name) elP2Name.textContent = p2Name;
    if (elP2Alias) elP2Alias.textContent = p2Alias ? '@' + p2Alias : '@Luchador2';
    if (elAvatar2) elAvatar2.textContent = p2Name.substring(0, 2).toUpperCase();

    // Empieza con 0 a 0
    mk1Score1 = 0;
    mk1Score2 = 0;
    updateMk1FightState();

    if (modalFinalMK1) modalFinalMK1.show();
  }

  // Controles de puntos MK1 (+ y - con reglas: máx 2, sin 2-2 ni 3+)
  const btnMkInc1 = document.getElementById('btnMkInc1');
  const btnMkDec1 = document.getElementById('btnMkDec1');
  const btnMkInc2 = document.getElementById('btnMkInc2');
  const btnMkDec2 = document.getElementById('btnMkDec2');

  if (btnMkInc1) {
    btnMkInc1.addEventListener('click', () => {
      if (mk1Score1 >= 2) {
        showToast('En Mortal Kombat los puntos no pueden llegar a 3 o más.', 'warning');
        return;
      }
      if (mk1Score1 + 1 === 2 && mk1Score2 === 2) {
        showToast('En Mortal Kombat ambos no pueden tener 2-2. El combate ya fue ganado.', 'warning');
        return;
      }
      mk1Score1++;
      updateMk1FightState();
    });
  }

  if (btnMkDec1) {
    btnMkDec1.addEventListener('click', () => {
      if (mk1Score1 > 0) {
        mk1Score1--;
        updateMk1FightState();
      }
    });
  }

  if (btnMkInc2) {
    btnMkInc2.addEventListener('click', () => {
      if (mk1Score2 >= 2) {
        showToast('En Mortal Kombat los puntos no pueden llegar a 3 o más.', 'warning');
        return;
      }
      if (mk1Score2 + 1 === 2 && mk1Score1 === 2) {
        showToast('En Mortal Kombat ambos no pueden tener 2-2. El combate ya fue ganado.', 'warning');
        return;
      }
      mk1Score2++;
      updateMk1FightState();
    });
  }

  if (btnMkDec2) {
    btnMkDec2.addEventListener('click', () => {
      if (mk1Score2 > 0) {
        mk1Score2--;
        updateMk1FightState();
      }
    });
  }

  // Quick chips oficiales MK1 (2-0 y 2-1 directos)
  document.querySelectorAll('[data-mk-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      const act = btn.getAttribute('data-mk-action');
      if (act === 'p1-2-0') {
        mk1Score1 = 2;
        mk1Score2 = 0;
      } else if (act === 'p1-2-1') {
        mk1Score1 = 2;
        mk1Score2 = 1;
      } else if (act === 'p2-2-0') {
        mk1Score1 = 0;
        mk1Score2 = 2;
      } else if (act === 'p2-2-1') {
        mk1Score1 = 1;
        mk1Score2 = 2;
      }
      updateMk1FightState();
    });
  });

  // ============================================================================
  // LÓGICA MODAL GRAN FINAL FC 26 (STADIUM BROADCAST & GOALS)
  // ============================================================================
  let fc26P1Data = { id: '', name: '', alias: '' };
  let fc26P2Data = { id: '', name: '', alias: '' };
  let fc26Score1 = 0;
  let fc26Score2 = 0;
  let fc26Pen1 = 0;
  let fc26Pen2 = 0;
  let fc26PenWinner = 0;
  let fc26Stage = 'regular'; // 'regular' (90 min) | 'extra_time' (120 min prórroga) | 'penalties' (tanda de penaltis)

  function updateFc26LiveStats() {
    const s1 = fc26Score1;
    const s2 = fc26Score2;

    const disp1 = document.getElementById('fc26ScoreDisp1');
    const disp2 = document.getElementById('fc26ScoreDisp2');
    const inp1 = document.getElementById('fc26FinalScore1');
    const inp2 = document.getElementById('fc26FinalScore2');
    const inpWinner = document.getElementById('fc26FinalWinnerId');
    const inpPen1 = document.getElementById('fc26FinalPenalties1');
    const inpPen2 = document.getElementById('fc26FinalPenalties2');

    if (disp1) disp1.textContent = s1;
    if (disp2) disp2.textContent = s2;
    if (inp1) inp1.value = s1;
    if (inp2) inp2.value = s2;

    const dg1 = s1 - s2;
    const dg2 = s2 - s1;

    const badgeDg1 = document.getElementById('fc26BadgeDgP1');
    const badgeDg2 = document.getElementById('fc26BadgeDgP2');
    if (badgeDg1) badgeDg1.textContent = (dg1 >= 0 ? `+${dg1}` : `${dg1}`) + ' DG';
    if (badgeDg2) badgeDg2.textContent = (dg2 >= 0 ? `+${dg2}` : `${dg2}`) + ' DG';

    const box1 = document.getElementById('boxFc26P1');
    const box2 = document.getElementById('boxFc26P2');
    const timeBadge = document.getElementById('fc26TimeBadge');
    const stageBadge = document.getElementById('fc26StageBadge');
    const tieControlBox = document.getElementById('fc26TieControlBox');
    const btnGoExtra = document.getElementById('btnFcGoToExtraTime');
    const btnGoPen = document.getElementById('btnFcGoToPenalties');
    const penBox = document.getElementById('fc26PenaltiesBox');
    const badgePts = document.getElementById('fc26CalculatedPoints');
    const labelDg = document.getElementById('fc26GoalDiffLabel');
    const ruleTxt = document.getElementById('fc26RuleText');
    const announceChamp = document.getElementById('fc26AnnounceChampion');
    const btnSubmitText = document.getElementById('btnSubmitFinalFC26Text');

    // Si los goles se desigualan pero estaba en penales, salir de penales
    if (s1 !== s2 && fc26Stage === 'penalties') {
      fc26Stage = 'regular';
    }

    if (s1 === s2) {
      // ========================================================================
      // CASO EMPATE (0-0, 1-1, 2-2, ETC.): SOLO BOTÓN DE PRÓRROGA O PENALTIS
      // ========================================================================
      if (fc26Stage === 'regular') {
        // 1. Fase Regular (90 min): solo botón "Ir a prórroga"
        if (timeBadge) timeBadge.textContent = '90 MIN';
        if (stageBadge) {
          stageBadge.textContent = (s1 === 0 && s2 === 0) ? '0 - 0' : 'EMPATE';
          stageBadge.className = 'badge bg-warning text-dark text-uppercase px-2 py-0 font-monospace small';
        }

        if (tieControlBox) tieControlBox.style.display = 'block';
        if (btnGoExtra) btnGoExtra.style.display = 'inline-block';
        if (btnGoPen) btnGoPen.style.display = 'none';
        if (penBox) penBox.style.display = 'none';

        if (inpPen1) inpPen1.value = '';
        if (inpPen2) inpPen2.value = '';
        if (inpWinner) inpWinner.value = '';

        if (box1) box1.classList.remove('selected-winner');
        if (box2) box2.classList.remove('selected-winner');

        if (badgePts) badgePts.textContent = '🤝 EMPATE 90 MIN';
        if (labelDg) labelDg.textContent = (s1 === 0 && s2 === 0) ? '0 - 0' : `DIFERENCIA: 0 GOLES (${s1} - ${s2})`;
        if (ruleTxt) ruleTxt.textContent = 'Regla del Torneo: Los puntos se obtienen por la diferencia neta de goles.';
        if (announceChamp) announceChamp.textContent = 'Por definir';
        if (btnSubmitText) btnSubmitText.textContent = '¡DEFINIR EN PRÓRROGA O PENALTIS!';

      } else if (fc26Stage === 'extra_time') {
        // 2. Fase de Prórroga (120 min): solo botón "Ir a penaltis"
        if (timeBadge) timeBadge.textContent = '120 MIN';
        if (stageBadge) {
          stageBadge.textContent = 'PRÓRROGA';
          stageBadge.className = 'badge bg-danger text-white text-uppercase px-2 py-0 font-monospace small';
        }

        if (tieControlBox) tieControlBox.style.display = 'block';
        if (btnGoExtra) btnGoExtra.style.display = 'none';
        if (btnGoPen) btnGoPen.style.display = 'inline-block';
        if (penBox) penBox.style.display = 'none';

        if (inpPen1) inpPen1.value = '';
        if (inpPen2) inpPen2.value = '';
        if (inpWinner) inpWinner.value = '';

        if (box1) box1.classList.remove('selected-winner');
        if (box2) box2.classList.remove('selected-winner');

        if (badgePts) badgePts.textContent = '⏱️ EMPATE EN PRÓRROGA (120 MIN)';
        if (labelDg) labelDg.textContent = `120 MIN: ${s1} - ${s2}`;
        if (ruleTxt) ruleTxt.textContent = 'Regla del Torneo: Los puntos se obtienen por la diferencia neta de goles.';
        if (announceChamp) announceChamp.textContent = 'Por definir';
        if (btnSubmitText) btnSubmitText.textContent = '¡DEFINIR EN TANDA DE PENALTIS!';

      } else if (fc26Stage === 'penalties') {
        // 3. Fase de Penaltis: La puntuación total es la suma de los goles del partido + penaltis
        const total1 = s1 + fc26Pen1;
        const total2 = s2 + fc26Pen2;

        if (disp1) disp1.textContent = total1;
        if (disp2) disp2.textContent = total2;
        if (inp1) inp1.value = total1;
        if (inp2) inp2.value = total2;

        const dgPen1 = total1 - total2;
        const dgPen2 = total2 - total1;
        if (badgeDg1) badgeDg1.textContent = (dgPen1 >= 0 ? `+${dgPen1}` : `${dgPen1}`) + ' DG';
        if (badgeDg2) badgeDg2.textContent = (dgPen2 >= 0 ? `+${dgPen2}` : `${dgPen2}`) + ' DG';

        if (timeBadge) timeBadge.textContent = 'PENALTIS';
        if (stageBadge) {
          stageBadge.textContent = 'DEFINICIÓN';
          stageBadge.className = 'badge bg-danger text-white text-uppercase px-2 py-0 font-monospace small';
        }

        if (tieControlBox) tieControlBox.style.display = 'none';
        if (penBox) penBox.style.display = 'block';

        const dispP1 = document.getElementById('dispPen1');
        const dispP2 = document.getElementById('dispPen2');
        if (dispP1) dispP1.textContent = fc26Pen1;
        if (dispP2) dispP2.textContent = fc26Pen2;

        const elTotalScore1 = document.getElementById('totalScoreText1');
        const elTotalScore2 = document.getElementById('totalScoreText2');
        const elCalcDetail1 = document.getElementById('calcDetail1');
        const elCalcDetail2 = document.getElementById('calcDetail2');
        if (elTotalScore1) elTotalScore1.textContent = total1;
        if (elTotalScore2) elTotalScore2.textContent = total2;
        if (elCalcDetail1) elCalcDetail1.textContent = `(${s1} partido + ${fc26Pen1} penales)`;
        if (elCalcDetail2) elCalcDetail2.textContent = `(${s2} partido + ${fc26Pen2} penales)`;

        if (inpPen1) inpPen1.value = fc26Pen1;
        if (inpPen2) inpPen2.value = fc26Pen2;

        // Auto-selección del ganador si uno tiene más goles totales
        if (total1 > total2) {
          fc26PenWinner = 1;
        } else if (total2 > total1) {
          fc26PenWinner = 2;
        }

        const winnerId = (fc26PenWinner === 1) ? fc26P1Data.id : (fc26PenWinner === 2 ? fc26P2Data.id : '');
        const winnerName = (fc26PenWinner === 1) ? fc26P1Data.name : (fc26PenWinner === 2 ? fc26P2Data.name : 'Por definir');
        if (inpWinner) inpWinner.value = winnerId;

        if (box1) box1.classList.toggle('selected-winner', fc26PenWinner === 1);
        if (box2) box2.classList.toggle('selected-winner', fc26PenWinner === 2);

        const btnW1 = document.getElementById('btnPenWinner1');
        const btnW2 = document.getElementById('btnPenWinner2');
        if (btnW1) {
          btnW1.className = `btn btn-sm ${fc26PenWinner === 1 ? 'btn-warning text-dark active shadow' : 'btn-outline-warning'} w-100 mt-2 font-monospace fw-bold btn-select-pen-winner`;
        }
        if (btnW2) {
          btnW2.className = `btn btn-sm ${fc26PenWinner === 2 ? 'btn-warning text-dark active shadow' : 'btn-outline-warning'} w-100 mt-2 font-monospace fw-bold btn-select-pen-winner`;
        }

        if (badgePts) badgePts.textContent = '⭐ DEFINICIÓN POR TANDA DE PENALTIS';
        if (labelDg) labelDg.textContent = `TOTAL ACUMULADO: ${total1} - ${total2} (${s1}+${fc26Pen1} vs ${s2}+${fc26Pen2})`;
        if (ruleTxt) ruleTxt.textContent = `Goles totales: ${s1} de juego + penaltis. Total final: ${total1} a ${total2}.`;
        if (announceChamp) announceChamp.textContent = winnerId ? `${winnerName} (Por Penales)` : 'Empate en penales (Sigue sumando)';
        if (btnSubmitText) btnSubmitText.textContent = winnerId ? `¡CORONAR A ${winnerName.split(' ')[0].toUpperCase()} POR PENALTIS!` : '¡DEFINIR GANADOR DE PENALTIS!';
      }

    } else {
      // ========================================================================
      // CASO VICTORIA POR GOLES (s1 !== s2)
      // ========================================================================
      const isP1Winner = (s1 > s2);
      const diff = isP1Winner ? dg1 : dg2;
      const gf = isP1Winner ? s1 : s2;
      const gc = isP1Winner ? s2 : s1;
      const winnerId = isP1Winner ? fc26P1Data.id : fc26P2Data.id;
      const winnerName = isP1Winner ? fc26P1Data.name : fc26P2Data.name;
      if (inpWinner) inpWinner.value = winnerId;

      if (tieControlBox) tieControlBox.style.display = 'none';
      if (penBox) penBox.style.display = 'none';
      if (inpPen1) inpPen1.value = '';
      if (inpPen2) inpPen2.value = '';

      if (box1) box1.classList.toggle('selected-winner', isP1Winner);
      if (box2) box2.classList.toggle('selected-winner', !isP1Winner);

      if (fc26Stage === 'extra_time') {
        // Victoria en Prórroga
        if (timeBadge) timeBadge.textContent = '120 MIN';
        if (stageBadge) {
          stageBadge.textContent = 'PRÓRROGA';
          stageBadge.className = 'badge bg-success text-white text-uppercase px-2 py-0 font-monospace small';
        }

        if (badgePts) badgePts.textContent = `⭐ +${diff} PUNTOS DE CAMPEONATO (PRÓRROGA)`;
        if (labelDg) labelDg.textContent = `DIFERENCIA: +${diff} GOLES (${gf} GF - ${gc} GC)`;
        if (ruleTxt) ruleTxt.textContent = `Victoria en tiempo suplementario: ${gf} a ${gc} otorga +${diff} puntos por diferencia de goles.`;
        if (announceChamp) announceChamp.textContent = `${winnerName} (Victoria en Prórroga)`;
        if (btnSubmitText) btnSubmitText.textContent = `¡CORONAR A ${winnerName.split(' ')[0].toUpperCase()} EN PRÓRROGA!`;

      } else {
        // Victoria regular en 90 min
        if (timeBadge) timeBadge.textContent = '90 MIN';
        if (stageBadge) {
          stageBadge.textContent = 'FINAL';
          stageBadge.className = 'badge bg-primary text-uppercase px-2 py-0 font-monospace small';
        }

        if (badgePts) badgePts.textContent = `⭐ +${diff} PUNTOS DE CAMPEONATO`;
        if (labelDg) labelDg.textContent = `DIFERENCIA: +${diff} GOLES (${gf} GF - ${gc} GC)`;
        if (ruleTxt) ruleTxt.textContent = `Regla del Torneo: ${gf} a ${gc} otorga +${diff} puntos por diferencia de goles (${gf} goles a favor).`;
        if (announceChamp) announceChamp.textContent = winnerName;
        if (btnSubmitText) btnSubmitText.textContent = '¡LEVANTAR LA OREJONA Y CORONAR AL CAMPEÓN!';
      }
    }
  }

  function openModalFinalFC26(matchId, p1Id, p1Name, p1Alias, p1Score, p2Id, p2Name, p2Alias, p2Score, currentWinner) {
    fc26P1Data = { id: p1Id, name: p1Name, alias: p1Alias };
    fc26P2Data = { id: p2Id, name: p2Name, alias: p2Alias };

    const inpMatch = document.getElementById('fc26FinalMatchId');
    if (inpMatch) inpMatch.value = matchId;

    const elP1Name = document.getElementById('fc26FinalP1Name');
    const elP1Alias = document.getElementById('fc26FinalP1Alias');
    const elAvatar1 = document.getElementById('fc26Avatar1');
    if (elP1Name) elP1Name.textContent = p1Name;
    if (elP1Alias) elP1Alias.textContent = p1Alias ? '@' + p1Alias : '@Finalista1';
    if (elAvatar1) elAvatar1.textContent = p1Name.substring(0, 2).toUpperCase();

    const elP2Name = document.getElementById('fc26FinalP2Name');
    const elP2Alias = document.getElementById('fc26FinalP2Alias');
    const elAvatar2 = document.getElementById('fc26Avatar2');
    if (elP2Name) elP2Name.textContent = p2Name;
    if (elP2Alias) elP2Alias.textContent = p2Alias ? '@' + p2Alias : '@Finalista2';
    if (elAvatar2) elAvatar2.textContent = p2Name.substring(0, 2).toUpperCase();

    document.querySelectorAll('.fc26-p1-label').forEach(el => el.textContent = p1Name.split(' ')[0]);
    document.querySelectorAll('.fc26-p2-label').forEach(el => el.textContent = p2Name.split(' ')[0]);

    fc26Score1 = parseInt(p1Score) || 0;
    fc26Score2 = parseInt(p2Score) || 0;

    fc26Pen1 = 0;
    fc26Pen2 = 0;
    fc26PenWinner = 0;
    fc26Stage = 'regular';
    updateFc26LiveStats();

    if (modalFinalFC26) modalFinalFC26.show();
  }

  // Controles de goles FC26 (+ y -)
  const btnFcInc1 = document.getElementById('btnFcInc1');
  const btnFcDec1 = document.getElementById('btnFcDec1');
  const btnFcInc2 = document.getElementById('btnFcInc2');
  const btnFcDec2 = document.getElementById('btnFcDec2');

  if (btnFcInc1) btnFcInc1.addEventListener('click', () => { fc26Score1++; updateFc26LiveStats(); });
  if (btnFcDec1) btnFcDec1.addEventListener('click', () => { fc26Score1 = Math.max(0, fc26Score1 - 1); updateFc26LiveStats(); });
  if (btnFcInc2) btnFcInc2.addEventListener('click', () => { fc26Score2++; updateFc26LiveStats(); });
  if (btnFcDec2) btnFcDec2.addEventListener('click', () => { fc26Score2 = Math.max(0, fc26Score2 - 1); updateFc26LiveStats(); });

  // Quick chips de goles
  document.querySelectorAll('.fc26-quick-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const target = chip.getAttribute('data-target');
      const val = parseInt(chip.getAttribute('data-val')) || 1;
      if (target === '1') fc26Score1 += val;
      else if (target === '2') fc26Score2 += val;
      updateFc26LiveStats();
    });
  });

  // Botones de transición de fases: Prórroga y Penaltis (sin mensajes)
  const btnFcGoToExtraTime = document.getElementById('btnFcGoToExtraTime');
  if (btnFcGoToExtraTime) {
    btnFcGoToExtraTime.addEventListener('click', () => {
      fc26Stage = 'extra_time';
      updateFc26LiveStats();
    });
  }

  const btnFcGoToPenalties = document.getElementById('btnFcGoToPenalties');
  if (btnFcGoToPenalties) {
    btnFcGoToPenalties.addEventListener('click', () => {
      fc26Stage = 'penalties';
      fc26Pen1 = 0;
      fc26Pen2 = 0;
      fc26PenWinner = 0;
      updateFc26LiveStats();
    });
  }

  const btnFcBackToExtra = document.getElementById('btnFcBackToExtra');
  if (btnFcBackToExtra) {
    btnFcBackToExtra.addEventListener('click', () => {
      fc26Stage = 'extra_time';
      fc26Pen1 = 0;
      fc26Pen2 = 0;
      fc26PenWinner = 0;
      updateFc26LiveStats();
    });
  }

  // Controles de tanda de penales
  const dispPen1 = document.getElementById('dispPen1');
  const dispPen2 = document.getElementById('dispPen2');
  const btnPenInc1 = document.getElementById('btnPenInc1');
  const btnPenDec1 = document.getElementById('btnPenDec1');
  const btnPenInc2 = document.getElementById('btnPenInc2');
  const btnPenDec2 = document.getElementById('btnPenDec2');

  if (btnPenInc1) btnPenInc1.addEventListener('click', () => { fc26Pen1++; if (dispPen1) dispPen1.textContent = fc26Pen1; updateFc26LiveStats(); });
  if (btnPenDec1) btnPenDec1.addEventListener('click', () => { fc26Pen1 = Math.max(0, fc26Pen1 - 1); if (dispPen1) dispPen1.textContent = fc26Pen1; updateFc26LiveStats(); });
  if (btnPenInc2) btnPenInc2.addEventListener('click', () => { fc26Pen2++; if (dispPen2) dispPen2.textContent = fc26Pen2; updateFc26LiveStats(); });
  if (btnPenDec2) btnPenDec2.addEventListener('click', () => { fc26Pen2 = Math.max(0, fc26Pen2 - 1); if (dispPen2) dispPen2.textContent = fc26Pen2; updateFc26LiveStats(); });

  document.querySelectorAll('.btn-select-pen-winner').forEach(btn => {
    btn.addEventListener('click', () => {
      fc26PenWinner = parseInt(btn.getAttribute('data-slot')) || 1;
      updateFc26LiveStats();
    });
  });

  // Categoría del partido actualmente abierto en modalRecordScore
  let currentStandardCategory = 'fc26';

  // Abrir modal de resultado al hacer clic en un partido
  document.querySelectorAll('.btn-open-match-modal').forEach(btn => {
    btn.addEventListener('click', () => {
      const matchId = btn.getAttribute('data-match-id');
      const roundName = btn.getAttribute('data-round-name');
      const matchNumber = btn.getAttribute('data-match-number');

      const p1Id = btn.getAttribute('data-p1-id');
      const p1Name = btn.getAttribute('data-p1-name');
      const p1Alias = btn.getAttribute('data-p1-alias') || '';
      const p1Score = btn.getAttribute('data-p1-score') || '0';

      const p2Id = btn.getAttribute('data-p2-id');
      const p2Name = btn.getAttribute('data-p2-name');
      const p2Alias = btn.getAttribute('data-p2-alias') || '';
      const p2Score = btn.getAttribute('data-p2-score') || '0';

      const currentWinner = btn.getAttribute('data-winner-id') || '';
      const isFinal = btn.getAttribute('data-is-final') === '1';
      const category = btn.getAttribute('data-category') || (new URLSearchParams(window.location.search).get('category') || 'fc26');
      currentStandardCategory = category;

      // Si es Gran Final: abrir modal temático específico según la categoría (FC26 o MK1)
      if (isFinal) {
        if (category === 'fc26') {
          openModalFinalFC26(matchId, p1Id, p1Name, p1Alias, p1Score, p2Id, p2Name, p2Alias, p2Score, currentWinner);
          return;
        } else {
          openModalFinalMK1(matchId, p1Id, p1Name, p1Alias, p1Score, p2Id, p2Name, p2Alias, p2Score, currentWinner);
          return;
        }
      }

      // Modal estándar de rondas previas
      if (!modalMatch) return;
      document.getElementById('modalMatchId').value = matchId;
      document.getElementById('modalMatchSubtitle').textContent = `${roundName} • Partido #${matchNumber}`;

      document.getElementById('modalP1Name').textContent = p1Name;
      document.getElementById('modalP2Name').textContent = p2Name;

      // Adaptación dinámica según categoría (MK1 vs FC26)
      const mk1StdBox = document.getElementById('mk1StandardPresetsBox');
      const lbl1 = document.getElementById('modalLabelScore1');
      const lbl2 = document.getElementById('modalLabelScore2');

      if (category === 'mk1') {
        if (mk1StdBox) {
          mk1StdBox.style.display = 'block';
          const p1Short = (p1Name || 'J1').split(' ')[0];
          const p2Short = (p2Name || 'J2').split(' ')[0];
          mk1StdBox.querySelectorAll('.btn-p1-label').forEach(el => el.textContent = p1Short);
          mk1StdBox.querySelectorAll('.btn-p2-label').forEach(el => el.textContent = p2Short);
        }
        if (lbl1) lbl1.textContent = 'Rounds Ganados (0 a 2)';
        if (lbl2) lbl2.textContent = 'Rounds Ganados (0 a 2)';
        if (inputScore1) {
          inputScore1.setAttribute('max', '2');
          let s1 = Math.min(2, Math.max(0, parseInt(p1Score) || 0));
          inputScore1.value = s1;
        }
        if (inputScore2) {
          inputScore2.setAttribute('max', '2');
          let s2 = Math.min(2, Math.max(0, parseInt(p2Score) || 0));
          // Evitar 2-2
          if (parseInt(inputScore1.value) === 2 && s2 === 2) {
            s2 = 1;
          }
          inputScore2.value = s2;
        }
      } else {
        if (mk1StdBox) mk1StdBox.style.display = 'none';
        if (lbl1) lbl1.textContent = 'Goles Marcados';
        if (lbl2) lbl2.textContent = 'Goles Marcados';
        if (inputScore1) {
          inputScore1.removeAttribute('max');
          inputScore1.value = p1Score;
        }
        if (inputScore2) {
          inputScore2.removeAttribute('max');
          inputScore2.value = p2Score;
        }
      }

      // Actualizar opciones del select de ganador
      selectWinner.innerHTML = `
        <option value="">-- Seleccionar quién avanza (o automático por puntuación) --</option>
        <option value="${p1Id}">👑 ${p1Name}</option>
        <option value="${p2Id}">👑 ${p2Name}</option>
      `;

      if (currentWinner) {
        selectWinner.value = currentWinner;
      } else {
        autoPickWinner();
      }

      const btnReset = document.getElementById('btnModalResetMatch');
      if (btnReset) {
        btnReset.setAttribute('data-match-id', matchId);
        btnReset.style.display = currentWinner ? 'inline-block' : 'none';
      }

      modalMatch.show();
    });
  });

  // Marcadores rápidos en modal estándar de Mortal Kombat
  document.querySelectorAll('.btn-mk-std-preset').forEach(btn => {
    btn.addEventListener('click', () => {
      const s1 = parseInt(btn.getAttribute('data-s1'));
      const s2 = parseInt(btn.getAttribute('data-s2'));
      if (inputScore1) inputScore1.value = s1;
      if (inputScore2) inputScore2.value = s2;
      autoPickWinner();
    });
  });

  // Pre-selección inteligente del ganador al cambiar scores y restricciones MK1
  function autoPickWinner() {
    if (!inputScore1 || !inputScore2 || !selectWinner) return;
    const s1 = parseInt(inputScore1.value) || 0;
    const s2 = parseInt(inputScore2.value) || 0;

    if (selectWinner.options.length >= 3) {
      const opt1 = selectWinner.options[1].value;
      const opt2 = selectWinner.options[2].value;

      if (s1 > s2) {
        selectWinner.value = opt1;
      } else if (s2 > s1) {
        selectWinner.value = opt2;
      }
    }
  }

  function enforceMk1StandardRules(activeInput, otherInput) {
    if (currentStandardCategory !== 'mk1') return;
    let val = parseInt(activeInput.value);
    if (isNaN(val)) return;

    if (val > 2) {
      activeInput.value = 2;
      showToast('En Mortal Kombat los puntos no pueden llegar a 3 o más.', 'warning');
    } else if (val < 0) {
      activeInput.value = 0;
    }

    const cur = parseInt(activeInput.value) || 0;
    const oth = parseInt(otherInput.value) || 0;
    if (cur === 2 && oth === 2) {
      otherInput.value = 1;
      showToast('En Mortal Kombat ambos no pueden tener 2-2. Ajustado a 2-1.', 'warning');
    }
  }

  if (inputScore1) {
    inputScore1.addEventListener('input', () => {
      enforceMk1StandardRules(inputScore1, inputScore2);
      autoPickWinner();
    });
  }
  if (inputScore2) {
    inputScore2.addEventListener('input', () => {
      enforceMk1StandardRules(inputScore2, inputScore1);
      autoPickWinner();
    });
  }

  // Enviar resultado del partido estándar
  if (formRecordScore) {
    formRecordScore.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Validación estricta MK1: solo 2-0 o 2-1, sin 3+ ni 2-2
      if (currentStandardCategory === 'mk1') {
        const s1 = parseInt(inputScore1.value) || 0;
        const s2 = parseInt(inputScore2.value) || 0;

        if (s1 > 2 || s2 > 2) {
          showToast('En Mortal Kombat los puntos no pueden llegar a 3 o más. Máximo 2 rounds ganados.', 'danger');
          return;
        }
        if (s1 === 2 && s2 === 2) {
          showToast('En Mortal Kombat ambos no pueden tener 2-2. El combate termina al alcanzar 2 rounds.', 'danger');
          return;
        }
        const isValidMK = (s1 === 2 && (s2 === 0 || s2 === 1)) || (s2 === 2 && (s1 === 0 || s1 === 1));
        if (!isValidMK) {
          showToast('En Mortal Kombat los puntos solo deben ser 2-0 o 2-1 (al mejor de 3 rounds).', 'danger');
          return;
        }
      }

      const formData = new FormData(formRecordScore);
      formData.append('action', 'update_match');

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          if (modalMatch) modalMatch.hide();

          const narrationText = data.data && data.data.narration_text;
          if (narrationText) {
            speakTournamentNarration(narrationText, () => {
              const delay = (data.data && data.data.finalists_arrived) ? 1200 : 400;
              setTimeout(() => location.reload(), delay);
            });
          } else {
            const delay = (data.data && data.data.finalists_arrived) ? 1400 : 800;
            setTimeout(() => location.reload(), delay);
          }
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al guardar resultado', 'danger');
      }
    });
  }

  // Enviar resultado de la Final MK1
  const formFinalMK1 = document.getElementById('formFinalMK1');
  if (formFinalMK1) {
    formFinalMK1.addEventListener('submit', async (e) => {
      e.preventDefault();
      const winnerId = document.getElementById('mk1FinalWinnerId').value;
      
      // Validación estricta MK1: solo 2-0 o 2-1, sin 3+ ni 2-2
      if (mk1Score1 > 2 || mk1Score2 > 2) {
        showToast('En Mortal Kombat los puntos no pueden llegar a 3 o más.', 'danger');
        return;
      }
      if (mk1Score1 === 2 && mk1Score2 === 2) {
        showToast('En Mortal Kombat ambos no pueden tener 2-2. El combate termina al alcanzar 2 rounds ganados.', 'danger');
        return;
      }
      const isValidMK = (mk1Score1 === 2 && (mk1Score2 === 0 || mk1Score2 === 1)) ||
                        (mk1Score2 === 2 && (mk1Score1 === 0 || mk1Score1 === 1));
      if (!isValidMK || !winnerId) {
        if (mk1Score1 === 1 && mk1Score2 === 1) {
          showToast('El combate está 1-1. Sube a 2 rounds al luchador ganador para definir 2-1.', 'warning');
        } else {
          showToast('En Mortal Kombat los puntos solo deben ser 2-0 o 2-1 (al mejor de 3 rounds).', 'danger');
        }
        return;
      }

      const formData = new FormData(formFinalMK1);
      formData.append('action', 'update_match');

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          if (modalFinalMK1) modalFinalMK1.hide();
          window.__finalMatchCompleted = true;

          const narrationText = data.data && data.data.narration_text;
          if (narrationText) {
            speakTournamentNarration(narrationText, null);
          }

          if (data.data && data.data.champion && typeof window.triggerChampionCelebration === 'function') {
            window.triggerChampionCelebration(data.data.champion, 'mk1', data.data.score1, data.data.score2);
          } else {
            setTimeout(() => location.reload(), 1000);
          }
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al coronar campeón de MK1', 'danger');
      }
    });
  }

  // Enviar resultado de la Final FC26
  const formFinalFC26 = document.getElementById('formFinalFC26');
  if (formFinalFC26) {
    formFinalFC26.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (fc26Score1 === 0 && fc26Score2 === 0 && fc26Stage === 'regular') {
        showToast('Debes ingresar los goles de la final para definir al campeón', 'danger');
        return;
      }

      if (fc26Score1 === fc26Score2 && fc26Stage === 'regular') {
        showToast('Pulsa "Ir a prórroga"', 'warning');
        return;
      }

      if (fc26Score1 === fc26Score2 && fc26Stage === 'extra_time') {
        showToast('Pulsa "Ir a penaltis"', 'warning');
        return;
      }

      const winnerId = document.getElementById('fc26FinalWinnerId').value;
      if (!winnerId) {
        showToast('Debes seleccionar al campeón vencedor', 'danger');
        return;
      }

      const formData = new FormData(formFinalFC26);
      formData.append('action', 'update_match');

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          if (modalFinalFC26) modalFinalFC26.hide();
          window.__finalMatchCompleted = true;

          const narrationText = data.data && data.data.narration_text;
          if (narrationText) {
            speakTournamentNarration(narrationText, null);
          }

          if (data.data && data.data.champion && typeof window.triggerChampionCelebration === 'function') {
            window.triggerChampionCelebration(data.data.champion, 'fc26', data.data.score1, data.data.score2);
          } else {
            setTimeout(() => location.reload(), 1000);
          }
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al registrar campeón de FC26', 'danger');
      }
    });
  }

  // Reabrir partido
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-reset-match');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();
    const matchId = btn.getAttribute('data-match-id');
    if (!confirm('¿Reabrir este partido y reiniciar el avance?')) return;

    const formData = new FormData();
    formData.append('action', 'reset_match');
    formData.append('match_id', matchId);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al reabrir partido', 'danger');
    }
  });
  // ============================================================================
  // 4. REGISTRO DE PARTIDO DE FASE PRELIMINAR (FC26 Y MK1)
  // ============================================================================
  const formRecordGroupMatch = document.getElementById('formRecordGroupMatch');
  const selP1 = document.getElementById('groupMatchP1');
  const selP2 = document.getElementById('groupMatchP2');
  const selMkWinner = document.getElementById('mkWinnerSelect');

  function updateMkWinnerOptions() {
    if (!selMkWinner || !selP1 || !selP2) return;
    const p1Id = selP1.value;
    const p1Text = selP1.options[selP1.selectedIndex]?.text || 'Jugador 1';
    const p2Id = selP2.value;
    const p2Text = selP2.options[selP2.selectedIndex]?.text || 'Jugador 2';

    selMkWinner.innerHTML = '<option value="">-- Elige quién ganó la pelea --</option>';
    if (p1Id) {
      selMkWinner.innerHTML += `<option value="${p1Id}">👑 ${p1Text}</option>`;
    }
    if (p2Id) {
      selMkWinner.innerHTML += `<option value="${p2Id}">👑 ${p2Text}</option>`;
    }
  }

  if (selP1) selP1.addEventListener('change', updateMkWinnerOptions);
  if (selP2) selP2.addEventListener('change', updateMkWinnerOptions);

  if (formRecordGroupMatch) {
    formRecordGroupMatch.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(formRecordGroupMatch);
      formData.append('action', 'record_group_match');

      try {
        const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 900);
        } else {
          showToast(data.message, 'danger');
        }
      } catch (err) {
        showToast('Error al registrar partido de grupo', 'danger');
      }
    });
  }

  // ============================================================================
  // 5. FASE ALEATORIA: SORTEO AUTOMÁTICO Y REGISTRO DE PARTIDOS
  // ============================================================================
  // Sorteo de partidos aleatorios
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-generate-random');
    if (!btn) return;

    e.preventDefault();
    const category = btn.getAttribute('data-category');
    const rounds = btn.getAttribute('data-rounds') || '1';

    if (!confirm(`¿Sortear automáticamente los enfrentamientos de la Fase Preliminar (${rounds} ronda/s)? Se reiniciarán los partidos previos de esta fase.`)) return;

    const formData = new FormData();
    formData.append('action', 'generate_random_matches');
    formData.append('category', category);
    formData.append('rounds', rounds);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al sortear partidos', 'danger');
    }
  });

  // Guardar marcador de partido preliminar / fase aleatoria
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.form-save-prelim-match');
    if (!form) return;

    e.preventDefault();
    const formData = new FormData(form);
    formData.append('action', 'update_preliminary_match');

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al guardar marcador', 'danger');
    }
  });

  // Reabrir partido preliminar
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-reset-prelim-match');
    if (!btn) return;

    e.preventDefault();
    const matchId = btn.getAttribute('data-match-id');
    if (!confirm('¿Reabrir este partido para modificar el marcador?')) return;

    const formData = new FormData();
    formData.append('action', 'reset_preliminary_match');
    formData.append('match_id', matchId);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al reabrir partido', 'danger');
    }
  });

  // Vaciar / reiniciar todos los partidos preliminares sorteados
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#btnClearPrelimMatches');
    if (!btn) return;

    e.preventDefault();
    const category = btn.getAttribute('data-category');
    if (!confirm('¿Estás seguro de que deseas reiniciar todos los partidos de la fase aleatoria?')) return;

    const formData = new FormData();
    formData.append('action', 'clear_preliminary_matches');
    formData.append('category', category);

    try {
      const res = await fetch('api/ajax.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast(data.message, 'danger');
      }
    } catch (err) {
      showToast('Error al reiniciar fase aleatoria', 'danger');
    }
  });

});
