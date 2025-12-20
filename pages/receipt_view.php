<?php
// pages/receipt_view.php
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT r.*, wo.codigo_os, wo.created_at AS wo_created_at, wo.status AS wo_status,
                            wo.subtotal, wo.desconto, wo.total, c.nome AS cliente_nome, c.tipo AS cliente_tipo,
                            c.cpf_cnpj, c.email, c.telefone, c.endereco, u.name AS autor
                     FROM receipts r
                     JOIN work_orders wo ON wo.id = r.work_order_id
                     JOIN customers c ON c.id = wo.customer_id
                     JOIN users u ON u.id = wo.user_id
                     WHERE r.work_order_id = ?
                     ORDER BY r.emitido_em DESC, r.id DESC
                     LIMIT 1");
$st->execute([$id]);
$recibo = $st->fetch();

if (!$recibo) {
  echo "<div class='container py-4'><div class='alert alert-danger'>Recibo não encontrado para esta OS.</div></div>";
  return;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/style.css">
  <title>Recibo OS <?=h($recibo['codigo_os'])?></title>
  <style>@media print {.no-print{display:none}}</style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Recibo da OS <?=h($recibo['codigo_os'])?></h3>
      <button class="btn btn-outline-secondary no-print" onclick="window.print()">Imprimir</button>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">Cliente</div>
          <div class="card-body">
            <div><strong><?=h($recibo['cliente_nome'])?></strong> (<?=h($recibo['cliente_tipo'])?>)</div>
            <?php if(!empty($recibo['cpf_cnpj'])): ?><div>CPF/CNPJ: <?=h($recibo['cpf_cnpj'])?></div><?php endif; ?>
            <?php if(!empty($recibo['telefone'])): ?><div>Telefone: <?=h($recibo['telefone'])?></div><?php endif; ?>
            <?php if(!empty($recibo['email'])): ?><div>E-mail: <?=h($recibo['email'])?></div><?php endif; ?>
            <?php if(!empty($recibo['endereco'])): ?><div>Endereço: <?=h($recibo['endereco'])?></div><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">Resumo da OS</div>
          <div class="card-body">
            <div>Autor: <strong><?=h($recibo['autor'])?></strong></div>
            <div>Data OS: <strong><?=h($recibo['wo_created_at'])?></strong></div>
            <div>Status: <strong><?=h($recibo['wo_status'])?></strong></div>
            <hr>
            <div>Subtotal: <strong>R$ <?=number_format($recibo['subtotal'],2,',','.')?></strong></div>
            <div>Desconto: <strong>R$ <?=number_format($recibo['desconto'],2,',','.')?></strong></div>
            <div>Total: <strong>R$ <?=number_format($recibo['total'],2,',','.')?></strong></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">Detalhes do recibo</div>
      <div class="card-body">
        <div>Valor recebido: <strong>R$ <?=number_format($recibo['valor'],2,',','.')?></strong></div>
        <div>Forma de pagamento: <strong><?=h($recibo['forma_pagto'])?></strong></div>
        <div>Emitido em: <strong><?=h($recibo['emitido_em'])?></strong></div>
        <?php if(!empty($recibo['observacao'])): ?>
          <div class="mt-2">Observação: <?=h($recibo['observacao'])?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
