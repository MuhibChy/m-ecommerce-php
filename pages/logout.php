<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$auth->logout();

// Redirect to home page
redirect('/index.php');
?>
