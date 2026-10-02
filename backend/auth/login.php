<?php

require_once 'auth.php';
require_once 'conexao.php';

iniciarSessao();

$redirect = destinoSeguro(
    $_GET['redirect']
    ?? $_POST['redirect']
    ?? 'dashboard.php'
);

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$erro = '';

$email = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim(
        (string)($_POST['email'] ?? '')
    );

    $senha = (string)(
        $_POST['senha'] ?? ''
    );

    $tipo = (string)(
        $_POST['tipo_usuario'] ?? ''
    );

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } elseif ($senha === '') {

        $erro = 'Digite sua senha.';

    } elseif (
        !in_array(
            $tipo,
            ['residencial', 'comercial'],
            true
        )
    ) {

        $erro = 'Selecione o tipo de uso.';

    } else {

        $stmt = $conexao->prepare(
            "SELECT
                id,
                nome,
                email,
                senha,
                cidade,
                telefone,
                tipo_usuario,
                tipo_imovel,
                endereco,
                numero,
                bairro,
                cep,
                empresa,
                tipo_estabelecimento
             FROM usuarios
             WHERE email = ?
             AND tipo_usuario = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            'ss',
            $email,
            $tipo
        );

        $stmt->execute();

        $usuario =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        $senhaOk = false;

        if ($usuario) {

            $senhaBanco =
                (string)$usuario['senha'];

            $senhaOk =
                password_verify(
                    $senha,
                    $senhaBanco
                )
                ||
                hash_equals(
                    $senhaBanco,
                    $senha
                );

            if (
                $senhaOk &&
                !password_get_info(
                    $senhaBanco
                )['algo']
            ) {

                $novoHash =
                    password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );

                $up = $conexao->prepare(
                    "UPDATE usuarios
                     SET senha = ?
                     WHERE id = ?"
                );

                $up->bind_param(
                    'si',
                    $novoHash,
                    $usuario['id']
                );

                $up->execute();

                $up->close();
            }
        }

        if (
            !$usuario ||
            !$senhaOk
        ) {

            $erro =
                'E-mail, senha ou tipo de uso incorreto.';

        } else {

            session_regenerate_id(true);

            $_SESSION['usuario_id'] =
                (int)$usuario['id'];

            $_SESSION['usuario_nome'] =
                $usuario['nome'];

            $_SESSION['usuario_email'] =
                $usuario['email'];

            $_SESSION['usuario_cidade'] =
                $usuario['cidade'];

            $_SESSION['usuario_telefone'] =
                $usuario['telefone'];

            $_SESSION['usuario_tipo'] =
                $usuario['tipo_usuario'];

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

            header(
                'Location: ' . $redirect
            );

            exit;
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

<title>GRAPE — Entrar</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

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

    --border:
        rgba(255,255,255,.08);

    --input:
        rgba(10,8,14,.72);
}


* {
    box-sizing: border-box;
}


html {
    min-height: 100%;
}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        'Nunito',
        sans-serif;

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


/* =====================================================
   FUNDO
===================================================== */

