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
      if (txtExpl) txtExpl.textContent = 'Combate 0 a 0. Sube los puntos de cada luchador con los botones + y - para registrar los rounds ganados.';
      if (txtWinner) txtWinner.textContent = 'Por definir (0 - 0)';
    } else if (s1 > s2) {
      const isClean = (s1 === 2 && s2 === 0);
      const isTight = (s1 === 2 && s2 === 1);
      const resultType = isClean ? '2-0' : (isTight ? '2-1' : `${s1}-${s2}`);
      if (inpResultType) inpResultType.value = resultType;
      if (inpWinner) inpWinner.value = mk1P1Data.id;

      if (box1) box1.classList.add('selected-winner');
      if (box2) box2.classList.remove('selected-winner');

      if (isClean) {
        if (badgePts) badgePts.textContent = '⭐ +2 PUNTOS DE CAMPEONATO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
          badgeStreak.textContent = '🐉 FLAWLESS RACHA 2-0';
        }
        if (txtExpl) txtExpl.textContent = '¡Victoria por Racha Imbatible! 2 rounds consecutivos ganados otorgan 2 puntos de campeonato.';
      } else if (isTight) {
        if (badgePts) badgePts.textContent = '⭐ +1 PUNTO DE CAMPEONATO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
          badgeStreak.textContent = '⚡ VICTORIA AJUSTADA 2-1';
        }
        if (txtExpl) txtExpl.textContent = '¡Fatality en Round Decisivo! Victoria 2 a 1 otorga 1 punto de campeonato.';
      } else {
        if (badgePts) badgePts.textContent = 'COMBATE EN CURSO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
          badgeStreak.textContent = '1 ROUND GANADO';
        }
        if (txtExpl) txtExpl.textContent = `${mk1P1Data.name} va ganando 1-0. Sube a 2 rounds para coronar al campeón.`;
      }
      if (txtWinner) txtWinner.textContent = mk1P1Data.name;
    } else if (s2 > s1) {
      const isClean = (s2 === 2 && s1 === 0);
      const isTight = (s2 === 2 && s1 === 1);
      const resultType = isClean ? '2-0' : (isTight ? '2-1' : `${s2}-${s1}`);
      if (inpResultType) inpResultType.value = resultType;
      if (inpWinner) inpWinner.value = mk1P2Data.id;

      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.add('selected-winner');

      if (isClean) {
        if (badgePts) badgePts.textContent = '⭐ +2 PUNTOS DE CAMPEONATO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-danger text-uppercase font-monospace';
          badgeStreak.textContent = '🐉 FLAWLESS RACHA 2-0';
        }
        if (txtExpl) txtExpl.textContent = '¡Victoria por Racha Imbatible! 2 rounds consecutivos ganados otorgan 2 puntos de campeonato.';
      } else if (isTight) {
        if (badgePts) badgePts.textContent = '⭐ +1 PUNTO DE CAMPEONATO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
          badgeStreak.textContent = '⚡ VICTORIA AJUSTADA 2-1';
        }
        if (txtExpl) txtExpl.textContent = '¡Fatality en Round Decisivo! Victoria 2 a 1 otorga 1 punto de campeonato.';
      } else {
        if (badgePts) badgePts.textContent = 'COMBATE EN CURSO';
        if (badgeStreak) {
          badgeStreak.className = 'badge bg-primary text-uppercase font-monospace';
          badgeStreak.textContent = '1 ROUND GANADO';
        }
        if (txtExpl) txtExpl.textContent = `${mk1P2Data.name} va ganando 1-0. Sube a 2 rounds para coronar al campeón.`;
      }
      if (txtWinner) txtWinner.textContent = mk1P2Data.name;
    } else {
      // 1 - 1
      if (inpResultType) inpResultType.value = '1-1';
      if (inpWinner) inpWinner.value = '';
      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.remove('selected-winner');
      if (badgePts) badgePts.textContent = '⚔️ EMPATE 1-1';
      if (badgeStreak) {
        badgeStreak.className = 'badge bg-warning text-dark text-uppercase font-monospace';
        badgeStreak.textContent = 'ROUND 3 DECISIVO';
      }
      if (txtExpl) txtExpl.textContent = 'Empate a 1 round por bando. Sube a 2 puntos al luchador que gane el round decisivo (otorgará 1 punto).';
      if (txtWinner) txtWinner.textContent = 'Empate 1-1 (Falta definir el round final)';
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

  // Controles de puntos MK1 (+ y -)
  const btnMkInc1 = document.getElementById('btnMkInc1');
  const btnMkDec1 = document.getElementById('btnMkDec1');
  const btnMkInc2 = document.getElementById('btnMkInc2');
  const btnMkDec2 = document.getElementById('btnMkDec2');

  if (btnMkInc1) btnMkInc1.addEventListener('click', () => { mk1Score1 = Math.min(2, mk1Score1 + 1); updateMk1FightState(); });
  if (btnMkDec1) btnMkDec1.addEventListener('click', () => { mk1Score1 = Math.max(0, mk1Score1 - 1); updateMk1FightState(); });
  if (btnMkInc2) btnMkInc2.addEventListener('click', () => { mk1Score2 = Math.min(2, mk1Score2 + 1); updateMk1FightState(); });
  if (btnMkDec2) btnMkDec2.addEventListener('click', () => { mk1Score2 = Math.max(0, mk1Score2 - 1); updateMk1FightState(); });

  // Quick chips MK1
  document.querySelectorAll('.mk1-quick-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const target = chip.getAttribute('data-target');
      const val = parseInt(chip.getAttribute('data-val')) || 1;
      if (target === '1') mk1Score1 = Math.min(2, val === 2 ? 2 : mk1Score1 + val);
      else if (target === '2') mk1Score2 = Math.min(2, val === 2 ? 2 : mk1Score2 + val);
      updateMk1FightState();
    });
  });

  // ============================================================================
  // LÓGICA MODAL GRAN FINAL FC 26 (STADIUM BROADCAST & GOALS)
  // ============================================================================
  let fc26P1Data = { id: '', name: '', alias: '' };
  let fc26P2Data = { id: '', name: '', alias: '' };
  let fc26Score1 = 3;
  let fc26Score2 = 1;
  let fc26Pen1 = 5;
  let fc26Pen2 = 4;
  let fc26PenWinner = 1;

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
    const penBox = document.getElementById('fc26PenaltiesBox');
    const badgePts = document.getElementById('fc26CalculatedPoints');
    const labelDg = document.getElementById('fc26GoalDiffLabel');
    const ruleTxt = document.getElementById('fc26RuleText');
    const announceChamp = document.getElementById('fc26AnnounceChampion');

    if (s1 === 0 && s2 === 0) {
      // ESTADO INICIAL 0 - 0 (Sin jugar)
      if (penBox) penBox.style.display = 'none';
      if (inpPen1) inpPen1.value = '';
      if (inpPen2) inpPen2.value = '';
      if (inpWinner) inpWinner.value = '';

      if (box1) box1.classList.remove('selected-winner');
      if (box2) box2.classList.remove('selected-winner');

      if (badgePts) badgePts.textContent = '⚽ PARTIDO LISTO PARA COMENZAR';
      if (labelDg) labelDg.textContent = 'MARCADOR 0 - 0 (Agrega los goles de cada finalista)';
      if (ruleTxt) ruleTxt.textContent = 'Sube los goles de cada finalista con los botones (+) o los chips rápidos para calcular los puntos por DG.';
      if (announceChamp) announceChamp.textContent = 'Por definir (Sube los goles)';
    } else if (s1 === s2) {
      // EMPATE: Activar sección de penales
      if (penBox) penBox.style.display = 'block';
      if (inpPen1) inpPen1.value = fc26Pen1;
      if (inpPen2) inpPen2.value = fc26Pen2;

      const winnerId = (fc26PenWinner === 1) ? fc26P1Data.id : fc26P2Data.id;
      const winnerName = (fc26PenWinner === 1) ? fc26P1Data.name : fc26P2Data.name;
      if (inpWinner) inpWinner.value = winnerId;

      if (box1) box1.classList.toggle('selected-winner', fc26PenWinner === 1);
      if (box2) box2.classList.toggle('selected-winner', fc26PenWinner === 2);

      if (badgePts) badgePts.textContent = '⭐ DEFINICIÓN POR TANDA DE PENALTIS';
      if (labelDg) labelDg.textContent = `EMPATE EN 90 MIN (${s1} - ${s2})`;
      if (ruleTxt) ruleTxt.textContent = `El partido terminó empatado a ${s1} goles. El campeón se define en la tanda de penales (${fc26Pen1} - ${fc26Pen2}).`;
      if (announceChamp) announceChamp.textContent = `${winnerName} (Por Penales)`;
    } else {
      // VICTORIA REGULAR POR GOLES
      if (penBox) penBox.style.display = 'none';
      if (inpPen1) inpPen1.value = '';
      if (inpPen2) inpPen2.value = '';

      const isP1Winner = (s1 > s2);
      const diff = isP1Winner ? dg1 : dg2;
      const gf = isP1Winner ? s1 : s2;
      const gc = isP1Winner ? s2 : s1;
      const winnerId = isP1Winner ? fc26P1Data.id : fc26P2Data.id;
      const winnerName = isP1Winner ? fc26P1Data.name : fc26P2Data.name;
      if (inpWinner) inpWinner.value = winnerId;

      if (box1) box1.classList.toggle('selected-winner', isP1Winner);
      if (box2) box2.classList.toggle('selected-winner', !isP1Winner);

      if (badgePts) badgePts.textContent = `⭐ +${diff} PUNTOS DE CAMPEONATO`;
      if (labelDg) labelDg.textContent = `DIFERENCIA: +${diff} GOLES (${gf} GF - ${gc} GC)`;
      if (ruleTxt) ruleTxt.textContent = `Regla del Torneo: ${gf} a ${gc} otorga +${diff} puntos por diferencia de goles (${gf} goles a favor).`;
      if (announceChamp) announceChamp.textContent = winnerName;
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

    fc26Pen1 = 5;
    fc26Pen2 = 4;
    fc26PenWinner = 1;
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

      inputScore1.value = p1Score;
      inputScore2.value = p2Score;

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

  // Pre-selección inteligente del ganador al cambiar scores
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

  if (inputScore1) inputScore1.addEventListener('input', autoPickWinner);
  if (inputScore2) inputScore2.addEventListener('input', autoPickWinner);

  // Enviar resultado del partido estándar
  if (formRecordScore) {
    formRecordScore.addEventListener('submit', async (e) => {
      e.preventDefault();
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
      if (!winnerId || (mk1Score1 === 0 && mk1Score2 === 0)) {
        showToast('Debes subir los puntos (+) para definir al ganador del combate', 'danger');
        return;
      }
      if (mk1Score1 === mk1Score2) {
        showToast('El combate está empatado (1-1). Sube el round decisivo a uno de los luchadores para desempatar', 'danger');
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
