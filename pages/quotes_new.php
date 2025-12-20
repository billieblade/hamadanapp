<?php
// pages/quotes_new.php
require_login();
// TEMP (debug enquanto ajusta): descomente se precisar
// error_reporting(E_ALL); ini_set('display_errors', 1);

ensure_services_catalog($pdo);

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
$MAX_PECAS = 10;

$msg = ''; $err = '';
$customer_mode = $_POST['customer_mode'] ?? 'existing';
$customer_id_selected = $_POST['customer_id'] ?? '';
$novo_cliente = [
  'tipo'      => $_POST['novo_tipo'] ?? 'final',
  'nome'      => trim($_POST['novo_nome'] ?? ''),
  'cpf_cnpj'  => trim($_POST['novo_cpf_cnpj'] ?? ''),
  'endereco'  => trim($_POST['novo_endereco'] ?? ''),
  'telefone'  => trim($_POST['novo_telefone'] ?? ''),
  'email'     => trim($_POST['novo_email'] ?? ''),
  'observacoes' => trim($_POST['novo_obs'] ?? ''),
];

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
  $customer_id   = (int)($_POST['customer_id'] ?? 0);
  $cliente       = null;

  if ($customer_mode === 'new') {
    $novo_tipo      = $_POST['novo_tipo'] ?? 'final';
    $novo_nome      = trim($_POST['novo_nome'] ?? '');
    $novo_doc       = trim($_POST['novo_cpf_cnpj'] ?? '');
    $novo_endereco  = trim($_POST['novo_endereco'] ?? '');
    $novo_tel       = trim($_POST['novo_telefone'] ?? '');
    $novo_email     = trim($_POST['novo_email'] ?? '');
    $novo_obs       = trim($_POST['novo_obs'] ?? '');

    if (!in_array($novo_tipo, ['final','corporativo'], true)) {
      $err = 'Tipo de cliente inválido.';
    } elseif ($novo_nome === '') {
      $err = 'Informe o nome do novo cliente.';
    } else {
      $insCliente = $pdo->prepare("INSERT INTO customers (tipo,nome,cpf_cnpj,endereco,telefone,email,observacoes) VALUES (?,?,?,?,?,?,?)");
      $insCliente->execute([$novo_tipo,$novo_nome,$novo_doc,$novo_endereco,$novo_tel,$novo_email,$novo_obs]);
      $customer_id = (int)$pdo->lastInsertId();
      $cliente = ['id'=>$customer_id,'nome'=>$novo_nome,'tipo'=>$novo_tipo];
    }
  } else {
    // tipo do cliente define o preço (final/corporativo)
    $c = $pdo->prepare("SELECT id, nome, tipo FROM customers WHERE id=?");
    $c->execute([$customer_id]);
    $cliente = $c->fetch();
    if (!$cliente) {
      $err = 'Cliente inválido.';
    }
  }

  if (!$err && $cliente) {
    // 1) cria orçamento rascunho
    $forma_pagto = trim($_POST['forma_pagto'] ?? '');
    $forma_pagto = ($forma_pagto === '') ? null : $forma_pagto;
    $ins = $pdo->prepare("INSERT INTO quotes (customer_id,user_id,price_list_id,forma_pagto,status,subtotal,desconto,total)
                          VALUES (?,?,?,?, 'rascunho',0,0,0)");
    $ins->execute([$customer_id, $_SESSION['uid'], null, $forma_pagto]);
    $qid = $pdo->lastInsertId();

    // indexa serviços por id
    $by_id = [];
    foreach ($catalog_all as $s) { $by_id[$s['id']] = $s; }

    $subtotal_geral = 0;
    $total_itens    = 0;

    // 2) processa peças
    for ($i=0; $i<$MAX_PECAS; $i++) {
      $svc_ids_raw = $_POST['svc_id'][$i] ?? [];
      $svc_mults   = $_POST['svc_qty'][$i] ?? [];
      $servicos    = [];
      foreach ($svc_ids_raw as $idx => $svc_id_raw) {
        $svc_id = (int)$svc_id_raw;
        if (!$svc_id || !isset($by_id[$svc_id])) continue;
        $servicos[] = [
          'id'   => $svc_id,
          'mult' => (float)($svc_mults[$idx] ?? 1)
        ];
      }

      if (!$servicos) continue; // peça sem serviços

      // coleta dados da peça
      $tipo_peca = $_POST['tipo_peca'][$i] ?? 'retangular';
      $larg_cm   = (float)($_POST['largura_cm'][$i] ?? 0);
      $comp_cm   = (float)($_POST['comprimento_cm'][$i] ?? 0);
      $diam_cm   = (float)($_POST['diametro_cm'][$i] ?? 0);
      $qtd       = (float)($_POST['qtd'][$i] ?? 1);
      $rotulo    = trim($_POST['peca_nome'][$i] ?? '');
      $lacre     = trim($_POST['lacre_numero'][$i] ?? '');

      $meta = ['origem'=>'quick-add'];
      if ($rotulo !== '') { $meta['rotulo'] = $rotulo; }

      // 2.1) cria peça (quote_item)
      $st = $pdo->prepare("INSERT INTO quote_items
        (quote_id,service_id,tipo_tapete,largura_cm,comprimento_cm,diametro_cm,qtd,preco_unitario,regra_aplicada_json,subtotal,lacre_numero)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$qid,null,$tipo_peca,$larg_cm,$comp_cm,$diam_cm,$qtd,0,json_encode($meta),0,$lacre]);
      $qi_id = $pdo->lastInsertId();

      $m2         = area_m2($tipo_peca, $larg_cm, $comp_cm, $diam_cm);
      $qtd_pecas  = max(0.0001, $qtd);
      $item_subtotal = 0.0;

      foreach ($servicos as $svc_info) {
        $svc = $by_id[$svc_info['id']];
        $unit = ($cliente['tipo']==='final') ? (float)$svc['preco_final'] : (float)$svc['preco_corporativo'];

        $multiplicador = max(0.0001, $svc_info['mult'] ?: 1);
        if ($svc['unidade']==='m2') {
          $base_qty = max(0.0001,$m2) * $qtd_pecas * $multiplicador;
        } else {
          $base_qty = max(0.0001, $qtd_pecas * $multiplicador);
        }

        $sub = $unit * $base_qty;

        $ins2 = $pdo->prepare("INSERT INTO quote_item_services (quote_item_id, service_all_id, qtd, preco_unitario, subtotal)
                               VALUES (?,?,?,?,?)");
        $ins2->execute([$qi_id, $svc_info['id'], $qtd_pecas * $multiplicador, $unit, $sub]);

        $subtotal_geral += $sub;
        $item_subtotal += $sub;
      }

      $pdo->prepare("UPDATE quote_items SET subtotal=? WHERE id=?")->execute([$item_subtotal, $qi_id]);
      $total_itens++;
    }

    if ($total_itens === 0) {
      $pdo->prepare("DELETE FROM quotes WHERE id=?")->execute([$qid]);
      $err = 'Adicione ao menos um serviço para gerar a OS.';
    } else {
      // 3) totais do orçamento e aprova
      $desconto = 0.00;
      $total    = max(0, $subtotal_geral - $desconto);
      $pdo->prepare("UPDATE quotes SET subtotal=?, desconto=?, total=?, status='aprovado' WHERE id=?")
          ->execute([$subtotal_geral,$desconto,$total,$qid]);

      // 4) cria OS
      $codigo = 'OS-'.date('ymd').'-'.str_pad((string)$qid,4,'0',STR_PAD_LEFT);
      $pdo->prepare("INSERT INTO work_orders (quote_id,codigo_os,customer_id,user_id,status,subtotal,desconto,total)
                     VALUES (?,?,?,?, 'aberta',?,?,?)")
          ->execute([$qid,$codigo,$customer_id,$_SESSION['uid'],$subtotal_geral,$desconto,$total]);
      $woid = $pdo->lastInsertId();

      // 5) etiquetas (1 por peça) + replica serviços no nível da OS
      $qi = $pdo->prepare("SELECT id, lacre_numero FROM quote_items WHERE quote_id=?");
      $qi->execute([$qid]);
      $svc_fetch = $pdo->prepare("SELECT qis.service_all_id, qis.qtd, qis.preco_unitario, qis.subtotal, s.unidade
                                  FROM quote_item_services qis
                                  JOIN services_all s ON s.id = qis.service_all_id
                                  WHERE qis.quote_item_id=?");
      $ins_work_svc = $pdo->prepare("INSERT INTO work_item_services (work_item_id,service_id,unidade,qtd,preco_unitario,subtotal)
                                     VALUES (?,?,?,?,?,?)");
      foreach ($qi as $row) {
        $label = 'ET-'.date('ymd').'-'.$row['id'];
        $svc_fetch->execute([$row['id']]);
        $svc_rows = $svc_fetch->fetchAll();
        $item_subtotal = 0.0;
        foreach ($svc_rows as $svc_row) {
          $item_subtotal += (float)$svc_row['subtotal'];
        }

        $pdo->prepare("INSERT INTO work_order_items (work_order_id,quote_item_id,etiqueta_codigo,lacre_numero,status_item,subtotal)
                       VALUES (?,?,?,?,'EM_TRANSITO',?)")
            ->execute([$woid,$row['id'],$label,$row['lacre_numero'],$item_subtotal]);
        $wo_item_id = $pdo->lastInsertId();

        foreach ($svc_rows as $svc_row) {
          $ins_work_svc->execute([
            $wo_item_id,
            $svc_row['service_all_id'],
            $svc_row['unidade'],
            $svc_row['qtd'],
            $svc_row['preco_unitario'],
            $svc_row['subtotal'],
          ]);
        }
      }

      // 6) redireciona para OS
      redirect('/?route=os-view&id='.$woid);
    }
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
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label d-block">Como deseja definir o cliente?</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input js-customer-mode" type="radio" name="customer_mode" id="mode-existing" value="existing"<?=($customer_mode==='new'?'':' checked')?>>
              <label class="form-check-label" for="mode-existing">Selecionar da lista</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input js-customer-mode" type="radio" name="customer_mode" id="mode-new" value="new"<?=($customer_mode==='new'?' checked':'')?>>
              <label class="form-check-label" for="mode-new">Cadastrar novo cliente</label>
            </div>
          </div>

          <div class="col-12 js-existing-fields<?=($customer_mode==='new'?' d-none':'')?>">
            <label class="form-label">Cliente</label>
            <select name="customer_id" class="form-select"<?=($customer_mode==='new'?' disabled':'')?>>
              <option value="">Selecione</option>
              <?php foreach($clientes as $c): ?>
                <option value="<?=$c['id']?>"<?=((string)$customer_id_selected===(string)$c['id']?' selected':'')?>><?=h($c['nome'])?> (<?=$c['tipo']?>) — #<?=$c['id']?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">O tipo do cliente (final/corporativo) define o preço usado.</div>
          </div>

          <div class="col-12 js-new-fields<?=($customer_mode==='new'?'':' d-none')?>">
            <div class="alert alert-info py-2">Os dados abaixo serão salvos automaticamente ao gerar a OS.</div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select name="novo_tipo" class="form-select form-select-sm"<?=($customer_mode==='new'?'':' disabled')?>>
                  <option value="final"<?=($novo_cliente['tipo']==='final'?' selected':'')?>>Final</option>
                  <option value="corporativo"<?=($novo_cliente['tipo']==='corporativo'?' selected':'')?>>Corporativo</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Nome / Razão Social</label>
                <input name="novo_nome" class="form-control form-control-sm" placeholder="Informe o nome completo" value="<?=h($novo_cliente['nome'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
              <div class="col-md-4">
                <label class="form-label">CPF / CNPJ</label>
                <input name="novo_cpf_cnpj" class="form-control form-control-sm" value="<?=h($novo_cliente['cpf_cnpj'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
              <div class="col-md-4">
                <label class="form-label">Telefone</label>
                <input name="novo_telefone" class="form-control form-control-sm" value="<?=h($novo_cliente['telefone'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
              <div class="col-md-4">
                <label class="form-label">E-mail</label>
                <input name="novo_email" type="email" class="form-control form-control-sm" value="<?=h($novo_cliente['email'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
              <div class="col-md-12">
                <label class="form-label">Endereço</label>
                <input name="novo_endereco" class="form-control form-control-sm" value="<?=h($novo_cliente['endereco'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
              <div class="col-md-12">
                <label class="form-label">Observação</label>
                <input name="novo_obs" class="form-control form-control-sm" value="<?=h($novo_cliente['observacoes'])?>"<?=($customer_mode==='new'?'':' disabled')?>>
              </div>
            </div>
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
    </div>

    <!-- Peças e serviços -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Peças e serviços (até <?=$MAX_PECAS?> peças)</span>
        <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalNovoServico">
          + Novo Serviço
        </button>
      </div>

      <div class="card-body">
        <?php for($i=0; $i<$MAX_PECAS; $i++): ?>
        <div class="border rounded p-3 mb-3 js-piece" data-piece="<?=$i?>">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Peça <?=($i+1)?></h6>
            <span class="text-muted small">Deixe em branco se não for utilizar.</span>
          </div>

          <div class="row g-2">
            <div class="col-md-6 col-lg-4">
              <label class="form-label">Identificação da peça</label>
              <input name="peca_nome[<?=$i?>]" class="form-control form-control-sm" placeholder="Ex: Tapete <?=($i+1)?>">
            </div>
            <div class="col-md-3 col-lg-2">
              <label class="form-label">Número do lacre</label>
              <input name="lacre_numero[<?=$i?>]" class="form-control form-control-sm" placeholder="Ex: 12345">
            </div>
            <div class="col-md-3 col-lg-2">
              <label class="form-label">Tipo</label>
              <select name="tipo_peca[<?=$i?>]" class="form-select form-select-sm">
                <option value="retangular">Retangular</option>
                <option value="redondo">Redondo</option>
              </select>
            </div>
            <div class="col-md-3 col-lg-2">
              <label class="form-label">Qtd. peças</label>
              <input name="qtd[<?=$i?>]" type="number" step="0.01" class="form-control form-control-sm" value="1">
            </div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-md-4 col-lg-2">
              <label class="form-label">Largura (cm)</label>
              <input name="largura_cm[<?=$i?>]" class="form-control form-control-sm js-dim" data-piece="<?=$i?>" type="number" step="0.01" placeholder="0" disabled>
            </div>
            <div class="col-md-4 col-lg-2">
              <label class="form-label">Comprimento (cm)</label>
              <input name="comprimento_cm[<?=$i?>]" class="form-control form-control-sm js-dim" data-piece="<?=$i?>" type="number" step="0.01" placeholder="0" disabled>
            </div>
            <div class="col-md-4 col-lg-2">
              <label class="form-label">Diâmetro (cm)</label>
              <input name="diametro_cm[<?=$i?>]" class="form-control form-control-sm js-dim" data-piece="<?=$i?>" type="number" step="0.01" placeholder="0" disabled>
            </div>
          </div>

          <div class="mt-3">
            <div class="small text-muted mb-2">Selecione todos os serviços aplicáveis a esta peça.</div>
            <div class="js-services" data-piece="<?=$i?>">
              <div class="row align-items-end g-2 js-service-row">
                <div class="col-md-6 col-lg-5">
                  <label class="form-label">Serviço (catálogo)</label>
                  <select name="svc_id[<?=$i?>][]" class="form-select form-select-sm js-svc" data-piece="<?=$i?>">
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
                </div>
                <div class="col-md-3 col-lg-2">
                  <label class="form-label d-block">Unidade</label>
                  <span class="badge bg-secondary w-100 text-center js-unit">—</span>
                </div>
                <div class="col-md-3 col-lg-2">
                  <label class="form-label">Qtd. serviço</label>
                  <input name="svc_qty[<?=$i?>][]" type="number" step="0.01" class="form-control form-control-sm" value="1">
                </div>
                <div class="col-md-12 col-lg-3">
                  <label class="form-label d-block">&nbsp;</label>
                  <button type="button" class="btn btn-outline-danger btn-sm w-100 js-remove-svc">Remover</button>
                </div>
              </div>
            </div>
            <div class="js-service-template d-none">
              <div class="row align-items-end g-2 js-service-row">
                <div class="col-md-6 col-lg-5">
                  <label class="form-label">Serviço (catálogo)</label>
                  <select name="svc_id[<?=$i?>][]" class="form-select form-select-sm js-svc" data-piece="<?=$i?>">
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
                </div>
                <div class="col-md-3 col-lg-2">
                  <label class="form-label d-block">Unidade</label>
                  <span class="badge bg-secondary w-100 text-center js-unit">—</span>
                </div>
                <div class="col-md-3 col-lg-2">
                  <label class="form-label">Qtd. serviço</label>
                  <input name="svc_qty[<?=$i?>][]" type="number" step="0.01" class="form-control form-control-sm" value="1">
                </div>
                <div class="col-md-12 col-lg-3">
                  <label class="form-label d-block">&nbsp;</label>
                  <button type="button" class="btn btn-outline-danger btn-sm w-100 js-remove-svc">Remover</button>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 js-add-svc" data-piece="<?=$i?>">+ Adicionar serviço</button>
          </div>
        </div>
        <?php endfor; ?>
        <div class="form-text">
          • Cadastre cada <strong>peça</strong> apenas uma vez e escolha todos os serviços aplicáveis.<br>
          • Campos de medidas só são necessários para serviços em <strong>m²</strong> (habilitados automaticamente).<br>
          • Use "Qtd. serviço" para ajustar volumes específicos (ex.: quantidade de barras).
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
(function(){
  function updateUnit(sel){
    var row = sel.closest('.js-service-row');
    var badge = row ? row.querySelector('.js-unit') : null;
    if (badge) {
      var opt = sel.options[sel.selectedIndex];
      var unit = opt ? (opt.getAttribute('data-unit') || '') : '';
      badge.textContent = unit || '—';
    }
  }

  function updateDims(piece){
    var requiresArea = false;
    document.querySelectorAll('.js-svc[data-piece="'+piece+'"]').forEach(function(sel){
      Array.from(sel.selectedOptions).forEach(function(opt){
        if ((opt.getAttribute('data-unit') || '') === 'm2') {
          requiresArea = true;
        }
      });
    });

    document.querySelectorAll('.js-dim[data-piece="'+piece+'"]').forEach(function(inp){
      if (requiresArea) {
        inp.removeAttribute('disabled');
      } else {
        inp.value = '';
        inp.setAttribute('disabled','disabled');
      }
    });
  }

  function toggleCustomerFields(mode){
    var existing = document.querySelector('.js-existing-fields');
    var existingInputs = existing ? existing.querySelectorAll('select,input') : [];
    var novos = document.querySelector('.js-new-fields');
    var novosInputs = novos ? novos.querySelectorAll('select,input') : [];

    if (mode === 'new') {
      if (existing) existing.classList.add('d-none');
      existingInputs.forEach(function(el){ el.setAttribute('disabled','disabled'); });
      if (novos) {
        novos.classList.remove('d-none');
        novosInputs.forEach(function(el){ el.removeAttribute('disabled'); });
      }
    } else {
      if (existing) existing.classList.remove('d-none');
      existingInputs.forEach(function(el){ el.removeAttribute('disabled'); });
      if (novos) {
        novos.classList.add('d-none');
        novosInputs.forEach(function(el){ el.setAttribute('disabled','disabled'); });
      }
    }
  }

  document.addEventListener('change', function(ev){
    if (ev.target.matches('.js-svc')) {
      updateUnit(ev.target);
      updateDims(ev.target.dataset.piece);
    }
    if (ev.target.matches('.js-customer-mode')) {
      toggleCustomerFields(ev.target.value);
    }
  });

  document.querySelectorAll('.js-add-svc').forEach(function(btn){
    btn.addEventListener('click', function(){
      var pieceContainer = this.closest('.js-piece');
      if (!pieceContainer) return;
      var template = pieceContainer.querySelector('.js-service-template .js-service-row');
      var target = pieceContainer.querySelector('.js-services');
      if (!template || !target) return;
      var clone = template.cloneNode(true);
      clone.querySelectorAll('select').forEach(function(sel){ sel.selectedIndex = 0; });
      clone.querySelectorAll('input').forEach(function(inp){ inp.value = inp.name.includes('svc_qty') ? '1' : ''; });
      var badge = clone.querySelector('.js-unit');
      if (badge) { badge.textContent = '—'; }
      target.appendChild(clone);
    });
  });

  document.addEventListener('click', function(ev){
    if (ev.target.matches('.js-remove-svc')) {
      var row = ev.target.closest('.js-service-row');
      var pieceContainer = ev.target.closest('.js-piece');
      if (!row || !pieceContainer) return;
      var servicesWrap = pieceContainer.querySelector('.js-services');
      var piece = pieceContainer.dataset.piece;
      if (servicesWrap && servicesWrap.querySelectorAll('.js-service-row').length > 1) {
        row.remove();
      } else {
        var sel = row.querySelector('select');
        if (sel) { sel.selectedIndex = 0; updateUnit(sel); }
        row.querySelectorAll('input').forEach(function(inp){ inp.value = inp.name.includes('svc_qty') ? '1' : ''; });
      }
      if (piece !== undefined) {
        updateDims(piece);
      }
    }
  });

  // Estado inicial
  document.querySelectorAll('.js-piece').forEach(function(piece){
    updateDims(piece.dataset.piece);
  });
  document.querySelectorAll('.js-svc').forEach(function(sel){
    updateUnit(sel);
  });
  toggleCustomerFields('<?=$customer_mode==='new'?'new':'existing'?>');
})();
</script>
