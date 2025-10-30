<?php
// pages/quotes_new.php
require_login();
// TEMP (debug enquanto ajusta): descomente se precisar
// error_reporting(E_ALL); ini_set('display_errors', 1);

// -------------------------------------------------------------------
// Carregamentos base
// -------------------------------------------------------------------
$clientes = $pdo->query("SELECT id, nome, tipo FROM customers ORDER BY nome")->fetchAll();

function load_catalog($pdo){
  $all = $pdo->query("SELECT * FROM services_all WHERE ativo=1 ORDER BY categoria, nome")->fetchAll();
  $groups = [];
  foreach ($all as $s) { $groups[$s['categoria']][] = $s; }
  return [$all, $groups];
}
list($catalog_all, $catalog_groups) = load_catalog($pdo);

$CATS  = ['CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES'];
$UNITS = ['m2'=>'m²','ml'=>'metro linear','peca'=>'peça'];
$MAX_ROWS = 10;

$msg = ''; $err = '';

// -------------------------------------------------------------------
// A) Adicionar novo serviço inline (modal)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_inline_service'])) {
  $categoria = $_POST['categoria'] ?? '';
  $nome      = trim($_POST['nome'] ?? '');
  $unidade   = $_POST['unidade'] ?? '';
  $pfinal    = $_POST['preco_final'] ?? '0';
  $pcorp     = $_POST['preco_corporativo'] ?? '0';
  $obs       = trim($_POST['observacao'] ?? '');

  if (!in_array($categoria,$CATS))                { $err = 'Categoria inválida.'; }
  elseif ($nome==='')                              { $err = 'Informe o nome do serviço.'; }
  elseif (!array_key_exists($unidade,$UNITS))      { $err = 'Unidade inválida.'; }
  else {
    $chk = $pdo->prepare("SELECT id FROM services_all WHERE categoria=? AND nome=? AND unidade=?");
    $chk->execute([$categoria,$nome,$unidade]);
    if ($chk->fetch()) {
      $err = 'Já existe um serviço com este nome/unidade nesta categoria.';
    } else {
      $ins = $pdo->prepare("INSERT INTO services_all (categoria,nome,unidade,preco_final,preco_corporativo,observacao,ativo)
                            VALUES (?,?,?,?,?,?,1)");
      $ins->execute([$categoria,$nome,$unidade,$pfinal,$pcorp,$obs]);
      $msg = 'Serviço adicionado ao catálogo.';
      // recarrega catálogo para o select refletir o novo serviço
      list($catalog_all, $catalog_groups) = load_catalog($pdo);
    }
  }
}

