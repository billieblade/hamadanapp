<?php
require __DIR__.'/app/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
  $rows = $pdo->query("SELECT id,categoria,nome,unidade,preco_final,preco_corporativo,ativo FROM services_all ORDER BY categoria,nome")->fetchAll(PDO::FETCH_ASSOC);
  echo "Total serviços ativos: ".$pdo->query("SELECT COUNT(*) FROM services_all WHERE ativo=1")->fetchColumn()."\n\n";
  foreach($rows as $r){
    echo sprintf("#%d [%s] %s (%s) F:%.2f C:%.2f ativo:%d\n",
      $r['id'],$r['categoria'],$r['nome'],$r['unidade'],$r['preco_final'],$r['preco_corporativo'],$r['ativo']
    );
  }
} catch(Throwable $e){
  echo "ERRO: ".$e->getMessage();
}
