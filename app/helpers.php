<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function code_os($id){ return 'OS-'.date('ymd').'-'.str_pad($id,4,'0',STR_PAD_LEFT); }
function code_label($itemId){ return 'ET-'.date('ymd').'-'.$itemId; }

function redirect($url){
  if (!headers_sent()) {
    header("Location: $url");
    exit;
  }
  // fallback quando já houve saída
  $u = h($url);
  echo "<script>location.replace('{$u}');</script>";
  echo "<noscript><meta http-equiv='refresh' content='0;url={$u}'></noscript>";
  exit;
}

function flash_set($msg, $type='success'){ $_SESSION['flash'] = ['msg'=>$msg, 'type'=>$type]; }
function flash_get(){
  if(!empty($_SESSION['flash'])){
    $f=$_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
  }
  return null;
}

function ensure_services_catalog(PDO $pdo): bool {
  static $done = false;
  static $ready = true;
  if ($done) {
    return $ready;
  }
  $done = true;

  try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM services_all")->fetchColumn();
  } catch (Exception $e) {
    $ready = false;
    return $ready;
  }

  if ($count > 0) {
    return $ready;
  }

  try {
    $legacyCount = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
  } catch (Exception $e) {
    $ready = false;
    return $ready;
  }

  if ($legacyCount === 0) {
    return $ready;
  }

  $stmt = $pdo->prepare(
    "INSERT INTO services_all (categoria,nome,unidade,preco_final,preco_corporativo,observacao,ativo)
     SELECT s.categoria, s.nome, s.unidade, s.preco_final, s.preco_corporativo, s.observacao, s.ativo
     FROM services s
     WHERE NOT EXISTS (
       SELECT 1 FROM services_all sa
       WHERE sa.categoria = s.categoria
         AND sa.nome = s.nome
         AND sa.unidade = s.unidade
     )"
  );
  $stmt->execute();
  return $ready;
}
