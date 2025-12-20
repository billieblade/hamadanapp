<?php
require_login();
$woId=(int)($_GET['id']??0); $single=(int)($_GET['single']??0);
$q="SELECT i.*, wo.codigo_os, c.nome as cliente FROM work_order_items i JOIN work_orders wo ON wo.id=i.work_order_id JOIN customers c ON c.id=wo.customer_id WHERE i.work_order_id=?";
$p=[$woId]; if($single){ $q.=" AND i.id=?"; $p[]=$single; }
$q.=" ORDER BY i.id"; $st=$pdo->prepare($q); $st->execute($p); $items=$st->fetchAll();
$cfg=require __DIR__.'/../app/config.php'; $base = rtrim($cfg['app']['base_url'],'/');
?><!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/style.css"><title>Etiquetas</title>
<style>@media print {.no-print{display:none}}</style></head><body><div class="container py-3">
<button class="btn btn-outline-secondary no-print mb-3" onclick="window.print()">Imprimir</button>
<div class="label-sheet">
<?php foreach($items as $it): $url = $base.'/?route=wo-view&id='.$woId; $qr  = $cfg['app']['qr_host'].urlencode($url); ?>
  <div class="label-box">
    <div class="label-left">
      <img src="/assets/hamadan-logo.png" class="logo" alt="Hamadan">
      <div class="code"><?=h($it['etiqueta_codigo'])?></div>
      <?php if(!empty($it['lacre_numero'])): ?>
        <small>Lacre: <?=h($it['lacre_numero'])?></small>
      <?php endif; ?>
      <small>OS: <?=h($it['codigo_os'])?></small>
      <small><?=h($it['cliente'])?></small>
    </div>
    <div class="label-right">
      <img src="<?=$qr?>" alt="QR" />
    </div>
  </div>
<?php endforeach; ?>
</div></div></body></html>
