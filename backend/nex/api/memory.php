<?php

require_once "config.php";

header(
    "Content-Type: application/json; charset=UTF-8"
);

$pdo = conectarBanco();

$metodo = $_SERVER["REQUEST_METHOD"];


/*
=========================================================
SALVAR MEMÓRIA
=========================================================
*/

if ($metodo === "POST") {

    $dados = json_decode(
        file_get_contents("php://input"),
        true
    );

    $chave = trim(
        $dados["chave"] ?? ""
    );

    $valor = trim(
        $dados["valor"] ?? ""
    );


    if ($chave === "" || $valor === "") {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Chave ou valor não informado."
        ]);

        exit;
    }


    $sql = "
        INSERT INTO nex_memoria
        (
            chave,
            valor
        )

        VALUES
        (
            :chave,
            :valor
        )

        ON DUPLICATE KEY UPDATE
            valor = VALUES(valor)
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":chave" => $chave,
        ":valor" => $valor
    ]);


    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Memória salva."
    ]);

    exit;
}


/*
=========================================================
LISTAR MEMÓRIA
=========================================================
*/

if ($metodo === "GET") {

    $stmt = $pdo->query("
        SELECT
            chave,
            valor
        FROM nex_memoria
        ORDER BY id DESC
    ");

    echo json_encode([
        "sucesso" => true,
        "memorias" => $stmt->fetchAll()
    ]);

    exit;
}


http_response_code(405);

echo json_encode([
    "sucesso" => false,
    "erro" => "Método não permitido."
]);