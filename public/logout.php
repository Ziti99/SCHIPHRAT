<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Auth\Auth;

$auth = new Auth();
$auth->logout();

// Redirection vers la page d'accueil
header('Location: /');
exit; 