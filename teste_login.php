<?php
require __DIR__.'/app/db.php';

// CONFIG
$email = 'admin@admin.com';
$senha = 'admin123';

// 1. Teste de conexão
try {
  $st = $pdo->query("SELECT NOW() AS agora");
  $r = $st->fetch();
  echo "<p style='color:green'>✅ Conexão OK: {$r['agora']}</p>";
} catch (Throwable $e) {
  die("<p style='color:red'>❌ Erro de conexão: ".$e->getMessage()."</p>");
}

// 2. Busca o usuário
$st = $pdo->prepare("SELECT * FROM users WHERE email=?");
$st->execute([$email]);
$u = $st->fetch();

if(!$u){
  die("<p style='color:red'>❌ Usuário não encontrado ($email)</p>");
}

echo "<p>Usuário encontrado: <strong>{$u['name']}</strong> (ativo={$u['ativo']})</p>";

// 3. Teste da senha
if(function_exists('password_verify')){
  $ok = password_verify($senha, $u['password_hash']);
  if($ok){
    echo "<p style='color:green'>✅ Senha correta (password_verify OK)</p>";
  }else{
    echo "<p style='color:red'>❌ Senha incorreta ou hash inválido.</p>";
    echo "<pre>Hash no banco: {$u['password_hash']}</pre>";
  }
} else {
  echo "<p style='color:red'>❌ Função password_verify() não existe — PHP muito antigo.</p>";
}

// 4. Debug da versão PHP
echo "<hr><p>Versão do PHP: ".phpversion()."</p>";
?>
