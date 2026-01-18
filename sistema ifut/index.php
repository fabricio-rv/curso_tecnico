<?php
require_once 'includes/session.php';

// Se estiver logado, redirecionar para tela inicial
if (isLoggedIn()) {
    header("Location: tela_inicial.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT - Organize suas Partidas de Futebol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="hero-title neon-text fade-in">
                            Organize suas<br>
                            Partidas de<br>
                            <span style="color: #00ff00;">Futebol Society</span>
                        </h1>
                        <p class="lead mb-4 fade-in">
                            Encontre jogadores, crie partidas e gerencie tudo em um só lugar. 
                            O IFUT é a plataforma definitiva para amantes do futebol society.
                        </p>
                        <div class="d-flex gap-3 flex-wrap fade-in">
                            <a href="cadastro.php" class="btn btn-success btn-lg neon-btn">
                                <i class="bi bi-person-plus"></i> Cadastre-se Grátis
                            </a>
                            <a href="tutorial.php" class="btn btn-outline-success btn-lg">
                                <i class="bi bi-play-circle"></i> Como Funciona
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <div class="hero-image">
                            <i class="bi bi-people-fill display-1 text-success pulse" style="font-size: 8rem;"></i>
                            <div class="floating-icons">
                                <i class="bi bi-geo-alt text-success"></i>
                                <i class="bi bi-calendar-check text-success"></i>
                                <i class="bi bi-clock text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center neon-text mb-5">Por que escolher o IFUT?</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card neon-card h-100 text-center fade-in">
                            <div class="card-body">
                                <i class="bi bi-plus-circle display-4 text-success mb-3"></i>
                                <h5 class="neon-text-white">Criar Partidas</h5>
                                <p class="text-muted">Organize jogos facilmente definindo local, data, horário e posições disponíveis.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card neon-card h-100 text-center fade-in">
                            <div class="card-body">
                                <i class="bi bi-search display-4 text-success mb-3"></i>
                                <h5 class="neon-text-white">Encontrar Jogos</h5>
                                <p class="text-muted">Busque partidas próximas a você e marque presença na posição que preferir.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card neon-card h-100 text-center fade-in">
                            <div class="card-body">
                                <i class="bi bi-people display-4 text-success mb-3"></i>
                                <h5 class="neon-text-white">Gerenciar Tudo</h5>
                                <p class="text-muted">Controle suas partidas criadas e acompanhe onde você marcou presença.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-5">
            <div class="container text-center">
                <h2 class="neon-text mb-4">Pronto para começar?</h2>
                <p class="lead mb-4">Junte-se a centenas de jogadores que já usam o IFUT!</p>
                <a href="cadastro.php" class="btn btn-success btn-lg neon-btn">
                    <i class="bi bi-rocket"></i> Começar Agora
                </a>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    
    <style>
        .hero-image {
            position: relative;
            padding: 50px;
        }
        
        .floating-icons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        
        .floating-icons i {
            position: absolute;
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .floating-icons i:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-icons i:nth-child(2) {
            top: 60%;
            right: 20%;
            animation-delay: 1s;
        }
        
        .floating-icons i:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 2s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</body>
</html>
