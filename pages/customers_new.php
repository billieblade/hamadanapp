<?php
require_login();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  $nome = trim($_POST['nome'] ?? '');
  $tipo = $_POST['tipo'] ?? 'final';

  if ($nome === '') {
    $err = 'Informe o nome do cliente.';
  } elseif (!in_array($tipo, ['final','corporativo'], true)) {
    $err = 'Tipo de cliente inválido.';
  } else {
    $ins = $pdo->prepare("INSERT INTO customers (nome, tipo, cpf_cnpj, email, telefone, endereco, observacoes)
                          VALUES (?,?,?,?,?,?,?)");
    $ins->execute([
      $nome,
      $tipo,
      trim($_POST['cpf_cnpj'] ?? ''),
      trim($_POST['email'] ?? ''),
      trim($_POST['telefone'] ?? ''),
      trim($_POST['endereco'] ?? ''),
      trim($_POST['observacoes'] ?? ''),
    ]);
    $id = (int)$pdo->lastInsertId();
    $_SESSION['last_created_customer'] = $id;
    flash_set('Cliente criado com sucesso.');
    redirect('/?route=customers-edit&created=1&id='.$id);
  }
}
?>
<div class="container py-2" style="max-width:860px">
  <h3>Novo Cliente</h3>

  <?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>

  <form method="post" class="row g-3">
    <div class="col-md-8"><label class="form-label">Nome</label><input name="nome" class="form-control" value="<?=h($_POST['nome'] ?? '')?>" required></div>
    <div class="col-md-4">
      <label class="form-label">Tipo</label>
      <select name="tipo" class="form-select">
        <option value="final" <?=($_POST['tipo'] ?? 'final')==='final'?'selected':''?>>Final</option>
        <option value="corporativo" <?=($_POST['tipo'] ?? '')==='corporativo'?'selected':''?>>Corporativo</option>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">CPF/CNPJ</label><input name="cpf_cnpj" class="form-control" value="<?=h($_POST['cpf_cnpj'] ?? '')?>"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?=h($_POST['email'] ?? '')?>"></div>
    <div class="col-md-4"><label class="form-label">Telefone</label><input name="telefone" class="form-control" value="<?=h($_POST['telefone'] ?? '')?>"></div>
    <div class="col-12"><label class="form-label">Endereço</label><input name="endereco" class="form-control" value="<?=h($_POST['endereco'] ?? '')?>"></div>
    <div class="col-12"><label class="form-label">Observações</label><input name="observacoes" class="form-control" value="<?=h($_POST['observacoes'] ?? '')?>"></div>
    <div class="col-12 d-flex gap-2">
      <button class="btn btn-primary" name="create" value="1">Salvar</button>
      <a class="btn btn-outline-secondary" href="/?route=customers">Cancelar</a>
    </div>
  </form>
</div>
