<?php
require_login();
$id=(int)($_GET['id']??0);
$check=$pdo->prepare("SELECT COUNT(*) FROM work_orders WHERE customer_id=?");
$check->execute([$id]); $qtd=(int)$check->fetchColumn();
if($qtd>0){
  flash_set('Não é possível remover: há ordens de serviço vinculadas a este cliente.','danger');
  redirect('/?route=customers');
}
$pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
flash_set('Cliente removido.');
redirect('/?route=customers');
