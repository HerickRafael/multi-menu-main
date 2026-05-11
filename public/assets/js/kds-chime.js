/**
 * KdsChime — Sistema de áudio para notificações do KDS/Admin
 *
 * Estratégia de playback:
 *  1. Se bell URL configurada → tenta <Audio> primeiro, cai no AudioContext
 *  2. Caso contrário → AudioContext primeiro, depois <Audio>
 *
 * Uso:
 *   const chime = new KdsChime(bellUrl);
 *   chime.activate();   // chamar no primeiro evento de interação do usuário
 *   chime.ring();       // tocar (short = 2x; loop = persistente)
 *   chime.stopAlarm();  // parar
 *   chime.dispose();    // liberar recursos
 */
(function (global) {
  'use strict';

  const DEFAULT_BELL_URI = 'data:audio/wav;base64,UklGRjQrAABXQVZFZm10IBAAAAABAAEAIlYAAESsAAACABAAZGF0YRArAAAA...';

  class KdsChime {
    constructor(fallbackUri) {
      this.AudioContext = global.AudioContext || global.webkitAudioContext || null;
      this.fallbackUri = _prepareUri((typeof fallbackUri === 'string' && fallbackUri.trim()) ? fallbackUri.trim() : DEFAULT_BELL_URI);
      this.preferFallback = this.fallbackUri && this.fallbackUri !== DEFAULT_BELL_URI;

      this.context       = null;
      this.unlocked      = false;
      this.pendingRing   = false;
      this.pendingMode   = null;
      this.lastPlayedAt  = 0;
      this.minimumGapMs  = 450;
      this.loopInterval  = null;
      this.shortTimeout  = null;
      this.isAlarmRunning = false;
      this.audioEl       = null;
      this.audioFailed   = false;
      this.audioLoaded   = false;
      this.debug         = false;
    }

    log(...args) {
      if (this.debug || global.chimeDebug) console.log('[KdsChime]', ...args);
    }

    isActivated() { return this.unlocked; }

    activate() {
      if (this.unlocked) return;
      this.unlocked = true;
      this.log('Ativado');
      this.ensureContext();
      this.preloadAudio();

      if (this.pendingRing) {
        const mode = this.pendingMode || 'loop';
        this.pendingRing = false;
        this.pendingMode = null;
        this.log('Tocando som pendente, modo:', mode);
        mode === 'short' ? this.playShortAlert() : this.startPersistentAlert();
      }
    }

    ring(mode) {
      const chosenMode = mode || (_isPageActive() ? 'short' : 'loop');
      this.log('ring() chamado, modo:', chosenMode, 'unlocked:', this.unlocked);

      if (!this.unlocked) {
        this.pendingRing = true;
        this.pendingMode = chosenMode;
        this.log('Áudio não ativado, pendente');
        return;
      }

      const now = Date.now();
      if (this.isAlarmRunning && (now - this.lastPlayedAt) < this.minimumGapMs) {
        this.log('Ignorando - muito próximo do último som');
        return;
      }

      chosenMode === 'short' ? this.playShortAlert() : this.startPersistentAlert();
    }

    playShortAlert() {
      this.stopAlarm(false);
      this.pendingRing = false;
      this.pendingMode = null;
      this.isAlarmRunning = true;
      this.log('Iniciando alerta curto');
      this.playOnce();
      this.shortTimeout = setTimeout(() => {
        this.playOnce();
        this.shortTimeout = setTimeout(() => this.stopAlarm(), 900);
      }, 800);
    }

    startPersistentAlert() {
      this.stopAlarm(false);
      this.pendingRing = false;
      this.pendingMode = null;
      this.isAlarmRunning = true;
      this.log('Iniciando alerta persistente');
      this.playOnce();
      this.loopInterval = setInterval(() => this.playOnce(), 4000);
    }

    handleUserActivity() {
      if (!this.unlocked) return;
      if (this.isAlarmRunning) {
        this.log('Parando alarme por atividade do usuário');
        this.stopAlarm();
      }
    }

    stopAlarm(resetPending = true) {
      if (this.loopInterval)  { clearInterval(this.loopInterval);  this.loopInterval  = null; }
      if (this.shortTimeout)  { clearTimeout(this.shortTimeout);   this.shortTimeout  = null; }
      this.isAlarmRunning = false;
      if (resetPending) { this.pendingRing = false; this.pendingMode = null; }
      this.log('Alarme parado');
    }

    playOnce() {
      this.log('playOnce() - preferFallback:', this.preferFallback, 'audioFailed:', this.audioFailed);
      let played = false;
      const markPlayed = () => { this.lastPlayedAt = Date.now(); };

      if (this.preferFallback && !this.audioFailed) {
        played = this.playAudioFile(markPlayed);
        if (!played && this.AudioContext) {
          this.log('Fallback para AudioContext');
          this.ensureContext();
          if (this.context && this.playWithContext()) { markPlayed(); played = true; }
        }
      } else {
        this.ensureContext();
        if (this.context && this.playWithContext()) { markPlayed(); played = true; }
        if (!played) {
          this.log('AudioContext falhou, tentando arquivo');
          played = this.playAudioFile(markPlayed);
        }
      }

      if (played) { this.pendingRing = false; this.log('Som tocado com sucesso'); }
      else { this.log('FALHA ao tocar som'); }
      return played;
    }

    ensureContext() {
      if (this.context || !this.AudioContext) return;
      try {
        this.context = new this.AudioContext();
        if (this.context && this.context.state === 'suspended') this.context.resume().catch(() => {});
        this.log('AudioContext criado, state:', this.context?.state);
      } catch (err) {
        this.log('Erro ao criar AudioContext:', err);
        this.context = null;
        this.AudioContext = null;
      }
    }

    playWithContext() {
      if (!this.context) return false;
      try {
        const ctx = this.context;
        if (ctx.state === 'suspended') ctx.resume().catch(() => {});

        const now  = ctx.currentTime;
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, now);
        osc.frequency.exponentialRampToValueAtTime(600, now + 0.15);

        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.4, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.3, now + 0.15);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.5);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.55);

        this.log('Som tocado via AudioContext');
        return true;
      } catch (err) {
        this.log('Erro no AudioContext:', err);
        this.context = null;
        return false;
      }
    }

    preloadAudio() {
      if (!this.fallbackUri || this.audioLoaded) return;
      try {
        if (!this.audioEl) {
          this.audioEl = new Audio();
          this.audioEl.preload = 'auto';
          this.audioEl.volume  = 0.8;
          this.audioEl.addEventListener('canplaythrough', () => {
            this.audioLoaded = true;
            this.log('Áudio pré-carregado com sucesso');
          }, { once: true });
          this.audioEl.addEventListener('error', (e) => {
            this.log('Erro ao carregar áudio:', e);
            this.audioFailed = true;
          }, { once: true });
          this.audioEl.src = this.fallbackUri;
          this.audioEl.load();
        }
      } catch (err) {
        this.log('Erro no preload:', err);
      }
    }

    playAudioFile(onSuccess) {
      if (!this.fallbackUri) {
        this.log('Sem URI de áudio configurada');
        return this.playWithContext();
      }
      try {
        if (!this.audioEl) {
          this.audioEl = new Audio();
          this.audioEl.preload = 'auto';
          this.audioEl.volume  = 0.8;
          this.audioEl.src     = this.fallbackUri;
        }
        this.audioEl.muted  = false;
        this.audioEl.onerror = (e) => {
          this.log('Erro ao tocar áudio:', e);
          this.audioFailed    = true;
          this.preferFallback = false;
        };
        try { this.audioEl.currentTime = 0; } catch (e) { this.log('Não foi possível resetar currentTime'); }

        const playPromise = this.audioEl.play();
        if (playPromise && typeof playPromise.then === 'function') {
          playPromise.then(() => {
            this.audioFailed = false;
            this.log('Arquivo de áudio tocando');
            if (typeof onSuccess === 'function') onSuccess();
          }).catch((err) => {
            this.log('Erro no play():', err.name, err.message);
            if (err.name === 'NotAllowedError') { this.pendingRing = true; }
            else { this.audioFailed = true; this.preferFallback = false; }
          });
          return true;
        }
        this.audioFailed = false;
        if (typeof onSuccess === 'function') onSuccess();
        return true;
      } catch (err) {
        this.log('Exceção no playAudioFile:', err);
        this.audioFailed = true;
        return this.playWithContext();
      }
    }

    /** Teste manual via console: window.chime.test() */
    test() {
      this.log('Teste de som iniciado');
      this.unlocked = true;
      return this.playOnce();
    }

    dispose() {
      this.stopAlarm();
      if (this.context && typeof this.context.close === 'function') {
        try { this.context.close(); } catch {}
      }
      this.context = null;
      if (this.audioEl) {
        try { this.audioEl.pause(); this.audioEl.currentTime = 0; } catch {}
      }
      this.audioEl = null;
      this.log('Disposed');
    }
  }

  /* ---- helpers internos ---- */

  function _prepareUri(value) {
    if (!value) return '';
    const raw = String(value).trim();
    if (!raw) return '';
    if (/^(data:|https?:|\/\/)/i.test(raw)) return raw;
    if (raw.startsWith('/')) return global.location.origin + raw;
    try { return new URL(raw, global.location.href).toString(); } catch { return raw; }
  }

  let _lastUserActivity = 0;
  function _isPageActive() {
    if (document.hidden) return false;
    if (!_lastUserActivity) return false;
    return (Date.now() - _lastUserActivity) <= 15000;
  }

  /* Expõe para uso externo */
  global.KdsChime          = KdsChime;
  global.KDS_DEFAULT_BELL_URI = DEFAULT_BELL_URI;
  global._kdsChimePrepareUri  = _prepareUri;
  global._kdsChimeIsPageActive = _isPageActive;
  global._kdsChimeTrackActivity = function () { _lastUserActivity = Date.now(); };

})(window);
