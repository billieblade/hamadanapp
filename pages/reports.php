<?php
require_login();
$users=$pdo->query("SELECT id,name FROM users WHERE ativo=1 ORDER BY name")->fetchAll();
$customers=$pdo->query("SELECT id,nome FROM customers ORDER BY nome")->fetchAll();
$u=(int)($_GET['user_id']??0); $cst=(int)($_GET['customer_id']??0); $from=$_GET['from']??''; $to=$_GET['to']??''; $params=[]; $where='WHERE 1=1';
if($u){ $where.=" AND wo.user_id=?"; $params[]=$u; }
if($cst){ $where.=" AND wo.customer_id=?"; $params[]=$cst; }
if($from){ $where.=" AND DATE(wo.created_at)>=?"; $params[]=$from; }
if($to){ $where.=" AND DATE(wo.created_at)<=?"; $params[]=$to; }
$rows=$pdo->prepare("SELECT wo.id,wo.codigo_os,wo.total,wo.created_at,c.nome FROM work_orders wo JOIN customers c ON c.id=wo.customer_id $where ORDER BY wo.id DESC LIMIT 1000"); $rows->execute($params); $rows=$rows->fetchAll();
$sum=0; foreach($rows as $r){ $sum+=$r['total']; }
?><h3>Relatórios</h3>
<form class="row g-2 mb-3"><input type="hidden" name="route" value="reports">
<div class="col-md-3"><label class="form-label">Usuário</label><select name="user_id" class="form-select"><option value="0">Todos</option>
<?php foreach($users as $us): ?><option value="<?=$us['id']?>" <?=$u===$us['id']?'selected':''?>><?=h($us['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label class="form-label">Cliente</label><select name="customer_id" class="form-select"><option value="0">Todos</option>
<?php foreach($customers as $cs): ?><option value="<?=$cs['id']?>" <?=$cst===$cs['id']?'selected':''?>><?=h($cs['nome'])?></option><?php endforeach;?></select></div>
<div class="col-md-2"><label class="form-label">De</label><input type="date" name="from" class="form-control" value="<?=h($from)?>"></div>
<div class="col-md-2"><label class="form-label">Até</label><input type="date" name="to" class="form-control" value="<?=h($to)?>"></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-secondary w-100">Filtrar</button></div></form>
<div class="card"><div class="card-header">OS no período</div><div class="card-body p-0"><div class="table-responsive">
<table class="table table-striped mb-0"><thead><tr><th>OS</th><th>Cliente</th><th>Valor</th><th>Data</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=h($r['codigo_os'])?></td><td><?=h($r['nome'])?></td><td>R$ <?=number_format($r['total'],2,',','.')?></td><td><?=h($r['created_at'])?></td>
<td><a class="btn btn-sm btn-outline-primary" href="/?route=wo-view&id=<?=$r['id']?>">Abrir</a></td></tr><?php endforeach; if(!$rows): ?>
<tr><td colspan="5" class="text-center p-4">Nada encontrado.</td></tr><?php endif; ?></tbody>
<?php if($rows): ?><tfoot><tr><th colspan="2" class="text-end">Total</th><th>R$ <?=number_format($sum,2,',','.')?></th><th colspan="2"></th></tr></tfoot><?php endif; ?>
</table></div></div></div>
