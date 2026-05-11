<div class="modal-overlay" id="infoModal" role="dialog" aria-modal="true" aria-labelledby="infoModal-title">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-icon">
        <?= svg_customization('info') ?>
      </div>
      <div class="modal-header-text">
        <h2 id="infoModal-title">Como funciona a personalização?</h2>
        <span class="modal-header-subtitle">Entenda quando algo já está incluso ou vira extra</span>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-example">
        <div class="modal-example-row">
          <div class="modal-example-icon included">
            <?= svg_customization('check') ?>
          </div>
          <div class="modal-example-text">
            <strong>Quantidade pré-selecionada</strong><br>
            <span class="modal-hint">O número que aparece no <strong>−</strong> e <strong>+</strong> já está incluso</span>
          </div>
          <span class="modal-example-badge free">Já incluso</span>
        </div>
        <div class="modal-example-row">
          <div class="modal-example-icon extra">
            <?= svg_customization('plus') ?>
          </div>
          <div class="modal-example-text">
            <strong>Adicionar mais unidades</strong><br>
            <span class="modal-hint">Só cobra se aumentar além do padrão</span>
          </div>
          <span class="modal-example-badge paid">+ Valor</span>
        </div>
        <div class="modal-example-row">
          <div class="modal-example-icon choice">
            <?= svg_customization('swap') ?>
          </div>
          <div class="modal-example-text">
            <strong>Trocar opção</strong><br>
            <span class="modal-hint">Ex: Cheddar ou Mussarela, sem custo extra</span>
          </div>
          <span class="modal-example-badge free">Sem custo</span>
        </div>
      </div>
      <button type="button" class="modal-btn" data-action="close-modal">
        Entendi, vamos lá!
      </button>
    </div>
  </div>
</div>
