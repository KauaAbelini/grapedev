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
// BUSCAR GASTOS
// =========================================

$stmt = $conexao->prepare("
    SELECT id, valor, data_gasto
    FROM gastos
    WHERE usuario_id = ?
    ORDER BY data_gasto ASC
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$gastos = [];

while ($linha = $resultado->fetch_assoc()) {
    $gastos[] = $linha;
}

$stmt->close();


// =========================================
// TOTAL
// =========================================

$totalGastos = 0;

foreach ($gastos as $gasto) {
    $totalGastos += (float)$gasto["valor"];
}


// =========================================
// MÉDIA
// =========================================

$mediaGasto = 0;

if (count($gastos) > 0) {
    $mediaGasto = $totalGastos / count($gastos);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Gastos | AquaFlow</title>

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

<a href="gastos.php" class="active">
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


<main class="main">


<header class="page-top">

<div>

<span class="page-label">
AQUAFLOW / GASTOS
</span>

<h1>
Gastos
</h1>

<p>
Acompanhe o custo estimado do seu consumo.
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


<section class="period-cards">


<div class="metric-card">

<span>
Gasto total
</span>

<strong>
R$ <?php echo number_format($totalGastos, 2, ',', '.'); ?>
</strong>

<small>
Total registrado
</small>

</div>


<div class="metric-card">

<span>
Gasto médio
</span>

<strong>
R$ <?php echo number_format($mediaGasto, 2, ',', '.'); ?>
</strong>

<small>
Por registro
</small>

</div>


<div class="metric-card">

<span>
Registros
</span>

<strong>
<?php echo count($gastos); ?>
</strong>

<small>
Registros financeiros
</small>

</div>


<div class="metric-card">

<span>
Status
</span>

<strong>

<?php

if ($totalGastos == 0) {
    echo "—";
} elseif ($totalGastos < 100) {
    echo "Bom";
} else {
    echo "Atenção";
}

?>

</strong>

<small>
Análise atual
</small>

</div>

</section>


<div class="large-chart-card">

<div class="chart-header">

<div>

<span class="section-mini-title">
FINANCEIRO
</span>

<h2>
Gastos registrados
</h2>

</div>

</div>


<?php if (count($gastos) == 0): ?>

<div class="empty-state">

<i class="bi bi-wallet2"></i>

<h2>
Nenhum gasto registrado
</h2>

<p>
Os custos aparecerão aqui quando houver
registros de consumo.
</p>

</div>

<?php else: ?>


<div class="expense-chart">

<?php

$maiorGasto = 0;

foreach ($gastos as $gasto) {

    if ((float)$gasto["valor"] > $maiorGasto) {
        $maiorGasto = (float)$gasto["valor"];
    }

}

$maiorGasto = $maiorGasto > 0 ? $maiorGasto : 1;

foreach ($gastos as $gasto):

    $altura =
        ((float)$gasto["valor"] / $maiorGasto) * 100;

?>

<div
class="expense-bar"
style="height: <?php echo $altura; ?>%;"
title="R$ <?php echo number_format($gasto["valor"], 2, ',', '.'); ?>"
>
</div>

<?php endforeach; ?>

</div>


<div class="chart-days">

<?php foreach ($gastos as $gasto): ?>

<span>

<?php
echo date(
    "d/m",
    strtotime($gasto["data_gasto"])
);
?>

</span>

<?php endforeach; ?>

</div>


<?php endif; ?>

</div>


<div class="analysis-box">

<div class="analysis-icon">

<i class="bi bi-graph-up"></i>

</div>

<div>

<h3>
Resumo financeiro
</h3>

<p>

<?php if (count($gastos) == 0): ?>

Ainda não existem gastos registrados.

<?php else: ?>

Seu gasto total registrado é de
<strong>
R$ <?php echo number_format($totalGastos, 2, ',', '.'); ?>
</strong>.

O valor médio por registro é de
<strong>
R$ <?php echo number_format($mediaGasto, 2, ',', '.'); ?>
</strong>.

<?php endif; ?>

</p>

</div>

</div>


</main>

</body>

</html>