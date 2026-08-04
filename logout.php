<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;

Auth::logout();

header('Location: /login.php?message=logged_out');
exit;
