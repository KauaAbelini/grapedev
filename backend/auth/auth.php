
<?php

function iniciarSessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function destinoSeguro(
    ?string $destino,
    string $padrao = '../dashboard/dashboard.php'
): string {

    $destino = trim((string)$destino);

    if (
        $destino === '' ||
        preg_match('/[\r\n]/', $destino)
    ) {
        return $padrao;
    }

    if (
        preg_match('#^(https?:)?//#i', $destino) ||
        str_starts_with($destino, '/')
    ) {
        return $padrao;
    }

    return $destino;
}

function exigirLogin(): void
{
    iniciarSessao();

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: /grapedev/backend/auth/login.php'
        );

        exit;
    }
}

?>
