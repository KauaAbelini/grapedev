<?php

/*
=========================================================
NEX JARVIS
CONFIGURAÇÃO PRINCIPAL
=========================================================
*/

define("DB_HOST", "localhost");
define("DB_NAME", "grapedev");
define("DB_USER", "root");
define("DB_PASS", "");

define(
    "OPENAI_API_KEY",
    "COLOQUE_SUA_CHAVE_AQUI"
);

define(
    "OPENAI_MODEL",
    "gpt-5.6-luna"
);


/*
=========================================================
CONEXÃO MYSQL
=========================================================
*/

function conectarBanco()
{
    try {

        $pdo = new PDO(
            "mysql:host=" . DB_HOST .
            ";dbname=" . DB_NAME .
            ";charset=utf8mb4",

            DB_USER,
            DB_PASS,

            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC
            ]
        );

        return $pdo;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Erro ao conectar ao banco."
        ]);

        exit;
    }
}