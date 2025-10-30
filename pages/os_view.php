<?php
$id = (int)($_GET['id'] ?? 0);
$os = $pdo->prepare("SELECT w.*, q.total, c.nome as cliente FROM work_orders w JOIN quotes q ON q.id=w.quote_id JOIN customers c ON c.id=q.customer_id WHERE w.id=?");
$os->execute([$id]); $os=$os->fetch();
if(!$os){ echo '<div class="container py-4"><div class="alert alert-danger">OS não encontrada</div></div>'; return; }
$items = $pdo->prepare("SELECT woi.*, qi.subtotal, s.nome as servico FROM work_order_items woi JOIN quote_items qi ON qi.id=woi.quote_item_id JOIN services s ON s.id=qi.service_id WHERE woi.work_order_id=?");
$items->execute([$id]); $items=$items->fetchAll();
?>
<div class="container py-4">
  <h3>OS <?=$os['codigo_os']?> — <?=h($os['cliente'])?></h3>
  <p>Status: <span class="badge bg-secondary"><?=$os['status']?></span> | Total: R$ <?=number_format($os['total'],2,',','.')?></p>
  <table class="table table-sm table-striped">
    <thead><tr><th>Item</th><th>Serviço</th><th>Etiqueta</th><th>Ações</th></tr></thead>
    <tbody>
      <?php foreach($items as $i): ?>
      <tr>
        <td><?=$i['quote_item_id']?></td>
        <td><?=h($i['servico'])?></td>
        <td><code><?=$i['etiqueta_codigo']?></code></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="/?route=labels-print&code=<?=urlencode($i['etiqueta_codigo'])?>">Imprimir etiqueta</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
