<?php
function login($email,$password,$pdo){
  $st = $pdo->prepare("SELECT * FROM users WHERE email=? AND ativo=1");
  $st->execute([$email]);
  $u = $st->fetch();
  if($u && password_verify($password,$u['password_hash'])){
    $_SESSION['uid']=$u['id'];
    $_SESSION['name']=$u['name'];
    $_SESSION['role']=$u['role'];
    return true;
  }
  return false;
}
function logout(){ session_destroy(); }
function require_login(){ if(empty($_SESSION['uid'])){ header('Location: /login.php'); exit; } }
function require_role(array $roles){
  if(empty($_SESSION['role']) || !in_array($_SESSION['role'],$roles,true)){
    http_response_code(403);
    echo "<div class='container py-4'><div class='alert alert-danger'>Acesso negado.</div></div>";
    include __DIR__.'/../views/partials/footer.php'; exit;
  }
}
