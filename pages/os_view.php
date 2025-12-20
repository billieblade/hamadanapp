<?php
$id = (int)($_GET['id'] ?? 0);
$os = $pdo->prepare("SELECT w.*, q.total, c.nome as cliente FROM work_orders w JOIN quotes q ON q.id=w.quote_id JOIN customers c ON c.id=q.customer_id WHERE w.id=?");
$os->execute([$id]); $os=$os->fetch();
if(!$os){ echo '<div class="container py-4"><div class="alert alert-danger">OS não encontrada</div></div>'; return; }
$items = $pdo->prepare("SELECT woi.*, qi.tipo_tapete, qi.largura_cm, qi.comprimento_cm, qi.diametro_cm, qi.qtd
                        FROM work_order_items woi
                        JOIN quote_items qi ON qi.id=woi.quote_item_id
                        WHERE woi.work_order_id=?");
$items->execute([$id]); $items=$items->fetchAll();
$svcs = $pdo->prepare("SELECT wis.*, s.nome, s.unidade
                       FROM work_item_services wis
                       JOIN services_all s ON s.id = wis.service_id
                       WHERE wis.work_item_id = ?");
?>
<div class="container py-4">
  <h3>OS <?=$os['codigo_os']?> — <?=h($os['cliente'])?></h3>
  <p>Status: <span class="badge bg-secondary"><?=$os['status']?></span> | Total: R$ <?=number_format($os['total'],2,',','.')?></p>
  <table class="table table-sm table-striped">
    <thead><tr><th>Item</th><th>Serviços</th><th>Etiqueta</th><th>Lacre</th><th>Ações</th></tr></thead>
    <tbody>
      <?php foreach($items as $i): ?>
      <tr>
        <td><?=$i['quote_item_id']?></td>
        <td>
          <?php $svcs->execute([$i['id']]); $linhas = $svcs->fetchAll(); ?>
          <?php if($linhas): ?>
            <ul class="mb-0">
              <?php foreach($linhas as $l): ?>
                <li><?=h($l['nome'])?> — <?=$l['unidade']?>: <?=$l['qtd']?> — R$ <?=number_format($l['subtotal'],2,',','.')?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <em class="text-muted">Sem serviços vinculados.</em>
          <?php endif; ?>
        </td>
        <td><code><?=$i['etiqueta_codigo']?></code></td>
        <td><?=h($i['lacre_numero'] ?? '—')?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="/?route=labels-print&id=<?=$id?>&single=<?=$i['id']?>">Imprimir etiqueta</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
