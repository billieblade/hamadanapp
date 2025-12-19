<?php
require_role(['master']);
$id=(int)($_GET['id']??0);
$exists=$pdo->prepare("SELECT COUNT(*) FROM work_orders WHERE id=?");
$exists->execute([$id]);
if(!(int)$exists->fetchColumn()){
  flash_set('OS não encontrada.','danger'); redirect('/?route=dashboard');
}
$it=$pdo->prepare("SELECT id FROM work_order_items WHERE work_order_id=?");
$it->execute([$id]); $items=$it->fetchAll();
$delSv=$pdo->prepare("DELETE FROM work_item_services WHERE work_item_id=?");
$delIt=$pdo->prepare("DELETE FROM work_order_items WHERE id=?");
foreach($items as $row){ $delSv->execute([$row['id']]); $delIt->execute([$row['id']]); }
$pdo->prepare("DELETE FROM work_orders WHERE id=?")->execute([$id]);
flash_set('OS removida.'); redirect('/?route=dashboard');
