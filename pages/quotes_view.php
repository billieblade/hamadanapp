<?php
// carregar catálogo
$catalog = $pdo->query("SELECT * FROM services_all WHERE ativo=1 ORDER BY categoria, nome")->fetchAll();

// adicionar serviço a um item
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_service_to_item'])){
  $qi_id = (int)$_POST['quote_item_id'];
  $svc_id = (int)$_POST['service_all_id'];
  $qtd = (int)($_POST['qtd'] ?: 1);

  // pega o tipo do cliente e o serviço
  $s = $pdo->prepare("SELECT * FROM services_all WHERE id=?"); $s->execute([$svc_id]); $svc = $s->fetch();
  $unit = ($quote['cliente_tipo']==='final') ? (float)$svc['preco_final'] : (float)$svc['preco_corporativo'];

  // se unidade for m2, usar área do item como base
  $qi = $pdo->prepare("SELECT * FROM quote_items WHERE id=?"); $qi->execute([$qi_id]); $item = $qi->fetch();
  $m2 = area_m2($item['tipo_tapete'], $item['largura_cm'], $item['comprimento_cm'], $item['diametro_cm']);
  $base_qty = ($svc['unidade']==='m2') ? max(0.0001,$m2) : $qtd;

  $subtotal = $unit * $base_qty;

  $ins=$pdo->prepare("INSERT INTO quote_item_services (quote_item_id, service_all_id, qtd, preco_unitario, subtotal)
                      VALUES (?,?,?,?,?)");
  $ins->execute([$qi_id, $svc_id, $qtd, $unit, $subtotal]);

  // opcional: atualizar totais do orçamento
}
?>
<div class="card mt-3">
  <div class="card-header">Adicionar serviço a um item</div>
  <div class="card-body">
    <form method="post" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">Item (peça)</label>
        <select name="quote_item_id" class="form-select" required>
          <?php foreach($items as $i): ?>
            <option value="<?=$i['id']?>">Item #<?=$i['id']?> (<?=$i['tipo_tapete']?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Serviço</label>
        <select name="service_all_id" class="form-select" required>
          <?php foreach($catalog as $s): ?>
            <option value="<?=$s['id']?>">
              [<?=$s['categoria']?>] <?=h($s['nome'])?> (<?=$s['unidade']?>) — Final R$ <?=number_format($s['preco_final'],2,',','.')?> / Corp R$ <?=number_format($s['preco_corporativo'],2,',','.')?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Qtd</label>
        <input class="form-control" name="qtd" type="number" step="1" value="1">
      </div>
      <div class="col-md-2 align-self-end">
        <button class="btn btn-outline-primary w-100" name="add_service_to_item" value="1">Adicionar</button>
      </div>
    </form>
  </div>
</div>
