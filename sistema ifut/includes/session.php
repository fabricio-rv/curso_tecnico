<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['usuario']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function getUser() {
    return $_SESSION['usuario'] ?? null;
}

function setUser($user) {
    $_SESSION['usuario'] = $user;
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
