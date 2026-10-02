<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode([
        "logado" => false
    ]);
    exit;
}

echo json_encode([
    "logado" => true,
    "id" => (int) $_SESSION["usuario_id"],
    "nome" => $_SESSION["usuario_nome"] ?? "",
    "email" => $_SESSION["usuario_email"] ?? "",
    "tipo_usuario" => $_SESSION["usuario_tipo"] ?? ""
], JSON_UNESCAPED_UNICODE);