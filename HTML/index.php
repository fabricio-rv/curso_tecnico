<?php
session_start();
$usuarioLogado = isset($_SESSION["usuario"]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
    <link rel="stylesheet" href="../CSS/index.css">
</head>
<body>
<?php include("navbar.php"); ?>

<!-- Carrossel -->
<div id="demo" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
      <button type="button" data-bs-target="#demo" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#demo" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#demo" data-bs-slide-to="2"></button>
  </div>
  <div class="carousel-inner">
      <div class="carousel-item active">
          <img src="../Imagem/logo_criadores.png" alt="Imagem 01" class="d-block w-100 tamanho_imagem_carrossel">
      </div>
      <div class="carousel-item">
          <img src="../Imagem/logo_ifut.png" alt="Imagem 02" class="d-block w-100 tamanho_imagem_carrossel">
      </div>
      <div class="carousel-item">
          <img src="../Imagem/logo_geral.png" alt="Imagem 03" class="d-block w-100 tamanho_imagem_carrossel">
      </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#demo" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#demo" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
  </button>
</div>
</body>
</html>