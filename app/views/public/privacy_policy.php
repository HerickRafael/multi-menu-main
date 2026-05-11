<?php
ob_start();
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slug = trim($slug, '/');
$backUrl = $slug !== '' ? base_url(rawurlencode($slug)) : base_url('/');
$companyName = e($company['name'] ?? 'Estabelecimento');
$companyWhatsappRaw = preg_replace('/\D/', '', $company['whatsapp'] ?? '');
$companyWhatsapp = e($company['whatsapp'] ?? '');
$showFooterMenu = true;
?>
<div class="max-w-3xl mx-auto p-4 pb-20">
  <a href="<?= e($backUrl) ?>" class="inline-flex items-center gap-1 text-sm text-blue-600 mb-4">&larr; Voltar ao cardápio</a>

  <h1 class="text-2xl font-bold mb-4">Política de Privacidade</h1>
  <p class="text-xs text-gray-400 mb-6">Última atualização: 07/05/2026</p>

  <div class="prose prose-sm max-w-none text-gray-700 space-y-4">
    <section>
      <h2 class="text-lg font-semibold">1. Informações que coletamos</h2>
      <p>Ao utilizar o cardápio digital de <strong><?= $companyName ?></strong>, podemos coletar:</p>
      <ul class="list-disc pl-5 space-y-1">
        <li>Nome e número de WhatsApp (para identificação e comunicação sobre pedidos);</li>
        <li>Endereço de entrega (quando aplicável);</li>
        <li>Histórico de pedidos (para melhorar sua experiência);</li>
        <li>Dados de navegação (cookies, IP) para funcionamento técnico do sistema.</li>
      </ul>
    </section>

    <section>
      <h2 class="text-lg font-semibold">2. Como usamos seus dados</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li>Processar e entregar seus pedidos;</li>
        <li>Enviar confirmações e atualizações de pedido via WhatsApp;</li>
        <li>Enviar comunicações sobre promoções (apenas com seu consentimento);</li>
        <li>Melhorar nossos produtos e serviços.</li>
      </ul>
    </section>

    <section>
      <h2 class="text-lg font-semibold">3. Compartilhamento de dados</h2>
      <p>Seus dados <strong>não são vendidos</strong> a terceiros. Podem ser compartilhados apenas com:</p>
      <ul class="list-disc pl-5 space-y-1">
        <li>Serviços de entrega (quando necessário para completar seu pedido);</li>
        <li>Provedores de infraestrutura técnica (hospedagem, processamento).</li>
      </ul>
    </section>

    <section>
      <h2 class="text-lg font-semibold">4. Seus direitos (LGPD)</h2>
      <p>Conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), você pode:</p>
      <ul class="list-disc pl-5 space-y-1">
        <li>Solicitar acesso aos seus dados pessoais;</li>
        <li>Corrigir dados incompletos ou desatualizados;</li>
        <li>Solicitar a exclusão dos seus dados;</li>
        <li>Revogar o consentimento a qualquer momento.</li>
      </ul>
      <?php if ($companyWhatsapp): ?>
      <p class="mt-2">Para exercer seus direitos, entre em contato pelo WhatsApp:
        <a href="https://wa.me/<?= e($companyWhatsappRaw) ?>" target="_blank" rel="noopener noreferrer" class="text-green-600 hover:underline"><strong><?= $companyWhatsapp ?></strong></a>
      </p>
      <?php endif; ?>
    </section>

    <section>
      <h2 class="text-lg font-semibold">5. Segurança</h2>
      <p>Adotamos medidas técnicas e organizacionais para proteger seus dados, incluindo criptografia, controle de acesso e monitoramento contínuo.</p>
    </section>

    <section>
      <h2 class="text-lg font-semibold">6. Cookies</h2>
      <p>Utilizamos cookies essenciais para manter sua sessão ativa e o carrinho de compras funcionando. Não utilizamos cookies de rastreamento de terceiros, exceto o Google Analytics quando configurado pelo estabelecimento.</p>
    </section>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
