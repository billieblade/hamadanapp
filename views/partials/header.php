<?php
// views/partials/header.php
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css?v=1.0">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="/?route=dashboard">Hamadan</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="navMenu" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- Clientes -->
        <li class="nav-item"><a class="nav-link" href="/?route=customers">Clientes</a></li>
        <li class="nav-item"><a class="nav-link" href="/?route=customers-new">Adicionar Clientes</a></li>

        <!-- Serviços -->
        <li class="nav-item"><a class="nav-link" href="/?route=services">Serviços</a></li>
        <li class="nav-item"><a class="nav-link" href="/?route=services-new">Adicionar Serviços</a></li>

        <!-- Tapetes -->
        <li class="nav-item"><a class="nav-link" href="/?route=tapetes">Tapetes</a></li>

        <!-- Relatórios -->
        <li class="nav-item"><a class="nav-link" href="/?route=reports">Relatórios</a></li>

        <!-- Preços -->
        <li class="nav-item"><a class="nav-link" href="/?route=prices">Preços</a></li>
      </ul>

      <span class="navbar-text text-light me-3">
        <?=htmlspecialchars($_SESSION['name'] ?? '')?> (<?=htmlspecialchars($_SESSION['role'] ?? '')?>)
      </span>
      <a class="btn btn-outline-light btn-sm" href="/logout.php">Sair</a>
    </div>
  </div>
</nav>
