<?php
session_start();
include("conexao.php");

if (isset($_POST["id_marcacao"])) {
    $id_marcacao = $_POST["id_marcacao"];

    // Recupera dados da marcação antes de excluir
    $stmtInfo = $mysqli->prepare("SELECT id_partida, posicao FROM marcacoes WHERE id_marcacao = ?");
    $stmtInfo->bind_param("i", $id_marcacao);
    $stmtInfo->execute();
    $stmtInfo->bind_result($id_partida, $posicao);
    $stmtInfo->fetch();
    $stmtInfo->close();

    // Devolve posição à lista de posições restantes
    $atual = $mysqli->query("SELECT posicoes_restantes FROM partidas WHERE id_partida = $id_partida")->fetch_assoc();
    $lista = explode(",", $atual["posicoes_restantes"]);
    $lista[] = $posicao;
    $atualizada = implode(",", array_unique($lista));
    $mysqli->query("UPDATE partidas SET posicoes_restantes = '$atualizada' WHERE id_partida = $id_partida");

    // Remove marcação
    $stmt = $mysqli->prepare("DELETE FROM marcacoes WHERE id_marcacao = ?");
    $stmt->bind_param("i", $id_marcacao);
    $stmt->execute();

    http_response_code(200); // sucesso
} else {
    http_response_code(400); // erro
}
?>