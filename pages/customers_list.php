<?php
require_login();
$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM customers WHERE 1=1"; $params=[];
if($q!==''){ $sql.=" AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)"; $params=['%'.$q.'%','%'.$q.'%','%'.$q.'%']; }
$sql.=" ORDER BY id DESC LIMIT 300";
$rows = $pdo->prepare($sql); $rows->execute($params); $rows=$rows->fetchAll();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
  <h3 class="mb-0">Clientes</h3>
  <div class="d-flex gap-2 w-100 w-md-auto">
    <a class="btn btn-primary flex-fill flex-md-none" href="/?route=customers-new">Adicionar Cliente</a>
  </div>
</div>
<form class="row g-2 mb-3"><input type="hidden" name="route" value="customers">
  <div class="col-9 col-md-10"><input class="form-control" name="q" placeholder="Buscar por nome, email, telefone" value="<?=h($q)?>"></div>
  <div class="col-3 col-md-2"><button class="btn btn-outline-secondary w-100">Buscar</button></div>
</form>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-striped mb-0"><thead><tr><th>#</th><th>Nome</th><th>Tipo</th><th>Email</th><th>Telefone</th><th class="text-end">Ações</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=$r['id']?></td>
  <td><?=h($r['nome'])?></td>
  <td><span class="badge bg-<?= $r['tipo']==='final' ? 'secondary':'info' ?>"><?=h($r['tipo'])?></span></td>
  <td><?=h($r['email'])?></td>
  <td><?=h($r['telefone'])?></td>
  <td class="text-end">
    <a class="btn btn-sm btn-outline-primary" href="/?route=customers-edit&id=<?=$r['id']?>">Editar</a>
    <a class="btn btn-sm btn-outline-danger" href="/?route=customers-delete&id=<?=$r['id']?>" onclick="return confirm('Remover este cliente?');">Excluir</a>
  </td>
</tr>
<?php endforeach; if(!$rows): ?>
<tr><td colspan="6" class="text-center p-4">Nenhum cliente.</td></tr>
<?php endif; ?>
</tbody></table></div></div></div>
