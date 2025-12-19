<?php
session_start();
require __DIR__.'/app/db.php';
require __DIR__.'/app/auth.php';
require __DIR__.'/app/helpers.php';

$route = $_GET['route'] ?? 'dashboard';

// Rotas públicas (não exigem login)
$PUBLIC_ROUTES = ['wo-view'];

// Gate de login: só bloqueia se NÃO for rota pública
if (!in_array($route, $PUBLIC_ROUTES, true) && empty($_SESSION['uid']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
  header('Location: /login.php'); exit;
}

include __DIR__.'/views/partials/header.php';

switch($route){
  case 'dashboard':         include __DIR__.'/pages/dashboard.php'; break;

  case 'customers':         include __DIR__.'/pages/customers_list.php'; break;
  case 'customers-new':     include __DIR__.'/pages/customers_new.php'; break;
  case 'customers-edit':    include __DIR__.'/pages/customers_edit.php'; break;
  case 'customers-delete':  include __DIR__.'/pages/customers_delete.php'; break;

  case 'services':          include __DIR__.'/pages/services.php'; break;

  case 'wo-new':            include __DIR__.'/pages/wo_new.php'; break;
  case 'wo-view':           include __DIR__.'/pages/wo_view.php'; break;   // ← PÚBLICA
  case 'wo-delete':         include __DIR__.'/pages/wo_delete.php'; break;

  case 'labels-print':      include __DIR__.'/pages/labels_print.php'; break;
  case 'status-update':     include __DIR__.'/pages/status_update.php'; break;
  case 'reports':           include __DIR__.'/pages/reports.php'; break;

  // (opcional) rota de ajuste de senha interna
  case 'adjust-password':   include __DIR__.'/pages/adjust_password.php'; break;

  default:
    echo "<div class='container py-4'><h3>Página não encontrada</h3></div>";
}

include __DIR__.'/views/partials/footer.php';
