<?php
require_role(['master']);
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])){
  $st=$pdo->prepare("INSERT INTO price_lists (nome,publico,ativo,vigencia_ini,vigencia_fim,criado_por,atualizado_por) VALUES (?,?,?,?,?, ?, ?)");
  $st->execute([$_POST['nome'],$_POST['publico'],1,$_POST['vigencia_ini'],$_POST['vigencia_fim'],$_SESSION['uid'],$_SESSION['uid']]);
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_price'])){
  $st=$pdo->prepare("UPDATE services SET preco=?, observacao=?, ativo=? WHERE id=?");
  $st->execute([$_POST['preco'], $_POST['observacao'], (int)!!$_POST['ativo'], $_POST['id']]);
}
$pls = $pdo->query("SELECT * FROM price_lists ORDER BY id DESC")->fetchAll();
?>
<div class="container py-4">
  <h3>Tabelas de Preço</h3>
  <div class="card mb-3"><div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="create" value="1">
      <div class="col-md-4"><input class="form-control" name="nome" placeholder="Nome da tabela" required></div>
      <div class="col-md-2">
        <select name="publico" class="form-select"><option value="final">final</option><option value="corporativo">corporativo</option></select>
      </div>
      <div class="col-md-2"><input type="date" class="form-control" name="vigencia_ini"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="vigencia_fim"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Criar</button></div>
    </form>
  </div></div>

  <div class="mb-3"><a class="btn btn-sm btn-outline-primary" href="/?route=admin_import_csv">Importar CSV para uma tabela</a></div>

  <?php foreach($pls as $pl): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div><strong>#<?=$pl['id']?> - <?=h($pl['nome'])?></strong> (<?=$pl['publico']?>) Vigência: <?=h($pl['vigencia_ini'])?> ~ <?=h($pl['vigencia_fim'])?></div>
        <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#pl<?=$pl['id']?>">Serviços</a>
      </div>
      <div id="pl<?=$pl['id']?>" class="collapse">
        <div class="card-body">
          <?php
            $sv=$pdo->prepare("SELECT * FROM services WHERE price_list_id=? ORDER BY nome");
            $sv->execute([$pl['id']]); $servs=$sv->fetchAll();
          ?>
          <table class="table table-sm">
            <thead><tr><th>Serviço</th><th>Unidade</th><th>Preço</th><th>Obs</th><th>Ativo</th><th></th></tr></thead>
            <tbody>
              <?php foreach($servs as $s): ?>
              <tr>
                <form method="post">
                  <td><?=h($s['nome'])?></td>
                  <td><?=h($s['unidade'])?></td>
                  <td><input type="number" step="0.01" name="preco" class="form-control form-control-sm" value="<?=$s['preco']?>"></td>
                  <td><input name="observacao" class="form-control form-control-sm" value="<?=h($s['observacao'])?>"></td>
                  <td><input type="checkbox" name="ativo" value="1" <?=$s['ativo']?'checked':''?>></td>
                  <td>
                    <input type="hidden" name="id" value="<?=$s['id']?>">
                    <button class="btn btn-sm btn-primary" name="update_price" value="1">Salvar</button>
                  </td>
                </form>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
