<?php
require_role(['master']);
$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv']) && isset($_POST['price_list_id'])){
  if(is_uploaded_file($_FILES['csv']['tmp_name'])){
    $plid = (int)$_POST['price_list_id'];
    $f = fopen($_FILES['csv']['tmp_name'],'r');
    $head = fgetcsv($f, 0, ';');
    if(!$head){ $msg = 'CSV vazio.'; }
    else{
      $count=0;
      while(($row=fgetcsv($f,0,';'))!==false){
        $nome = $row[0] ?? '';
        $unid = $row[1] ?? '';
        $preco = $row[2] ?? '0';
        $obs = $row[3] ?? '';
        $ativo = (int)($row[4] ?? 1);
        if(!in_array($unid,['m2','ml','peca'])) continue;
        $find = $pdo->prepare("SELECT id FROM services WHERE price_list_id=? AND nome=? AND unidade=?");
        $find->execute([$plid,$nome,$unid]);
        if($r=$find->fetch()){
          $upd=$pdo->prepare("UPDATE services SET preco=?, observacao=?, ativo=? WHERE id=?");
          $upd->execute([$preco,$obs,$ativo,$r['id']]);
        }else{
          $ins=$pdo->prepare("INSERT INTO services (price_list_id,nome,unidade,preco,observacao,ativo) VALUES (?,?,?,?,?,?)");
          $ins->execute([$plid,$nome,$unid,$preco,$obs,$ativo]);
        }
        $count++;
      }
      fclose($f);
      $msg = "Importação concluída: $count linhas processadas. (Sem arredondamentos)";
    }
  }
}
$pls = $pdo->query("SELECT id,nome,publico FROM price_lists ORDER BY id DESC")->fetchAll();
?>
<div class="container py-4" style="max-width:720px">
  <h3>Importar serviços por CSV</h3>
  <?php if($msg): ?><div class="alert alert-info"><?=h($msg)?></div><?php endif; ?>
  <p>Formato do CSV (separador <code>;</code>): <code>nome;unidade;preco;observacao;ativo</code><br>
  <small><strong>Sem arredondar</strong>: os valores são gravados exatamente como fornecidos.</small></p>
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <div class="col-12">
      <label class="form-label">Tabela de preço</label>
      <select name="price_list_id" class="form-select" required>
        <?php foreach($pls as $pl): ?>
          <option value="<?=$pl['id']?>">#<?=$pl['id']?> - <?=h($pl['nome'])?> (<?=$pl['publico']?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Arquivo CSV</label>
      <input type="file" name="csv" accept=".csv,text/csv" class="form-control" required>
    </div>
    <div class="col-12"><button class="btn btn-primary">Importar</button></div>
  </form>
</div>
