<?php
require_login();
$code = trim($_GET['code'] ?? ''); $to   = trim($_GET['to'] ?? '');
$allow = ['EM_TRANSITO','LAVANDERIA','REPAROS','SECAGEM','ESPERANDO_ENTREGA','FINALIZADO'];
if(!$code || !in_array($to,$allow,true)){ http_response_code(400); echo "Parâmetros inválidos"; exit; }
$it = $pdo->prepare("SELECT id, work_order_id FROM work_order_items WHERE etiqueta_codigo=?"); $it->execute([$code]); $row=$it->fetch();
if(!$row){ http_response_code(404); echo "Etiqueta não encontrada"; exit; }
$pdo->prepare("UPDATE work_order_items SET status_item=? WHERE id=?")->execute([$to,$row['id']]);
header("Location: /?route=wo-view&id=".$row['work_order_id']);
