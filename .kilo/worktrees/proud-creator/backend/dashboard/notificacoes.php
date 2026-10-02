<?php

session_start();

include("conexao.php");

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION["usuario_id"];

// Busca os alertas do usuário logado
$sql = "SELECT *
        FROM alertas
        WHERE usuario_id = ?
        ORDER BY id DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

// Conta alertas
$quantidade = $resultado->num_rows;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notificações | Grape</title>

<link
href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap"
rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: 'Nunito', sans-serif;

    min-height: 100vh;

    background:
    radial-gradient(
        circle at top left,
        rgba(140,82,255,0.25),
        transparent 30%
    ),

    radial-gradient(
        circle at bottom right,
        rgba(95,46,234,0.20),
        transparent 30%
    ),

    #050507;

    color: white;

    padding: 40px;

}

.container {

    max-width: 1000px;

    margin: auto;

}

.voltar {

    display: inline-block;

    background: #8c52ff;

    color: white;

    text-decoration: none;

    padding: 12px 22px;

    border-radius: 50px;

    font-weight: 700;

    margin-bottom: 40px;

    transition: 0.3s;

}

.voltar:hover {

    background: #6d3be6;

    transform: translateY(-3px);

}

.titulo {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;

}

.titulo h1 {

    font-size: 38px;

}

.contador {

    background: #8c52ff;

    padding: 8px 16px;

    border-radius: 50px;

    font-weight: 800;

}

.alerta {

    background: rgba(255,255,255,0.05);

    border: 1px solid rgba(255,255,255,0.08);

    border-radius: 18px;

    padding: 22px;

    margin-bottom: 15px;

    transition: 0.3s;

}

.alerta:hover {

    transform: translateY(-3px);

    border-color: rgba(140,82,255,0.5);

}

.alerta h3 {

    margin-bottom: 8px;

    color: #b892ff;

}

.alerta p {

    color: rgba(255,255,255,0.8);

    line-height: 1.6;

}

.alerta-data {

    display: block;

    margin-top: 10px;

    color: rgba(255,255,255,0.45);

    font-size: 13px;

}

.sem-alertas {

    text-align: center;

    padding: 70px 20px;

    background: rgba(255,255,255,0.04);

    border-radius: 20px;

    color: rgba(255,255,255,0.65);

}

.icone {

    font-size: 45px;

    margin-bottom: 15px;

}

</style>

</head>

<body>

<div class="container">

    <a href="dashboard.php" class="voltar">
        ← Voltar
    </a>

    <div class="titulo">

        <h1>🔔 Notificações</h1>

        <span class="contador">
            <?php echo $quantidade; ?>
        </span>

    </div>


    <?php if ($quantidade == 0): ?>

        <div class="sem-alertas">

            <div class="icone">🔔</div>

            <h2>Nenhuma notificação</h2>

            <p>
                Você não possui novos avisos no momento.
            </p>

        </div>

    <?php else: ?>


        <?php while ($alerta = $resultado->fetch_assoc()): ?>

            <div class="alerta">

                <h3>

                    ⚠️

                    <?php

                    if (isset($alerta["tipo"])) {
                        echo htmlspecialchars($alerta["tipo"]);
                    } else {
                        echo "Aviso";
                    }

                    ?>

                </h3>


                <p>

                    <?php

                    if (isset($alerta["mensagem"])) {

                        echo htmlspecialchars($alerta["mensagem"]);

                    } elseif (isset($alerta["descricao"])) {

                        echo htmlspecialchars($alerta["descricao"]);

                    } else {

                        echo "Você possui um novo alerta.";

                    }

                    ?>

                </p>


                <?php

                if (isset($alerta["data_criacao"])):

                ?>

                    <span class="alerta-data">

                        <?php

                        echo date(
                            "d/m/Y H:i",
                            strtotime($alerta["data_criacao"])
                        );

                        ?>

                    </span>

                <?php endif; ?>


            </div>

        <?php endwhile; ?>


    <?php endif; ?>

</div>

</body>

</html>