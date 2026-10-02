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
// BUSCAR HISTÓRICO
// =========================================

$stmt = $conexao->prepare("
    SELECT
        id,
        tipo,
        descricao,
        valor,
        data_registro
    FROM historico
    WHERE usuario_id = ?
    ORDER BY data_registro DESC
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$historicos = [];

while ($linha = $resultado->fetch_assoc()) {
    $historicos[] = $linha;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Histórico | AquaFlow</title>

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

<a href="dispositivos.php">
<i class="bi bi-cpu"></i>
Dispositivos
</a>

<a href="alertas.php">
<i class="bi bi-exclamation-triangle"></i>
Alertas
</a>

<a href="historico.php" class="active">
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
AQUAFLOW / HISTÓRICO
</span>

<h1>
Histórico
</h1>

<p>
Confira os registros realizados pelo AquaFlow.
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


<?php if (count($historicos) == 0): ?>


<div class="large-chart-card">

<div class="empty-state">

<i class="bi bi-clock-history"></i>

<h2>
Nenhum registro encontrado
</h2>

<p>
Seu histórico aparecerá aqui assim que o AquaFlow
começar a registrar informações.
</p>

</div>

</div>


<?php else: ?>


<div class="history-card">

<div class="table-responsive">

<table>

<thead>

<tr>

<th>
Data
</th>

<th>
Tipo
</th>

<th>
Descrição
</th>

<th>
Valor
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach ($historicos as $historico): ?>


<tr>


<td>

<?php

echo date(
    "d/m/Y H:i",
    strtotime($historico["data_registro"])
);

?>

</td>


<td>

<?php

$icone = "bi-clock";

if ($historico["tipo"] === "Consumo") {

    $icone = "bi-droplet";

} elseif ($historico["tipo"] === "Alerta") {

    $icone = "bi-exclamation-triangle";

}

?>

<i class="bi <?php echo $icone; ?>"></i>

<?php echo htmlspecialchars($historico["tipo"]); ?>

</td>


<td>

<?php
echo htmlspecialchars(
    $historico["descricao"]
);
?>

</td>


<td>

<?php

if ($historico["valor"] !== null) {

    echo number_format(
        (float)$historico["valor"],
        0,
        ',',
        '.'
    );

    echo " L";

} else {

    echo "—";

}

?>

</td>


<td>

<span class="table-status normal">

Registrado

</span>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>

</div>


<div class="history-footer">

<span>

<?php echo count($historicos); ?>

registro(s) encontrado(s)

</span>

</div>


<?php endif; ?>


</main>

</body>

</html>