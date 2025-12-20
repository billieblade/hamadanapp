<?php
// ----------------------------------------------------
// PÚBLICO: pode ser acessado sem login
// ----------------------------------------------------
$id = (int)($_GET['id'] ?? ($_SESSION['last_created_os'] ?? 0));
unset($_SESSION['last_created_os']);

$h = $pdo->prepare("SELECT wo.*, c.nome as cliente, c.tipo as cliente_tipo, u.name as autor
                    FROM work_orders wo
                    JOIN customers c ON c.id = wo.customer_id
                    JOIN users u ON u.id = wo.user_id
                    WHERE wo.id = ?");
$h->execute([$id]);
$wo = $h->fetch();

if(!$wo){
  echo "<div class='container py-4'><div class='alert alert-danger'>OS não encontrada.</div></div>";
  return;
}

$receiptStmt = $pdo->prepare("SELECT * FROM work_order_receipts WHERE work_order_id=? ORDER BY id DESC LIMIT 1");
$receiptStmt->execute([$id]);
$receipt = $receiptStmt->fetch();

$items = $pdo->prepare("SELECT * FROM work_order_items WHERE work_order_id=? ORDER BY id");
$items->execute([$id]);
$items = $items->fetchAll();

$svcs = $pdo->prepare("SELECT w.*, s.nome
                       FROM work_item_services w
                       JOIN services_all s ON s.id = w.service_id
                       WHERE w.work_item_id = ?");

$can_edit = (!empty($_SESSION['role']) && in_array($_SESSION['role'], ['master','funcionario'], true));

// atualiza status de peça
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_status'])){
  if(!$can_edit){ http_response_code(403); die('Acesso negado'); }
  $itemId = (int)$_POST['item_id'];
  $status = $_POST['status_item'];
  $pdo->prepare("UPDATE work_order_items SET status_item=? WHERE id=? AND work_order_id=?")
      ->execute([$status,$itemId,$id]);
  flash_set('Status da peça atualizado.');
  redirect('/?route=wo-view&id='.$id);
}

// atualiza status da OS
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_os_status'])){
  if(!$can_edit){ http_response_code(403); die('Acesso negado'); }
  $st = $_POST['os_status'] ?? 'aberta';
  $pdo->prepare("UPDATE work_orders SET status=? WHERE id=?")->execute([$st,$id]);
  if ($st === 'fechada') {
    $det = $pdo->prepare("SELECT wo.total, q.forma_pagto
                          FROM work_orders wo
                          LEFT JOIN quotes q ON q.id = wo.quote_id
                          WHERE wo.id = ?");
    $det->execute([$id]);
    $det = $det->fetch();
    if ($det && !empty($det['forma_pagto']) && !$receipt) {
      $pdo->prepare("INSERT INTO receipts (work_order_id, valor, forma_pagto, observacao)
                     VALUES (?,?,?,?)")
          ->execute([$id, $det['total'], $det['forma_pagto'], null]);
    }
  }
  flash_set('Status da OS atualizado.');
  redirect('/?route=wo-view&id='.$id);
}

// atualiza status de pagamento e gera recibo
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_payment'])){
  if(!$can_edit){ http_response_code(403); die('Acesso negado'); }
  $statusPagamento = $_POST['status_pagamento'] ?? 'pendente';
  $validStatus = ['pendente','pago','inadimplente'];
  if(!in_array($statusPagamento, $validStatus, true)){
    $statusPagamento = 'pendente';
  }

  $pdo->prepare("UPDATE work_orders SET status_pagamento=? WHERE id=?")
      ->execute([$statusPagamento,$id]);

  if($statusPagamento === 'pago'){
    $valorRaw = trim((string)($_POST['valor'] ?? ''));
    if($valorRaw === ''){
      $valor = (float)$wo['total'];
    } else {
      $valorNormalized = str_replace(' ', '', $valorRaw);
      if(strpos($valorNormalized, ',') !== false){
        $valorNormalized = str_replace('.', '', $valorNormalized);
        $valorNormalized = str_replace(',', '.', $valorNormalized);
      }
      $valor = (float)$valorNormalized;
    }

    $formaPagto = trim((string)($_POST['forma_pagto'] ?? ''));
    $banco = trim((string)($_POST['banco'] ?? ''));
    $emitidoEm = trim((string)($_POST['emitido_em'] ?? ''));
    if($emitidoEm === ''){
      $emitidoEm = date('Y-m-d');
    }
    if(strlen($emitidoEm) === 10){
      $emitidoEm .= ' 00:00:00';
    }
    $observacao = trim((string)($_POST['observacao'] ?? ''));

    if($receipt){
      $pdo->prepare("UPDATE work_order_receipts SET valor=?, forma_pagto=?, banco=?, emitido_em=?, observacao=? WHERE id=?")
          ->execute([$valor,$formaPagto,$banco,$emitidoEm,$observacao,$receipt['id']]);
    } else {
      $pdo->prepare("INSERT INTO work_order_receipts (work_order_id,valor,forma_pagto,banco,emitido_em,observacao) VALUES (?,?,?,?,?,?)")
          ->execute([$id,$valor,$formaPagto,$banco,$emitidoEm,$observacao]);
    }
  }

  flash_set('Status de pagamento atualizado.');
  redirect('/?route=wo-view&id='.$id);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>OS <?=h($wo['codigo_os'])?> — <?=h($wo['cliente'])?> (<?=$wo['cliente_tipo']?>)</h3>
  <div class="d-flex gap-2 flex-wrap">
    <?php if($receipt): ?>
      <a class="btn btn-outline-secondary" href="/?route=receipt-view&id=<?=$id?>">Imprimir recibo</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="/?route=labels-print&id=<?=$id?>">Imprimir etiquetas</a>
    <?php if($can_edit && $receipt): ?>
      <a class="btn btn-outline-secondary" href="/?route=receipt-view&id=<?=$id?>">Imprimir recibo</a>
    <?php endif; ?>
    <?php if($can_edit && $_SESSION['role']==='master'): ?>
      <a class="btn btn-outline-danger" href="/?route=wo-delete&id=<?=$id?>" onclick="return confirm('Remover OS inteira? Esta ação é irreversível.');">Excluir OS</a>
    <?php endif; ?>
  </div>
</div>

<?php if(($_GET['created'] ?? '')==='1'): ?>
  <div class="alert alert-success">
    <strong>OS criada!</strong> Código: <code><?=h($wo['codigo_os'])?></code> —
    Cliente: <strong><?=h($wo['cliente'])?></strong> —
    Total: <strong>R$ <?=number_format($wo['total'],2,',','.')?></strong>
  </div>
<?php endif; ?>

<div class="row g-3">
  <!-- coluna esquerda: resumo -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Resumo</div>
      <div class="card-body">
        <?php if($can_edit): ?>
        <form method="post" class="d-flex gap-2 align-items-end">
          <div class="flex-grow-1">
            <label class="form-label">Status OS</label>
            <select name="os_status" class="form-select">
              <?php foreach(['aberta','fechada','cancelada'] as $s): ?>
                <option value="<?=$s?>" <?=$wo['status']===$s?'selected':''?>><?=$s?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary" name="change_os_status" value="1">Salvar</button>
        </form>
        <hr>
        <?php else: ?>
          <div>Status OS: <strong><?=h($wo['status'])?></strong></div>
          <hr>
        <?php endif; ?>

        <?php if($can_edit): ?>
        <form method="post" class="mt-3">
          <div class="row g-2 align-items-end">
            <div class="col-12">
              <label class="form-label">Status pagamento</label>
              <select name="status_pagamento" class="form-select">
                <?php foreach(['pendente','pago','inadimplente'] as $s): ?>
                  <option value="<?=$s?>" <?=$wo['status_pagamento']===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Valor</label>
              <input class="form-control" name="valor" value="<?=h($receipt['valor'] ?? $wo['total'])?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Forma de pagamento</label>
              <select class="form-select" name="forma_pagto">
                <?php foreach(['dinheiro','debito','credito','pix','transferencia','boleto'] as $f): ?>
                  <option value="<?=$f?>" <?=($receipt['forma_pagto'] ?? '')===$f?'selected':''?>><?=$f?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Banco</label>
              <input class="form-control" name="banco" value="<?=h($receipt['banco'] ?? '')?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Emitido em</label>
              <input class="form-control" type="date" name="emitido_em" value="<?=h(isset($receipt['emitido_em']) ? substr($receipt['emitido_em'],0,10) : date('Y-m-d'))?>">
            </div>
            <div class="col-12">
              <label class="form-label">Observação</label>
              <textarea class="form-control" name="observacao" rows="2"><?=h($receipt['observacao'] ?? '')?></textarea>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" name="save_payment" value="1">Salvar pagamento</button>
            </div>
          </div>
        </form>
        <hr>
        <?php else: ?>
          <div>Status pagamento: <strong><?=h($wo['status_pagamento'])?></strong></div>
          <hr>
        <?php endif; ?>

        <div>Autor: <strong><?=h($wo['autor'])?></strong></div>
        <div>Data: <strong><?=h($wo['created_at'])?></strong></div>
        <hr>
        <div>Subtotal: <strong>R$ <?=number_format($wo['subtotal'],2,',','.')?></strong></div>
        <div>Desconto: <strong>R$ <?=number_format($wo['desconto'],2,',','.')?></strong></div>
        <div class="h5">Total: <strong>R$ <?=number_format($wo['total'],2,',','.')?></strong></div>
        <hr>
        <div class="mb-2"><strong>Recibo de pagamento</strong></div>
        <?php if($receipt): ?>
          <div>Valor: <strong>R$ <?=number_format($receipt['valor'],2,',','.')?></strong></div>
          <div>Forma: <strong><?=h($receipt['forma_pagto'])?></strong></div>
          <div>Emitido em: <strong><?=h($receipt['emitido_em'])?></strong></div>
          <?php if(!empty($receipt['observacao'])): ?>
            <div>Obs.: <?=h($receipt['observacao'])?></div>
          <?php endif; ?>
          <div class="mt-2">
            <a class="btn btn-sm btn-outline-secondary" href="/?route=receipt-view&id=<?=$id?>">Imprimir recibo</a>
          </div>
        <?php elseif($can_edit): ?>
          <form method="post" class="mt-2">
            <div class="mb-2">
              <label class="form-label">Forma de pagamento</label>
              <select name="receipt_forma_pagto" class="form-select form-select-sm" required>
                <option value="">Selecione</option>
                <option value="dinheiro">dinheiro</option>
                <option value="debito">debito</option>
                <option value="credito">credito</option>
                <option value="pix">pix</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Valor recebido</label>
              <input name="receipt_valor" type="number" step="0.01" class="form-control form-control-sm" value="<?=h($wo['total'])?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Observação</label>
              <input name="receipt_obs" class="form-control form-control-sm" placeholder="Opcional">
            </div>
            <button class="btn btn-sm btn-success" name="create_receipt" value="1">Registrar recibo</button>
          </form>
        <?php else: ?>
          <div class="text-muted">Nenhum recibo registrado.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- coluna direita: itens -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Peças</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead><tr><th>#</th><th>Etiqueta</th><th>Serviços</th><th>Status</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach($items as $it):
              $svcs->execute([$it['id']]);
              $linhas = $svcs->fetchAll(); ?>
              <tr>
                <td><?=$it['id']?></td>
                <td>
                  <code><?=$it['etiqueta_codigo']?></code><br>
                  <a class="btn btn-sm btn-outline-secondary mt-1" href="/?route=labels-print&id=<?=$id?>&single=<?=$it['id']?>">Etiqueta</a>
                </td>
                <td>
                  <ul class="mb-0">
                    <?php foreach($linhas as $l): ?>
                      <li><?=h($l['nome'])?> — <?=$l['unidade']?>: <?=$l['qtd']?> — R$ <?=number_format($l['subtotal'],2,',','.')?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td>
                  <?php if($can_edit): ?>
                    <form method="post" class="d-flex gap-2">
                      <input type="hidden" name="item_id" value="<?=$it['id']?>">
                      <select name="status_item" class="form-select form-select-sm">
                        <?php foreach(['EM_TRANSITO','LAVANDERIA','REPAROS','SECAGEM','ESPERANDO_ENTREGA','FINALIZADO'] as $s): ?>
                          <option value="<?=$s?>" <?=$it['status_item']===$s?'selected':''?>><?=str_replace('_',' ',$s)?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn btn-sm btn-primary" name="change_status" value="1">Salvar</button>
                    </form>
                  <?php else: ?>
                    <?=h($it['status_item'])?>
                  <?php endif; ?>
                </td>
                <td class="text-nowrap">R$ <?=number_format($it['subtotal'],2,',','.')?></td>
              </tr>
            <?php endforeach; if(!$items): ?>
              <tr><td colspan="5" class="text-center p-4">Sem peças nesta OS.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="alert alert-info mt-3">
      Dica: leitores de QR podem abrir uma URL que <strong>muda o status automaticamente</strong>.<br>
      Exemplo: <code>/?route=status-update&code=ET-XYZ&to=LAVANDERIA</code><br>
      (É necessário estar logado para alterar status.)
    </div>
  </div>
</div>
