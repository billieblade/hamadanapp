<?php
require_role(['master']);
$ini = $_GET['ini'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-d');

$diario = $pdo->query("SELECT DATE(created_at) dia, COUNT(*) os, SUM(q.total) valor
FROM work_orders w JOIN quotes q ON q.id=w.quote_id
GROUP BY DATE(created_at) ORDER BY dia DESC LIMIT 30")->fetchAll();

$ps = $pdo->prepare("SELECT s.nome, s.unidade, SUM(qi.qtd) qtd, SUM(qi.subtotal) total
FROM quote_items qi JOIN services s ON s.id=qi.service_id
JOIN quotes q ON q.id=qi.quote_id
WHERE q.created_at BETWEEN ? AND ?
GROUP BY s.id ORDER BY total DESC LIMIT 50");
$ps->execute([$ini.' 00:00:00',$fim.' 23:59:59']);
$por_servico = $ps->fetchAll();

if(isset($_GET['export']) && $_GET['export']==='csv'){
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=relatorio.csv');
  $out = fopen('php://output', 'w');
  if($_GET['type']==='por_servico'){
    fputcsv($out, ['Serviço','Unidade','Qtd','Total']);
    $stmt = $pdo->prepare("SELECT s.nome, s.unidade, SUM(qi.qtd) qtd, SUM(qi.subtotal) total
      FROM quote_items qi JOIN services s ON s.id=qi.service_id
      JOIN quotes q ON q.id=qi.quote_id
      WHERE q.created_at BETWEEN ? AND ?
      GROUP BY s.id ORDER BY total DESC");
    $stmt->execute([$ini.' 00:00:00', $fim.' 23:59:59']);
    foreach($stmt as $r){ fputcsv($out, [$r['nome'],$r['unidade'],$r['qtd'],$r['total']]); }
    fclose($out); exit;
  }
}

?>
<div class="container py-4">
  <h3>Relatórios</h3>
  <form class="row g-2 mb-3">
    <input type="hidden" name="route" value="reports">
    <div class="col-auto"><label class="form-label">De</label><input type="date" name="ini" class="form-control" value="<?=h($ini)?>"></div>
    <div class="col-auto"><label class="form-label">Até</label><input type="date" name="fim" class="form-control" value="<?=h($fim)?>"></div>
    <div class="col-auto align-self-end"><button class="btn btn-outline-secondary">Aplicar</button></div>
  </form>

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">OS por dia (últimos 30 registros)</div>
        <div class="card-body">
          <table class="table table-sm">
            <thead><tr><th>Dia</th><th>OS</th><th>Valor</th></tr></thead>
            <tbody>
              <?php foreach($diario as $r): ?>
                <tr><td><?=$r['dia']?></td><td><?=$r['os']?></td><td>R$ <?=number_format($r['valor'],2,',','.')?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Top serviços (por período)</span>
          <a class="btn btn-sm btn-outline-secondary" href="/?route=reports&ini=<?=$ini?>&fim=<?=$fim?>&export=csv&type=por_servico">Exportar CSV</a>
        </div>
        <div class="card-body">
          <table class="table table-sm">
            <thead><tr><th>Serviço</th><th>Unid</th><th>Qtd</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach($por_servico as $r): ?>
                <tr><td><?=h($r['nome'])?></td><td><?=$r['unidade']?></td><td><?=$r['qtd']?></td><td>R$ <?=number_format($r['total'],2,',','.')?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
