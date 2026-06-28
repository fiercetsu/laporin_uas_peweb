<?php
declare(strict_types=1);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/petugas-tugas.php');
$basePath = rtrim(dirname($scriptName), '/');
$basePath = ($basePath === '.' || $basePath === '/') ? '' : $basePath;

$_SERVER['REQUEST_URI'] = $basePath . '/petugas-tugas';
$_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';

require __DIR__ . '/../../index.php';
