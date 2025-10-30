<?php
require_login();

$st = $pdo->query("
  SELECT woi.id, woi.etiqueta_codigo, w.codigo_os, woi.status_item,
         c.nome AS cliente, s.nome AS servico
  FROM work_order_items woi
  JOIN work_orders w ON w.id=woi.work_order_id
  JOIN quotes q ON q.id=w.quote_id
  JOIN customers c ON c.id=q.customer_id
  JOIN quote_items qi ON qi.id=woi.quote_item_id
  JOIN services_all s ON s.id = (
    SELECT qis.service_all_id FROM quote_item_services qis WHERE qis.quote_item_id=qi.id LIMIT 1
  )
  ORDER BY woi.id DESC
");

$itens = $st->fetchAll();

$estagios = [
  'EM_TRANSITO'=>'Em Trânsito',
  'LAVAGEM'=>'Lavagem',
  'SECAGEM'=>'Secagem',
  'RESTAURACAO'=>'Restauração',
  'PRONTO'=>'Pronto',
  'ENTREGUE'=>'Entregue'
];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set_status'])){
  $upd = $pdo->prepare("UPDATE work_order_items SET status_item=? WHERE id=?");
  $upd->execute([$_POST['novo_status'], (int)$_POST['id']]);
  redirect('/?route=tapetes');
}
?>
<div class="container py-4">
  <h3>Tapetes</h3>
  <table class="table table-sm table-striped">
    <thead><tr>
      <th>#</th><th>Etiqueta</th><th>OS</th><th>Cliente</th><th>Serviço Principal</th><th>Status</th><th>Ações</th>
    </tr></thead>
    <tbody>
      <?php foreach($itens as $r): ?>
      <tr>
        <td><?=$r['id']?></td>
        <td><code><?=$r['etiqueta_codigo']?></code></td>
        <td><?=$r['codigo_os']?></td>
        <td><?=h($r['cliente'])?></td>
        <td><?=h($r['servico'])?></td>
        <td>
          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="id" value="<?=$r['id']?>">
            <select name="novo_status" class="form-select form-select-sm">
              <?php foreach($estagios as $k=>$v): ?>
                <option value="<?=$k?>" <?=$k==$r['status_item']?'selected':''?>><?=$v?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary" name="set_status" value="1">Atualizar</button>
          </form>
        </td>
        <td>
          <a class="btn btn-sm btn-outline-secondary" href="/?route=labels-print&code=<?=urlencode($r['etiqueta_codigo'])?>">Etiqueta</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
