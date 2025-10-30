<?php
require __DIR__.'/../vendor/autoload.php';
use Dompdf\Dompdf;
use chillerlan\QRCode\QRCode;

$code = $_GET['code'] ?? '';
if(!$code){ die('Código inválido'); }

$st=$pdo->prepare("SELECT w.codigo_os, c.nome as cliente, s.nome as servico, qi.largura_cm, qi.comprimento_cm, qi.diametro_cm
FROM work_order_items woi
JOIN work_orders w ON w.id=woi.work_order_id
JOIN quotes q ON q.id=w.quote_id
JOIN customers c ON c.id=q.customer_id
JOIN quote_items qi ON qi.id=woi.quote_item_id
JOIN services s ON s.id=qi.service_id
WHERE woi.etiqueta_codigo=?");
$st->execute([$code]); $d=$st->fetch();
if(!$d){ die('Etiqueta não encontrada'); }

$qrData = base_url().'/i/'.urlencode($code);
$qrPng  = (new QRCode)->render($qrData);
$medidas = ($d['diametro_cm']>0) ? ('Ø '.number_format($d['diametro_cm'],0).' cm') : (number_format($d['largura_cm'],0).'x'.number_format($d['comprimento_cm'],0).' cm');

$logoPath = __DIR__.'/../assets/hamadanlogo.png';
$logoTag = file_exists($logoPath) ? "<img src='../assets/hamadanlogo.png' style='height:20px'>" : '';

$html = "
<div style='width:80mm;height:50mm;font-family:sans-serif;font-size:12px;padding:6px;border:1px solid #000;box-sizing:border-box'>
  <div style='display:flex;justify-content:space-between;gap:8px;align-items:flex-start'>
    <div>
      <div style=\"margin-bottom:4px\">$logoTag</div>
      <div><strong>Cliente:</strong> ".htmlspecialchars($d['cliente'])."</div>
      <div><strong>OS:</strong> ".htmlspecialchars($d['codigo_os'])."</div>
      <div><strong>Serviço:</strong> ".htmlspecialchars($d['servico'])."</div>
      <div><strong>Medidas:</strong> ".$medidas."</div>
      <div><small>".date('d/m/Y H:i')."</small></div>
    </div>
    <div><img src='".$qrPng."' style='width:90px'></div>
  </div>
  <div style='margin-top:4px'><small><strong>Código:</strong> ".htmlspecialchars($code)."</small></div>
</div>";

$dompdf = new Dompdf(['isRemoteEnabled'=>true,'defaultPaperSize'=>'A7','defaultPaperOrientation'=>'landscape']);
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("etiqueta-$code.pdf", ["Attachment"=>false]);
exit;
