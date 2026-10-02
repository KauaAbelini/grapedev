<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: auth/login.php");
    exit();
}

include("database/conexao.php");

$usuario_id = $_SESSION["usuario_id"];

$mensagem = "";
$erro = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");

    if ($nome === "" || $email === "") {

        $erro = "Nome e email são obrigatórios.";
    } else {

        $stmt = $conexao->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            AND id != ?
        ");

        $stmt->bind_param(
            "si",
            $email,
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $erro = "Esse email já está sendo utilizado.";
        } else {

            $stmt->close();

            $stmt = $conexao->prepare("
                UPDATE usuarios
                SET
                    nome = ?,
                    email = ?,
                    cidade = ?,
                    telefone = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssi",
                $nome,
                $email,
                $cidade,
                $telefone,
                $usuario_id
            );

            if ($stmt->execute()) {

                // Atualiza a sessão
                $_SESSION["usuario_nome"] = $nome;
                $_SESSION["usuario_email"] = $email;
                $_SESSION["usuario_cidade"] = $cidade;
                $_SESSION["usuario_telefone"] = $telefone;

                $mensagem =
                    "Dados atualizados com sucesso!";
            } else {
                $erro =
                    "Não foi possível atualizar os dados.";
            }
        }
        $stmt->close();
    }
}

$stmt = $conexao->prepare("
    SELECT
        nome,
        email,
        cidade,
        telefone
    FROM usuarios
    WHERE id = ?
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();


$nome = $usuario["nome"] ?? "Usuário";
$email = $usuario["email"] ?? "";
$cidade = $usuario["cidade"] ?? "";
$telefone = $usuario["telefone"] ?? "";

$primeiraLetra =
    strtoupper(substr($nome, 0, 1));

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Configurações | AquaFlow</title>

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="dashboard.css">


    <style>
        .config-card {

            background: var(--card);

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 30px;

            max-width: 800px;

        }

        .config-card h2 {

            font-family: "Bebas Neue", sans-serif;
            font-size: 28px;
            margin-bottom: 8px;

        }

        .config-card>p {

            color: var(--muted);

            margin-bottom: 30px;

        }

        .config-form {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }

        .config-input {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }

        .config-input.full {

            grid-column: 1 / -1;

        }

        .config-input label {

            color: var(--muted);

            font-size: 13px;

        }

        .config-input input {

            background: #211b2b;

            border: 1px solid var(--border);

            color: white;

            padding: 14px;

            border-radius: 10px;

            outline: none;

            font-family: "Nunito", sans-serif;

        }

        .config-input input:focus {

            border-color: var(--purple);

        }

        .config-button {

            grid-column: 1 / -1;

            border: none;

            background: var(--purple);

            color: white;

            padding: 14px;

            border-radius: 10px;

            font-weight: 700;

            cursor: pointer;

            transition: .3s;

        }

        .config-button:hover {

            filter: brightness(1.15);

        }

        .config-success {

            background: rgba(74, 222, 128, .10);

            border: 1px solid rgba(74, 222, 128, .25);

            color: var(--green);

            padding: 13px;

            border-radius: 10px;

            margin-bottom: 20px;

        }

        .config-error {

            background: rgba(255, 92, 92, .10);

            border: 1px solid rgba(255, 92, 92, .25);

            color: var(--red);

            padding: 13px;

            border-radius: 10px;

            margin-bottom: 20px;

        }

        @media(max-width:700px) {

            .config-form {

                grid-template-columns: 1fr;

            }

            .config-input.full {

                grid-column: auto;

            }

            .config-button {

                grid-column: auto;

            }

        }
    </style>

</head>

<body>


    <aside class="sidebar">

        <div class="logo">
            Aqua<span>Flow</span>
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                <i class="bi bi-grid"></i>
                Dashboard
            </a>

            <a href="consumo.php">
                <i class="bi bi-droplet"></i>
                Consumo
            </a>

            <a href="gastos.php">
                <i class="bi bi-currency-dollar"></i>
                Gastos
            </a>

            <a href="dispositivos.php">
                <i class="bi bi-cpu"></i>
                Dispositivos
            </a>

            <a href="alertas.php">
                <i class="bi bi-exclamation-triangle"></i>
                Alertas
            </a>

            <a href="historico.php">
                <i class="bi bi-clock-history"></i>
                Histórico
            </a>

            <a href="configuracoes.php" class="active">
                <i class="bi bi-gear"></i>
                Configurações
            </a>

            <a href="logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i>
                Sair
            </a>

        </nav>

    </aside>


    <main class="main">


        <header class="page-top">

            <div>

                <span class="page-label">
                    AQUAFLOW / CONFIGURAÇÕES
                </span>

                <h1>
                    Configurações
                </h1>

                <p>
                    Gerencie as informações da sua conta.
                </p>

            </div>


            <div class="profile">

                <div class="avatar">
                    <?php echo htmlspecialchars($primeiraLetra); ?>
                </div>

                <div class="profile-info">

                    <strong>
                        <?php echo htmlspecialchars($nome); ?>
                    </strong>

                    <span>
                        <?php echo htmlspecialchars($cidade); ?>
                    </span>

                </div>

            </div>

        </header>


        <div class="config-card">

            <h2>
                Informações da conta
            </h2>

            <p>
                Atualize seus dados pessoais.
            </p>


            <?php if ($mensagem !== ""): ?>

                <div class="config-success">

                    <i class="bi bi-check-circle"></i>

                    <?php echo htmlspecialchars($mensagem); ?>

                </div>

            <?php endif; ?>


            <?php if ($erro !== ""): ?>

                <div class="config-error">

                    <i class="bi bi-exclamation-triangle"></i>

                    <?php echo htmlspecialchars($erro); ?>

                </div>

            <?php endif; ?>


            <form method="POST" class="config-form">


                <div class="config-input">

                    <label>
                        Nome
                    </label>

                    <input
                        type="text"
                        name="nome"
                        value="<?php echo htmlspecialchars($nome); ?>"
                        required>

                </div>


                <div class="config-input">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required>

                </div>


                <div class="config-input">

                    <label>
                        Cidade
                    </label>

                    <input
                        type="text"
                        name="cidade"
                        value="<?php echo htmlspecialchars($cidade); ?>">

                </div>


                <div class="config-input">

                    <label>
                        Telefone
                    </label>

                    <input
                        type="text"
                        name="telefone"
                        value="<?php echo htmlspecialchars($telefone); ?>">

                </div>


                <button
                    type="submit"
                    class="config-button">

                    <i class="bi bi-check-lg"></i>

                    Salvar alterações

                </button>


            </form>

        </div>


    </main>

</body>

</html>