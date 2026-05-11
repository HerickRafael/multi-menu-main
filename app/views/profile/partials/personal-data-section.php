<section class="card">
  <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2>Dados pessoais</h2>
    <span class="tag">Atualize seus dados</span>
  </div>
  <form method="post" action="<?= e($updateUrl) ?>" class="grid gap-12">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>

    <div class="grid gap-8">
      <?php $loyaltyProgress = $loyalty ?? null; ?>

      <label class="field">
        <span>Nome completo</span>
        <input type="text" name="profile[name]" value="<?= e($customer['name'] ?? '') ?>" placeholder="Seu nome" required>
      </label>
      <label class="field">
        <span>WhatsApp</span>
        <input type="tel" name="profile[whatsapp]" value="<?= e(format_phone_br($customer['whatsapp'] ?? '')) ?>" placeholder="(11) 90000-0000" required>
      </label>
      <label class="field">
        <span>E-mail</span>
        <input type="email" name="profile[email]" value="<?= e($customer['email'] ?? '') ?>" placeholder="voce@exemplo.com">
      </label>
      <div class="grid-two" style="align-items:start;">
        <div class="field-wrapper">
          <label class="field">
            <span>Data de nascimento<?= $showIcons ? ' 🎂' : '' ?></span>
            <?php
            $minDate = date('Y-m-d', strtotime('-120 years'));
            ?>
            <input type="date"
                   name="profile[birthdate]"
                   id="birthdate-input"
                   value="<?= e($customer['birthdate'] ?? '') ?>"
                   min="<?= $minDate ?>">
          </label>
          <small id="birthdate-error" style="color:#dc2626;font-size:11px;display:none;margin-top:4px;"></small>
        </div>
        <div class="field-wrapper">
          <label class="field">
            <span>CPF<?= $showIcons ? ' 🎁' : '' ?></span>
            <input type="text" name="profile[document]" id="cpf-input" value="<?= e($customer['document'] ?? '') ?>" placeholder="000.000.000-00" inputmode="numeric" maxlength="14">
          </label>
        </div>
      </div>

      <?php
      if ($loyaltyActive && $loyaltyDiscount > 0): ?>
        <?php if ($showCoupon && $customerCoupon): ?>
          <div style="background:<?= $customerCoupon['is_used'] ? '#fee2e2' : '#dcfce7' ?>;border:1px solid <?= $customerCoupon['is_used'] ? '#fecaca' : '#bbf7d0' ?>;border-radius:14px;padding:16px;margin-top:4px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <span style="font-size:24px;"><?= $customerCoupon['is_used'] ? '✅' : '🎁' ?></span>
              <div style="flex:1;">
                <div style="font-weight:700;font-size:15px;color:<?= $customerCoupon['is_used'] ? '#991b1b' : '#166534' ?>;">
                  <?php if ($customerCoupon['is_used']): ?>
                    Cupom já utilizado
                  <?php else: ?>
                    Seu cupom de <?= number_format($customerCoupon['discount_percentage'], 0) ?>% OFF está ativo!
                  <?php endif; ?>
                </div>
                <div style="background:<?= $customerCoupon['is_used'] ? '#fef2f2' : '#f0fdf4' ?>;border:2px dashed <?= $customerCoupon['is_used'] ? '#fca5a5' : '#86efac' ?>;border-radius:8px;padding:12px;margin-top:8px;text-align:center;">
                  <div style="font-size:11px;color:<?= $customerCoupon['is_used'] ? '#7f1d1d' : '#14532d' ?>;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Código do cupom</div>
                  <div style="font-size:20px;font-weight:800;color:<?= $customerCoupon['is_used'] ? '#991b1b' : '#166534' ?>;letter-spacing:2px;font-family:monospace;"><?= e($customerCoupon['coupon_code']) ?></div>
                </div>
              </div>
            </div>
            <?php if ($customerCoupon['is_used']): ?>
              <div style="font-size:12px;color:#991b1b;margin-top:8px;text-align:center;">
                Este cupom já foi utilizado em um pedido anterior.
              </div>
            <?php else: ?>
              <div style="font-size:12px;color:#15803d;margin-top:8px;text-align:center;">
                💡 Use este código no checkout para ganhar <strong><?= number_format($customerCoupon['discount_percentage'], 0) ?>% de desconto</strong> no seu próximo pedido!
              </div>
            <?php endif; ?>
          </div>
        <?php elseif ($customerCoupon && !$hasCompletedProfile): ?>
          <div style="background:#fef3c7;border:2px solid #fbbf24;border-radius:14px;padding:16px;margin-top:4px;">
            <div style="display:flex;align-items:center;gap:12px;">
              <span style="font-size:28px;">🔒</span>
              <div>
                <div style="font-weight:700;font-size:15px;color:#92400e;margin-bottom:4px;">
                  Seu cupom está bloqueado
                </div>
                <div style="font-size:13px;color:#a16207;">
                  Você já possui um cupom de <strong><?= number_format($customerCoupon['discount_percentage'], 0) ?>% OFF</strong>!<br>
                  Para visualizá-lo, mantenha seus dados (🎂 Data de nascimento e 🎁 CPF) preenchidos.
                </div>
              </div>
            </div>
          </div>
        <?php elseif (!$hasCompletedProfile): ?>
          <div style="background:linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);border:2px solid #fbbf24;border-radius:14px;padding:18px;margin-top:4px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;font-size:80px;opacity:0.1;">🎁</div>
            <div style="position:relative;z-index:1;">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <span style="font-size:32px;animation:bounce 2s infinite;">🎁</span>
                <div style="flex:1;">
                  <div style="font-weight:800;font-size:17px;color:#78350f;margin-bottom:4px;">
                    Ganhe <?= number_format($loyaltyDiscount, 0) ?>% OFF no seu primeiro pedido!
                  </div>
                  <div style="font-size:13px;color:#92400e;">
                    Complete os campos marcados com 🎂 e 🎁
                  </div>
                </div>
              </div>

              <div style="background:rgba(255,255,255,0.7);border-radius:10px;padding:14px;margin-bottom:10px;">
                <div style="font-size:13px;color:#78350f;font-weight:600;margin-bottom:8px;">✨ O que você ganha:</div>
                <div style="display:grid;gap:6px;">
                  <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#92400e;">
                    <span style="background:#fbbf24;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;">1</span>
                    <span>Cupom exclusivo de <strong><?= number_format($loyaltyDiscount, 0) ?>% de desconto</strong></span>
                  </div>
                  <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#92400e;">
                    <span style="background:#fbbf24;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;">2</span>
                    <span>Use no seu próximo pedido</span>
                  </div>
                  <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#92400e;">
                    <span style="background:#fbbf24;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;">3</span>
                    <span>Economize em produtos deliciosos!</span>
                  </div>
                </div>
              </div>

              <div style="background:#78350f;color:white;border-radius:10px;padding:10px;text-align:center;font-size:13px;font-weight:700;">
                ⚠️ Mantenha seus dados preenchidos para usar o cupom!
              </div>

              <?php if ($loyaltyMessage): ?>
                <div style="font-size:12px;color:#92400e;margin-top:10px;text-align:center;font-style:italic;">
                  "<?= e($loyaltyMessage) ?>"
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="grid gap-8">
      <button class="cta" type="submit">Salvar alterações</button>
      <form method="post" action="<?= e($logoutUrl) ?>" style="margin:0;">
        <?php if (function_exists('csrf_field')) {
            echo csrf_field();
        } ?>
        <button class="ghost-btn" type="submit" style="width:100%;">Sair da conta</button>
      </form>
    </div>
  </form>
</section>
