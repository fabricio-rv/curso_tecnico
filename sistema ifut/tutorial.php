<?php
require_once 'includes/session.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Como Funciona - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        body {
            background-color: #000;
            color: #fff;
            overflow-x: hidden;
        }

        .hero-section {
            background: linear-gradient(45deg, #000000, #0a0a0a, #141414);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../IMG/fundo_estadio.jpg') center center/cover no-repeat;
            opacity: 0.3;
            z-index: 0;
        }

        .floating-lightbulb {
            position: absolute;
            top: 10%;
            right: 10%;
            width: 100px;
            height: 100px;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            animation: floating 3s infinite alternate, fadeIn 1s ease-out;
            box-shadow: 0 0 25px rgba(0, 255, 0, 0.6);
            z-index: 1;
            border: 3px solid rgba(0, 255, 0, 0.5);
            overflow: hidden;
        }

        @keyframes floating {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-20px);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
            margin-bottom: 20px;
            animation: neonGlow 1.5s ease-in-out infinite alternate;
        }

        @keyframes neonGlow {
            from {
                text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
            }

            to {
                text-shadow: 0 0 20px rgba(0, 255, 0, 0.9);
            }
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #eee;
            margin-bottom: 30px;
        }

        .tutorial-steps {
            padding: 50px 0;
        }

        .step-card {
            background: linear-gradient(45deg, #1e1e1e, #282828);
            border: 2px solid #00ff00; /* Bordinha verde adicionada */
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            overflow: hidden;
            position: relative;
            height: 200px; /* Altura fixa para todas as caixas */
            display: flex;
            flex-direction: column;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.3);
        }

        .step-card .card-body {
            padding: 20px;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .step-card .d-flex {
            height: 100%;
            align-items: center;
        }

        .step-icon {
            font-size: 2.5rem;
            color: #00ff00;
            margin-right: 20px;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
            animation: pulse 2s infinite alternate;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.1);
            }
        }

        .step-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 15px;
        }

        .step-description {
            color: #ccc;
            font-size: 1.1rem;
        }

        .recursos-principais {
            background: linear-gradient(45deg, #0a0a0a, #141414);
            padding: 50px 0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .recurso-card {
            background: linear-gradient(45deg, #1e1e1e, #282828);
            border: 2px solid #00ff00; /* Bordinha verde adicionada */
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .recurso-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.3);
        }

        .recurso-icon {
            font-size: 3rem;
            color: #00ff00;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
            animation: floating 2.5s infinite alternate;
        }

        .recurso-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 15px;
        }

        .recurso-description {
            color: #ccc;
            font-size: 1.1rem;
        }

        .cta-section {
            padding: 80px 0;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 30px;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
        }

        .cta-button {
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .cta-button-primary {
            background-color: #00ff00;
            color: #000;
            box-shadow: 0 4px 15px rgba(0, 255, 0, 0.5);
        }

        .cta-button-primary:hover {
            background-color: #00cc00;
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.7);
        }

        .cta-button-secondary {
            background-color: #333;
            color: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .cta-button-secondary:hover {
            background-color: #555;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7);
        }

        .staggered-entrance {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .staggered-entrance.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .floating-lightbulb {
                width: 80px;
                height: 80px;
                top: 5%;
                right: 5%;
            }

            .step-icon {
                font-size: 2rem;
                margin-right: 10px;
            }

            .step-title {
                font-size: 1.5rem;
            }

            .step-description {
                font-size: 1rem;
            }

            .recurso-icon {
                font-size: 2.5rem;
            }

            .recurso-title {
                font-size: 1.3rem;
            }

            .recurso-description {
                font-size: 1rem;
            }

            .cta-title {
                font-size: 2rem;
            }

            .cta-button {
                font-size: 1rem;
                padding: 12px 25px;
            }

            .step-card {
                height: 180px; /* Altura menor para mobile */
            }
        }
    </style>
</head>

<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="floating-lightbulb">
                <img src="../IMG/logo_ifut.png" alt="Logo IFUT" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 text-center hero-content">
                        <h1 class="hero-title">
                            <i class="bi bi-play-circle"></i> Como Funciona o IFUT
                        </h1>
                        <p class="hero-subtitle">Aprenda a usar nossa plataforma em poucos passos e aproveite ao máximo
                            suas partidas!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tutorial Steps -->
        <section class="tutorial-steps">
            <div class="container">
                <div class="row">
                    <!-- Passo 1 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-plus step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Cadastre-se</h4>
                                        <p class="step-description">Crie sua conta gratuitamente informando seus dados
                                            básicos e suas posições preferidas no campo.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 2 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-search step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Encontre Partidas</h4>
                                        <p class="step-description">Explore nossa lista de partidas abertas e solicite participação nas que mais combinam com seu perfil e localização.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 3 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-plus-circle step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Crie Suas Partidas</h4>
                                        <p class="step-description">Organize suas próprias partidas definindo local,
                                            data e horário, e aprove os jogadores que desejam participar.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 4 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Escolha sua Posição</h4>
                                        <p class="step-description">Selecione a posição que deseja jogar no campo de
                                            futebol society visualizado na plataforma.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 5 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar-check step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Gerencie suas Partidas</h4>
                                        <p class="step-description">Acompanhe suas partidas marcadas e criadas, aprove solicitações de participação e gerencie os jogadores.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 6 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-star step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Avalie e Seja Avaliado</h4>
                                        <p class="step-description">Após as partidas, avalie os jogadores ou o criador e construa sua reputação na plataforma.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passo 7 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-chat-dots step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Comunique-se</h4>
                                        <p class="step-description">Use o sistema de chat para conversar com outros jogadores e organizar melhor suas partidas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passo 8 -->
                    <div class="col-md-6 staggered-entrance">
                        <div class="step-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-trophy step-icon"></i>
                                    <div>
                                        <h4 class="step-title">Jogue e Divirta-se!</h4>
                                        <p class="step-description">Compareça no local e horário marcado e aproveite sua
                                            partida de futebol society!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recursos Principais -->
        <section class="recursos-principais">
            <div class="container">
                <div class="row">
                    <div class="col-md-3">
                        <div class="recurso-card">
                            <i class="bi bi-geo-alt recurso-icon"></i>
                            <h4 class="recurso-title">Localização</h4>
                            <p class="recurso-description">Encontre partidas perto de você com facilidade.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="recurso-card">
                            <i class="bi bi-clock recurso-icon"></i>
                            <h4 class="recurso-title">Horários Flexíveis</h4>
                            <p class="recurso-description">Partidas que se encaixam na sua agenda.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="recurso-card">
                            <i class="bi bi-star recurso-icon"></i>
                            <h4 class="recurso-title">Sistema de Avaliações</h4>
                            <p class="recurso-description">Avalie jogadores e criadores após as partidas.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="recurso-card">
                            <i class="bi bi-shield-check recurso-icon"></i>
                            <h4 class="recurso-title">Aprovação de Jogadores</h4>
                            <p class="recurso-description">Controle quem participa das suas partidas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action - Mostrar apenas para usuários NÃO logados -->
        <?php if (!isLoggedIn()) { ?>
        <section class="cta-section">
            <div class="container">
                <h2 class="cta-title">Pronto para começar?</h2>
                <a href="cadastro.php" class="btn cta-button cta-button-primary">
                    <i class="bi bi-rocket"></i> Cadastre-se Agora
                </a>
                <a href="login.php" class="btn cta-button cta-button-secondary">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </section>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const elements = document.querySelectorAll('.staggered-entrance');
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('active');
                }, 150 * index);
            });
        });
    </script>
</body>

</html>
