<?php
require_login();

// ✅ carrega o ID, usando sessão como fallback
$id = (int)($_GET['id'] ?? ($_SESSION['last_created_customer'] ?? 0));
unset($_SESSION['last_created_customer']);

$st=$pdo->prepare("SELECT * FROM customers WHERE id=?"); 
$st->execute([$id]); 
$c=$st->fetch();
if(!$c){ echo "<div class='alert alert-danger'>Cliente não encontrado.</div>"; return; }

$pendingOsStmt = $pdo->prepare("SELECT id, codigo_os, created_at, total, status_pagamento
                                FROM work_orders
                                WHERE customer_id = ?
                                  AND status_pagamento IN ('pendente','inadimplente')
                                ORDER BY created_at DESC");
$pendingOsStmt->execute([$id]);
$pendingOs = $pendingOsStmt->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=$pdo->prepare("UPDATE customers SET nome=?, tipo=?, cpf_cnpj=?, email=?, telefone=?, endereco=?, observacoes=? WHERE id=?");
  $u->execute([
    trim($_POST['nome']??''), $_POST['tipo']??'final', $_POST['cpf_cnpj']??null,
    $_POST['email']??null, $_POST['telefone']??null, $_POST['endereco']??null,
    $_POST['observacoes']??null, $id
  ]);
  flash_set('Cliente atualizado.');
  redirect('/?route=customers');
}
?>
<div class="container py-2" style="max-width:860px">
  <h3>Editar Cliente #<?=$id?></h3>

  <?php if(($_GET['created'] ?? '')==='1'): ?>
    <div class="alert alert-success">
      <strong>Cliente criado!</strong>
      Nome: <strong><?=h($c['nome'])?></strong> —
      Tipo: <span class="badge bg-<?= $c['tipo']==='final' ? 'secondary':'info' ?>"><?=h($c['tipo'])?></span>
      <?php if(!empty($c['email'])): ?> — E-mail: <strong><?=h($c['email'])?></strong><?php endif; ?>
      <?php if(!empty($c['telefone'])): ?> — Tel: <strong><?=h($c['telefone'])?></strong><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if($pendingOs): ?>
    <div class="alert alert-warning">
      <strong>Cliente com OS em aberto.</strong>
      Existem <?=count($pendingOs)?> OS com pagamento pendente/inadimplente.
    </div>
    <div class="card mb-3">
      <div class="card-header">OS pendentes</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr><th>#</th><th>Código</th><th>Status pagamento</th><th>Total</th><th>Data</th></tr>
            </thead>
            <tbody>
              <?php foreach($pendingOs as $os): ?>
                <tr>
                  <td><?=$os['id']?></td>
                  <td><a href="/?route=wo-view&id=<?=$os['id']?>"><?=h($os['codigo_os'])?></a></td>
                  <td><?=h($os['status_pagamento'])?></td>
                  <td class="text-nowrap">R$ <?=number_format($os['total'],2,',','.')?></td>
                  <td><?=h($os['created_at'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" class="row g-3">
    <div class="col-md-8"><label class="form-label">Nome</label><input name="nome" class="form-control" value="<?=h($c['nome'])?>" required></div>
    <div class="col-md-4">
      <label class="form-label">Tipo</label>
      <select name="tipo" class="form-select">
        <option value="final" <?=$c['tipo']==='final'?'selected':''?>>Final</option>
        <option value="corporativo" <?=$c['tipo']==='corporativo'?'selected':''?>>Corporativo</option>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">CPF/CNPJ</label><input name="cpf_cnpj" class="form-control" value="<?=h($c['cpf_cnpj'])?>"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?=h($c['email'])?>"></div>
    <div class="col-md-4"><label class="form-label">Telefone</label><input name="telefone" class="form-control" value="<?=h($c['telefone'])?>"></div>
    <div class="col-12"><label class="form-label">Endereço</label><input name="endereco" class="form-control" value="<?=h($c['endereco'])?>"></div>
    <div class="col-12"><label class="form-label">Observações</label><input name="observacoes" class="form-control" value="<?=h($c['observacoes'])?>"></div>
    <div class="col-12 d-flex gap-2">
      <button class="btn btn-primary">Salvar</button>
      <a class="btn btn-outline-secondary" href="/?route=customers">Cancelar</a>
    </div>
  </form>
</div>
