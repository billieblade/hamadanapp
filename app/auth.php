<?php
session_start();
function require_login() {
  if (empty($_SESSION['uid'])) { header('Location: /login.php'); exit; }
}
function require_role($roles){
  if(empty($_SESSION['role']) || !in_array($_SESSION['role'], (array)$roles)){
    http_response_code(403); echo 'Acesso negado'; exit;
  }
}
function login($email,$pass,$pdo){
  $st=$pdo->prepare("SELECT * FROM users WHERE email=? AND ativo=1");
  $st->execute([$email]); $u=$st->fetch();
  if($u && password_verify($pass,$u['password_hash'])){
    $_SESSION['uid']=$u['id']; $_SESSION['name']=$u['name']; $_SESSION['role']=$u['role'];
    return true;
  }
  return false;
}
function logout(){ session_destroy(); header('Location: /login.php'); exit; }
