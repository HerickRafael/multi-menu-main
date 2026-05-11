<div class="actions">
  <?php if ($whatsappUrl !== ''): ?>
    <a class="btn btn-whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer">
      <?= svg_checkout_success('whatsapp') ?>
      Enviar pedido pelo WhatsApp
    </a>
  <?php endif; ?>

  <a class="btn btn-primary" href="<?= e($successOrder->baseLink()) ?>">Voltar ao cardápio</a>
</div>
