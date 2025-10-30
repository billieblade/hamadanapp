<?php
require_role(['master']);

$ini = $_GET['ini'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-d');

$periodo_ini = $ini.' 00:00:00';
$periodo_fim = $fim.' 23:59:59';

$ordens_stmt = $pdo->prepare("SELECT w.id, w.codigo_os, w.status, w.created_at,
                                     q.total, c.nome AS cliente, u.name AS responsavel
                              FROM work_orders w
                              JOIN quotes q ON q.id = w.quote_id
                              JOIN customers c ON c.id = q.customer_id
                              LEFT JOIN users u ON u.id = q.user_id
                              WHERE w.created_at BETWEEN ? AND ?
                              ORDER BY w.created_at DESC");
$ordens_stmt->execute([$periodo_ini, $periodo_fim]);
$ordens = $ordens_stmt->fetchAll();

$itens_por_os = [];
if ($ordens) {
  $ids = array_column($ordens, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT woi.work_order_id, woi.id, woi.etiqueta_codigo, woi.status_item,
                                qi.tipo_tapete, qi.largura_cm, qi.comprimento_cm, qi.diametro_cm, qi.qtd,
                                GROUP_CONCAT(DISTINCT sa.nome ORDER BY sa.nome SEPARATOR ', ') AS servicos
                         FROM work_order_items woi
                         JOIN quote_items qi ON qi.id = woi.quote_item_id
                         LEFT JOIN quote_item_services qis ON qis.quote_item_id = qi.id
                         LEFT JOIN services_all sa ON sa.id = qis.service_all_id
                         WHERE woi.work_order_id IN ($placeholders)
                         GROUP BY woi.id
                         ORDER BY woi.work_order_id, woi.id");
  $stmt->execute($ids);
  foreach ($stmt as $row) {
    $itens_por_os[$row['work_order_id']][] = $row;
  }
}

$totais = ['ordens'=>count($ordens), 'pecas'=>0, 'valor'=>0.0];
$status_counts = [];
foreach ($ordens as $os) {
  $totais['valor'] += (float)$os['total'];
  $status = $os['status'];
  $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
  $totais['pecas'] += count($itens_por_os[$os['id']] ?? []);
}
?>
<div class="container py-4">
  <h3>Relatórios de Ordens de Serviço</h3>
  <p class="text-muted">Consulte todas as ordens de serviço geradas no período e as peças vinculadas a cada uma.</p>

  <form class="row g-2 mb-4" method="get">
    <input type="hidden" name="route" value="reports">
    <div class="col-md-3">
      <label class="form-label">De</label>
      <input type="date" name="ini" class="form-control" value="<?=h($ini)?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Até</label>
      <input type="date" name="fim" class="form-control" value="<?=h($fim)?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-outline-secondary w-100">Aplicar</button>
    </div>
  </form>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted">Ordens de serviço</div>
          <div class="display-6"><?=$totais['ordens']?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted">Peças vinculadas</div>
          <div class="display-6"><?=$totais['pecas']?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted">Valor total do período</div>
          <div class="display-6">R$ <?=number_format($totais['valor'],2,',','.')?></div>
        </div>
      </div>
    </div>
  </div>

  <?php if($status_counts): ?>
    <div class="mb-4">
      <h5>Status das ordens</h5>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach($status_counts as $st=>$count): ?>
          <span class="badge bg-secondary p-2 text-uppercase"><?=$st?>: <?=$count?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if(!$ordens): ?>
    <div class="alert alert-warning">Nenhuma ordem de serviço encontrada no período selecionado.</div>
  <?php endif; ?>

  <?php foreach($ordens as $os): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <strong><?=h($os['codigo_os'] ?? ('OS-'.$os['id']))?></strong>
          <span class="badge bg-secondary ms-2 text-uppercase"><?=h($os['status'])?></span>
        </div>
        <div class="text-end small text-muted">
          <div>Data: <?=date('d/m/Y H:i', strtotime($os['created_at']))?></div>
          <div>Responsável: <?=h($os['responsavel'] ?? '—')?></div>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-2">
          <div class="col-md-4"><strong>Cliente:</strong> <?=h($os['cliente'])?></div>
          <div class="col-md-3"><strong>Valor:</strong> R$ <?=number_format($os['total'],2,',','.')?></div>
          <div class="col-md-2"><strong>Peças:</strong> <?=count($itens_por_os[$os['id']] ?? [])?></div>
          <div class="col-md-3 text-md-end"><a class="btn btn-sm btn-outline-primary" href="/?route=os-view&id=<?=$os['id']?>">Ver detalhes</a></div>
        </div>
        <?php $pecas = $itens_por_os[$os['id']] ?? []; ?>
        <?php if($pecas): ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Etiqueta</th>
                  <th>Tipo</th>
                  <th>Dimensões</th>
                  <th>Qtd</th>
                  <th>Serviços</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($pecas as $p): ?>
                  <tr>
                    <td><?=$p['id']?></td>
                    <td><code><?=h($p['etiqueta_codigo'])?></code></td>
                    <td><?=h($p['tipo_tapete'])?></td>
                    <td>
                      <?php if($p['tipo_tapete']==='redondo'): ?>
                        Ø <?=number_format($p['diametro_cm'],0,',','.')?> cm
                      <?php else: ?>
                        <?=number_format($p['largura_cm'],0,',','.')?> x <?=number_format($p['comprimento_cm'],0,',','.')?> cm
                      <?php endif; ?>
                    </td>
                    <td><?=number_format($p['qtd'],0,',','.')?></td>
                    <td><?=h($p['servicos'] ?? '—')?></td>
                    <td><?=h($p['status_item'])?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-light mb-0">Nenhuma peça vinculada a esta ordem.</div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
