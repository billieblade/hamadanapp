<?php
/**
 * adjust_password_debug.php
 * Ferramenta de DEBUG (sem login nem key)
 * Uso: abra diretamente no navegador.
 * IMPORTANTE: Apague depois de usar.
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');

// ====== CONEXÃO COM O BANCO ======
require __DIR__ . '/app/db.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$info = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'reset_any') {
    $email = trim($_POST['email'] ?? '');
    $new   = $_POST['new_password'] ?? '';
    $conf  = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = 'E-mail inválido.';
    } elseif (strlen($new) < 4) {
      $err = 'Senha muito curta (mínimo 4).';
    } elseif ($new !== $conf) {
      $err = 'Confirmação de senha não confere.';
    } else {
      $hash = password_hash($new, PASSWORD_BCRYPT);
      $st = $pdo->prepare("UPDATE users SET password_hash=?, ativo=1 WHERE email=?");
      $st->execute([$hash, $email]);
      if ($st->rowCount() > 0) {
        $info = "Senha atualizada para <strong>".h($email)."</strong>.";
      } else {
        $err = "Usuário não encontrado: ".h($email);
      }
    }
  }

  if ($action === 'reset_master') {
    $email = 'admin@admin.com';
    $name  = 'Master';
    $hash  = password_hash('admin', PASSWORD_BCRYPT);

    $pdo->prepare("INSERT IGNORE INTO users (name,email,password_hash,role,ativo)
                   VALUES (?,?,?,'master',1)")
        ->execute([$name, $email, $hash]);
    $pdo->prepare("UPDATE users SET password_hash=?, role='master', ativo=1 WHERE email=?")
        ->execute([$hash, $email]);

    $info = "Master garantido. Login: <strong>$email</strong> / Senha: <strong>admin</strong>";
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DEBUG: Ajuste de Senhas (sem login)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:820px">
  <div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
      <strong>Ferramenta de DEBUG (sem login) — Apague após uso</strong>
    </div>
    <div class="card-body">
      <?php if ($info): ?><div class="alert alert-success"><?= $info ?></div><?php endif; ?>
      <?php if ($err):  ?><div class="alert alert-danger"><?= $err  ?></div><?php endif; ?>

      <h5>1) Resetar senha de QUALQUER usuário</h5>
      <form method="post" class="row g-3">
        <input type="hidden" name="action" value="reset_any">
        <div class="col-md-6">
          <label class="form-label">E-mail do usuário</label>
          <input type="email" name="email" class="form-control" placeholder="ex: func@hamadan.com" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Nova senha</label>
          <input type="password" name="new_password" class="form-control" minlength="4" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Confirmar</label>
          <input type="password" name="confirm_password" class="form-control" minlength="4" required>
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Salvar</button>
        </div>
      </form>

      <hr>

      <h5>2) Garantir/Resetar usuário Master</h5>
      <form method="post" onsubmit="return confirm('Isso vai definir admin@admin.com / admin. Continuar?');">
        <input type="hidden" name="action" value="reset_master">
        <button class="btn btn-danger">Forçar Master (admin@admin.com / admin)</button>
      </form>

      <hr>
      <div class="alert alert-warning mb-0">
        <strong>Após terminar, APAGUE este arquivo:</strong> <code>adjust_password_debug.php</code>
      </div>
    </div>
  </div>
</div>
</body>
</html>
