<?php
session_start();

require_once __DIR__ . '/../database/conexao.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$erro = "";

$tiposResidenciais = [
    "casa" => "Casa",
    "predio_residencial" => "Prédio residencial"
];

$tiposComerciais = [
    "empresa" => "Empresa / Escritório",
    "industria" => "Indústria",
    "predio_comercial" => "Prédio comercial / industrial",
    "escola" => "Escola",
    "agro" => "Agronegócio"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $tipo = trim($_POST["tipo_imovel"] ?? "");
    $empresa = trim($_POST["empresa"] ?? "");
    $cep = trim($_POST["cep"] ?? "");
    $endereco = trim($_POST["endereco"] ?? "");
    $numero = trim($_POST["numero"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $estado = strtoupper(trim($_POST["estado"] ?? ""));

    $tiposValidos = array_merge(
        array_keys($tiposResidenciais),
        array_keys($tiposComerciais)
    );

    if (
        $nome === "" ||
        !in_array($tipo, $tiposValidos, true)
    ) {
        $erro = "Preencha o nome e escolha o tipo do local.";

    } elseif (
        $endereco === "" ||
        $numero === "" ||
        $bairro === "" ||
        $cidade === ""
    ) {
        $erro = "Preencha o endereço completo do local.";

    } elseif (
        in_array($tipo, array_keys($tiposComerciais), true) &&
        $empresa === ""
    ) {
        $erro = "Informe o nome da empresa ou estabelecimento.";

    } else {
        $sql = "INSERT INTO locais (
                    usuario_id,
                    nome,
                    tipo_imovel,
                    empresa,
                    cep,
                    endereco,
                    numero,
                    bairro,
                    cidade,
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($sql);

        if ($stmt) {
            $stmt->bind_param(
                "isssssssss",
                $usuarioId,
                $nome,
                $tipo,
                $empresa,
                $cep,
                $endereco,
                $numero,
                $bairro,
                $cidade,
                $estado
            );

            if ($stmt->execute()) {
                $novoLocalId = (int) $stmt->insert_id;

                $_SESSION["local_id"] = $novoLocalId;
                $_SESSION["local_nome"] = $nome;
                $_SESSION["local_tipo_imovel"] = $tipo;

                $stmt->close();

                // Volta para a lista de locais.
                header("Location: locais.php");
                exit;
            }

            error_log("Erro ao cadastrar local: " . $stmt->error);

            $erro = "Não foi possível cadastrar o local. Verifique os dados e tente novamente.";

            $stmt->close();
        } else {
            error_log("Erro ao preparar cadastro: " . $conexao->error);

            $erro = "Não foi possível preparar o cadastro. Tente novamente.";
        }
    }
}

function escapar($valor)
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo local | AquaFlow</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800&display=swap">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

    body {
        width: 100%;
        height: 100vh;
        margin: 0;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;

        background:
            linear-gradient(
                90deg,
                rgba(5, 3, 13, 0.45),
                rgba(5, 3, 13, 0.82)
            ),
            url("../../storage/images/Nex_cadastro.png") center center / cover no-repeat;

        color: #fff;
        font-family: 'Nunito', Arial, sans-serif;
        overflow: hidden;
    }

        .box {
    width: 100%;
    max-width: 760px;
    max-height: calc(100vh - 40px);
    margin: 0 auto;
    padding: 24px;

    background: rgba(9, 10, 25, 0.88);
    border: 1px solid rgba(150, 60, 255, 0.28);
    border-radius: 20px;

    box-shadow:
        0 0 35px rgba(112, 45, 255, 0.12),
        inset 0 0 25px rgba(112, 45, 255, 0.03);

    backdrop-filter: blur(12px);
    overflow: hidden;
}

        .eyebrow {
            font-size: 11px;
            letter-spacing: 2px;
            opacity: .5;
            font-weight: 800;
        }

        .title {
            font-family: 'Bebas Neue', Impact, sans-serif;
            font-size: 44px;
            font-weight: 400;
            margin: 4px 0 22px;
        }

        .title span {
            color: #7654ff;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 13px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 800;
            opacity: .7;
        }

        input,
        select {
            width: 100%;
            padding: 13px;
            border-radius: 11px;
            border: 1px solid rgba(255, 255, 255, .09);
            background: #0a1120;
            color: #fff;
            outline: none;
            font: inherit;
        }

        input:focus,
        select:focus {
            border-color: #7654ff;
        }

        select option,
        select optgroup {
            background: #0a1120;
            color: #fff;
        }

        button,
        a {
            display: inline-block;
            padding: 12px 17px;
            border-radius: 11px;
            border: 0;
            font-weight: 800;
            text-decoration: none;
            font-family: inherit;
            font-size: 13px;
        }

        .save {
            background: #7654ff;
            color: #fff;
            cursor: pointer;
            transition: background .2s;
        }

        .save:hover {
            background: #6240e6;
        }

        .back {
            background: rgba(255, 255, 255, .07);
            color: #fff;
            margin-right: 8px;
        }

        .back:hover {
            background: rgba(255, 255, 255, .12);
        }

        .error {
            background: rgba(255, 70, 90, .1);
            border: 1px solid rgba(255, 70, 90, .25);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 16px;
            color: #ff9eaa;
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

       @media (max-width: 600px) {
    html,
    body {
        min-height: 100%;
        height: auto;
    }

    body {
        padding: 14px;
        overflow-y: auto;
        scrollbar-width: none;
    }

    body::-webkit-scrollbar {
        display: none;
    }

    .box {
        max-height: none;
        padding: 20px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .title {
        font-size: 38px;
    }
}
    </style>
</head>

<body>
    <main class="box">

        <div class="eyebrow">AQUAFLOW / NOVO LOCAL</div>

        <h1 class="title">
            Adicionar <span>local</span>
        </h1>

        <?php if ($erro !== ""): ?>
            <div class="error">
                <?php echo escapar($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid">

                <div class="full">
                    <label for="nome">Nome do local</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        required
                        placeholder="Ex.: Matriz, Fábrica, Fazenda..."
                        value="<?php echo escapar($_POST['nome'] ?? ''); ?>">
                </div>

                <div class="full">
                    <label for="tipo_imovel">Tipo do imóvel</label>

                    <select id="tipo_imovel" name="tipo_imovel" required>
                        <option value="">Selecione...</option>

                        <optgroup label="Residencial">
                            <?php foreach ($tiposResidenciais as $k => $v): ?>
                                <option
                                    value="<?php echo escapar($k); ?>"
                                    <?php echo (($_POST['tipo_imovel'] ?? '') === $k) ? 'selected' : ''; ?>>
                                    <?php echo escapar($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>

                        <optgroup label="Comercial / Industrial">
                            <?php foreach ($tiposComerciais as $k => $v): ?>
                                <option
                                    value="<?php echo escapar($k); ?>"
                                    <?php echo (($_POST['tipo_imovel'] ?? '') === $k) ? 'selected' : ''; ?>>
                                    <?php echo escapar($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label for="empresa">Empresa / estabelecimento</label>
                    <input
                        type="text"
                        id="empresa"
                        name="empresa"
                        value="<?php echo escapar($_POST['empresa'] ?? ''); ?>">
                </div>

                <div>
                    <label for="cep">CEP</label>
                    <input
                        type="text"
                        id="cep"
                        name="cep"
                        value="<?php echo escapar($_POST['cep'] ?? ''); ?>"
                        placeholder="00000-000">
                </div>

                <div class="full">
                    <label for="endereco">Endereço</label>
                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                        required
                        value="<?php echo escapar($_POST['endereco'] ?? ''); ?>">
                </div>

                <div>
                    <label for="numero">Número</label>
                    <input
                        type="text"
                        id="numero"
                        name="numero"
                        required
                        value="<?php echo escapar($_POST['numero'] ?? ''); ?>">
                </div>

                <div>
                    <label for="bairro">Bairro</label>
                    <input
                        type="text"
                        id="bairro"
                        name="bairro"
                        required
                        value="<?php echo escapar($_POST['bairro'] ?? ''); ?>">
                </div>

                <div>
                    <label for="cidade">Cidade</label>
                    <input
                        type="text"
                        id="cidade"
                        name="cidade"
                        required
                        value="<?php echo escapar($_POST['cidade'] ?? ($_SESSION['usuario_cidade'] ?? '')); ?>">
                </div>

                <div>
                    <label for="estado">Estado</label>
                    <input
                        type="text"
                        id="estado"
                        name="estado"
                        maxlength="2"
                        value="<?php echo escapar($_POST['estado'] ?? ''); ?>"
                        placeholder="SP">
                </div>

            </div>

            <div class="form-actions">
                <a class="back" href="locais.php">Cancelar</a>
                <button class="save" type="submit">
                    Adicionar local
                </button>
            </div>
        </form>

    </main>
</body>

</html>