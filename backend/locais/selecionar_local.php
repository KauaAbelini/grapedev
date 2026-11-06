<?php
session_start();

require_once __DIR__ . '/../database/conexao.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: /grapedev/backend/auth/login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$localId = (int) ($_GET["id"] ?? 0);

$destinoSolicitado = $_GET["destino"] ?? "../dashboard/dashboard.php";

// Destinos permitidos e seus caminhos absolutos.
$destinosPermitidos = [
    "../dashboard/dashboard.php" =>
        "/grapedev/backend/dashboard/dashboard.php",

    "../../public/3d/3d.php" =>
        "/grapedev/public/3d/3d.php",

    "../../public/pages/cidade_3d.html" =>
        "/grapedev/public/pages/cidade_3d.html",

    "locais.php" =>
        "/grapedev/backend/locais/locais.php"
];

$destino = $destinosPermitidos[$destinoSolicitado]
    ?? "/grapedev/backend/dashboard/dashboard.php";

// Verifica se o local pertence ao usuário e está ativo.
$stmt = $conexao->prepare(
    "SELECT id, nome, tipo_imovel
     FROM locais
     WHERE id = ? AND usuario_id = ? AND ativo = 1
     LIMIT 1"
);

if (!$stmt) {
    error_log("Erro ao preparar seleção de local: " . $conexao->error);
    header("Location: /grapedev/backend/locais/locais.php?erro=selecao");
    exit;
}

$stmt->bind_param("ii", $localId, $usuarioId);
$stmt->execute();

$resultado = $stmt->get_result();
$local = $resultado->fetch_assoc();

$stmt->close();

if (!$local) {
    header("Location: /grapedev/backend/locais/locais.php?erro=local_invalido");
    exit;
}

// Salva o local selecionado na sessão.
$_SESSION["local_id"] = (int) $local["id"];
$_SESSION["local_nome"] = $local["nome"];
$_SESSION["local_tipo_imovel"] = $local["tipo_imovel"];

// Redireciona para o destino permitido.
header("Location: " . $destino);
exit;
