<?php
session_start();
require __DIR__.'/app/db.php';
require __DIR__.'/app/auth.php';
require __DIR__.'/app/helpers.php';

// Protege rotas
if (!isset($_SESSION['uid']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: /login.php');
    exit;
}

// Define rota
$route = $_GET['route'] ?? 'dashboard';

// Inclui topo e header (navbar)
include __DIR__.'/views/partials/header.php';

// Controle de páginas
switch ($route) {

  // Dashboard inicial
  case 'dashboard':
    include __DIR__.'/pages/dashboard.php';
    break;

  // --- Clientes ---
  case 'customers':
    include __DIR__.'/pages/customers_list.php';
    break;

  case 'customers-new':
    include __DIR__.'/pages/customers_new.php';
    break;

  // --- Orçamentos e Ordens de Serviço ---
  case 'quotes-new':
    include __DIR__.'/pages/quotes_new.php';
    break;

  case 'quotes-view':
    include __DIR__.'/pages/quotes_view.php';
    break;

  case 'os-view':
    include __DIR__.'/pages/os_view.php';
    break;

  // --- Etiquetas ---
  case 'labels-print':
    include __DIR__.'/pages/labels_print.php';
    break;

  // --- Serviços (novo modelo único) ---
  case 'services':
    include __DIR__.'/pages/services_list.php';
    break;

  case 'services-new':
    include __DIR__.'/pages/services_new.php';
    break;

  // --- Tapetes (com estágios) ---
  case 'tapetes':
    include __DIR__.'/pages/tapetes.php';
    break;

  // --- Relatórios ---
  case 'reports':
    include __DIR__.'/pages/reports.php';
    break;

  // --- Preços (edição em massa dos dois tipos) ---
  case 'prices':
    include __DIR__.'/pages/prices.php';
    break;

  // Página padrão (erro)
  default:
    echo "<div class='container py-4'><h3>Página não encontrada</h3></div>";
    break;
}

// Rodapé opcional
include __DIR__.'/views/partials/footer.php';
