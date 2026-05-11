<?php

$cities = is_array($cities ?? null) ? $cities : [];
$zonesByCity = is_array($zonesByCity ?? null) ? $zonesByCity : [];
$address = is_array($address ?? null) ? $address : [];

$selectedCityId = (int) ($selectedCityId ?? 0);
$selectedZoneId = (int) ($selectedZoneId ?? 0);
$isEdit = !empty($isEdit);
$initialZones = ($selectedCityId && isset($zonesByCity[$selectedCityId])) ? $zonesByCity[$selectedCityId] : [];
?>
<section class="card">
  <h2>Identificacao do endereco</h2>
  <p class="description">De um nome para facilitar a identificacao</p>

  <label class="field">
    <span>Nome do endereco</span>
    <input type="text" name="label" id="label-input" value="<?= e($address['label'] ?? '') ?>" placeholder="Ex: Casa, Trabalho, Casa da vo..." maxlength="50">
  </label>

  <div class="label-presets">
    <button type="button" class="label-preset" data-label="Casa">Casa</button>
    <button type="button" class="label-preset" data-label="Trabalho">Trabalho</button>
    <button type="button" class="label-preset" data-label="Outros">Outros</button>
  </div>
</section>

<section class="card">
  <h2>Dados do destinatario</h2>

  <label class="field">
    <span>Nome completo</span>
    <input type="text" name="name" value="<?= e($address['name'] ?? '') ?>" placeholder="Quem vai receber" required>
  </label>

  <label class="field">
    <span>Telefone / WhatsApp</span>
    <input type="tel" name="phone" id="phone-input" value="<?= e($address['phone'] ?? '') ?>" placeholder="(51) 99999-0000" required>
  </label>
</section>

<section class="card">
  <h2>Localizacao</h2>

  <label class="field">
    <span>Cidade atendida</span>
    <select id="city-select" name="city_id" required>
      <option value="">Selecione a cidade</option>
      <?php foreach ($cities as $city): $cityId = (int) ($city['id'] ?? 0); ?>
        <option value="<?= $cityId ?>"<?= $cityId === $selectedCityId ? ' selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="field">
    <span>Bairro</span>
    <select id="zone-select" name="zone_id" required<?= $selectedCityId ? '' : ' disabled' ?>>
      <option value=""><?= $selectedCityId ? 'Selecione o bairro' : 'Escolha a cidade primeiro' ?></option>
      <?php foreach ($initialZones as $zone): $zoneId = (int) ($zone['id'] ?? 0); ?>
        <option value="<?= $zoneId ?>" data-city-name="<?= e($zone['city_name'] ?? '') ?>" data-zone-name="<?= e($zone['name'] ?? '') ?>"<?= $zoneId === $selectedZoneId ? ' selected' : '' ?>>
          <?= e($zone['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <input type="hidden" name="city" id="city-name" value="<?= e($address['city'] ?? '') ?>">
  <input type="hidden" name="neighborhood" id="zone-name" value="<?= e($address['neighborhood'] ?? '') ?>">
</section>

<section class="card">
  <h2>Endereco completo</h2>

  <label class="field street-ac-wrap">
    <span>Rua / Avenida</span>
    <input type="text" name="street" id="addr-street" value="<?= e($address['street'] ?? '') ?>" placeholder="Digite para buscar a rua..." required autocomplete="off">
    <div id="addr-street-ac-list" class="street-ac-list"></div>
  </label>

  <label class="field">
    <span>Numero</span>
    <input type="number" name="number" value="<?= e($address['number'] ?? '') ?>" placeholder="123" required min="1" step="1">
  </label>

  <label class="field">
    <span>Complemento (opcional)</span>
    <input type="text" name="complement" value="<?= e($address['complement'] ?? '') ?>" placeholder="Apto, bloco, casa">
  </label>

  <label class="field">
    <span>Ponto de referencia</span>
    <textarea name="reference" placeholder="Ajude o entregador a encontrar mais rapido"><?= e($address['reference'] ?? '') ?></textarea>
  </label>
</section>
