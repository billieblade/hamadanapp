<?php
require_login();

$dia = $_GET['dia'] ?? '';
$usuario = $_GET['user'] ?? '';
$status = $_GET['status'] ?? '';

$users = $pdo->query("SELECT id, name FROM users WHERE ativo=1 ORDER BY name")->fetchAll();
$statuses = ['aberta'=>'Aberta','coleta'=>'Coleta','lavagem'=>'Lavagem','acabamento'=>'Acabamento','pronta'=>'Pronta','entregue'=>'Entregue'];

$sql = "SELECT w.id, w.codigo_os, w.status, w.created_at, q.total, c.nome AS cliente, u.name AS responsavel,
               COUNT(DISTINCT woi.id) AS qtd_itens,
               SUM(CASE WHEN woi.status_item='ENTREGUE' THEN 1 ELSE 0 END) AS itens_entregues
        FROM work_orders w
        JOIN quotes q   ON q.id = w.quote_id
        JOIN customers c ON c.id = q.customer_id
        LEFT JOIN users u ON u.id = q.user_id
        LEFT JOIN work_order_items woi ON woi.work_order_id = w.id
        WHERE 1=1";
$params = [];

if ($dia !== '') {
  $sql .= " AND DATE(w.created_at) = ?";
  $params[] = $dia;
}
if ($usuario !== '') {
  $sql .= " AND q.user_id = ?";
  $params[] = (int)$usuario;
}
if ($status !== '' && array_key_exists($status, $statuses)) {
  $sql .= " AND w.status = ?";
  $params[] = $status;
}

$sql .= " GROUP BY w.id, w.codigo_os, w.status, w.created_at, q.total, c.nome, u.name";
$sql .= " ORDER BY w.created_at DESC";
$sql .= " LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);
$ordens = $st->fetchAll();

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
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="mb-0">Serviços (Ordens de Serviço)</h3>
      <small class="text-muted">Visualize as ordens de serviço por data ou responsável.</small>
    </div>
    <a class="btn btn-primary" href="/?route=quotes-new">Nova Ordem de Serviço</a>
  </div>

  <form class="row g-2 mb-4" method="get">
    <input type="hidden" name="route" value="services">
    <div class="col-sm-3">
      <label class="form-label">Dia</label>
      <input type="date" class="form-control" name="dia" value="<?=h($dia)?>">
    </div>
    <div class="col-sm-3">
      <label class="form-label">Responsável</label>
      <select class="form-select" name="user">
        <option value="">Todos</option>
        <?php foreach($users as $u): ?>
          <option value="<?=$u['id']?>" <?=$usuario!=='' && (int)$usuario===$u['id']?'selected':''?>><?=h($u['name'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-3">
      <label class="form-label">Status da OS</label>
      <select class="form-select" name="status">
        <option value="">Todos</option>
        <?php foreach($statuses as $key=>$label): ?>
          <option value="<?=$key?>" <?=$status===$key?'selected':''?>><?=$label?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-3 d-flex align-items-end gap-2">
      <button class="btn btn-outline-secondary flex-fill">Filtrar</button>
      <a class="btn btn-outline-dark" href="/?route=services">Limpar</a>
    </div>
  </form>

  <?php if(!$ordens): ?>
    <div class="alert alert-warning">Nenhuma ordem de serviço encontrada para os filtros informados.</div>
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
          <div class="col-md-3"><strong>Peças:</strong> <?=$os['qtd_itens']?> (<?=$os['itens_entregues']?> entregues)</div>
          <div class="col-md-3"><strong>Total:</strong> R$ <?=number_format($os['total'],2,',','.')?></div>
          <div class="col-md-2 text-md-end"><a class="btn btn-sm btn-outline-primary" href="/?route=os-view&id=<?=$os['id']?>">Ver detalhes</a></div>
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
          <div class="alert alert-light mb-0">Nenhuma peça vinculada a esta ordem de serviço.</div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
