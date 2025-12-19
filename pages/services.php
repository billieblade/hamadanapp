<?php
require_role(['master']);

$CATS=['CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES'];
$msg=''; $err='';

// ---------- Ações ----------
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])){
  try{
    $st=$pdo->prepare("INSERT INTO services (categoria,nome,unidade,preco_final,preco_corporativo,observacao,ativo)
                       VALUES (?,?,?,?,?,?,1)");
    $st->execute([
      $_POST['categoria'],
      trim($_POST['nome']),
      $_POST['unidade'],
      $_POST['preco_final'],
      $_POST['preco_corporativo'],
      $_POST['observacao']??null
    ]);
    $msg='Serviço adicionado.';
  }catch(Exception $e){ $err='Erro ao adicionar: '.$e->getMessage(); }
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save'])){
  try{
    $st=$pdo->prepare("UPDATE services SET preco_final=?,preco_corporativo=? WHERE id=?");
    $st->execute([$_POST['preco_final'],$_POST['preco_corporativo'],(int)$_POST['id']]);
    $msg='Preços salvos.';
  }catch(Exception $e){ $err='Erro ao salvar: '.$e->getMessage(); }
}

// Soft delete (ativo=0)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])){
  $id=(int)$_POST['id'];
  try{
    // Sempre usar soft delete para não quebrar FKs/OS antigas
    $pdo->prepare("UPDATE services SET ativo=0 WHERE id=?")->execute([$id]);
    $msg='Serviço desativado (excluído) com sucesso.';
  }catch(Exception $e){ $err='Erro ao excluir: '.$e->getMessage(); }
}

// Restaurar (ativo=1)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['restore'])){
  $id=(int)$_POST['id'];
  try{
    $pdo->prepare("UPDATE services SET ativo=1 WHERE id=?")->execute([$id]);
    $msg='Serviço restaurado com sucesso.';
  }catch(Exception $e){ $err='Erro ao restaurar: '.$e->getMessage(); }
}

// ---------- Filtros ----------
$f_cat = $_GET['cat'] ?? '';
$q     = trim($_GET['q'] ?? '');
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive']=='1';

$sql = "SELECT * FROM services WHERE 1=1";
$p   = [];
if(!$show_inactive){
  $sql .= " AND ativo=1";
}else{
  $sql .= " AND ativo=0";
}
if(in_array($f_cat,$CATS,true)){ $sql.=" AND categoria=?"; $p[]=$f_cat; }
if($q!==''){ $sql.=" AND (nome LIKE ? OR observacao LIKE ?)"; $p[]='%'.$q.'%'; $p[]='%'.$q.'%'; }
$sql.=" ORDER BY categoria,nome";

$st=$pdo->prepare($sql); 
$st->execute($p); 
$rows=$st->fetchAll();

$group=[]; foreach($rows as $r){ $group[$r['categoria']][]=$r; }
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
  <h3 class="mb-0">Serviços / Preços</h3>
  <div class="text-muted">Listados: <strong><?=count($rows)?></strong></div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>

<form class="row g-2 mb-3">
  <input type="hidden" name="route" value="services">
  <div class="col-md-3">
    <select name="cat" class="form-select">
      <option value="">Todas categorias</option>
      <?php foreach($CATS as $c): ?>
        <option value="<?=$c?>" <?=$f_cat===$c?'selected':''?>><?=$c?></option>
      <?php endforeach;?>
    </select>
  </div>
  <div class="col-md-5">
    <input name="q" class="form-control" placeholder="Buscar por nome/observação" value="<?=h($q)?>">
  </div>
  <div class="col-md-2 d-flex align-items-center">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" value="1" id="show_inactive" name="show_inactive" <?=$show_inactive?'checked':''?>>
      <label class="form-check-label" for="show_inactive">Mostrar inativos</label>
    </div>
  </div>
  <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filtrar</button></div>
</form>

<div class="card mb-4">
  <div class="card-header">Adicionar serviço</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <input type="hidden" name="add" value="1">
      <div class="col-md-3">
        <label class="form-label">Categoria</label>
        <select name="categoria" class="form-select">
          <?php foreach($CATS as $c):?><option><?=$c?></option><?php endforeach;?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nome</label>
        <input name="nome" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Unidade</label>
        <select name="unidade" class="form-select">
          <option value="m2">m2</option>
          <option value="ml">ml</option>
          <option value="peca">peça</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Preço Final</label>
        <input name="preco_final" type="number" step="0.01" class="form-control" value="0.00">
      </div>
      <div class="col-md-1">
        <label class="form-label">Preço Corp.</label>
        <input name="preco_corporativo" type="number" step="0.01" class="form-control" value="0.00">
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100">Salvar</button>
      </div>
      <div class="col-12">
        <label class="form-label">Observação</label>
        <input name="observacao" class="form-control">
      </div>
    </form>
  </div>
</div>

<?php foreach($CATS as $cat): $list=$group[$cat]??[]; if(!$list) continue; ?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><?=$cat?></strong>
    <small class="text-muted"><?=$show_inactive?'(inativos)':'(ativos)'?></small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0 align-middle">
        <thead>
          <tr>
            <th>Serviço</th><th>Unid</th>
            <th style="width:150px">Preço Final</th>
            <th style="width:170px">Preço Corporativo</th>
            <th>Obs</th>
            <th style="width:160px" class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($list as $r): ?>
          <tr>
            <form method="post" class="row g-1">
              <td><?=h($r['nome'])?></td>
              <td><?=h($r['unidade'])?></td>
              <td><input name="preco_final" type="number" step="0.01" class="form-control form-control-sm"
                         value="<?=number_format((float)$r['preco_final'],2,'.','')?>"></td>
              <td><input name="preco_corporativo" type="number" step="0.01" class="form-control form-control-sm"
                         value="<?=number_format((float)$r['preco_corporativo'],2,'.','')?>"></td>
              <td><input class="form-control form-control-sm" value="<?=h($r['observacao'])?>" disabled></td>
              <td class="text-end">
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <?php if(!$show_inactive): ?>
                  <button class="btn btn-sm btn-primary" name="save" value="1">Salvar</button>
                  <button class="btn btn-sm btn-outline-danger" name="delete" value="1"
                          onclick="return confirm('Desativar este serviço? OS antigas não serão afetadas.');">
                    Excluir
                  </button>
                <?php else: ?>
                  <button class="btn btn-sm btn-success" name="restore" value="1">Restaurar</button>
                <?php endif; ?>
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
