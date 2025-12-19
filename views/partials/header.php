<?php
if (!headers_sent()) { header('Content-Type: text/html; charset=utf-8'); }
ini_set('default_charset','UTF-8');
?><!doctype html><html lang="pt-br"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css"><title>HamadanApp</title></head><body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top"><div class="container-fluid">
<a class="navbar-brand d-flex align-items-center" href="/?route=dashboard">
<img src="/assets/hamadan-logo.png" alt="Hamadan" style="height:26px" class="me-2"> Hamadan</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
<span class="navbar-toggler-icon"></span></button>
<div id="navMenu" class="collapse navbar-collapse">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link" href="/?route=wo-new">Nova OS</a></li>
<li class="nav-item"><a class="nav-link" href="/?route=customers">Clientes</a></li>
<?php if(($_SESSION['role']??'')==='master'): ?>
<li class="nav-item"><a class="nav-link" href="/?route=services">Serviços/Preços</a></li>
<?php endif; ?>
<li class="nav-item"><a class="nav-link" href="/?route=reports">Relatórios</a></li>
</ul>
<span class="navbar-text text-light me-3"><?=h($_SESSION['name']??'')?> (<?=h($_SESSION['role']??'')?>)</span>
<a class="btn btn-outline-light btn-sm" href="/logout.php">Sair</a>
</div></div></nav>
<div class="container my-3 px-2 px-md-0">
<?php if(function_exists('flash_get')){ $f=flash_get(); if($f): ?>
  <div class="alert alert-<?=h($f['type'])?>"><?=h($f['msg'])?></div>
<?php endif; } ?>
