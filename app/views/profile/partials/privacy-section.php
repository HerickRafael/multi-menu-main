<section class="profile-section" style="margin-top:24px;">
  <h2 class="section-title">🔒 Meus Dados</h2>
  <p class="text-xs text-gray-500 mb-3">Conforme a LGPD, você pode solicitar a exclusão da sua conta e dados pessoais.</p>
  <div class="flex gap-2">
    <form method="post" action="<?= e(base_url($slugClean . '/profile/request-deletion')) ?>" style="flex:1;margin:0;" data-confirm="Tem certeza? Sua conta será removida e você será desconectado. Esta ação não pode ser desfeita.">
      <?php if (function_exists('csrf_field')): echo csrf_field(); endif; ?>
      <button type="submit" class="ghost-btn danger" style="width:100%;">🗑️ Excluir minha conta</button>
    </form>
  </div>
</section>
