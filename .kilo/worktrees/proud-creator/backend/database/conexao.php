<?php

$host = "10.140.169.14";
$usuario = "root";
$senha = "123456";
$banco = "grapedev";
$porta = 3306;

$conexao = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco,
    $porta
);

if ($conexao->connect_error) {
    die("Erro de conexão com o banco de dados.");
}

$conexao->set_charset("utf8mb4");

?>