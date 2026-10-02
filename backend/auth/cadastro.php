<?php

require_once 'auth.php';
require_once 'conexao.php';

iniciarSessao();

$redirect = destinoSeguro(
    $_GET['redirect']
    ?? $_POST['redirect']
    ?? 'dashboard.php'
);

$erro = '';

$nome = '';
$email = '';
$senha = '';
$cidade = '';
$telefone = '';

$tipo_usuario = '';

$cep = '';
$endereco = '';
$numero = '';
$bairro = '';

$empresa = '';
$tipo_estabelecimento = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $cidade = trim($_POST['cidade'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    $tipo_usuario = $_POST['tipo_usuario'] ?? '';

    $cep = trim($_POST['cep'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');

    $empresa = trim($_POST['empresa'] ?? '');
    $tipo_estabelecimento = trim($_POST['tipo_estabelecimento'] ?? '');


    if (
        $nome === '' ||
        $email === '' ||
        $senha === '' ||
        $cidade === '' ||
        $telefone === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $erro = 'Digite um e-mail válido.';

    } elseif (
        strlen($senha) < 6
    ) {

        $erro = 'A senha deve ter pelo menos 6 caracteres.';

    } elseif (
        !in_array(
            $tipo_usuario,
            ['residencial', 'comercial'],
            true
        )
    ) {

        $erro = 'Selecione o tipo de uso.';

    } elseif (
        $cep === '' ||
        $endereco === '' ||
        $numero === '' ||
        $bairro === ''
    ) {

        $erro = 'Preencha o endereço completo.';

    } elseif (
        $tipo_usuario === 'comercial' &&
        (
            $empresa === '' ||
            $tipo_estabelecimento === ''
        )
    ) {

        $erro = 'Preencha os dados comerciais.';

    } else {

        $stmt = $conexao->prepare(
            "SELECT id
             FROM usuarios
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $existe = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();


        if ($existe) {

            $erro = 'Este e-mail já está cadastrado.';

        } else {

            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );


            $stmt = $conexao->prepare(
                "INSERT INTO usuarios
                (
                    nome,
                    email,
                    senha,
                    cidade,
                    telefone,
                    tipo_usuario,
                    endereco,
                    numero,
                    bairro,
                    cep,
                    empresa,
                    tipo_estabelecimento
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'ssssssssssss',
                $nome,
                $email,
                $senhaHash,
                $cidade,
                $telefone,
                $tipo_usuario,
                $endereco,
                $numero,
                $bairro,
                $cep,
                $empresa,
                $tipo_estabelecimento
            );


            if ($stmt->execute()) {

                session_regenerate_id(true);

                $_SESSION['usuario_id'] =
                    $conexao->insert_id;

                $_SESSION['usuario_nome'] =
                    $nome;

                $_SESSION['usuario_email'] =
                    $email;

                $_SESSION['usuario_cidade'] =
                    $cidade;

                $_SESSION['usuario_telefone'] =
                    $telefone;

                $_SESSION['usuario_tipo'] =
                    $tipo_usuario;

                $_SESSION['usuario_endereco'] =
                    $endereco;

                $_SESSION['usuario_numero'] =
                    $numero;

                $_SESSION['usuario_bairro'] =
                    $bairro;

                $_SESSION['usuario_cep'] =
                    $cep;

                $_SESSION['usuario_empresa'] =
                    $empresa;

                $_SESSION['usuario_tipo_estabelecimento'] =
                    $tipo_estabelecimento;


                header(
                    'Location: ' . $redirect
                );

                exit;

            } else {

                $erro =
                    'Não foi possível realizar o cadastro.';
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>GRAPE — Criar conta</title>

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
    --bg-dark: #1d1924;
    --bg-black: #0b090f;
    --purple: #8247e5;
    --purple-dark: #4a2480;
    --purple-light: #a875ff;
    --white: #ffffff;
    --text: #eeeaf5;
    --muted: #938b9f;
    --card: rgba(28, 24, 36, .88);
    --border: rgba(255,255,255,.08);
    --input: rgba(10,8,14,.72);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;

    font-family: 'Nunito', sans-serif;

    color: var(--text);

    background:
        radial-gradient(
            circle at 50% -10%,
            rgba(130,71,229,.18),
            transparent 32%
        ),
        radial-gradient(
            circle at 0% 100%,
            rgba(74,36,128,.13),
            transparent 28%
        ),
        linear-gradient(
            135deg,
            var(--bg-dark),
            var(--bg-black)
        );

    overflow-x: hidden;
}

.background-glow {
    position: fixed;

    width: 400px;
    height: 400px;

    border-radius: 50%;

    background: rgba(130,71,229,.07);

    filter: blur(110px);

    pointer-events: none;

    z-index: 0;
}

.glow-one {
    top: -220px;
    left: 20%;
}

.glow-two {
    bottom: -250px;
    right: -100px;
}

.topbar {
    position: relative;

    width: min(1050px, calc(100% - 36px));

    margin: 0 auto;

    padding: 21px 0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    z-index: 10;
}

.logo {
    display: flex;

    align-items: center;

    gap: 8px;

    color: white;

    text-decoration: none;
}

.logo img {
    width: 29px;
    height: 29px;

    object-fit: contain;

    filter:
        drop-shadow(
            0 0 10px
            rgba(130,71,229,.4)
        );
}

.logo-text {
    font-size: 18px;

    font-weight: 900;

    letter-spacing: -.5px;
}

.logo-text span {
    color: var(--purple-light);
}

.back-button {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 8px 14px;

    border: 1px solid rgba(255,255,255,.08);

    border-radius: 25px;

    background: rgba(255,255,255,.035);

    color: #ddd7e5;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none;

    transition: .25s ease;
}

.back-button:hover {
    color: white;

    border-color: rgba(130,71,229,.4);

    background: rgba(130,71,229,.09);

    transform: translateX(-2px);
}

.page {
    position: relative;

    width: min(980px, calc(100% - 32px));

    margin: 12px auto 45px;

    z-index: 2;
}


.intro {
    max-width: 650px;

    margin: 0 auto 28px;

    text-align: center;
}

.intro-small {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 9px;

    color: var(--purple-light);

    font-size: 9px;

    font-weight: 900;

    letter-spacing: 1.8px;

    text-transform: uppercase;
}

.intro-small::before,
.intro-small::after {
    content: "";

    width: 20px;

    height: 1px;

    background: var(--purple);
}

h1 {
    margin: 0;

    font-family: 'Bebas Neue', sans-serif;

    font-size: clamp(46px, 6vw, 66px);

    line-height: .86;

    letter-spacing: .8px;

    color: white;

    text-transform: uppercase;
}

h1 span {
    color: var(--purple);
}

.intro p {
    max-width: 500px;

    margin: 11px auto 0;

    color: var(--muted);

    font-size: 13px;

    line-height: 1.55;
}

.form-card {
    position: relative;

    width: 100%;

    padding: 28px;

    border-radius: 22px;

    border: 1px solid var(--border);

    background:
        linear-gradient(
            145deg,
            rgba(30,25,40,.95),
            rgba(14,12,19,.95)
        );

    box-shadow:
        0 25px 65px rgba(0,0,0,.42),
        0 0 60px rgba(130,71,229,.04);

    overflow: hidden;
}

.form-card::before {
    content: "";

    position: absolute;

    top: 0;
    left: 10%;

    width: 80%;
    height: 1px;

    background:
        linear-gradient(
            90deg,
            transparent,
            var(--purple),
            transparent
        );
}

.section-heading {
    margin-bottom: 16px;
}

.section-label {
    margin-bottom: 4px;

    color: var(--purple-light);

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;

    text-transform: uppercase;
}

.section-heading h2 {
    margin: 0;

    font-family: 'Bebas Neue', sans-serif;

    font-size: 27px;

    letter-spacing: .4px;

    color: white;
}

.type-title {
    margin-bottom: 9px;

    color: #dcd6e5;

    font-size: 11px;

    font-weight: 800;
}

.types {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 12px;

    margin-bottom: 28px;
}

.types label {
    margin: 0;
}

.types input {
    position: absolute;

    opacity: 0;

    pointer-events: none;
}

.type {
    position: relative;

    min-height: 88px;

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 15px;

    border-radius: 15px;

    border: 1px solid rgba(255,255,255,.07);

    background:
        linear-gradient(
            145deg,
            rgba(30,26,39,.92),
            rgba(14,12,20,.92)
        );

    cursor: pointer;

    transition: .25s ease;
}

.type:hover {
    transform: translateY(-2px);

    border-color: rgba(130,71,229,.35);
}

.types input:checked + .type {
    border-color: var(--purple);

    background:
        linear-gradient(
            145deg,
            rgba(130,71,229,.15),
            rgba(74,36,128,.09)
        );

    box-shadow:
        0 0 0 1px rgba(130,71,229,.08);
}

.type-icon {
    flex-shrink: 0;

    width: 43px;
    height: 43px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: rgba(130,71,229,.10);

    border: 1px solid rgba(130,71,229,.17);

    color: var(--purple-light);

    font-size: 18px;
}

.types input:checked + .type .type-icon {
    background: var(--purple);

    color: white;

    border-color: var(--purple);
}

.type-name {
    display: block;

    margin-bottom: 3px;

    color: white;

    font-size: 13px;

    font-weight: 900;
}

.type-description {
    display: block;

    color: #847d8e;

    font-size: 10px;

    line-height: 1.35;

    font-weight: 600;
}


.divider {
    height: 1px;

    margin: 0 0 27px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.07),
            transparent
        );
}


.grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap: 15px 14px;
}

.full {
    grid-column: 1 / -1;
}


.field label {
    display: block;

    margin-bottom: 6px;

    color: #cfc8da;

    font-size: 9px;

    font-weight: 900;

    letter-spacing: .6px;

    text-transform: uppercase;
}

.input-wrap {
    position: relative;
}

.input-wrap i {
    position: absolute;

    left: 14px;
    top: 50%;

    transform: translateY(-50%);

    color: #756c81;

    font-size: 13px;

    pointer-events: none;

    transition: .2s ease;
}

input,
select {
    width: 100%;

    height: 45px;

    padding: 0 14px 0 39px;

    border: 1px solid rgba(255,255,255,.07);

    border-radius: 10px;

    outline: none;

    background: var(--input);

    color: white;

    font-family: 'Nunito', sans-serif;

    font-size: 11px;

    font-weight: 600;

    transition: .2s ease;
}

input::placeholder {
    color: #5e5669;
}

input:hover,
select:hover {
    border-color: rgba(130,71,229,.28);
}

input:focus,
select:focus {
    border-color: var(--purple);

    box-shadow:
        0 0 0 2px
        rgba(130,71,229,.07);
}

.input-wrap:focus-within i {
    color: var(--purple-light);
}

select {
    appearance: none;

    cursor: pointer;

    padding-right: 35px;

    background-image:
        linear-gradient(
            45deg,
            transparent 50%,
            #837b90 50%
        ),
        linear-gradient(
            135deg,
            #837b90 50%,
            transparent 50%
        );

    background-position:
        calc(100% - 17px) 19px,
        calc(100% - 12px) 19px;

    background-size: 5px 5px;

    background-repeat: no-repeat;
}

select option {
    background: #15111d;

    color: white;
}


/* COMERCIAL */

.comercial {
    display: none;

    margin-top: 23px;

    padding: 18px;

    border-radius: 15px;

    border: 1px solid rgba(130,71,229,.18);

    background:
        linear-gradient(
            145deg,
            rgba(130,71,229,.06),
            rgba(12,10,17,.5)
        );

    animation: aparecer .3s ease;
}

@keyframes aparecer {
    from {
        opacity: 0;

        transform: translateY(-5px);
    }

    to {
        opacity: 1;

        transform: translateY(0);
    }
}

.comercial-header {
    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 17px;
}

.comercial-icon {
    width: 35px;
    height: 35px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: rgba(130,71,229,.11);

    color: var(--purple-light);

    font-size: 15px;
}

.comercial-header strong {
    display: block;

    color: white;

    font-size: 12px;

    font-weight: 900;
}

.comercial-header span {
    display: block;

    margin-top: 2px;

    color: #776f80;

    font-size: 9px;

    font-weight: 600;
}


/* BOTÃO */

.submit {
    position: relative;

    width: 100%;

    height: 49px;

    margin-top: 25px;

    border: 0;

    border-radius: 11px;

    background:
        linear-gradient(
            110deg,
            #6d35c4,
            #8247e5,
            #9a68f0
        );

    color: white;

    font-family: 'Nunito', sans-serif;

    font-size: 11px;

    font-weight: 900;

    letter-spacing: .5px;

    cursor: pointer;

    box-shadow:
        0 12px 28px
        rgba(74,36,128,.3);

    transition: .25s ease;
}

.submit:hover {
    transform: translateY(-2px);

    box-shadow:
        0 16px 32px
        rgba(74,36,128,.42);
}

.submit i {
    margin-left: 5px;

    transition: .2s ease;
}

.submit:hover i {
    transform: translateX(3px);
}


/* ERRO */

.error-box {
    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 18px;

    padding: 11px 13px;

    border-radius: 10px;

    border: 1px solid rgba(248,113,113,.18);

    background: rgba(127,29,29,.13);

    color: #fca5a5;

    font-size: 10px;

    font-weight: 700;
}


/* LOGIN */

.login-link {
    margin-top: 17px;

    text-align: center;

    color: #716a7b;

    font-size: 10px;

    font-weight: 600;
}

.login-link a {
    color: var(--purple-light);

    text-decoration: none;

    font-weight: 900;
}

.login-link a:hover {
    color: white;
}


/* RODAPÉ */

.footer-note {
    margin-top: 20px;

    text-align: center;

    color: #5f5869;

    font-size: 8px;

    font-weight: 700;

    letter-spacing: .5px;
}


/* RESPONSIVO */

@media (max-width: 700px) {

    .topbar {
        width: calc(100% - 28px);

        padding: 17px 0;
    }

    .page {
        width: calc(100% - 20px);

        margin-top: 8px;
    }

    .form-card {
        padding: 21px 17px;

        border-radius: 18px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .types {
        grid-template-columns: 1fr;

        gap: 9px;
    }

    .type {
        min-height: 78px;
    }

    .intro {
        margin-bottom: 22px;
    }

    h1 {
        font-size: 50px;
    }
}


@media (max-width: 420px) {

    .logo-text {
        font-size: 16px;
    }

    .logo img {
        width: 27px;
        height: 27px;
    }

    .back-button {
        padding: 7px 10px;
    }

    .back-button span {
        display: none;
    }

    h1 {
        font-size: 46px;
    }

    .intro p {
        font-size: 11px;
    }

    .form-card {
        padding: 19px 14px;
    }

    input,
    select {
        height: 43px;
    }
}
</style>

</head>


<body>


<div class="background-glow glow-one"></div>
<div class="background-glow glow-two"></div>


<header class="topbar">

    <a
        href="home.html"
        class="logo"
    >

        <img
            src="fotos/icone uva sem fundo.png"
            alt="GRAPE"
        >

        <div class="logo-text">
            GRAPE<span>Dev</span>
        </div>

    </a>


    <a
        href="home.html"
        class="back-button"
    >

        <i class="bi bi-arrow-left"></i>

        <span>Voltar</span>

    </a>

</header>


<main class="page">


    <section class="intro">

        <div class="intro-small">
            GRAPE ACCOUNT
        </div>

        <h1>
            CRIE SUA <span>CONTA.</span>
        </h1>

        <p>
            Entre para o ecossistema GRAPE e configure
            sua experiência de acordo com o seu ambiente.
        </p>

    </section>


    <section class="form-card">


        <?php if ($erro !== ''): ?>

            <div class="error-box">

                <i class="bi bi-exclamation-circle"></i>

                <span>
                    <?= htmlspecialchars(
                        $erro,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            autocomplete="on"
        >


            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars(
                    $redirect,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <div class="section-heading">

                <div class="section-label">
                    PRIMEIRO PASSO
                </div>

                <h2>
                    SELECIONE SEU AMBIENTE
                </h2>

            </div>


            <div class="type-title">
                Como você pretende utilizar o GRAPE?
            </div>


            <div class="types">


                <label>

                    <input
                        type="radio"
                        name="tipo_usuario"
                        value="residencial"

                        <?= $tipo_usuario === 'residencial'
                            ? 'checked'
                            : '' ?>

                        required
                    >

                    <span class="type">

                        <span class="type-icon">
                            <i class="bi bi-house-door-fill"></i>
                        </span>

                        <span class="type-content">

                            <span class="type-name">
                                Residencial
                            </span>

                            <span class="type-description">
                                Casa, apartamento ou
                                residência.
                            </span>

                        </span>

                    </span>

                </label>


                <label>

                    <input
                        type="radio"
                        name="tipo_usuario"
                        value="comercial"

                        <?= $tipo_usuario === 'comercial'
                            ? 'checked'
                            : '' ?>

                        required
                    >

                    <span class="type">

                        <span class="type-icon">
                            <i class="bi bi-building-fill"></i>
                        </span>

                        <span class="type-content">

                            <span class="type-name">
                                Comercial / Indústria
                            </span>

                            <span class="type-description">
                                Empresa, escola, indústria
                                ou estabelecimento.
                            </span>

                        </span>

                    </span>

                </label>


            </div>


            <div class="divider"></div>


            <div class="section-heading">

                <div class="section-label">
                    SEGUNDO PASSO
                </div>

                <h2>
                    SEUS DADOS
                </h2>

            </div>


            <div class="grid">


                <div class="field">

                    <label for="nome">
                        Nome
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-person"></i>

                        <input
                            id="nome"
                            name="nome"
                            type="text"
                            autocomplete="name"
                            placeholder="Seu nome"
                            required

                            value="<?= htmlspecialchars(
                                $nome,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="email">
                        E-mail
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-envelope"></i>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="seu@email.com"
                            required

                            value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="senha">
                        Senha
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-lock"></i>

                        <input
                            id="senha"
                            name="senha"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Mínimo de 6 caracteres"
                            minlength="6"
                            required
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="telefone">
                        Telefone
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-telephone"></i>

                        <input
                            id="telefone"
                            name="telefone"
                            type="tel"
                            autocomplete="tel"
                            placeholder="(00) 00000-0000"
                            required

                            value="<?= htmlspecialchars(
                                $telefone,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


            </div>


            <div
                id="comercialBox"
                class="comercial"
            >

                <div class="comercial-header">

                    <div class="comercial-icon">

                        <i class="bi bi-buildings-fill"></i>

                    </div>

                    <div>

                        <strong>
                            Dados do estabelecimento
                        </strong>

                        <span>
                            Essas informações serão utilizadas
                            para configurar seu ambiente.
                        </span>

                    </div>

                </div>


                <div class="grid">


                    <div class="field">

                        <label for="empresa">
                            Empresa
                        </label>

                        <div class="input-wrap">

                            <i class="bi bi-building"></i>

                            <input
                                id="empresa"
                                name="empresa"
                                type="text"
                                placeholder="Nome da empresa"

                                value="<?= htmlspecialchars(
                                    $empresa,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>

                    </div>


                    <div class="field">

                        <label for="tipo_estabelecimento">
                            Tipo de estabelecimento
                        </label>

                        <div class="input-wrap">

                            <i class="bi bi-diagram-3"></i>

                            <select
                                id="tipo_estabelecimento"
                                name="tipo_estabelecimento"
                            >

                                <option value="">
                                    Selecione
                                </option>

                                <option
                                    value="Industria"

                                    <?= $tipo_estabelecimento === 'Industria'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Indústria
                                </option>

                                <option
                                    value="Escola"

                                    <?= $tipo_estabelecimento === 'Escola'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Escola
                                </option>

                                <option
                                    value="Fazenda"

                                    <?= $tipo_estabelecimento === 'Fazenda'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Fazenda
                                </option>

                                <option
                                    value="Empresa"

                                    <?= $tipo_estabelecimento === 'Empresa'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Empresa
                                </option>

                            </select>

                        </div>

                    </div>


                </div>

            </div>


            <div
                class="section-heading"
                style="margin-top: 38px;"
            >

                <div class="section-label">
                    ENDEREÇO
                </div>

                <h2>
                    LOCALIZAÇÃO
                </h2>

            </div>


            <div class="grid">


                <div class="field">

                    <label for="cep">
                        CEP
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-geo-alt"></i>

                        <input
                            id="cep"
                            name="cep"
                            type="text"
                            inputmode="numeric"
                            autocomplete="postal-code"
                            maxlength="9"
                            placeholder="00000-000"
                            required

                            value="<?= htmlspecialchars(
                                $cep,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="cidade">
                        Cidade
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-pin-map"></i>

                        <input
                            id="cidade"
                            name="cidade"
                            type="text"
                            autocomplete="address-level2"
                            placeholder="Sua cidade"
                            required

                            value="<?= htmlspecialchars(
                                $cidade,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field full">

                    <label for="endereco">
                        Endereço
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-signpost-2"></i>

                        <input
                            id="endereco"
                            name="endereco"
                            type="text"
                            autocomplete="street-address"
                            placeholder="Rua, avenida..."
                            required

                            value="<?= htmlspecialchars(
                                $endereco,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="numero">
                        Número
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-hash"></i>

                        <input
                            id="numero"
                            name="numero"
                            type="text"
                            placeholder="123"
                            required

                            value="<?= htmlspecialchars(
                                $numero,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="field">

                    <label for="bairro">
                        Bairro
                    </label>

                    <div class="input-wrap">

                        <i class="bi bi-map"></i>

                        <input
                            id="bairro"
                            name="bairro"
                            type="text"
                            autocomplete="address-level3"
                            placeholder="Seu bairro"
                            required

                            value="<?= htmlspecialchars(
                                $bairro,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


            </div>


            <button
                type="submit"
                class="submit"
            >

                CRIAR MINHA CONTA

            </button>


        </form>


        <div class="login-link">

            Já possui uma conta?

            <a
                href="login.php?redirect=<?= urlencode($redirect) ?>"
            >
                Entrar
            </a>

        </div>


    </section>


    <div class="footer-note">

        GRAPE DIGITAL STUDIO · CREATE THE FUTURE.

    </div>


</main>


<script>


/* =====================================================
   TIPO DE USUÁRIO
===================================================== */

const tipoRadios =
    document.querySelectorAll(
        'input[name="tipo_usuario"]'
    );


const comercialBox =
    document.getElementById(
        'comercialBox'
    );


const empresa =
    document.getElementById(
        'empresa'
    );


const estabelecimento =
    document.getElementById(
        'tipo_estabelecimento'
    );


function atualizarTipo() {

    const selecionado =
        document.querySelector(
            'input[name="tipo_usuario"]:checked'
        );


    const comercial =
        selecionado &&
        selecionado.value === 'comercial';


    comercialBox.style.display =
        comercial
            ? 'block'
            : 'none';


    empresa.required =
        comercial;


    estabelecimento.required =
        comercial;

}


tipoRadios.forEach(
    function(radio) {

        radio.addEventListener(
            'change',
            atualizarTipo
        );

    }
);


atualizarTipo();


/* =====================================================
   CEP
===================================================== */

const cepInput =
    document.getElementById(
        'cep'
    );


cepInput.addEventListener(
    'input',
    function() {

        let valor =
            cepInput.value
                .replace(/\D/g, '')
                .slice(0, 8);


        if (valor.length > 5) {

            valor =
                valor.substring(0, 5)
                + '-'
                + valor.substring(5);

        }


        cepInput.value =
            valor;

    }
);


cepInput.addEventListener(
    'blur',
    async function() {

        const cep =
            cepInput.value
                .replace(/\D/g, '');


        if (cep.length !== 8) {
            return;
        }


        try {

            const resposta =
                await fetch(
                    'https://viacep.com.br/ws/' +
                    cep +
                    '/json/'
                );


            if (!resposta.ok) {
                return;
            }


            const dados =
                await resposta.json();


            if (dados.erro) {
                return;
            }


            document.getElementById(
                'endereco'
            ).value =
                dados.logradouro || '';


            document.getElementById(
                'bairro'
            ).value =
                dados.bairro || '';


            document.getElementById(
                'cidade'
            ).value =
                dados.localidade || '';

        } catch (erro) {

            console.error(
                'Erro ao consultar CEP:',
                erro
            );

        }

    }
);


/* =====================================================
   MÁSCARA TELEFONE
===================================================== */

const telefone =
    document.getElementById(
        'telefone'
    );


telefone.addEventListener(
    'input',
    function() {

        let valor =
            telefone.value
                .replace(/\D/g, '')
                .slice(0, 11);


        if (valor.length <= 10) {

            valor =
                valor.replace(
                    /^(\d{2})(\d)/,
                    '($1) $2'
                );

            valor =
                valor.replace(
                    /(\d{4})(\d)/,
                    '$1-$2'
                );

        } else {

            valor =
                valor.replace(
                    /^(\d{2})(\d)/,
                    '($1) $2'
                );

            valor =
                valor.replace(
                    /(\d{5})(\d)/,
                    '$1-$2'
                );

        }


        telefone.value =
            valor;

    }
);

</script>


</body>

</html>