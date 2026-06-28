<?php
declare(strict_types=1);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/session-ping.php');
$basePath = rtrim(dirname($scriptName), '/');
$basePath = ($basePath === '.' || $basePath === '/') ? '' : $basePath;

$_SERVER['REQUEST_URI'] = $basePath . '/session-ping';
$_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';

require __DIR__ . '/../../index.php';
