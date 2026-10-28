
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../database/conexao.php';

iniciarSessao();

$redirect = destinoSeguro(
    $_GET['redirect']
    ?? $_POST['redirect']
    ?? '../../backend/dashboard/dashboard.php'
);

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$erro = '';
$email = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $tipo = (string)($_POST['tipo_usuario'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';

    } elseif ($senha === '') {
        $erro = 'Digite sua senha.';

    } elseif (!in_array($tipo, ['residencial', 'comercial'], true)) {
        $erro = 'Selecione o tipo de uso.';

    } else {

        $stmt = $conexao->prepare(
            "SELECT
                id, nome, email, senha, cidade, telefone,
                tipo_usuario, tipo_imovel, endereco, numero,
                bairro, cep, empresa, tipo_estabelecimento
             FROM usuarios
             WHERE email = ? AND tipo_usuario = ?
             LIMIT 1"
        );

        if (!$stmt) {
            error_log('Erro ao preparar login: ' . $conexao->error);
            $erro = 'Não foi possível realizar o login. Tente novamente.';

        } else {

            $stmt->bind_param('ss', $email, $tipo);
            $stmt->execute();

            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $senhaOk = false;

            if ($usuario) {

                $senhaBanco = (string)$usuario['senha'];

                $senhaOk = password_verify($senha, $senhaBanco);

                // Migração de senhas antigas em texto puro.
                if (!$senhaOk && hash_equals($senhaBanco, $senha)) {

                    $senhaOk = true;
                    $novoHash = password_hash($senha, PASSWORD_DEFAULT);

                    $up = $conexao->prepare(
                        "UPDATE usuarios SET senha = ? WHERE id = ?"
                    );

                    if ($up) {
                        $idUsuario = (int)$usuario['id'];
                        $up->bind_param('si', $novoHash, $idUsuario);
                        $up->execute();
                        $up->close();
                    }
                }
            }

            if (!$usuario || !$senhaOk) {

                $erro = 'E-mail, senha ou tipo de uso incorreto.';

            } else {

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = (int)$usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_cidade'] = $usuario['cidade'];
                $_SESSION['usuario_telefone'] = $usuario['telefone'];
                $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];

                $_SESSION['usuario_tipo_imovel'] =
                    $usuario['tipo_imovel'] ?? '';

                $_SESSION['usuario_endereco'] =
                    $usuario['endereco'] ?? '';

                $_SESSION['usuario_numero'] =
                    $usuario['numero'] ?? '';

                $_SESSION['usuario_bairro'] =
                    $usuario['bairro'] ?? '';

                $_SESSION['usuario_cep'] =
                    $usuario['cep'] ?? '';

                $_SESSION['usuario_empresa'] =
                    $usuario['empresa'] ?? '';

                $_SESSION['usuario_tipo_estabelecimento'] =
                    $usuario['tipo_estabelecimento'] ?? '';

                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$homeUrl = '../public/pages/home.html';
$cadastroUrl = '../backend/auth/cadastro.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar — GRAPE Digital Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        :root {
            --purple: #8247e5;
            --purple-light: #b58aff;
            --text: #f5f0ff;
            --muted: #b0a4c2;
        }

        * {
            box-sizing: border-box;
        }

        html {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            width: 100%;
            height: 100vh;
            height: 100dvh;
            margin: 0;
            padding: 0;

            overflow: hidden;

            font-family: 'Nunito', sans-serif;
            color: var(--text);

            background-color: #080411;

            background-image:
                linear-gradient(
                    110deg,
                    rgba(5, 3, 15, .42),
                    rgba(5, 3, 15, .66)
                ),
                url('/grapedev/storage/images/fundo_login_grape.png');

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Camada de profundidade */

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;

            background:
                radial-gradient(
                    ellipse at 50% 42%,
                    rgba(30, 10, 60, .12),
                    rgba(5, 3, 12, .43) 100%
                );
        }

        /* Luzes ambientes */

        .glow {
            position: fixed;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            filter: blur(110px);
            pointer-events: none;
            z-index: 0;
        }

        .glow-one {
            top: -190px;
            left: 15%;
            background: rgba(130, 71, 229, .19);
        }

        .glow-two {
            right: -180px;
            bottom: -200px;
            background: rgba(116, 45, 225, .18);
        }

        /* Topo sem logotipo */

        .topbar {
            position: relative;
            z-index: 3;

            width: calc(100% - 64px);
            max-width: 1440px;

            margin: 0 auto;
            padding-top: 20px;

            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Botão voltar */

        .back-button {
            position: relative;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 11px 19px;

            color: #f5efff;
            text-decoration: none;

            font-size: 11px;
            font-weight: 900;
            letter-spacing: .3px;

            border: 1px solid rgba(255,255,255,.15);
            border-radius: 50px;

            background: rgba(14, 9, 25, .65);

            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);

            box-shadow: 0 5px 25px rgba(0,0,0,.18);

            transition:
                color .25s ease,
                border-color .25s ease,
                background .25s ease,
                transform .25s ease;
        }

        .back-button i {
            font-size: 14px;
            transition: transform .25s ease;
        }

        .back-button:hover {
            color: white;
            border-color: rgba(181,138,255,.65);
            background: rgba(130,71,229,.24);
            transform: translateY(-2px);
        }

        .back-button:hover i {
            transform: translateX(-3px);
        }

        /* Área central */

        .page {
            position: relative;
            z-index: 2;

            width: min(510px, calc(100% - 32px));

            margin: 9px auto 0;
        }

        /* Introdução */

        .intro {
            text-align: center;
            margin-bottom: 17px;
        }

        .intro-small {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            margin-bottom: 7px;

            color: var(--purple-light);

            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2.7px;
        }

        .intro-small::before,
        .intro-small::after {
            content: "";
            width: 23px;
            height: 1px;

            background: linear-gradient(
                90deg,
                transparent,
                var(--purple-light)
            );
        }

        .intro-small::after {
            transform: rotate(180deg);
        }

        h1 {
            margin: 0;

            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(45px, 5vw, 63px);
            font-weight: 400;
            line-height: .91;
            letter-spacing: 1px;

            color: #fff;

            text-shadow: 0 4px 30px rgba(0,0,0,.4);
        }

        h1 span {
            display: block;

            margin-top: 4px;

            color: var(--purple-light);

            text-shadow:
                0 0 22px rgba(130,71,229,.35);
        }

        .intro p {
            max-width: 380px;
            margin: 10px auto 0;

            color: #c5bad5;

            font-size: 11px;
            line-height: 1.5;
        }

        /* Cartão de login */

        .login-card {
            position: relative;

            padding: 23px 27px 20px;

            border: 1px solid rgba(188,151,255,.22);
            border-radius: 22px;

            background:
                linear-gradient(
                    145deg,
                    rgba(25, 17, 40, .91),
                    rgba(8, 6, 16, .93)
                );

            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);

            box-shadow:
                0 25px 70px rgba(0,0,0,.40),
                0 0 45px rgba(130,71,229,.09),
                inset 0 1px 0 rgba(255,255,255,.06);

            overflow: hidden;
        }

        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 12%;

            width: 76%;
            height: 1px;

            background: linear-gradient(
                90deg,
                transparent,
                #8247e5,
                #d1b5ff,
                #8247e5,
                transparent
            );
        }

        .login-card::after {
            content: "";
            position: absolute;

            top: -90px;
            right: -90px;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            background: rgba(130,71,229,.08);
            filter: blur(45px);

            pointer-events: none;
        }

        /* Mensagem de erro */

        .error-box {
            position: relative;
            z-index: 1;

            display: flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 14px;
            padding: 11px 13px;

            border: 1px solid rgba(248,113,113,.28);
            border-radius: 11px;

            background: rgba(127,29,29,.25);

            color: #fecaca;
            font-size: 11px;
            font-weight: 800;
        }

        /* Títulos das seções */

        .section-label {
            margin-bottom: 3px;

            color: var(--purple-light);

            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.8px;
        }

        .section-title {
            margin: 0 0 12px;

            font-family: 'Bebas Neue', sans-serif;
            font-size: 25px;
            font-weight: 400;
            letter-spacing: .7px;

            color: white;
        }

        /* Tipos de conta */

        .types {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;

            margin-bottom: 18px;
        }

        .types label {
            display: block;
            margin: 0;
        }

        .types input[type="radio"] {
            position: absolute;

            width: 1px;
            height: 1px;

            opacity: 0;
            pointer-events: none;
        }

        .type {
            display: flex;
            align-items: center;
            gap: 10px;

            min-height: 66px;
            padding: 10px;

            border: 1px solid rgba(255,255,255,.09);
            border-radius: 13px;

            background: rgba(8,6,16,.62);

            cursor: pointer;

            transition:
                border-color .25s ease,
                background .25s ease,
                transform .25s ease,
                box-shadow .25s ease;
        }

        .type:hover {
            transform: translateY(-2px);
            border-color: rgba(181,138,255,.45);
            background: rgba(130,71,229,.10);
        }

        .types input:checked + .type {
            border-color: #a875ff;

            background:
                linear-gradient(
                    135deg,
                    rgba(130,71,229,.23),
                    rgba(74,36,128,.13)
                );

            box-shadow:
                0 0 0 1px rgba(130,71,229,.12),
                0 0 20px rgba(130,71,229,.08);
        }

        .types input:focus-visible + .type {
            outline: 2px solid var(--purple-light);
            outline-offset: 3px;
        }

        .type-icon {
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            width: 37px;
            height: 37px;

            border-radius: 11px;

            background: rgba(130,71,229,.17);
            color: #bd9aff;

            font-size: 17px;

            transition: .25s ease;
        }

        .types input:checked + .type .type-icon {
            color: white;

            background: linear-gradient(
                135deg,
                #8247e5,
                #a875ff
            );

            box-shadow: 0 0 18px rgba(130,71,229,.32);
        }

        .type-name {
            display: block;
            margin-bottom: 3px;

            color: #fff;
            font-size: 11px;
            font-weight: 900;
        }

        .type-description {
            display: block;

            color: #a99db9;
            font-size: 9px;
            line-height: 1.35;
        }

        /* Campos */

        .field {
            margin-bottom: 13px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;

            color: #e6dcef;

            font-size: 9px;
            font-weight: 900;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap > i {
            position: absolute;
            top: 50%;
            left: 14px;

            transform: translateY(-50%);

            color: #9887b0;
            font-size: 14px;

            pointer-events: none;
            transition: color .2s ease;
        }

        .input-wrap input {
            display: block;

            width: 100%;
            height: 44px;

            padding: 0 14px 0 40px;

            border: 1px solid rgba(255,255,255,.11);
            border-radius: 10px;

            outline: none;

            background: rgba(5,4,11,.70);
            color: white;

            font-family: 'Nunito', sans-serif;
            font-size: 11px;
            font-weight: 700;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .input-wrap input::placeholder {
            color: #81758f;
            font-weight: 500;
        }

        .input-wrap input:hover {
            border-color: rgba(181,138,255,.40);
        }

        .input-wrap input:focus {
            border-color: #a875ff;
            background: rgba(10,6,20,.92);

            box-shadow:
                0 0 0 3px rgba(130,71,229,.13),
                0 0 20px rgba(130,71,229,.06);
        }

        .input-wrap:focus-within > i {
            color: #c4a4ff;
        }

        /* Botão principal moderno */

        .submit {
            position: relative;
            isolation: isolate;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;

            width: 100%;
            min-height: 49px;

            margin-top: 7px;
            padding: 12px 18px;

            overflow: hidden;

            border: 1px solid rgba(255,255,255,.23);
            border-radius: 12px;

            background: linear-gradient(
                105deg,
                #6429bf 0%,
                #8247e5 45%,
                #a66bff 100%
            );

            color: #fff;

            font-family: 'Nunito', sans-serif;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;

            cursor: pointer;

            box-shadow:
                0 8px 25px rgba(103,48,192,.35),
                0 0 0 1px rgba(180,140,255,.08),
                inset 0 1px 0 rgba(255,255,255,.20);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                filter .25s ease;
        }

        /* Reflexo animado do botão */

        .submit::before {
            content: "";

            position: absolute;
            z-index: -1;

            top: -50%;
            left: -80%;

            width: 55%;
            height: 200%;

            transform: rotate(25deg);

            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,.24),
                transparent
            );

            transition: left .6s ease;
        }

        .submit:hover {
            transform: translateY(-3px);

            filter: brightness(1.10);

            box-shadow:
                0 13px 34px rgba(103,48,192,.47),
                0 0 25px rgba(130,71,229,.20),
                inset 0 1px 0 rgba(255,255,255,.25);
        }

        .submit:hover::before {
            left: 130%;
        }

        .submit:active {
            transform: translateY(-1px) scale(.99);
        }

        .submit:focus-visible {
            outline: 2px solid #d3baff;
            outline-offset: 4px;
        }

        .submit .button-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 26px;
            height: 26px;

            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px;

            background: rgba(255,255,255,.13);

            transition:
                transform .25s ease,
                background .25s ease;
        }

        .submit .button-icon i {
            font-size: 14px;
        }

        .submit:hover .button-icon {
            transform: translateX(3px);
            background: rgba(255,255,255,.22);
        }

        /* Cadastro */

        .register {
            margin-top: 14px;

            text-align: center;

            color: #b0a4c2;

            font-size: 10px;
            font-weight: 600;
        }

        .register a {
            color: #c1a0ff;
            text-decoration: none;
            font-weight: 900;

            transition: color .2s ease;
        }

        .register a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Rodapé */

        .footer-note {
            margin-top: 14px;

            text-align: center;

            color: rgba(220,205,245,.72);

            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1.4px;

            text-shadow: 0 2px 10px #000;
        }

        /* Telas menores */

        @media (max-height: 800px) {
            .topbar {
                padding-top: 10px;
            }

            .page {
                margin-top: 2px;
            }

            .intro {
                margin-bottom: 11px;
            }

            h1 {
                font-size: 46px;
            }

            .intro p {
                margin-top: 7px;
            }

            .login-card {
                padding: 17px 23px 15px;
            }

            .section-title {
                margin-bottom: 9px;
                font-size: 23px;
            }

            .types {
                margin-bottom: 13px;
                gap: 8px;
            }

            .type {
                min-height: 57px;
                padding: 8px;
            }

            .type-icon {
                width: 33px;
                height: 33px;
            }

            .field {
                margin-bottom: 10px;
            }

            .input-wrap input {
                height: 40px;
            }

            .submit {
                min-height: 43px;
                margin-top: 4px;
            }

            .register {
                margin-top: 10px;
            }

            .footer-note {
                margin-top: 9px;
            }
        }

        @media (max-height: 650px) and (min-width: 551px) {
            .topbar {
                padding-top: 6px;
            }

            .page {
                width: min(460px, calc(100% - 32px));
                margin-top: 0;
            }

            .intro {
                margin-bottom: 7px;
            }

            .intro-small {
                margin-bottom: 4px;
                font-size: 8px;
            }

            h1 {
                font-size: 37px;
            }

            .intro p {
                margin-top: 5px;
                font-size: 10px;
            }

            .login-card {
                padding: 12px 20px;
            }

            .section-title {
                font-size: 20px;
                margin-bottom: 7px;
            }

            .section-label {
                font-size: 7px;
            }

            .types {
                margin-bottom: 9px;
            }

            .type {
                min-height: 48px;
                gap: 7px;
                padding: 6px;
            }

            .type-icon {
                width: 29px;
                height: 29px;
                font-size: 14px;
            }

            .type-name {
                font-size: 10px;
            }

            .type-description {
                font-size: 8px;
            }

            .field {
                margin-bottom: 7px;
            }

            .field label {
                margin-bottom: 4px;
                font-size: 8px;
            }

            .input-wrap input {
                height: 35px;
            }

            .submit {
                min-height: 38px;
                padding: 6px 12px;
            }

            .submit .button-icon {
                width: 22px;
                height: 22px;
            }

            .register {
                margin-top: 7px;
                font-size: 9px;
            }

            .footer-note {
                margin-top: 6px;
            }
        }

        @media (max-width: 550px) {
            .topbar {
                width: calc(100% - 30px);
                padding-top: 13px;
            }

            .page {
                width: calc(100% - 26px);
                margin-top: 10px;
            }

            .login-card {
                padding: 21px 17px;
            }

            h1 {
                font-size: clamp(39px, 11vw, 53px);
            }

            .types {
                gap: 8px;
            }

            .type {
                min-height: 60px;
            }

            .intro p {
                font-size: 10px;
            }

            .back-button {
                padding: 9px 14px;
            }
        }

        @media (max-width: 550px) and (max-height: 760px) {
            .topbar {
                padding-top: 7px;
            }

            .page {
                margin-top: 3px;
            }

            .intro {
                margin-bottom: 10px;
            }

            h1 {
                font-size: 39px;
            }

            .intro p {
                margin-top: 6px;
            }

            .login-card {
                padding: 15px 16px;
            }

            .section-title {
                margin-bottom: 8px;
            }

            .types {
                margin-bottom: 12px;
            }

            .type {
                min-height: 53px;
            }

            .field {
                margin-bottom: 9px;
            }

            .input-wrap input {
                height: 39px;
            }

            .submit {
                min-height: 43px;
            }

            .footer-note {
                margin-top: 7px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }

    </style>

</head>

<body>

    <div class="glow glow-one"></div>
    <div class="glow glow-two"></div>

    <!-- Topo somente com o botão de voltar -->

    <header class="topbar">

        <a href="<?= e($homeUrl) ?>" class="back-button">
            <span>VOLTAR AO SITE</span>
        </a>

    </header>

    <main class="page">

        <section class="intro">

            <div class="intro-small">
                GRAPE ACCOUNT
            </div>

            <h1>
                BEM-VINDO
                <span>DE VOLTA.</span>
            </h1>

            <p>
                Entre na sua conta e continue explorando
                o ecossistema digital da GRAPE.
            </p>

        </section>

        <section class="login-card">

            <?php if ($erro !== ''): ?>

                <div class="error-box" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= e($erro) ?></span>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <input
                    type="hidden"
                    name="redirect"
                    value="<?= e($redirect) ?>"
                >

                <div class="section-label">
                    SEU AMBIENTE
                </div>

                <h2 class="section-title">
                    COMO VOCÊ VAI UTILIZAR?
                </h2>

                <div class="types">

                    <label>

                        <input
                            type="radio"
                            name="tipo_usuario"
                            value="residencial"
                            <?= $tipo === 'residencial' ? 'checked' : '' ?>
                            required
                        >

                        <span class="type">

                            <span class="type-icon">
                                <i class="bi bi-house-door-fill"></i>
                            </span>

                            <span>
                                <span class="type-name">Residencial</span>
                                <span class="type-description">
                                    Casa ou apartamento
                                </span>
                            </span>

                        </span>

                    </label>

                    <label>

                        <input
                            type="radio"
                            name="tipo_usuario"
                            value="comercial"
                            <?= $tipo === 'comercial' ? 'checked' : '' ?>
                            required
                        >

                        <span class="type">

                            <span class="type-icon">
                                <i class="bi bi-building-fill"></i>
                            </span>

                            <span>
                                <span class="type-name">Comercial</span>
                                <span class="type-description">
                                    Empresa ou indústria
                                </span>
                            </span>

                        </span>

                    </label>

                </div>

                <div class="section-label">
                    ACESSO SEGURO
                </div>

                <h2 class="section-title">
                    SEUS DADOS
                </h2>

                <div class="field">

                    <label for="email">E-mail</label>

                    <div class="input-wrap">

                        <i class="bi bi-envelope"></i>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="seu@email.com"
                            value="<?= e($email) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="field">

                    <label for="senha">Senha</label>

                    <div class="input-wrap">

                        <i class="bi bi-lock"></i>

                        <input
                            id="senha"
                            name="senha"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Digite sua senha"
                            required
                        >

                    </div>

                </div>

                <button type="submit" class="submit">

                    <span>ENTRAR NA MINHA CONTA</span>

                </button>

            </form>

            <div class="register">

                Ainda não possui uma conta?

                <a href="<?= e($cadastroUrl) ?>?redirect=<?= urlencode($redirect) ?>">
                    Criar cadastro
                </a>

            </div>

        </section>

        <div class="footer-note">
            GRAPE DIGITAL STUDIO &nbsp;·&nbsp; CREATE THE FUTURE
        </div>

    </main>

</body>
</html>