// -------------------------------------------------------------------
// B) Criar OS: orçamento → peças → serviços por peça → OS + etiquetas
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_os'])) {
  $customer_id = (int)($_POST['customer_id'] ?? 0);

  // tipo do cliente define o preço (final/corporativo)
  $c = $pdo->prepare("SELECT id, nome, tipo FROM customers WHERE id=?");
  $c->execute([$customer_id]);
  $cliente = $c->fetch();

  if (!$cliente) {
    $err = 'Cliente inválido.';
  } else {
    // 1) cria orçamento rascunho
    $ins = $pdo->prepare("INSERT INTO quotes (customer_id,user_id,price_list_id,forma_pagto,status,subtotal,desconto,total)
                          VALUES (?,?,?,?, 'rascunho',0,0,0)");
    $ins->execute([$customer_id, $_SESSION['uid'], null, $_POST['forma_pagto'] ?? null]);
    $qid = $pdo->lastInsertId();

    // indexa serviços por id
    $by_id = [];
    foreach ($catalog_all as $s) { $by_id[$s['id']] = $s; }

    $subtotal_geral = 0;

    // 2) processa linhas
    for ($i=0; $i<$MAX_ROWS; $i++) {
      $svc_id = (int)($_POST['svc_id'][$i] ?? 0);
      if (!$svc_id) continue; // linha vazia

      if (!isset($by_id[$svc_id])) continue;
      $svc = $by_id[$svc_id];

      // coleta medidas/quantidade
      $tipo_peca = $_POST['tipo_peca'][$i] ?? 'retangular';
      $larg_cm   = (float)($_POST['largura_cm'][$i] ?? 0);
      $comp_cm   = (float)($_POST['comprimento_cm'][$i] ?? 0);
      $diam_cm   = (float)($_POST['diametro_cm'][$i] ?? 0);
      $qtd       = (float)($_POST['qtd'][$i] ?? 1);

      // 2.1) cria peça (quote_item)
      $st = $pdo->prepare("INSERT INTO quote_items
        (quote_id,service_id,tipo_tapete,largura_cm,comprimento_cm,diametro_cm,qtd,preco_unitario,regra_aplicada_json,subtotal)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$qid,null,$tipo_peca,$larg_cm,$comp_cm,$diam_cm,$qtd,0,json_encode(['origem'=>'quick-add']),0]);
      $qi_id = $pdo->lastInsertId();

      // 2.2) preço conforme tipo do cliente
      $unit = ($cliente['tipo']==='final') ? (float)$svc['preco_final'] : (float)$svc['preco_corporativo'];

      // 2.3) base de quantidade
      $m2 = area_m2($tipo_peca, $larg_cm, $comp_cm, $diam_cm);
      if ($svc['unidade']==='m2') {
        $base_qty = max(0.0001,$m2) * max(1,$qtd);
      } else {
        $base_qty = max(1,$qtd);
      }

      $sub = $unit * $base_qty;

      // 2.4) vincula serviço à peça
      $ins2 = $pdo->prepare("INSERT INTO quote_item_services (quote_item_id, service_all_id, qtd, preco_unitario, subtotal)
                             VALUES (?,?,?,?,?)");
      $ins2->execute([$qi_id, $svc_id, $qtd, $unit, $sub]);

      $subtotal_geral += $sub;
    }

    // 3) totais do orçamento e aprova
    $desconto = 0.00;
    $total    = max(0, $subtotal_geral - $desconto);
    $pdo->prepare("UPDATE quotes SET subtotal=?, desconto=?, total=?, status='aprovado' WHERE id=?")
        ->execute([$subtotal_geral,$desconto,$total,$qid]);

    // 4) cria OS
    $codigo = 'OS-'.date('ymd').'-'.str_pad((string)$qid,4,'0',STR_PAD_LEFT);
    $pdo->prepare("INSERT INTO work_orders (quote_id,codigo_os,status) VALUES (?,?, 'aberta')")
        ->execute([$qid,$codigo]);
    $woid = $pdo->lastInsertId();

    // 5) etiquetas (1 por peça)
    $qi = $pdo->prepare("SELECT id FROM quote_items WHERE quote_id=?");
    $qi->execute([$qid]);
    foreach ($qi as $row) {
      $label = 'ET-'.date('ymd').'-'.$row['id'];
      $pdo->prepare("INSERT INTO work_order_items (work_order_id,quote_item_id,etiqueta_codigo,status_item)
                     VALUES (?,?,?,'EM_TRANSITO')")
          ->execute([$woid,$row['id'],$label]);
    }

    // 6) redireciona para OS
    redirect('/?route=os-view&id='.$woid);
  }
}
?>
<div class="container py-4">
  <h3>Adicionar Serviços / Criar OS</h3>

  <?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
  <?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif; ?>

  <form method="post">
    <!-- Cliente -->
    <div class="card mb-3">
      <div class="card-header">Cliente e pagamento</div>
      <div class="card-body row g-3">
        <div class="col-md-8">
          <label class="form-label">Cliente</label>
          <select name="customer_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach($clientes as $c): ?>
              <option value="<?=$c['id']?>"><?=h($c['nome'])?> (<?=$c['tipo']?>) — #<?=$c['id']?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">O tipo do cliente (final/corporativo) define o preço usado.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Forma de pagamento</label>
          <select name="forma_pagto" class="form-select">
            <option value="">Definir depois</option>
            <option>dinheiro</option>
            <option>debito</option>
            <option>credito</option>
            <option>pix</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Linhas de serviço -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Serviços (até <?=$MAX_ROWS?> linhas)</span>
        <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalNovoServico">
          + Novo Serviço
        </button>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="min-width:320px">Serviço (catálogo)</th>
              <th class="text-center">Unidade</th>
              <th>Tipo Peça</th>
              <th>Larg (cm)</th>
              <th>Comp (cm)</th>
              <th>Diâm (cm)</th>
              <th>Qtd</th>
            </tr>
          </thead>
          <tbody>
          <?php for($i=0; $i<$MAX_ROWS; $i++): ?>
            <tr>
              <td>
                <select name="svc_id[<?=$i?>]" class="form-select js-svc" data-row="<?=$i?>">
                  <option value="">—</option>
                  <?php foreach($catalog_groups as $cat => $items): ?>
                    <optgroup label="<?=h($cat)?>">
                      <?php foreach($items as $s): 
                        $label = '['.$s['categoria'].'] '.$s['nome'].' ('.$s['unidade'].') — Final R$ '.number_format($s['preco_final'],2,',','.')
                                 .' / Corp R$ '.number_format($s['preco_corporativo'],2,',','.');
                      ?>
                        <option value="<?=$s['id']?>" data-unit="<?=$s['unidade']?>"><?=h($label)?></option>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="text-center">
                <span class="badge bg-secondary js-unit" id="unit-<?=$i?>">—</span>
              </td>
              <td>
                <select name="tipo_peca[<?=$i?>]" class="form-select form-select-sm">
                  <option value="retangular">Retangular</option>
                  <option value="redondo">Redondo</option>
                </select>
              </td>
              <td><input name="largura_cm[<?=$i?>]"      class="form-control form-control-sm js-dim js-dim-<?=$i?>" type="number" step="0.01" placeholder="0" disabled></td>
              <td><input name="comprimento_cm[<?=$i?>]"  class="form-control form-control-sm js-dim js-dim-<?=$i?>" type="number" step="0.01" placeholder="0" disabled></td>
              <td><input name="diametro_cm[<?=$i?>]"     class="form-control form-control-sm js-dim js-dim-<?=$i?>" type="number" step="0.01" placeholder="0" disabled></td>
              <td><input name="qtd[<?=$i?>]"             class="form-control form-control-sm" type="number" step="0.01" value="1"></td>
            </tr>
          <?php endfor; ?>
          </tbody>
        </table>
        <div class="form-text">
          • O <strong>nome do serviço</strong> vem sempre do <strong>catálogo</strong> acima (agrupado por categoria).<br>
          • Para serviços em <strong>m²</strong>, as medidas são habilitadas automaticamente (usa a área da peça × Qtd).<br>
          • Para <strong>ml</strong> ou <strong>peça</strong>, informe apenas <strong>Qtd</strong>.
        </div>
      </div>
    </div>

    <div class="text-end">
      <button class="btn btn-success" name="create_os" value="1">Criar OS agora</button>
    </div>
  </form>
</div>

<!-- Modal: Novo Serviço (inline) -->
<div class="modal fade" id="modalNovoServico" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Novo Serviço</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="add_inline_service" value="1">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Categoria</label>
              <select name="categoria" class="form-select" required>
                <?php foreach($CATS as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Nome do serviço</label>
              <input name="nome" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Unidade</label>
              <select name="unidade" class="form-select">
                <option value="m2">m2 (m²)</option>
                <option value="ml">ml (metro linear)</option>
                <option value="peca">peça</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Preço Final</label>
              <input name="preco_final" type="number" step="0.01" class="form-control" value="0.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Preço Corporativo</label>
              <input name="preco_corporativo" type="number" step="0.01" class="form-control" value="0.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Observação</label>
              <input name="observacao" class="form-control">
            </div>
          </div>
          <div class="form-text mt-2">Ao salvar, o serviço entra na lista imediatamente.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">Salvar serviço</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Habilita/Desabilita campos de medidas conforme a unidade do serviço escolhido
document.querySelectorAll('.js-svc').forEach(function(sel){
  sel.addEventListener('change', function(){
    var row = this.dataset.row;
    var opt = this.options[this.selectedIndex];
    var unit = opt.getAttribute('data-unit') || '';
    var badge = document.getElementById('unit-'+row);
    if (badge) { badge.textContent = unit || '—'; }

    var dims = document.querySelectorAll('.js-dim-'+row);
    dims.forEach(function(inp){
      if (unit==='m2') { inp.removeAttribute('disabled'); }
      else { inp.value=''; inp.setAttribute('disabled','disabled'); }
    });
  });
});
</script>
