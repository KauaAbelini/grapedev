<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

include("conexao.php");

$usuario_id = $_SESSION["usuario_id"];

$nome = $_SESSION["usuario_nome"] ?? "Usuário";
$cidade = $_SESSION["usuario_cidade"] ?? "";

$primeiraLetra = strtoupper(substr($nome, 0, 1));


// =========================================
// BUSCAR DISPOSITIVOS
// =========================================

$stmt = $conexao->prepare("
    SELECT
        id,
        nome,
        tipo,
        status,
        localizacao,
        ultima_leitura
    FROM dispositivos
    WHERE usuario_id = ?
    ORDER BY id ASC
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$dispositivos = [];

while ($linha = $resultado->fetch_assoc()) {
    $dispositivos[] = $linha;
}

$stmt->close();


// =========================================
// CONTADORES
// =========================================

$totalDispositivos = count($dispositivos);

$online = 0;
$offline = 0;

foreach ($dispositivos as $dispositivo) {

    if ($dispositivo["status"] === "online") {
        $online++;
    } else {
        $offline++;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Dispositivos | AquaFlow</title>

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="dashboard.css">

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

            <a href="dispositivos.php" class="active">
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

            <a href="configuracoes.php">
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
                    AQUAFLOW / DISPOSITIVOS
                </span>

                <h1>
                    Dispositivos
                </h1>

                <p>
                    Gerencie os dispositivos conectados ao AquaFlow.
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


        <section class="device-summary">


            <div class="status-box">

                <i class="bi bi-cpu"></i>

                <div>

                    <span>
                        Total
                    </span>

                    <strong>
                        <?php echo $totalDispositivos; ?>
                    </strong>

                </div>

            </div>


            <div class="status-box">

                <i class="bi bi-wifi"></i>

                <div>

                    <span>
                        Online
                    </span>

                    <strong class="online">
                        <?php echo $online; ?>
                    </strong>

                </div>

            </div>


            <div class="status-box">

                <i class="bi bi-wifi-off"></i>

                <div>

                    <span>
                        Offline
                    </span>

                    <strong>
                        <?php echo $offline; ?>
                    </strong>

                </div>

            </div>

        </section>


        <?php if ($totalDispositivos == 0): ?>

            <div class="large-chart-card">

                <div class="empty-state">

                    <i class="bi bi-cpu"></i>

                    <h2>
                        Nenhum dispositivo conectado
                    </h2>

                    <p>
                        Quando você conectar um dispositivo AquaFlow,
                        ele aparecerá automaticamente nesta página.
                    </p>

                </div>

            </div>

        <?php else: ?>


            <section class="devices-grid">


                <?php foreach ($dispositivos as $dispositivo): ?>


                    <div class="device-card">


                        <div class="device-top">

                            <div class="device-icon">

                                <i class="bi bi-cpu"></i>

                            </div>


                            <span class="status
<?php
                    echo $dispositivo["status"] === "online"
                        ? " online-status"
                        : "";
?>">

                                ●
                                <?php echo htmlspecialchars($dispositivo["status"]); ?>

                            </span>

                        </div>


                        <h2>
                            <?php echo htmlspecialchars($dispositivo["nome"]); ?>
                        </h2>


                        <p>
                            <?php echo htmlspecialchars($dispositivo["tipo"] ?? "Dispositivo AquaFlow"); ?>
                        </p>


                        <div class="device-info">

                            <span>
                                Localização
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $dispositivo["localizacao"] ?? "Não informado"
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="device-info">

                            <span>
                                Última leitura
                            </span>

                            <strong>

                                <?php

                                if (!empty($dispositivo["ultima_leitura"])) {

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime($dispositivo["ultima_leitura"])
                                    );
                                } else {

                                    echo "Nenhuma";
                                }

                                ?>

                            </strong>

                        </div>


                        <button class="device-button">

                            <i class="bi bi-gear"></i>

                            Gerenciar dispositivo

                        </button>


                    </div>


                <?php endforeach; ?>


            </section>

        <?php endif; ?>


    </main>

</body>

</html>