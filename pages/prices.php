<?php
require_role(['master']);
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_price'])){
  $st=$pdo->prepare("UPDATE services_all SET preco_final=?, preco_corporativo=? WHERE id=?");
  $st->execute([$_POST['preco_final'], $_POST['preco_corporativo'], $_POST['id']]);
}
$rows=$pdo->query("SELECT * FROM services_all WHERE ativo=1 ORDER BY categoria, nome")->fetchAll();
?>
<div class="container py-4">
  <h3>Preços (Final / Corporativo)</h3>
  <table class="table table-sm table-striped">
    <thead><tr><th>Categoria</th><th>Serviço</th><th>Unid</th><th>Preço Final</th><th>Preço Corporativo</th><th></th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <form method="post">
            <td><?=h($r['categoria'])?></td>
            <td><?=h($r['nome'])?></td>
            <td><?=h($r['unidade'])?></td>
            <td><input name="preco_final" type="number" step="0.01" class="form-control form-control-sm" value="<?=$r['preco_final']?>"></td>
            <td><input name="preco_corporativo" type="number" step="0.01" class="form-control form-control-sm" value="<?=$r['preco_corporativo']?>"></td>
            <td>
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <button class="btn btn-sm btn-primary" name="save_price" value="1">Salvar</button>
            </td>
          </form>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
