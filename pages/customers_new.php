<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
  $st=$pdo->prepare("INSERT INTO customers (tipo,nome,cpf_cnpj,endereco,telefone,email,obs) VALUES (?,?,?,?,?,?,?)");
  $st->execute([$_POST['tipo'],$_POST['nome'],$_POST['cpf_cnpj'],$_POST['endereco'],$_POST['telefone'],$_POST['email'],$_POST['obs']]);
  redirect('/?route=customers');
}
?>
<div class="container py-4" style="max-width:720px">
  <h3>Novo Cliente</h3>
  <form method="post">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
          <option value="final">Final</option>
          <option value="corporativo">Corporativo</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Nome completo / Razão social</label>
        <input name="nome" class="form-control" required>
      </div>
      <div class="col-md-6"><label class="form-label">CPF/CNPJ</label><input name="cpf_cnpj" class="form-control"></div>
      <div class="col-md-6"><label class="form-label">Telefone</label><input name="telefone" class="form-control"></div>
      <div class="col-12"><label class="form-label">E-mail</label><input name="email" type="email" class="form-control"></div>
      <div class="col-12"><label class="form-label">Endereço</label><input name="endereco" class="form-control"></div>
      <div class="col-12"><label class="form-label">Observações</label><textarea name="obs" class="form-control"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Salvar</button></div>
  </form>
</div>
