<?php
session_start();
include("conexao.php");
include("local_contexto.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$erro = "";

$tipos = [
    "casa" => "Casa",
    "predio_residencial" => "Prédio residencial",
    "empresa" => "Empresa / Escritório",
    "industria" => "Indústria",
    "predio_comercial" => "Prédio comercial / industrial",
    "escola" => "Escola",
    "agro" => "Agronegócio / Fazenda"
];

$tiposComNome = ["empresa", "industria", "predio_comercial", "escola", "agro"];

$dados = [
    "nome" => trim($_POST["nome"] ?? ""),
    "tipo_imovel" => trim($_POST["tipo_imovel"] ?? ""),
    "empresa" => trim($_POST["empresa"] ?? ""),
    "cep" => trim($_POST["cep"] ?? ""),
    "endereco" => trim($_POST["endereco"] ?? ""),
    "numero" => trim($_POST["numero"] ?? ""),
    "bairro" => trim($_POST["bairro"] ?? ""),
    "cidade" => trim($_POST["cidade"] ?? ($_SESSION["usuario_cidade"] ?? "")),
    "estado" => strtoupper(trim($_POST["estado"] ?? ""))
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($dados["nome"] === "") {
        $erro = "Digite um nome para identificar este local.";
    } elseif (!isset($tipos[$dados["tipo_imovel"]])) {
        $erro = "Selecione o tipo do imóvel.";
    } elseif ($dados["cep"] === "") {
        $erro = "Informe o CEP.";
    } elseif ($dados["endereco"] === "") {
        $erro = "Informe o endereço.";
    } elseif ($dados["numero"] === "") {
        $erro = "Informe o número.";
    } elseif ($dados["bairro"] === "") {
        $erro = "Informe o bairro.";
    } elseif ($dados["cidade"] === "") {
        $erro = "Informe a cidade.";
    } elseif ($dados["estado"] === "") {
        $erro = "Informe o estado. Ex.: SP.";
    } elseif (strlen($dados["estado"]) !== 2) {
        $erro = "O estado deve ter 2 letras. Ex.: SP.";
    } elseif (in_array($dados["tipo_imovel"], $tiposComNome, true) && $dados["empresa"] === "") {
        $erro = "Informe o nome da empresa ou propriedade.";
    }

    if ($erro === "") {

        $tipoEstabelecimento = null;

        switch ($dados["tipo_imovel"]) {
            case "empresa":
                $tipoEstabelecimento = "Empresa / Escritório";
                break;
            case "industria":
                $tipoEstabelecimento = "Indústria";
                break;
            case "predio_comercial":
                $tipoEstabelecimento = "Prédio comercial / industrial";
                break;
            case "escola":
                $tipoEstabelecimento = "Escola";
                break;
            case "agro":
                $tipoEstabelecimento = "Agronegócio / Fazenda";
                break;
        }

        $sql = "INSERT INTO locais
            (usuario_id, nome, tipo_imovel, empresa, cep, endereco, numero, bairro, cidade, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {
            $erro = "Erro no banco de dados: " . $conexao->error;
        } else {

            $stmt->bind_param(
                "isssssssss",
                $usuarioId,
                $dados["nome"],
                $dados["tipo_imovel"],
                $dados["empresa"],
                $dados["cep"],
                $dados["endereco"],
                $dados["numero"],
                $dados["bairro"],
                $dados["cidade"],
                $dados["estado"]
            );

            if ($stmt->execute()) {
                $_SESSION["local_id"] = (int) $stmt->insert_id;
                $_SESSION["local_nome"] = $dados["nome"];
                $_SESSION["local_tipo_imovel"] = $dados["tipo_imovel"];
                $_SESSION["local_empresa"] = $dados["empresa"];

                $stmt->close();
                header("Location: dashboard.php");
                exit;
            }

            $erro = "Erro ao salvar o local: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar local | AquaFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            min-height: 100vh;
            background: #050811;
            color: #fff;
            font-family: Nunito, Arial, sans-serif;
            padding: 24px
        }

        .box {
            width: min(950px, 100%);
            margin: auto;
            background: linear-gradient(145deg, #11192b, #080d19);
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 28px;
            padding: 34px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .35)
        }

        .eyebrow {
            font-size: 14px;
            letter-spacing: 2px;
            color: #9298a5;
            font-weight: 900;
            margin-bottom: 10px
        }

        .title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 62px;
            line-height: .95;
            letter-spacing: 1px;
            margin-bottom: 26px
        }

        .title span {
            color: #7654ff
        }

        .error {
            background: rgba(255, 70, 90, .10);
            border: 1px solid rgba(255, 70, 90, .38);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: #ff8393;
            font-size: 16px;
            font-weight: 700
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px
        }

        .full {
            grid-column: 1/-1
        }

        .field {
            margin-bottom: 0
        }

        label {
            font-size: 15px;
            font-weight: 900;
            color: #b8bdc8;
            display: block;
            margin-bottom: 8px
        }

        .help {
            font-size: 12px;
            color: #6f7786;
            margin-top: 7px;
            line-height: 1.45
        }

        input,
        select {
            width: 100%;
            height: 54px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid #273043;
            background: #080f1d;
            color: #fff;
            outline: none;
            font: 600 16px Nunito, Arial, sans-serif;
            transition: .2s
        }

        input::placeholder {
            color: #5f6878
        }

        input:focus,
        select:focus {
            border-color: #7654ff;
            box-shadow: 0 0 0 3px rgba(118, 84, 255, .12)
        }

        select {
            cursor: pointer
        }

        select option,
        select optgroup {
            background: #0d1422;
            color: #fff
        }

        .info {
            grid-column: 1/-1;
            background: rgba(118, 84, 255, .08);
            border: 1px solid rgba(118, 84, 255, .20);
            border-radius: 14px;
            padding: 14px 16px;
            color: #c2baff;
            font-size: 14px;
            line-height: 1.5
        }

        #campoEmpresa {
            display: none
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 26px
        }

        .button {
            border: 0;
            border-radius: 14px;
            padding: 15px 22px;
            font: 900 16px Nunito, Arial, sans-serif;
            text-decoration: none;
            cursor: pointer
        }

        .back {
            background: #1a2333;
            color: #fff
        }

        .save {
            background: #7654ff;
            color: #fff
        }

        .save:hover {
            background: #6946f1
        }

        @media(max-width:700px) {
            body {
                padding: 10px
            }

            .box {
                padding: 24px 18px;
                border-radius: 22px
            }

            .title {
                font-size: 50px
            }

            .grid {
                grid-template-columns: 1fr
            }

            .full,
            .info {
                grid-column: auto
            }

            .buttons {
                flex-direction: column
            }

            .button {
                text-align: center
            }
        }
    </style>
</head>

<body>
    <div class="box">
        <div class="eyebrow">AQUAFLOW / NOVO LOCAL</div>
        <h1 class="title">ADICIONAR <span>LOCAL</span></h1>

        <?php if ($erro !== ""): ?>
            <div class="error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <div class="grid">

                <div class="field full">
                    <label for="nome">Nome do local</label>
                    <input id="nome" name="nome" type="text" required value="<?php echo htmlspecialchars($dados["nome"]); ?>" placeholder="Ex.: Casa Principal, Escritório Centro, Fábrica">
                    <div class="help">É o nome que aparecerá quando você trocar de local no AquaFlow.</div>
                </div>

                <div class="field full">
                    <label for="tipo_imovel">Tipo do imóvel</label>
                    <select id="tipo_imovel" name="tipo_imovel" required>
                        <option value="">Selecione o tipo do imóvel</option>
                        <optgroup label="Residencial">
                            <option value="casa" <?php echo $dados["tipo_imovel"] === "casa" ? "selected" : ""; ?>>Casa</option>
                            <option value="predio_residencial" <?php echo $dados["tipo_imovel"] === "predio_residencial" ? "selected" : ""; ?>>Prédio residencial</option>
                        </optgroup>
                        <optgroup label="Comercial / Industrial">
                            <option value="empresa" <?php echo $dados["tipo_imovel"] === "empresa" ? "selected" : ""; ?>>Empresa / Escritório</option>
                            <option value="industria" <?php echo $dados["tipo_imovel"] === "industria" ? "selected" : ""; ?>>Indústria</option>
                            <option value="predio_comercial" <?php echo $dados["tipo_imovel"] === "predio_comercial" ? "selected" : ""; ?>>Prédio comercial / industrial</option>
                            <option value="escola" <?php echo $dados["tipo_imovel"] === "escola" ? "selected" : ""; ?>>Escola</option>
                            <option value="agro" <?php echo $dados["tipo_imovel"] === "agro" ? "selected" : ""; ?>>Agronegócio / Fazenda</option>
                        </optgroup>
                    </select>
                </div>

                <div class="info" id="infoTipo">Selecione o tipo do imóvel para entender o que o AquaFlow vai representar no 3D.</div>

                <div class="field full" id="campoEmpresa">
                    <label for="empresa" id="labelEmpresa">Nome da empresa ou propriedade</label>
                    <input id="empresa" name="empresa" type="text" value="<?php echo htmlspecialchars($dados["empresa"]); ?>" placeholder="Ex.: GrapeDev Tecnologia">
                    <div class="help" id="helpEmpresa">Use este campo para informar o nome da empresa, escola, indústria ou propriedade.</div>
                </div>

                <div class="field">
                    <label for="cep">CEP</label>
                    <input id="cep" name="cep" type="text" maxlength="9" value="<?php echo htmlspecialchars($dados["cep"]); ?>" placeholder="Ex.: 00000-000" required>
                </div>

                <div class="field">
                    <label for="estado">Estado</label>
                    <input id="estado" name="estado" type="text" maxlength="2" value="<?php echo htmlspecialchars($dados["estado"]); ?>" placeholder="Ex.: Estado" required>
                </div>

                <div class="field full">
                    <label for="endereco">Endereço</label>
                    <input id="endereco" name="endereco" type="text" value="<?php echo htmlspecialchars($dados["endereco"]); ?>" placeholder="Ex.: Endereço " required>
                </div>

                <div class="field">
                    <label for="numero">Número</label>
                    <input id="numero" name="numero" type="text" value="<?php echo htmlspecialchars($dados["numero"]); ?>" placeholder="Ex.: Número" required>
                </div>

                <div class="field">
                    <label for="bairro">Bairro</label>
                    <input id="bairro" name="bairro" type="text" value="<?php echo htmlspecialchars($dados["bairro"]); ?>" placeholder="Ex.: Bairro" required>
                </div>

                <div class="field full">
                    <label for="cidade">Cidade</label>
                    <input id="cidade" name="cidade" type="text" value="<?php echo htmlspecialchars($dados["cidade"]); ?>" placeholder="Ex.: Cidade" required>
                </div>
            </div>

            <div class="buttons">
                <a class="button back" href="dashboard.php">Cancelar</a>
                <button class="button save" type="submit">Adicionar local</button>
            </div>
        </form>
    </div>

    <script>
        const tipo = document.getElementById("tipo_imovel");
        const campoEmpresa = document.getElementById("campoEmpresa");
        const empresa = document.getElementById("empresa");
        const infoTipo = document.getElementById("infoTipo");
        const labelEmpresa = document.getElementById("labelEmpresa");
        const helpEmpresa = document.getElementById("helpEmpresa");

        const configuracoes = {
            casa: {
                info: "Casa residencial. O 3D deste local será uma casa e ficará ligado a este local.",
                mostrarEmpresa: false
            },
            predio_residencial: {
                info: "Prédio residencial. O 3D deste local será um edifício residencial com apartamentos.",
                mostrarEmpresa: false
            },
            empresa: {
                info: "Empresa / escritório. O 3D deste local será um ambiente corporativo com copa, banheiros e pontos hidráulicos.",
                mostrarEmpresa: true,
                label: "Nome da empresa",
                placeholder: "Ex.: GrapeDev Tecnologia",
                ajuda: "Digite o nome da empresa que ocupa este local."
            },
            industria: {
                info: "Indústria. O 3D deste local será um ambiente industrial com sua rede hidráulica.",
                mostrarEmpresa: true,
                label: "Nome da indústria",
                placeholder: "Ex.: GrapeDev Indústria",
                ajuda: "Digite o nome da indústria que ocupa este local."
            },
            predio_comercial: {
                info: "Prédio comercial / industrial. O 3D deste local será um edifício com vários pavimentos.",
                mostrarEmpresa: true,
                label: "Nome do empreendimento ou empresa",
                placeholder: "Ex.: Edifício Grape Tower",
                ajuda: "Digite o nome que identifica este prédio."
            },
            escola: {
                info: "Escola. O 3D deste local será um ambiente escolar com pontos hidráulicos próprios.",
                mostrarEmpresa: true,
                label: "Nome da escola",
                placeholder: "Ex.: Escola Municipal João Silva",
                ajuda: "Digite o nome da escola."
            },
            agro: {
                info: "Agronegócio / fazenda. O 3D deste local representará uma propriedade rural com pontos de consumo e irrigação.",
                mostrarEmpresa: true,
                label: "Nome da propriedade",
                placeholder: "Ex.: Fazenda São José",
                ajuda: "Digite o nome da fazenda ou propriedade."
            }
        };

        function atualizarTipo() {
            const config = configuracoes[tipo.value];

            if (!config) {
                campoEmpresa.style.display = "none";
                empresa.required = false;
                infoTipo.textContent = "Selecione o tipo do imóvel para entender o que o AquaFlow vai representar no 3D.";
                return;
            }

            infoTipo.textContent = config.info;

            if (config.mostrarEmpresa) {
                campoEmpresa.style.display = "block";
                empresa.required = true;
                labelEmpresa.textContent = config.label;
                empresa.placeholder = config.placeholder;
                helpEmpresa.textContent = config.ajuda;
            } else {
                campoEmpresa.style.display = "none";
                empresa.required = false;
                empresa.value = "";
            }
        }

        tipo.addEventListener("change", atualizarTipo);
        atualizarTipo();

        document.getElementById("cep").addEventListener("input", function() {
            let v = this.value.replace(/\D/g, "").slice(0, 8);
            if (v.length > 5) v = v.slice(0, 5) + "-" + v.slice(5);
            this.value = v;
        });

        document.getElementById("estado").addEventListener("input", function() {
            this.value = this.value.replace(/[^a-zA-Z]/g, "").slice(0, 2).toUpperCase();
        });
    </script>
</body>

</html>