<div class="hero">
  <div class="hero-icon">
    <?= svg_checkout_success('check') ?>
  </div>
  <div class="pill">
    Pedido <?= $successOrder->orderId > 0 ? '#' . e((string) $successOrder->orderId) : 'confirmado' ?>
  </div>
  <h1>Pedido confirmado com sucesso!</h1>
  <p class="subtitle">
    Seu pedido foi registrado<?= $successOrder->customer !== '' ? ' para ' . e($successOrder->customer) : '' ?>.
    Agora, envie os detalhes pelo WhatsApp para finalizar!
  </p>
</div>
