<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if (isset($_SESSION["usuario_id"])) {

    echo json_encode([
        "logado" => true,
        "usuario" => [
            "id" => $_SESSION["usuario_id"],
            "nome" => $_SESSION["usuario_nome"],
            "email" => $_SESSION["usuario_email"],
            "cidade" => $_SESSION["usuario_cidade"],
            "telefone" => $_SESSION["usuario_telefone"]
        ]
    ]);

} else {

    echo json_encode([
        "logado" => false
    ]);
} 