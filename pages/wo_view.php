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
  flash_set('Status da OS atualizado.');
  redirect('/?route=wo-view&id='.$id);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>OS <?=h($wo['codigo_os'])?> — <?=h($wo['cliente'])?> (<?=$wo['cliente_tipo']?>)</h3>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="/?route=receipt-view&id=<?=$id?>">Imprimir recibo</a>
    <a class="btn btn-outline-secondary" href="/?route=labels-print&id=<?=$id?>">Imprimir etiquetas</a>
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

        <div>Autor: <strong><?=h($wo['autor'])?></strong></div>
        <div>Data: <strong><?=h($wo['created_at'])?></strong></div>
        <hr>
        <div>Subtotal: <strong>R$ <?=number_format($wo['subtotal'],2,',','.')?></strong></div>
        <div>Desconto: <strong>R$ <?=number_format($wo['desconto'],2,',','.')?></strong></div>
        <div class="h5">Total: <strong>R$ <?=number_format($wo['total'],2,',','.')?></strong></div>
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
