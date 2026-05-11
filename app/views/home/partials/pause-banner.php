<?php if (!empty($pauseStatus['is_paused'])): ?>
  <div class="scheduled-pause-banner">
    <div class="scheduled-pause-icon">
      <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
        <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
      </svg>
    </div>
    <div class="scheduled-pause-content">
      <div class="scheduled-pause-title">
        <?php if (($pauseStatus['pause_type'] ?? 'timed') === 'indefinite'): ?>
          Estamos em pausa no momento
        <?php else: ?>
          Estamos em pausa temporaria
        <?php endif; ?>
      </div>
      <div class="scheduled-pause-reason">
        <?php if (!empty($pauseStatus['pause_reason'])): ?>
          <?= e($pauseStatus['pause_reason']) ?>
        <?php endif; ?>
        <?php if (($pauseStatus['pause_type'] ?? 'timed') !== 'indefinite' && !empty($pauseStatus['remaining_text'])): ?>
          <span class="scheduled-pause-time">
            . Retornamos em <?= e($pauseStatus['remaining_text']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
