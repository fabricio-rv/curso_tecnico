<div class="sidebar">
    <div class="sidebar-header">
        <!-- Ícone de 3 linhas quando retraído -->
        <div class="sidebar-hamburger">
            <div class="hamburger-line"></div>
            <div class="hamburger-line"></div>
            <div class="hamburger-line"></div>
        </div>
        
        <!-- Texto IFUT quando expandido -->
        <div class="sidebar-logo-expanded">
            <span class="sidebar-logo-text">IFUT</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if (isLoggedIn()): ?>
            <!-- Menu para usuários logados -->
            <a href="tela_inicial.php" class="sidebar-item">
                <i class="bi bi-house-door"></i>
                <span class="sidebar-text">Início</span>
            </a>
            <a href="tutorial.php" class="sidebar-item">
                <i class="bi bi-question-circle"></i>
                <span class="sidebar-text">Tutorial</span>
            </a>
            <a href="criar_partida.php" class="sidebar-item">
                <i class="bi bi-plus-circle"></i>
                <span class="sidebar-text">Criar Partida</span>
            </a>
            <a href="encontrar_partida.php" class="sidebar-item">
                <i class="bi bi-search"></i>
                <span class="sidebar-text">Encontrar Partidas</span>
            </a>
            <a href="partidas_marcadas.php" class="sidebar-item">
                <i class="bi bi-calendar-check"></i>
                <span class="sidebar-text">Partidas Marcadas</span>
            </a>
            <a href="partidas_criadas.php" class="sidebar-item">
                <i class="bi bi-list-task"></i>
                <span class="sidebar-text">Partidas Criadas</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="perfil.php" class="sidebar-item">
                <i class="bi bi-person-circle"></i>
                <span class="sidebar-text">Meu Perfil</span>
            </a>
            <a href="#" class="sidebar-item" onclick="abrirModalLogout(event)">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sidebar-text">Sair</span>
            </a>
        <?php else: ?>
            <!-- Menu para visitantes -->
            <a href="index.php" class="sidebar-item">
                <i class="bi bi-house-door"></i>
                <span class="sidebar-text">Início</span>
            </a>
            <a href="login.php" class="sidebar-item">
                <i class="bi bi-box-arrow-in-right"></i>
                <span class="sidebar-text">Login</span>
            </a>
            <a href="cadastro.php" class="sidebar-item">
                <i class="bi bi-person-plus"></i>
                <span class="sidebar-text">Cadastro</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="tutorial.php" class="sidebar-item">
                <i class="bi bi-question-circle"></i>
                <span class="sidebar-text">Tutorial</span>
            </a>
            <a href="esqueceu_sua_senha.php" class="sidebar-item">
                <i class="bi bi-key"></i>
                <span class="sidebar-text">Recuperar Senha</span>
            </a>
        <?php endif; ?>
    </nav>
</div>

<!-- Modal de Confirmação de Logout (só aparece se logado) -->
<?php if (isLoggedIn()): ?>
<div class="modal fade modal-logout" id="modalLogout" tabindex="-1" data-bs-backdrop="static" style="background-color: rgba(0, 0, 0, 0.8); backdrop-filter: blur(2px);">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content" style="background: #000000; border: 2px solid #00ff00; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);">
            <div class="modal-header" style="border-bottom: 1px solid #00ff00; background: #000000; padding: 15px 20px;">
                <h5 class="modal-title" style="color: #00ff00; font-weight: bold; text-shadow: 0 0 10px rgba(0, 255, 0, 0.5); font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar Saída
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 30px; text-align: center; color: #ffffff; background: #000000;">
                <div style="font-size: 4em; color: #00ff00; margin-bottom: 20px; text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <p style="font-size: 1.1em; margin-bottom: 25px;"><strong>Tem certeza que deseja sair da sua conta e voltar ao menu principal?</strong></p>
                
                <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: left; color: #00ff00;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-info-circle" style="font-size: 1.2em;"></i>
                        <span>Você precisará fazer login novamente para acessar o sistema.</span>
                    </div>
                </div>
                
                <div style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107; border-radius: 8px; padding: 15px; margin: 15px 0;">
                    <i class="bi bi-info-circle"></i>
                    <strong>Esta ação encerrará sua sessão atual!</strong>
                </div>
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal" style="background: #dc3545; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; transition: all 0.3s; min-width: 120px;">
                        <i class="bi bi-x-circle"></i> Voltar
                    </button>
                    <button type="button" onclick="confirmarLogout()" style="background: #00ff00; color: #000; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; transition: all 0.3s; min-width: 120px;">
                        <i class="bi bi-check-circle"></i> Sim, Sair
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalLogout(event) {
    event.preventDefault();
    new bootstrap.Modal(document.getElementById('modalLogout')).show();
}

function confirmarLogout() {
    // Fechar o modal
    bootstrap.Modal.getInstance(document.getElementById('modalLogout')).hide();
    
    // Redirecionar para logout
    window.location.href = 'logout.php';
}
</script>
<?php endif; ?>

<style>
/* Melhorar centralização dos ícones no sidebar */
.sidebar-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin: 4px 8px;
    position: relative;
}

.sidebar-item i {
    font-size: 1.2rem;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
    text-align: center;
}

/* Quando sidebar está retraído, centralizar melhor os ícones */
.sidebar.collapsed .sidebar-item {
    justify-content: center;
    padding: 12px;
}

.sidebar.collapsed .sidebar-item i {
    margin-right: 0;
    width: 28px;
    height: 28px;
    font-size: 1.3rem;
}

/* Hover effects */
.sidebar-item:hover {
    background-color: rgba(0, 255, 0, 0.1);
    color: #00ff00;
    transform: translateX(4px);
}

.sidebar-item:hover i {
    color: #00ff00;
    text-shadow: 0 0 8px rgba(0, 255, 0, 0.6);
}

/* Active state */
.sidebar-item.active {
    background-color: rgba(0, 255, 0, 0.15);
    color: #00ff00;
    border-left: 3px solid #00ff00;
}

.sidebar-item.active i {
    color: #00ff00;
    text-shadow: 0 0 8px rgba(0, 255, 0, 0.6);
}

/* Divider */
.sidebar-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 255, 0, 0.3), transparent);
    margin: 8px 16px;
}

/* Responsividade */
@media (max-width: 768px) {
    .sidebar-item {
        padding: 14px 16px;
    }
    
    .sidebar-item i {
        width: 26px;
        height: 26px;
        font-size: 1.25rem;
    }
}
</style>
