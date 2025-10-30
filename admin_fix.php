<?php
// /public_html/admin_fix.php  (APAGAR DEPOIS DE USAR)
error_reporting(E_ALL); ini_set('display_errors', 1);

require __DIR__.'/app/db.php';

// util
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 0) Mostrar DB atual (garante que é o banco certo)
$dbname = $pdo->query("SELECT DATABASE() db")->fetch()['db'] ?? '(desconhecido)';

// 1) Buscar usuário
$email = 'admin@admin.com';
$st = $pdo->prepare("SELECT * FROM users WHERE email=?");
$st->execute([$email]);
$u = $st->fetch();

if(!$u){
  echo "<p style='color:red'>❌ Usuário não encontrado em <b>".h($dbname)."</b> — <code>$email</code></p>";
  echo "<p>Confira se o <b>env.php</b> aponta para o mesmo DB que você abriu no phpMyAdmin.</p>";
  exit;
}

echo "<p>DB atual: <b>".h($dbname)."</b></p>";
echo "<p>Usuário: <b>".h($u['name'])."</b> | email: <code>".h($u['email'])."</code> | role: <b>".h($u['role'])."</b> | ativo: <b>".h($u['ativo'])."</b></p>";

// 2) Testar senha enviada via GET ?test=...
if(isset($_GET['test'])){
  $test = $_GET['test'];
  $ok = function_exists('password_verify') ? password_verify($test, $u['password_hash']) : false;
  echo $ok
    ? "<p style='color:green'>✅ password_verify OK com a senha enviada.</p>"
    : "<p style='color:red'>❌ password_verify FALHOU com a senha enviada.</p>";
  echo "<p>Hash (início): <code>".h(substr($u['password_hash'],0,25))."...</code></p>";
  echo "<hr>";
}

// 3) Resetar a senha com segurança (POST)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['newpass'])){
  $new = (string)$_POST['newpass'];
  if(strlen($new) < 6){
    echo "<p style='color:red'>❌ Informe uma senha com pelo menos 6 caracteres.</p>";
  }else{
    $hash = password_hash($new, PASSWORD_BCRYPT);
    $up = $pdo->prepare("UPDATE users SET password_hash=?, ativo=1, role='master' WHERE email=?");
    $up->execute([$hash, $email]);

    // Recarrega para testar
    $st = $pdo->prepare("SELECT password_hash FROM users WHERE email=?");
    $st->execute([$email]);
    $row = $st->fetch();
    $ok = password_verify($new, $row['password_hash']);
    if($ok){
      echo "<p style='color:green'>✅ Senha redefinida com sucesso para <b>".h($email)."</b>.</p>";
      echo "<p>Agora você deve conseguir logar no <a href='/login.php'>/login.php</a>.</p>";
    }else{
      echo "<p style='color:red'>❌ Algo deu errado ao salvar a nova senha.</p>";
    }
  }
}
?>
<!doctype html>
<html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Fix</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-4" style="max-width:560px">
  <h3>Resetar senha do master</h3>
  <ol>
    <li>Opcional: teste uma senha com <code>?test=SUA_SENHA</code> (ex.: <code>?test=admin123</code>).</li>
    <li>Defina abaixo uma nova senha e salve.</li>
    <li>Depois, tente logar em <a href="/login.php">/login.php</a> e <b>APAGUE</b> este arquivo.</li>
  </ol>
  <form method="post" class="card card-body">
    <label class="form-label">Nova senha para <code>admin@admin.com</code></label>
    <input type="text" class="form-control mb-3" name="newpass" placeholder="Digite a nova senha" required>
    <button class="btn btn-primary">Salvar nova senha</button>
  </form>
  <hr>
  <p class="text-muted"><small>Segurança: este arquivo é provisório; apague-o após o uso.</small></p>
</div>
</body></html>
