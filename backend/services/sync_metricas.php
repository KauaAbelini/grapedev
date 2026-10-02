<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'erro' => 'Não autenticado'
    ]);

    exit;
}

$usuarioId =
    (int)$_SESSION['usuario_id'];

$conexao->query("
    CREATE TABLE IF NOT EXISTS aquaflow_metricas (

        usuario_id INT PRIMARY KEY,

        perda_total_litros
            DECIMAL(14,2)
            NOT NULL
            DEFAULT 0,

        consumo_base_litros
            DECIMAL(14,2)
            NOT NULL
            DEFAULT 12840,

        atualizado_em DATETIME
            NOT NULL
            DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP

    ) ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
");

$stmt = $conexao->prepare("
    INSERT IGNORE INTO aquaflow_metricas
    (
        usuario_id,
        perda_total_litros,
        consumo_base_litros
    )
    VALUES (?, 0, 12840)
");

$stmt->bind_param(
    'i',
    $usuarioId
);

$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $conexao->prepare("
        SELECT
            perda_total_litros,
            consumo_base_litros,
            atualizado_em
        FROM aquaflow_metricas
        WHERE usuario_id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        'i',
        $usuarioId
    );

    $stmt->execute();

    $dados =
        $stmt
            ->get_result()
            ->fetch_assoc();

    echo json_encode([

        'ok' => true,

        'perda_total_litros' =>
            (float)$dados['perda_total_litros'],

        'consumo_base_litros' =>
            (float)$dados['consumo_base_litros'],

        'atualizado_em' =>
            $dados['atualizado_em']

    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dados = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($dados)) {
    $dados = $_POST;
}

$perda =
    (float)(
        $dados['perda_total_litros']
        ?? 0
    );

if ($perda < 0) {
    $perda = 0;
}

$stmt = $conexao->prepare("
    UPDATE aquaflow_metricas
    SET
        perda_total_litros = ?,
        atualizado_em = CURRENT_TIMESTAMP
    WHERE usuario_id = ?
");

$stmt->bind_param(
    'di',
    $perda,
    $usuarioId
);

$stmt->execute();

echo json_encode([
    'ok' => true,
    'perda_total_litros' => $perda
], JSON_UNESCAPED_UNICODE);

?>