.background-glow {

    position: fixed;

    width: 400px;
    height: 400px;

    border-radius: 50%;

    background:
        rgba(130,71,229,.07);

    filter:
        blur(110px);

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


/* =====================================================
   TOPO
===================================================== */

.topbar {

    position: relative;

    width:
        min(1050px, calc(100% - 36px));

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

    color:
        var(--purple-light);
}


.back-button {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 8px 14px;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius: 25px;

    background:
        rgba(255,255,255,.035);

    color: #ddd7e5;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none;

    transition: .25s ease;
}


.back-button:hover {

    color: white;

    border-color:
        rgba(130,71,229,.4);

    background:
        rgba(130,71,229,.09);

    transform:
        translateX(-2px);
}


/* =====================================================
   CONTAINER
===================================================== */

.page {

    position: relative;

    width:
        min(520px, calc(100% - 28px));

    margin:
        8px auto 45px;

    z-index: 2;
}


/* =====================================================
   INTRO
===================================================== */

.intro {

    margin:
        0 auto 25px;

    text-align: center;
}


.intro-small {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 9px;

    color:
        var(--purple-light);

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

    background:
        var(--purple);
}


h1 {

    margin: 0;

    font-family:
        'Bebas Neue',
        sans-serif;

    font-size:
        clamp(48px, 7vw, 64px);

    line-height: .86;

    letter-spacing: .8px;

    color: white;

    text-transform: uppercase;
}


h1 span {

    color:
        var(--purple);
}


.intro p {

    max-width: 430px;

    margin:
        11px auto 0;

    color:
        var(--muted);

    font-size: 12px;

    line-height: 1.55;
}


/* =====================================================
   CARD
===================================================== */

.login-card {

    position: relative;

    padding: 27px;

    border-radius: 21px;

    border:
        1px solid
        var(--border);

    background:

        linear-gradient(
            145deg,
            rgba(30,25,40,.95),
            rgba(14,12,19,.95)
        );

    box-shadow:

        0 25px 65px
        rgba(0,0,0,.42),

        0 0 60px
        rgba(130,71,229,.04);

    overflow: hidden;
}


.login-card::before {

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


/* =====================================================
   ERRO
===================================================== */

.error-box {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 18px;

    padding: 11px 13px;

    border-radius: 10px;

    border:
        1px solid
        rgba(248,113,113,.18);

    background:
        rgba(127,29,29,.13);

    color:
        #fca5a5;

    font-size: 10px;

    font-weight: 700;
}


.error-box i {

    font-size: 14px;
}


/* =====================================================
   SEÇÕES
===================================================== */

.section-label {

    margin-bottom: 5px;

    color:
        var(--purple-light);

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;

    text-transform: uppercase;
}


.section-title {

    margin: 0 0 15px;

    font-family:
        'Bebas Neue',
        sans-serif;

    font-size: 27px;

    letter-spacing: .4px;

    color: white;
}


/* =====================================================
   TIPO DE USO
===================================================== */

.types {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 10px;

    margin-bottom: 25px;
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

    min-height: 74px;

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 13px;

    border-radius: 14px;

    border:
        1px solid
        rgba(255,255,255,.07);

    background:

        linear-gradient(
            145deg,
            rgba(30,26,39,.92),
            rgba(14,12,20,.92)
        );

    color: #cbd5e4;

    cursor: pointer;

    transition: .25s ease;
}


.type:hover {

    transform:
        translateY(-2px);

    border-color:
        rgba(130,71,229,.35);
}


.types input:checked + .type {

    border-color:
        var(--purple);

    background:

        linear-gradient(
            145deg,
            rgba(130,71,229,.15),
            rgba(74,36,128,.09)
        );

    box-shadow:
        0 0 0 1px
        rgba(130,71,229,.08);
}


.type-icon {

    flex-shrink: 0;

    width: 40px;
    height: 40px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background:
        rgba(130,71,229,.10);

    color:
        var(--purple-light);

    font-size: 17px;
}


.types input:checked + .type .type-icon {

    background:
        var(--purple);

    color:
        white;
}


.type-name {

    display: block;

    margin-bottom: 2px;

    color: white;

    font-size: 12px;

    font-weight: 900;
}


.type-description {

    display: block;

    color:
        #847d8e;

    font-size: 9px;

    line-height: 1.35;

    font-weight: 600;
}


/* =====================================================
   CAMPOS
===================================================== */

.field {

    margin-bottom: 15px;
}


.field label {

    display: block;

    margin-bottom: 6px;

    color:
        #cfc8da;

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

    transform:
        translateY(-50%);

    color:
        #756c81;

    font-size: 13px;

    pointer-events: none;

    transition: .2s ease;
}


input {

    width: 100%;

    height: 45px;

    padding:
        0 14px 0 39px;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius: 10px;

    outline: none;

    background:
        var(--input);

    color: white;

    font-family:
        'Nunito',
        sans-serif;

    font-size: 11px;

    font-weight: 600;

    transition: .2s ease;
}


input::placeholder {

    color:
        #5e5669;
}


input:hover {

    border-color:
        rgba(130,71,229,.28);
}


input:focus {

    border-color:
        var(--purple);

    box-shadow:
        0 0 0 2px
        rgba(130,71,229,.07);
}


.input-wrap:focus-within i {

    color:
        var(--purple-light);
}


/* =====================================================
   BOTÃO
===================================================== */

.submit {

    position: relative;

    width: 100%;

    height: 49px;

    margin-top: 8px;

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

    font-family:
        'Nunito',
        sans-serif;

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

    transform:
        translateY(-2px);

    box-shadow:

        0 16px 32px
        rgba(74,36,128,.42);
}


.submit i {

    margin-left: 5px;

    transition: .2s ease;
}


.submit:hover i {

    transform:
        translateX(3px);
}


/* =====================================================
   CADASTRO
===================================================== */

.register {

    margin-top: 17px;

    text-align: center;

    color:
        #716a7b;

    font-size: 10px;

    font-weight: 600;
}


.register a {

    color:
        var(--purple-light);

    text-decoration: none;

    font-weight: 900;
}


.register a:hover {

    color: white;
}


/* =====================================================
   RODAPÉ
===================================================== */

.footer-note {

    margin-top: 20px;

    text-align: center;

    color:
        #5f5869;

    font-size: 8px;

    font-weight: 700;

    letter-spacing: .5px;
}


/* =====================================================
   RESPONSIVO
===================================================== */

@media (max-width: 550px) {

    .topbar {

        width:
            calc(100% - 28px);

        padding:
            17px 0;
    }


    .page {

        width:
            calc(100% - 20px);
    }


    .login-card {

        padding:
            21px 17px;

        border-radius:
            18px;
    }


    .types {

        grid-template-columns:
            1fr;

        gap: 8px;
    }


    .type {

        min-height:
            70px;
    }


    h1 {

        font-size:
            50px;
    }
}


@media (max-width: 420px) {

    .logo-text {

        font-size:
            16px;
    }


    .logo img {

        width:
            27px;

        height:
            27px;
    }


    .back-button {

        padding:
            7px 10px;
    }


    .back-button span {

        display:
            none;
    }


    h1 {

        font-size:
            46px;
    }


    .intro p {

        font-size:
            11px;
    }


    .login-card {

        padding:
            19px 14px;
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
            BEM-VINDO <span>DE VOLTA.</span>
        </h1>

        <p>
            Acesse sua conta para continuar
            sua experiência no ecossistema GRAPE.
        </p>

    </section>


    <section class="login-card">


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


        <form method="POST">


            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars(
                    $redirect,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
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

                        <?= $tipo === 'residencial'
                            ? 'checked'
                            : '' ?>

                        required
                    >

                    <span class="type">

                        <span class="type-icon">

                            <i class="bi bi-house-door-fill"></i>

                        </span>

                        <span>

                            <span class="type-name">
                                Residencial
                            </span>

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

                        <?= $tipo === 'comercial'
                            ? 'checked'
                            : '' ?>

                        required
                    >

                    <span class="type">

                        <span class="type-icon">

                            <i class="bi bi-building-fill"></i>

                        </span>

                        <span>

                            <span class="type-name">
                                Comercial
                            </span>

                            <span class="type-description">
                                Empresa ou indústria
                            </span>

                        </span>

                    </span>

                </label>


            </div>


            <div class="section-label">
                ACESSO
            </div>

            <h2 class="section-title">
                SEUS DADOS
            </h2>


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
                        value="<?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
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
                        autocomplete="current-password"
                        placeholder="Digite sua senha"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="submit"
            >

                ENTRAR NA MINHA CONTA

                <i class="bi bi-arrow-right"></i>

            </button>


        </form>


        <div class="register">

            Ainda não possui uma conta?

            <a
                href="cadastro.php?redirect=<?= urlencode($redirect) ?>"
            >
                Criar cadastro
            </a>

        </div>


    </section>


    <div class="footer-note">

        GRAPE DIGITAL STUDIO · CREATE THE FUTURE.

    </div>


</main>

</body>

</html>