<?php
require_login();
$woId = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT r.*, wo.codigo_os, wo.total, wo.status_pagamento, c.nome as cliente, c.cpf_cnpj, c.endereco, c.telefone
                     FROM work_order_receipts r
                     JOIN work_orders wo ON wo.id = r.work_order_id
                     JOIN customers c ON c.id = wo.customer_id
                     WHERE r.work_order_id = ?
                     ORDER BY r.id DESC
                     LIMIT 1");
$st->execute([$woId]);
$receipt = $st->fetch();

if(!$receipt){
  echo "<div class='container py-4'><div class='alert alert-danger'>Recibo não encontrado.</div></div>";
  return;
}

$emitidoEm = $receipt['emitido_em'] ? date('d/m/Y', strtotime($receipt['emitido_em'])) : '';
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/style.css">
  <title>Recibo</title>
  <style>
    @media print { .no-print { display: none; } }
    .receipt-box { border: 1px solid #ddd; padding: 24px; border-radius: 8px; }
    .receipt-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .receipt-title { font-size: 24px; font-weight: 600; }
  </style>
</head>
<body>
  <div class="container py-4">
    <button class="btn btn-outline-secondary no-print mb-3" onclick="window.print()">Imprimir</button>
    <div class="receipt-box">
      <div class="receipt-header mb-4">
        <div>
          <img src="/assets/hamadan-logo.png" alt="Hamadan" style="max-height:64px;">
        </div>
        <div class="text-end">
          <div class="receipt-title">Recibo</div>
          <div>OS: <strong><?=h($receipt['codigo_os'])?></strong></div>
          <div>Emitido em: <strong><?=h($emitidoEm)?></strong></div>
        </div>
      </div>

      <div class="mb-3">
        <div><strong>Cliente:</strong> <?=h($receipt['cliente'])?></div>
        <?php if(!empty($receipt['cpf_cnpj'])): ?>
          <div><strong>CPF/CNPJ:</strong> <?=h($receipt['cpf_cnpj'])?></div>
        <?php endif; ?>
        <?php if(!empty($receipt['telefone'])): ?>
          <div><strong>Telefone:</strong> <?=h($receipt['telefone'])?></div>
        <?php endif; ?>
        <?php if(!empty($receipt['endereco'])): ?>
          <div><strong>Endereço:</strong> <?=h($receipt['endereco'])?></div>
        <?php endif; ?>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div><strong>Valor:</strong> R$ <?=number_format($receipt['valor'],2,',','.')?></div>
          <div><strong>Forma de pagamento:</strong> <?=h($receipt['forma_pagto'] ?: '-')?></div>
        </div>
        <div class="col-md-6">
          <div><strong>Banco:</strong> <?=h($receipt['banco'] ?: '-')?></div>
          <div><strong>Status:</strong> <?=h($receipt['status_pagamento'])?></div>
        </div>
      </div>

      <?php if(!empty($receipt['observacao'])): ?>
        <div class="mb-3">
          <strong>Observação:</strong>
          <div><?=nl2br(h($receipt['observacao']))?></div>
        </div>
      <?php endif; ?>

      <div class="mt-4">
        <div>Assinatura: ____________________________________________</div>
      </div>
    </div>
  </div>
</body>
</html>
