<?php
$statuses = ['EM_TRANSITO','LAVANDERIA','REPAROS','SECAGEM','ESPERANDO_ENTREGA','FINALIZADO'];
$counts = [];
foreach($statuses as $s){ $st = $pdo->prepare("SELECT COUNT(*) FROM work_order_items WHERE status_item=?"); $st->execute([$s]); $counts[$s] = (int)$st->fetchColumn(); }
$os = $pdo->query("SELECT wo.id,wo.codigo_os,wo.total,wo.created_at,c.nome FROM work_orders wo JOIN customers c ON c.id=wo.customer_id ORDER BY wo.id DESC LIMIT 10")->fetchAll();
?>
<div class="row g-3">
  <?php foreach($statuses as $s): ?>
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card text-center"><div class="card-body">
      <div class="h6"><?=str_replace('_',' ', $s)?></div>
      <div class="h3 mb-0"><?=$counts[$s]?></div>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>
<div class="card mt-4"><div class="card-header">Últimas OS</div><div class="card-body p-0">
<div class="table-responsive"><table class="table table-striped mb-0">
<thead><tr><th>OS</th><th>Cliente</th><th>Valor</th><th>Data</th><th></th></tr></thead><tbody>
<?php foreach($os as $r): ?><tr>
<td><?=h($r['codigo_os'])?></td><td><?=h($r['nome'])?></td><td>R$ <?=number_format($r['total'],2,',','.')?></td><td><?=h($r['created_at'])?></td>
<td><a class="btn btn-sm btn-outline-primary" href="/?route=wo-view&id=<?=$r['id']?>">Abrir</a></td></tr><?php endforeach; if(!$os): ?>
<tr><td colspan="5" class="text-center p-4">Nenhuma OS ainda.</td></tr>
<?php endif; ?>
</tbody></table></div></div></div>
