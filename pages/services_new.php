<?php
// pages/services_new.php
require_role(['master']); // Apenas master pode criar/editar serviços
$cats = ['CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES'];
$units = ['m2'=>'m²','ml'=>'metro linear','peca'=>'peça'];

$msg = '';
$err = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $categoria = $_POST['categoria'] ?? '';
  $nome      = trim($_POST['nome'] ?? '');
  $unidade   = $_POST['unidade'] ?? '';
  $pfinal    = $_POST['preco_final'] ?? '0';
  $pcorp     = $_POST['preco_corporativo'] ?? '0';
  $obs       = trim($_POST['observacao'] ?? '');
  $ativo     = (int)!!($_POST['ativo'] ?? 1);

  // Validações básicas
  if(!in_array($categoria,$cats))          $err = 'Categoria inválida.';
  elseif($nome==='')                        $err = 'Informe o nome do serviço.';
  elseif(!array_key_exists($unidade,$units))$err = 'Unidade inválida.';
  else{
    // Evitar duplicidade (mesmo nome + unidade + categoria)
    $chk = $pdo->prepare("SELECT id FROM services_all WHERE categoria=? AND nome=? AND unidade=?");
    $chk->execute([$categoria,$nome,$unidade]);
    if($chk->fetch()){
      $err = 'Já existe um serviço com este nome/unidade nesta categoria.';
    }else{
      $ins = $pdo->prepare("INSERT INTO services_all 
        (categoria,nome,unidade,preco_final,preco_corporativo,observacao,ativo)
        VALUES (?,?,?,?,?,?,?)");
      $ins->execute([$categoria,$nome,$unidade,$pfinal,$pcorp,$obs,$ativo]);
      $msg = 'Serviço cadastrado com sucesso.';
      // Limpa o form após sucesso
      $_POST = ['categoria'=>$categoria,'unidade'=>$unidade,'ativo'=>1];
    }
  }
}
?>
<div class="container py-4" style="max-width:860px">
  <h3>Adicionar Serviço</h3>

  <?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
  <?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif; ?>

  <form method="post" class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Categoria</label>
      <select name="categoria" class="form-select" required>
        <?php foreach($cats as $c): ?>
          <option value="<?=$c?>" <?=isset($_POST['categoria']) && $_POST['categoria']===$c?'selected':''?>><?=$c?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-5">
      <label class="form-label">Nome do serviço</label>
      <input name="nome" class="form-control" required value="<?=h($_POST['nome'] ?? '')?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Unidade</label>
      <select name="unidade" class="form-select">
        <?php foreach($units as $k=>$label): ?>
          <option value="<?=$k?>" <?=isset($_POST['unidade']) && $_POST['unidade']===$k?'selected':''?>><?=$k?> (<?=$label?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Preço Cliente Final</label>
      <input name="preco_final" type="number" step="0.01" class="form-control" value="<?=h($_POST['preco_final'] ?? '0')?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Preço Corporativo</label>
      <input name="preco_corporativo" type="number" step="0.01" class="form-control" value="<?=h($_POST['preco_corporativo'] ?? '0')?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Observação</label>
      <input name="observacao" class="form-control" value="<?=h($_POST['observacao'] ?? '')?>">
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativoCheck" <?=(!isset($_POST['ativo']) || $_POST['ativo'])?'checked':''?>>
        <label class="form-check-label" for="ativoCheck">Ativo</label>
      </div>
    </div>

    <div class="col-12">
      <button class="btn btn-primary">Salvar serviço</button>
      <a class="btn btn-outline-secondary" href="/?route=services">Voltar à lista</a>
    </div>
  </form>
</div>
