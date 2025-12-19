<?php
session_start();
require __DIR__.'/app/db.php';
require __DIR__.'/app/auth.php';
require __DIR__.'/app/helpers.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(login($_POST['email']??'', $_POST['password']??'', $pdo)){ header('Location: /'); exit; }
  else { $err="Credenciais inválidas."; }
}
?><!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css"><title>Login</title></head>
<body class="bg-light"><div class="container py-5" style="max-width:420px">
<div class="card p-4"><h3 class="mb-3 text-center">Hamadan</h3>
<?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
<form method="post" class="needs-validation" novalidate>
  <div class="mb-3"><label class="form-label">E-mail</label><input name="email" type="email" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Senha</label><input name="password" type="password" class="form-control" required></div>
  <button class="btn btn-primary w-100">Entrar</button>
</form>
<div class="text-center mt-3"><small>Esqueceu a senha? Master: <code>/adjust_password.php</code></small></div>
</div></div></body></html>
