<?php
$usuarioLogado = isset($_SESSION["usuario"]);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="../Imagem/logo_ifut.png" alt="Logo IFUT" class="logo_menu">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="tutorial.php"><span>Tutorial</span></a></li>
                <?php if (!$usuarioLogado): ?>
                    <li class="nav-item"><a class="nav-link" href="cadastro.php"><span>Cadastro</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php"><span>Login</span></a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="criar_partida.php"><span>Criar Partida</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="encontrar_partida.php"><span>Encontrar Partida</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="partidas_criadas.php"><span>Partidas Criadas</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="partidas_marcadas.php"><span>Partidas Marcadas</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="gerenciar_perfil.php"><span>Gerenciar Perfil</span></a></li>
                    <li class="nav-item">
                      <a class="nav-link text-danger btn-modal" href="#" data-texto="Deseja realmente sair da conta?" data-modal="logout.php"><span>Sair</span></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div id="modal-confirmacao" class="modal-custom" style="display: none;">
  <div class="modal-content-custom">
    <h5 id="texto-modal"></h5>
    <a id="link-confirmar" href="#" class="btn btn-success">Sim</a>
    <button onclick="fecharModal()" class="btn btn-danger mt-2">Voltar</button>
  </div>
</div>

<script>
function abrirModal(texto, link) {
  document.getElementById("texto-modal").textContent = texto;
  document.getElementById("link-confirmar").href = link;
  document.getElementById("modal-confirmacao").style.display = "flex";
}
function fecharModal() {
  document.getElementById("modal-confirmacao").style.display = "none";
}
document.querySelectorAll('.btn-modal').forEach(function(botao) {
  botao.addEventListener("click", function (e) {
    e.preventDefault();
    const texto = botao.getAttribute("data-texto") || "Tem certeza?";
    const destino = botao.getAttribute("data-modal") || "#";
    abrirModal(texto, destino);
  });
});
</script>