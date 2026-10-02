<?php

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

if (!isset($_SESSION['usuario_id'])) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'erro' => 'Não autenticado'
    ]);

    exit;
}

$tipo =
    $_SESSION['usuario_tipo']
    ?? null;

if (
    !in_array(
        $tipo,
        ['residencial', 'comercial'],
        true
    )
) {

    http_response_code(403);

    echo json_encode([
        'ok' => false,
        'erro' => 'Tipo de usuário inválido'
    ]);

    exit;
}

echo json_encode([

    'ok' => true,

    'usuario_id' =>
        (int)$_SESSION['usuario_id'],

    'tipo_usuario' =>
        $tipo,

    'ambientes_permitidos' =>
        $tipo === 'residencial'
        ? ['casa']
        : [
            'casa',
            'escola',
            'industria',
            'agro'
        ]

], JSON_UNESCAPED_UNICODE);

?>