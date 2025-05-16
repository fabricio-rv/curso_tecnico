<?php
session_start();
$usuarioLogado = isset($_SESSION["usuario"]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT - Tutorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
    <link rel="stylesheet" href="../CSS/tutorial.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="text-center text-success">📖 Como Usar o IFUT?</h2>
    <p class="text-center text-success">Aprenda a usar o IFUT para encontrar e organizar partidas de Futebol Society! ⚽</p>

    <!-- Fundadores -->
    <div class="text-center mt-3">
        <img src="../Imagem/logo_criadores.png" class="img-fluid rounded imagem-fundadores" alt="Fundadores do IFUT">
        <p class="fundadores-texto"><strong>Os Fundadores do IFUT</strong></p>
    </div>

    <!-- Objetivo -->
    <div class="card mt-4 bg-dark text-light p-3">
        <h4 class="text-success">🎯 Objetivo do IFUT</h4>
        <p>O IFUT foi criado para ajudar apaixonados por Futebol Society a encontrar partidas disponíveis ou organizar suas próprias partidas, de maneira rápida e gratuita.</p>
    </div>

    <!-- Funcionalidades -->
    <div class="card mt-4 bg-dark text-light p-3">
        <h4 class="text-success">⚡ Funcionalidades do IFUT</h4>
        <ul>
            <li><strong>📌 Tela Principal:</strong> Acesso rápido para criar, encontrar e gerenciar partidas.</li>
            <li><strong>⚽ Criar Partida:</strong> Configure local, data, horário e posições disponíveis.</li>
            <li><strong>🔎 Encontrar Partida:</strong> Busque jogos disponíveis com base na posição desejada e turno.</li>
            <li><strong>📅 Partidas Criadas:</strong> Gerencie partidas que você organizou.</li>
            <li><strong>✅ Partidas Marcadas:</strong> Veja em quais partidas você se inscreveu.</li>
        </ul>
    </div>

    <!-- Como Funciona -->
    <div class="card mt-4 bg-dark text-light p-3">
        <h4 class="text-success">🔄 Como Funciona?</h4>
        <p>Simples! Escolha entre <strong>Criar uma partida</strong> ou <strong>Encontrar uma partida</strong>. Veja as opções disponíveis, marque presença e aproveite o jogo! 🎉</p>
    </div>

    <!-- Botão de Prosseguir -->
    <div class="text-center mt-4">
        <a href="login.php" class="btn btn-success btn-lg">🚀 Prosseguir</a>
    </div>
</div>
</body>
</html>