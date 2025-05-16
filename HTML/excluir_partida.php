<?php
include("conexao.php");

if (!isset($_GET["id_partida"])) {
    echo "ID da partida não informado!";
    exit;
}

$id_partida = intval($_GET["id_partida"]);

// Exclui marcações antes da partida
$mysqli->query("DELETE FROM marcacoes WHERE id_partida = $id_partida");

// Exclui a partida
$mysqli->query("DELETE FROM partidas WHERE id_partida = $id_partida");

// Retorna resposta para JS
echo "ok";
?>