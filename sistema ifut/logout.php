<?php
require_once 'includes/session.php';

// Destruir a sessão
session_start();
session_destroy();

// Limpar cookies se existirem
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirecionar para a página inicial (index.php) ou login
header("Location: index.php");
exit;
?>
