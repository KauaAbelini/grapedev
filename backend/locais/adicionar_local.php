<?php
session_start();
include("conexao.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$usuarioId = (int)$_SESSION["usuario_id"];
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
    $estado = trim($_POST["estado"] ?? "");

    $tiposValidos = array_merge(array_keys($tiposResidenciais), array_keys($tiposComerciais));

    if ($nome === "" || !in_array($tipo, $tiposValidos, true)) {
        $erro = "Preencha o nome e escolha o tipo do local.";
    } elseif ($endereco === "" || $numero === "" || $bairro === "" || $cidade === "") {
        $erro = "Preencha o endereço completo do local.";
    } elseif (in_array($tipo, array_keys($tiposComerciais), true) && $empresa === "") {
        $erro = "Informe o nome da empresa ou estabelecimento.";
    } else {
        $stmt = $conexao->prepare("INSERT INTO locais (usuario_id,nome,tipo_imovel,empresa,cep,endereco,numero,bairro,cidade,estado) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param("isssssssss", $usuarioId, $nome, $tipo, $empresa, $cep, $endereco, $numero, $bairro, $cidade, $estado);
            if ($stmt->execute()) {
                $_SESSION["local_id"] = (int)$stmt->insert_id;
                $_SESSION["local_nome"] = $nome;
                $_SESSION["local_tipo_imovel"] = $tipo;
                $stmt->close();
                header("Location: locais.php");
                exit;
            }
            $erro = "Não foi possível cadastrar o local.";
            $stmt->close();
        } else {
            $erro = "Não foi possível preparar o cadastro.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Novo local | AquaFlow</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800&display=swap">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            background: #050811;
            color: #fff;
            font-family: Nunito, Arial, sans-serif;
            padding: 35px
        }

        .box {
            max-width: 760px;
            margin: auto;
            background: linear-gradient(145deg, #11192b, #080d19);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 22px;
            padding: 28px
        }

        .eyebrow {
            font-size: 11px;
            letter-spacing: 2px;
            opacity: .5;
            font-weight: 800
        }

        .title {
            font-family: 'Bebas Neue';
            font-size: 44px;
            margin: 4px 0 22px
        }

        .title span {
            color: #7654ff
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 13px
        }

        .full {
            grid-column: 1/-1
        }

        label {
            font-size: 12px;
            font-weight: 800;
            opacity: .7;
            display: block;
            margin-bottom: 6px
        }

        input,
        select {
            width: 100%;
            padding: 13px;
            border-radius: 11px;
            border: 1px solid rgba(255, 255, 255, .09);
            background: #0a1120;
            color: #fff;
            outline: none
        }

        button,
        a {
            display: inline-block;
            padding: 12px 17px;
            border-radius: 11px;
            border: 0;
            font-weight: 800;
            text-decoration: none;
            font-family: inherit
        }

        .save {
            background: #7654ff;
            color: #fff;
            cursor: pointer
        }

        .back {
            background: rgba(255, 255, 255, .07);
            color: #fff;
            margin-right: 8px
        }

        .error {
            background: rgba(255, 70, 90, .1);
            border: 1px solid rgba(255, 70, 90, .25);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 16px;
            color: #ff9eaa
        }

        @media(max-width:600px) {
            body {
                padding: 20px
            }

            .grid {
                grid-template-columns: 1fr
            }

            .full {
                grid-column: auto
            }
        }
    </style>
</head>

<body>
    <div class="box">
        <div class="eyebrow">AQUAFLOW / NOVO LOCAL</div>
        <h1 class="title">Adicionar <span>local</span></h1>
        <?php if ($erro): ?><div class="error"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
        <form method="POST">
            <div class="grid">
                <div class="full"><label>Nome do local</label><input name="nome" required placeholder="Ex.: Matriz, Fábrica, Fazenda..." value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"></div>
                <div class="full"><label>Tipo do imóvel</label><select name="tipo_imovel" required>
                        <option value="">Selecione...</option>
                        <optgroup label="Residencial"><?php foreach ($tiposResidenciais as $k => $v): ?><option value="<?php echo $k; ?>" <?php echo (($_POST['tipo_imovel'] ?? '') === $k ? 'selected' : ''); ?>><?php echo $v; ?></option><?php endforeach; ?></optgroup>
                        <optgroup label="Comercial / Industrial"><?php foreach ($tiposComerciais as $k => $v): ?><option value="<?php echo $k; ?>" <?php echo (($_POST['tipo_imovel'] ?? '') === $k ? 'selected' : ''); ?>><?php echo $v; ?></option><?php endforeach; ?></optgroup>
                    </select></div>
                <div><label>Empresa / estabelecimento</label><input name="empresa" value="<?php echo htmlspecialchars($_POST['empresa'] ?? ''); ?>"></div>
                <div><label>CEP</label><input name="cep" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>"></div>
                <div class="full"><label>Endereço</label><input name="endereco" required value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>"></div>
                <div><label>Número</label><input name="numero" required value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>"></div>
                <div><label>Bairro</label><input name="bairro" required value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>"></div>
                <div><label>Cidade</label><input name="cidade" required value="<?php echo htmlspecialchars($_POST['cidade'] ?? ($_SESSION['usuario_cidade'] ?? '')); ?>"></div>
                <div><label>Estado</label><input name="estado" value="<?php echo htmlspecialchars($_POST['estado'] ?? ''); ?>" placeholder="SP"></div>
            </div>
            <div style="margin-top:22px"><a class="back" href="locais.php">Cancelar</a><button class="save" type="submit">Adicionar local</button></div>
        </form>
    </div>
</body>

</html>