<header>
  <div class="rounded-2xl overflow-hidden">
    <?php if ($bannerUrl): ?>
      <div class="relative">
        <img src="<?= e($bannerUrl) ?>" class="w-full h-36 md:h-48 object-cover" alt="Banner">
        <div class="absolute inset-0 bg-black/30"></div>
      </div>
    <?php else: ?>
      <div class="h-24 menu-header-fallback"></div>
    <?php endif; ?>

    <div class="p-5 pr-28 relative -mt-10 rounded-2xl no-focus-ring menu-header">
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= e(base_url($company['logo'])) ?>"
             class="w-24 h-24 rounded-full object-cover border-4 absolute -top-10 right-6 pointer-events-none js-company-logo"
             alt="<?= e($company['name'] ?? 'Logo') ?>"
             data-logo-img="1">
        <div class="w-24 h-24 rounded-full border-4 absolute -top-10 right-6 pointer-events-none hidden items-center justify-center menu-logo-frame"
             data-logo-fallback="1">
          <svg class="hero-placeholder-icon w-12 h-12 text-white/40" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </div>
      <?php else: ?>
        <div class="w-24 h-24 rounded-full border-4 absolute -top-10 right-6 pointer-events-none flex items-center justify-center menu-logo-frame">
          <svg class="hero-placeholder-icon w-12 h-12 text-white/40" viewBox="0 0 24 24" fill="none">
            <path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </div>
      <?php endif; ?>

      <div class="flex flex-wrap items-center gap-2 text-sm mt-1">
        <?php
        $pauseStatus = $pauseStatus ?? ['is_paused' => false];
        $statusClass = !empty($isOpenNow) ? 'open' : 'closed';
        ?>
        <span class="status-badge menu-header-btn inline-flex items-center px-2 py-0.5 rounded-lg font-semibold <?= $statusClass ?>">
          <?= !empty($isOpenNow) ? 'Aberto!' : 'Fechado' ?>
        </span>

        <?php if (!empty($todayLabel)): ?>
          <button type="button" id="btn-hours" class="font-semibold menu-header-link" aria-haspopup="dialog" aria-controls="hours-modal" aria-expanded="false"><?= e($todayLabel) ?></button>
          <span id="btn-hours-ico" class="menu-header-icon inline-flex items-center justify-center w-5 h-5 rounded-full cursor-pointer" aria-hidden="true">i</span>
        <?php endif; ?>

        <?php if (!empty($company['min_order'])): ?>
          <span class="menu-header-meta text-sm mt-1">
            Pedido minimo: <strong>R$ <?= number_format((float)$company['min_order'], 2, ',', '.') ?></strong>
          </span>
        <?php endif; ?>

        <?php if (!empty($company['whatsapp'])): ?>
          <a class="inline-flex items-center gap-1 underline menu-header-link" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string)$company['whatsapp'])) ?>" target="_blank" aria-label="WhatsApp">
            <span class="menu-header-link-icon" aria-hidden="true">
              <svg width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
              </svg>
            </span>
            <span class="menu-header-link-text">WhatsApp</span>
          </a>
        <?php endif; ?>

        <?php if (!empty($customer) && isset($company['id']) && isset($customer['company_id']) && (int)$customer['company_id'] === (int)$company['id']): ?>
          <div class="flex items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0 self-center">
            <span class="px-2 py-0.5 rounded-lg menu-header-btn font-semibold">
              Ola, <?= e($customer['name'] ?? 'Cliente') ?>
            </span>
            <form method="post" action="<?= base_url(rawurlencode((string)$company['slug']).'/customer-logout') ?>" class="js-logout-form" data-confirm-message="Sair?">
              <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
              <button class="px-2 py-0.5 rounded-lg menu-header-btn-outline font-semibold">Sair</button>
            </form>
          </div>
        <?php else: ?>
          <div class="w-full sm:w-auto mt-2 sm:mt-0 self-center">
            <button type="button" id="btn-open-login" class="px-2 py-0.5 rounded-lg menu-header-btn font-semibold">Entrar</button>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($company['address'])): ?>
        <div class="menu-header-text text-xs mt-1"><?= e($company['address']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <?php $dailyHighlightText = get_daily_highlight_text($company); ?>
  <?php if (!empty($dailyHighlightText)): ?>
    <div class="pt-4 rounded-xl">
      <p class="p-5 rounded-xl menu-welcome-box">
        <?= nl2br(e($dailyHighlightText)) ?>
      </p>
    </div>
  <?php endif; ?>

  <?php include __DIR__ . '/pause-banner.php'; ?>
  <?php include __DIR__ . '/hours-modal.php'; ?>
</header>
