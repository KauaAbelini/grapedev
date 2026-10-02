<?php
session_start();

include("conexao.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int)$_SESSION["usuario_id"];
$localId = (int)($_GET["id"] ?? 0);
$destino = $_GET["destino"] ?? "dashboard.php";

$destinosPermitidos = ["dashboard.php", "3d.php", "locais.php"];
if (!in_array($destino, $destinosPermitidos, true)) {
    $destino = "dashboard.php";
}

$stmt = $conexao->prepare("SELECT id,nome,tipo_imovel FROM locais WHERE id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1");

if ($stmt) {
    $stmt->bind_param("ii", $localId, $usuarioId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $local = $resultado->fetch_assoc();
    $stmt->close();

    if ($local) {
        $_SESSION["local_id"] = (int)$local["id"];
        $_SESSION["local_nome"] = $local["nome"];
        $_SESSION["local_tipo_imovel"] = $local["tipo_imovel"];
    }
}

header("Location: " . $destino);
exit;
