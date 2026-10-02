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
// BUSCAR CONSUMOS DO USUÁRIO
// =========================================

$stmt = $conexao->prepare("
    SELECT id, litros, data_consumo
    FROM consumo
    WHERE usuario_id = ?
    ORDER BY data_consumo ASC
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$consumos = [];

while ($linha = $resultado->fetch_assoc()) {
    $consumos[] = $linha;
}

$stmt->close();


// =========================================
// CALCULAR TOTAL
// =========================================

$totalConsumo = 0;

foreach ($consumos as $consumo) {
    $totalConsumo += (float)$consumo["litros"];
}


// =========================================
// MÉDIA
// =========================================

$mediaConsumo = 0;

if (count($consumos) > 0) {
    $mediaConsumo = $totalConsumo / count($consumos);
}


// =========================================
// MAIOR CONSUMO
// =========================================

$maiorConsumo = 0;

foreach ($consumos as $consumo) {

    if ((float)$consumo["litros"] > $maiorConsumo) {
        $maiorConsumo = (float)$consumo["litros"];
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Consumo | AquaFlow</title>

<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700&display=swap"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet" href="dashboard.css">

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

<div class="logo">
Aqua<span>Flow</span>
</div>

<nav class="menu">

<a href="dashboard.php">
<i class="bi bi-grid"></i>
Dashboard
</a>

<a href="consumo.php" class="active">
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


<!-- MAIN -->

<main class="main">


<header class="page-top">

<div>

<span class="page-label">
AQUAFLOW / CONSUMO
</span>

<h1>
Consumo de água
</h1>

<p>
Acompanhe seu consumo de água.
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


<!-- MÉTRICAS -->

<section class="period-cards">


<div class="metric-card">

<span>
Consumo total
</span>

<strong>
<?php echo number_format($totalConsumo, 0, ',', '.'); ?> L
</strong>

<small>
Registros realizados
</small>

</div>


<div class="metric-card">

<span>
Média por registro
</span>

<strong>
<?php echo number_format($mediaConsumo, 0, ',', '.'); ?> L
</strong>

<small>
Consumo médio
</small>

</div>


<div class="metric-card">

<span>
Maior consumo
</span>

<strong>
<?php echo number_format($maiorConsumo, 0, ',', '.'); ?> L
</strong>

<small>
Maior registro
</small>

</div>


<div class="metric-card">

<span>
Registros
</span>

<strong>
<?php echo count($consumos); ?>
</strong>

<small>
Leituras realizadas
</small>

</div>

</section>


<!-- GRÁFICO -->

<div class="large-chart-card">

<div class="chart-header">

<div>

<span class="section-mini-title">
MONITORAMENTO
</span>

<h2>
Histórico de consumo
</h2>

</div>

</div>


<?php if (count($consumos) == 0): ?>

<div class="empty-state">

<i class="bi bi-droplet"></i>

<h2>
Nenhum consumo registrado
</h2>

<p>
Seu consumo aparecerá aqui quando o AquaFlow
começar a receber dados do dispositivo.
</p>

</div>

<?php else: ?>


<div class="large-chart" id="consumoChart">

<?php

$maior = $maiorConsumo > 0 ? $maiorConsumo : 1;

foreach ($consumos as $consumo):

    $altura = ((float)$consumo["litros"] / $maior) * 100;

?>

<div class="large-bar-container">

<div
class="large-bar"
style="height: <?php echo $altura; ?>%;"
title="<?php echo $consumo["litros"]; ?> litros"
>
</div>

<span>

<?php
echo date(
    "d/m",
    strtotime($consumo["data_consumo"])
);
?>

</span>

</div>

<?php endforeach; ?>

</div>


<?php endif; ?>

</div>


<!-- ANÁLISE -->

<div class="analysis-box">

<div class="analysis-icon">

<i class="bi bi-lightbulb"></i>

</div>

<div>

<h3>
Análise do consumo
</h3>

<p>

<?php if (count($consumos) == 0): ?>

Ainda não existem dados suficientes para realizar uma análise.
Quando seu dispositivo começar a registrar o consumo,
as informações aparecerão automaticamente aqui.

<?php elseif ($mediaConsumo <= 450): ?>

Seu consumo médio está dentro de uma faixa considerada
boa. Continue acompanhando seus hábitos para manter
o uso consciente da água.

<?php else: ?>

Seu consumo médio está um pouco elevado.
Vale a pena acompanhar os horários de maior utilização
para identificar possíveis desperdícios.

<?php endif; ?>

</p>

</div>

</div>


</main>

</body>

</html>