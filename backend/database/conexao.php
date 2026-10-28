<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "10.140.169.12";
$usuario = "root";
$senha = "123456";
$banco = "grapedev";
$porta = 3306;

try {
    $conexao = new mysqli(
        $host,
        $usuario,
        $senha,
        $banco,
        $porta
    );

    $conexao->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('Falha na conexão com o banco de dados.');

    http_response_code(500);
    exit('Não foi possível conectar ao sistema.');
}