<?php
$cfg = require __DIR__.'/config/env.php';
date_default_timezone_set($cfg['app']['timezone'] ?? 'America/Sao_Paulo');
$dsn = "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";
$pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);
