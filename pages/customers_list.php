<?php
$q = $_GET['q'] ?? '';
$st = $pdo->prepare("SELECT * FROM customers WHERE nome LIKE ? ORDER BY id DESC LIMIT 200");
$st->execute(['%'.$q+'%']);  # safe concat for display
$rows = $st->fetchAll();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Clientes</h3>
    <a class="btn btn-primary" href="/?route=customers-new">Novo Cliente</a>
  </div>
  <form class="row g-2 mb-3">
    <input type="hidden" name="route" value="customers">
    <div class="col-auto"><input name="q" class="form-control" placeholder="Buscar por nome" value="<?=h($q)?>"></div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Buscar</button></div>
  </form>
  <table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Tipo</th><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>E-mail</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=$r['tipo']?></td>
          <td><?=h($r['nome'])?></td>
          <td><?=h($r['cpf_cnpj'])?></td>
          <td><?=h($r['telefone'])?></td>
          <td><?=h($r['email'])?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
