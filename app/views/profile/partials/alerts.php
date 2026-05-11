<?php if ($successMsg): ?>
  <div class="alert alert-success"><?= e($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="alert alert-error"><?= e($errorMsg) ?></div>
<?php endif; ?>
