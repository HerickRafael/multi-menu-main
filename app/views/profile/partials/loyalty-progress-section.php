<?php
$loyaltyProgress = $loyaltyProgress ?? null;
if ($loyaltyProgress && isset($loyaltyProgress['program'])):
    $prog = $loyaltyProgress['program'];
    $pct = (int) $loyaltyProgress['percentage'];
    $remaining = (int) $loyaltyProgress['remaining'];
    $current = (int) $loyaltyProgress['current_count'];
    $required = (int) $loyaltyProgress['required_orders'];
?>
<section class="profile-section" style="margin-top:24px;">
  <h2 class="section-title">🏆 <?= e($prog['name']) ?></h2>
  <p style="font-size:13px;color:#6b7280;margin-bottom:12px;"><?= e($prog['reward_description']) ?></p>
  <div style="background:#f3f4f6;border-radius:12px;height:28px;overflow:hidden;position:relative;">
    <div style="background:linear-gradient(90deg,#f59e0b,#ef4444);height:100%;width:<?= $pct ?>%;border-radius:12px;transition:width .5s ease;min-width:<?= $pct > 0 ? '28px' : '0' ?>;"></div>
    <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:12px;font-weight:700;color:<?= $pct > 50 ? '#fff' : '#374151' ?>;"><?= $current ?>/<?= $required ?></span>
  </div>
  <?php if ($remaining > 0): ?>
    <p style="font-size:14px;font-weight:600;color:#d97706;margin-top:10px;text-align:center;">
      🔥 Faltam <strong><?= $remaining ?></strong> pedido<?= $remaining > 1 ? 's' : '' ?> para ganhar sua recompensa!
    </p>
  <?php else: ?>
    <p style="font-size:14px;font-weight:600;color:#059669;margin-top:10px;text-align:center;">
      🎉 Parabéns! Você já completou <?= (int) $loyaltyProgress['times_completed'] ?> ciclo<?= (int) $loyaltyProgress['times_completed'] > 1 ? 's' : '' ?>!
    </p>
  <?php endif; ?>
</section>
<?php endif; ?>
