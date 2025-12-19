<?php
require_login();

$clientes = $pdo->query("SELECT id,nome,tipo FROM customers ORDER BY nome")->fetchAll();
$servs    = $pdo->query("SELECT * FROM services WHERE ativo=1 ORDER BY categoria,nome")->fetchAll();
$byId=[]; foreach($servs as $s){ $byId[$s['id']]=$s; }
$MAX_ROWS = 12;

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])){
  $cid=(int)$_POST['customer_id'];
  $c=$pdo->prepare("SELECT * FROM customers WHERE id=?"); $c->execute([$cid]); $cliente=$c->fetch();
  if(!$cliente){ flash_set('Cliente inválido.','danger'); redirect('/?route=wo-new'); }

  // cria OS
  $pdo->prepare("INSERT INTO work_orders (codigo_os,customer_id,user_id,status,subtotal,desconto,total)
                 VALUES ('tmp',?,?, 'aberta',0,0,0)")
      ->execute([$cid,$_SESSION['uid']]);
  $woId   = (int)$pdo->lastInsertId();
  $codigo = code_os($woId);
  $pdo->prepare("UPDATE work_orders SET codigo_os=? WHERE id=?")->execute([$codigo,$woId]);

  $subtotal_os=0;

  for($i=0;$i<$MAX_ROWS;$i++){
    $svc_id=(int)($_POST['svc_id'][$i]??0);
    if(!$svc_id || !isset($byId[$svc_id])) continue;

    $svc    = $byId[$svc_id];
    $un     = $svc['unidade'];
    $medida = (float)($_POST['medida'][$i]??0);
    if($medida<=0) continue;

    $pdo->prepare("INSERT INTO work_order_items (work_order_id,tipo_peca,largura_cm,comprimento_cm,diametro_cm,qtd,etiqueta_codigo,status_item,subtotal)
                   VALUES (?,?,?,?,?,?,?,?,0)")
        ->execute([$woId,'retangular',0,0,0,1,'tmp','EM_TRANSITO']);
    $itemId=(int)$pdo->lastInsertId();
    $label = code_label($itemId);
    $pdo->prepare("UPDATE work_order_items SET etiqueta_codigo=? WHERE id=?")->execute([$label,$itemId]);

    $unit = ($cliente['tipo']==='final') ? (float)$svc['preco_final'] : (float)$svc['preco_corporativo'];
    $sub  = $unit * $medida;

    $pdo->prepare("INSERT INTO work_item_services (work_item_id,service_id,unidade,qtd,preco_unitario,subtotal)
                   VALUES (?,?,?,?,?,?)")
        ->execute([$itemId,$svc_id,$un,$medida,$unit,$sub]);

    $pdo->prepare("UPDATE work_order_items SET subtotal=? WHERE id=?")->execute([$sub,$itemId]);
    $subtotal_os += $sub;
  }

  $pdo->prepare("UPDATE work_orders SET subtotal=?, total=? WHERE id=?")->execute([$subtotal_os,$subtotal_os,$woId]);

  // ✅ grava ID na sessão e redireciona
  $_SESSION['last_created_os'] = $woId;
  flash_set("OS {$codigo} criada com sucesso.");
  redirect('/?route=wo-view&created=1');
}
?>
<div class="container py-3">
  <h3>Criar OS</h3>
  <form method="post">
    <div class="card mb-3">
      <div class="card-header">Cliente</div>
      <div class="card-body">
        <select name="customer_id" class="form-select" required>
          <option value="">Selecione</option>
          <?php foreach($clientes as $c): ?>
            <option value="<?=$c['id']?>"><?=h($c['nome'])?> (<?=$c['tipo']?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Peças e Serviços (até <?=$MAX_ROWS?> linhas)</div>
      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Serviço</th><th>Unid</th><th class="w-25">Medida</th></tr></thead>
          <tbody>
          <?php for($i=0;$i<$MAX_ROWS;$i++): ?>
            <tr>
              <td>
                <select name="svc_id[<?=$i?>]" class="form-select svc-select" data-row="<?=$i?>">
                  <option value="">—</option>
                  <?php $cat=''; foreach($servs as $s): if($s['categoria']!==$cat){ if($cat!=='') echo "</optgroup>"; $cat=$s['categoria']; echo '<optgroup label="'.h($cat).'">'; } ?>
                    <option value="<?=$s['id']?>" data-unit="<?=$s['unidade']?>">
                      [<?=$s['categoria']?>] <?=h($s['nome'])?> (<?=$s['unidade']?>)
                    </option>
                  <?php endforeach; if($cat!==''){ echo "</optgroup>"; } ?>
                </select>
              </td>
              <td><input class="form-control form-control-sm unit-cell" data-row="<?=$i?>" value="-" readonly></td>
              <td>
                <div class="input-group input-group-sm">
                  <input name="medida[<?=$i?>]" class="form-control medida-input" data-row="<?=$i?>" type="number" step="0.01" placeholder="Informe a medida">
                  <span class="input-group-text medida-label" data-row="<?=$i?>">—</span>
                </div>
              </td>
            </tr>
          <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="text-end mt-3">
      <button class="btn btn-success" name="create" value="1">Criar OS</button>
    </div>
  </form>
</div>
<script>
document.querySelectorAll('.svc-select').forEach(sel=>{
  sel.addEventListener('change', e=>{
    const row = e.target.dataset.row;
    const opt = e.target.selectedOptions[0];
    const unit = opt ? (opt.getAttribute('data-unit') || '-') : '-';
    document.querySelector('.unit-cell[data-row="'+row+'"]').value = unit;
    const label = document.querySelector('.medida-label[data-row="'+row+'"]');
    const input = document.querySelector('.medida-input[data-row="'+row+'"]');
    if(unit==='m2'){ label.textContent='m²'; input.placeholder='Área em m²'; }
    else if(unit==='ml'){ label.textContent='m'; input.placeholder='Comprimento em metros'; }
    else if(unit==='peca'){ label.textContent='peça(s)'; input.placeholder='Quantidade'; }
    else { label.textContent='—'; input.placeholder='Informe a medida'; }
  });
});
</script>
