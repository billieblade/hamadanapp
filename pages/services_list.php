<?php
// pages/services_list.php
require_role(['master']); // Master gerencia os serviços

$cats  = ['CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES'];
$units = ['m2'=>'m²','ml'=>'metro linear','peca'=>'peça'];

$msg=''; $err='';

// Atualizar linha individual
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_row'])){
  $id    = (int)$_POST['id'];
  $nome  = trim($_POST['nome'] ?? '');
  $uni   = $_POST['unidade'] ?? 'm2';
  $pf    = $_POST['preco_final'] ?? '0';
  $pc    = $_POST['preco_corporativo'] ?? '0';
  $obs   = trim($_POST['observacao'] ?? '');
  $ativo = (int)!!($_POST['ativo'] ?? 0);

  if($nome===''){ $err='Informe o nome.'; }
  elseif(!array_key_exists($uni,$units)){ $err='Unidade inválida.'; }
  else{
    $st = $pdo->prepare("UPDATE services_all SET nome=?, unidade=?, preco_final=?, preco_corporativo=?, observacao=?, ativo=? WHERE id=?");
    $st->execute([$nome,$uni,$pf,$pc,$obs,$ativo,$id]);
    $msg='Serviço atualizado.';
  }
}

// Filtro básico (categoria e texto)
$f_cat = $_GET['cat'] ?? '';
$q    = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM services_all WHERE 1=1";
$params = [];
if(in_array($f_cat,$cats)){ $sql .= " AND categoria=?"; $params[]=$f_cat; }
if($q!==''){ $sql .= " AND (nome LIKE ? OR observacao LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
$sql .= " ORDER BY categoria, nome";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// Agrupa por categoria para exibir blocos
$grouped = [];
foreach($rows as $r){ $grouped[$r['categoria']][] = $r; }
?>
<div class="container py-4">
  <h3>Serviços</h3>

  <?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
  <?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif; ?>

  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="route" value="services">
    <div class="col-md-3">
      <select name="cat" class="form-select">
        <option value="">Todas as categorias</option>
        <?php foreach($cats as $c): ?>
          <option value="<?=$c?>" <?=$f_cat===$c?'selected':''?>><?=$c?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <input name="q" class="form-control" placeholder="Buscar por nome/observação" value="<?=h($q)?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-secondary w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
      <a class="btn btn-primary w-100" href="/?route=services-new">Adicionar Serviço</a>
    </div>
  </form>

  <?php if(!$rows): ?>
    <div class="alert alert-warning">Nenhum serviço encontrado. <a href="/?route=services-new">Adicionar agora</a>.</div>
  <?php endif; ?>

  <?php foreach($cats as $c): 
    $list = $grouped[$c] ?? [];
    if(!$list) continue; ?>
    <div class="card mb-3">
      <div class="card-header"><strong><?=h($c)?></strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0 align-middle">
            <thead>
              <tr>
                <th style="min-width:220px">Serviço</th>
                <th>Unid</th>
                <th style="min-width:130px">Preço Final</th>
                <th style="min-width:170px">Preço Corporativo</th>
                <th>Obs</th>
                <th>Ativo</th>
                <th style="width:100px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($list as $r): ?>
              <tr>
                <form method="post" class="row g-1">
                  <td><input name="nome" class="form-control form-control-sm" value="<?=h($r['nome'])?>"></td>

                  <td>
                    <select name="unidade" class="form-select form-select-sm">
                      <?php foreach($units as $k=>$label): ?>
                        <option value="<?=$k?>" <?=$r['unidade']===$k?'selected':''?>><?=$k?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>

                  <td><input name="preco_final" type="number" step="0.01" class="form-control form-control-sm" value="<?=$r['preco_final']?>"></td>
                  <td><input name="preco_corporativo" type="number" step="0.01" class="form-control form-control-sm" value="<?=$r['preco_corporativo']?>"></td>

                  <td><input name="observacao" class="form-control form-control-sm" value="<?=h($r['observacao'])?>"></td>

                  <td class="text-center">
                    <input type="checkbox" name="ativo" value="1" <?=$r['ativo']?'checked':''?>>
                  </td>

                  <td>
                    <input type="hidden" name="id" value="<?=$r['id']?>">
                    <button class="btn btn-sm btn-primary w-100" name="save_row" value="1">Salvar</button>
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
