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
