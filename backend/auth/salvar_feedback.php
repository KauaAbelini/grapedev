<?php

include("conexao.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: home.html");
    exit();
}

$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$nota = intval($_POST["nota"] ?? 0);
$comentario = trim($_POST["comentario"] ?? "");

// Validações
if ($nome === "" || $comentario === "") {
    header("Location: home.html?feedback=erro");
    exit();
}

if ($nota < 1 || $nota > 5) {
    header("Location: home.html?feedback=erro");
    exit();
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: home.html?feedback=erro");
    exit();
}

$sql = "INSERT INTO feedbacks 
        (nome, email, nota, comentario) 
        VALUES (?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param(
    "ssis",
    $nome,
    $email,
    $nota,
    $comentario
);

if ($stmt->execute()) {

    header("Location: home.html?feedback=sucesso");
    exit();

} else {

    die("Erro ao salvar feedback: " . $stmt->error);

}

$stmt->close();
$conexao->close();
?>