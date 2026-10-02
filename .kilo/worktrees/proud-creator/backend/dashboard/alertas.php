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
// RESOLVER ALERTA
// =========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["resolver_id"])
) {

    $alerta_id = intval($_POST["resolver_id"]);

    $stmt = $conexao->prepare("
        UPDATE alertas
        SET resolvido = TRUE
        WHERE id = ?
        AND usuario_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $alerta_id,
        $usuario_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: alertas.php");
    exit();
}


// =========================================
// BUSCAR ALERTAS
// =========================================

$stmt = $conexao->prepare("
    SELECT
        id,
        titulo,
        descricao,
        tipo,
        resolvido,
        data_alerta
    FROM alertas
    WHERE usuario_id = ?
    ORDER BY data_alerta DESC
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

$alertas = [];

while ($linha = $resultado->fetch_assoc()) {
    $alertas[] = $linha;
}

$stmt->close();


// =========================================
// CONTADORES
// =========================================

$pendentes = 0;
$resolvidos = 0;

foreach ($alertas as $alerta) {

    if ($alerta["resolvido"]) {
        $resolvidos++;
    } else {
        $pendentes++;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Alertas | AquaFlow</title>

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

<a href="alertas.php" class="active">
<i class="bi bi-exclamation-triangle"></i>
Alertas

<?php if ($pendentes > 0): ?>

<span class="menu-badge">
<?php echo $pendentes; ?>
</span>

<?php endif; ?>

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
AQUAFLOW / ALERTAS
</span>

<h1>
Alertas
</h1>

<p>
Veja possíveis problemas detectados pelo sistema.
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


<section class="alert-summary">


<div class="alert-summary-card">

<i class="bi bi-exclamation-triangle"></i>

<div>

<span>
Alertas pendentes
</span>

<strong>
<?php echo $pendentes; ?>
</strong>

</div>

</div>


<div class="alert-summary-card success">

<i class="bi bi-check-circle"></i>

<div>

<span>
Resolvidos
</span>

<strong>
<?php echo $resolvidos; ?>
</strong>

</div>

</div>

</section>


<?php if (count($alertas) == 0): ?>


<div class="large-chart-card">

<div class="empty-state">

<i class="bi bi-shield-check"></i>

<h2>
Tudo certo!
</h2>

<p>
Você ainda não possui alertas registrados.
Quando o AquaFlow detectar algo fora do normal,
o aviso aparecerá aqui.
</p>

</div>

</div>


<?php else: ?>


<section class="alert-list">


<?php foreach ($alertas as $alerta): ?>


<div class="full-alert
<?php

if ($alerta["resolvido"]) {

    echo " success-alert";

} elseif ($alerta["tipo"] === "danger") {

    echo " danger-alert";

} else {

    echo " warning-alert";

}

?>">


<div class="full-alert-icon">

<?php

if ($alerta["resolvido"]) {

    echo '<i class="bi bi-check-circle"></i>';

} elseif ($alerta["tipo"] === "danger") {

    echo '<i class="bi bi-exclamation-triangle"></i>';

} else {

    echo '<i class="bi bi-droplet"></i>';

}

?>

</div>


<div class="full-alert-content">


<div class="alert-title-row">

<h2>
<?php echo htmlspecialchars($alerta["titulo"]); ?>
</h2>


<span class="<?php

if ($alerta["resolvido"]) {

    echo "success-label";

} elseif ($alerta["tipo"] === "danger") {

    echo "danger-label";

} else {

    echo "warning-label";

}

?>">

<?php

if ($alerta["resolvido"]) {

    echo "RESOLVIDO";

} elseif ($alerta["tipo"] === "danger") {

    echo "URGENTE";

} else {

    echo "ATENÇÃO";

}

?>

</span>

</div>


<p>
<?php echo htmlspecialchars($alerta["descricao"]); ?>
</p>


<div class="alert-details">

<span>
<i class="bi bi-calendar"></i>

<?php
echo date(
    "d/m/Y H:i",
    strtotime($alerta["data_alerta"])
);
?>

</span>

</div>


<?php if (!$alerta["resolvido"]): ?>

<form method="POST">

<input
type="hidden"
name="resolver_id"
value="<?php echo $alerta["id"]; ?>"
>

<button
type="submit"
class="resolve-button"
>

<i class="bi bi-check"></i>

Marcar como resolvido

</button>

</form>

<?php endif; ?>


</div>

</div>


<?php endforeach; ?>


</section>

<?php endif; ?>


</main>

</body>

</html>