<?php
function base_url(){ $cfg = require __DIR__.'/config/env.php'; return rtrim($cfg['app']['base_url'],'/'); }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($path){ header('Location: '.$path); exit; }

function area_m2($tipo,$larg_cm,$comp_cm,$diam_cm){
  if($tipo==='redondo'){
    $r = ($diam_cm/100)/2; return M_PI*$r*$r;
  }
  $larg = $larg_cm/100; $comp = $comp_cm/100; return $larg*$comp;
}
