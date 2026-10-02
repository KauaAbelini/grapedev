<?php

session_start();

include("conexao.php");
include("local_contexto.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];

$nome = $_SESSION["usuario_nome"] ?? "Usuário";
$email = $_SESSION["usuario_email"] ?? "";
$cidade = $_SESSION["usuario_cidade"] ?? "";

$localAtual = obterLocalAtual($conexao, $usuario_id);
$localId = $localAtual ? (int)$localAtual["id"] : 0;
$localNome = $localAtual["nome"] ?? "Meu local";
$localTipo = $localAtual["tipo_imovel"] ?? "casa";

$primeiraLetra = strtoupper(substr($nome, 0, 1));

$modoDemo = ($usuario_id === 2);

$tarifaAgua = 7.50;

$consumoMesBase = 0;
$gastoEstimadoBase = 0;

if ($modoDemo) {

    $consumoMesBase = 12840;
    $gastoEstimadoBase = 86.40;

    $variacaoConsumo = "↓ 8,7%";
    $economia = "18,7%";

    $gastoMedio = "R$ 2,88";

    $dispositivosConectados = 3;

    $ultimaLeitura = "428 L";
    $ultimaLeituraHorario = "Hoje às 18:42";

    $dadosGrafico = [
        3 => [
            420,
            480,
            390
        ],

        7 => [
            420,
            510,
            460,
            390,
            520,
            580,
            428
        ],

        30 => [
            410, 450, 390, 470, 520,
            430, 490, 510, 460, 480,
            550, 510, 470, 430, 500,
            520, 490, 460, 510, 530,
            480, 450, 500, 540, 490,
            460, 520, 510, 470, 428
        ]
    ];

} else {

    $consumoMesBase = 0;
    $gastoEstimadoBase = 0;

    $variacaoConsumo = "—";
    $economia = "0%";

    $gastoMedio = "R$ 0,00";

    $dispositivosConectados = 0;

    $ultimaLeitura = "—";
    $ultimaLeituraHorario = "Nenhuma leitura registrada";

    $dadosGrafico = [
        3 => [],
        7 => [],
        30 => []
    ];
}

$quantidadeAlertas = 0;

$alertas = [];

try {

    $stmt = $conexao->prepare("
        SELECT
            id,
            ambiente,
            ponto,
            vazao,
            inicio
        FROM aquaflow_vazamentos
        WHERE usuario_id = ?
        AND local_id = ?
        AND ativo = 1
        ORDER BY inicio DESC
    ");

    if ($stmt) {

        $stmt->bind_param("ii", $usuario_id, $localId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        while ($vazamento = $resultado->fetch_assoc()) {

            $quantidadeAlertas++;

            $alertas[] = [
                "tipo" => "danger",
                "icone" => "bi-exclamation-triangle",
                "titulo" => "Vazamento detectado",
                "descricao" => $vazamento["ambiente"] . " — " . $vazamento["ponto"],
                "tempo" => "Ativo agora"
            ];
        }

        $stmt->close();
    }

} catch (Throwable $e) {

    $quantidadeAlertas = 0;
    $alertas = [];
}

$consumoMes = number_format(
    $consumoMesBase,
    0,
    ",",
    "."
) . " L";

$gastoEstimado = "R$ " . number_format(
    $gastoEstimadoBase,
    2,
    ",",
    "."
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | AquaFlow</title>

<link
href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link
rel="stylesheet"
href="dashboard.css">

<style>

.aquaflow-3d-button {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    border: 1px solid rgba(120, 90, 220, .22);
    background: rgba(120, 90, 220, .10);
    color: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: .2s ease;
    margin-right: 10px;
}

.aquaflow-3d-button i {
    font-size: 21px;
}

.aquaflow-3d-button:hover {
    transform: translateY(-2px);
    background: rgba(120, 90, 220, .18);
    box-shadow: 0 8px 22px rgba(0,0,0,.12);
}

.aquaflow-3d-button:active {
    transform: scale(.96);
}

.top .profile {
    display: flex;
    align-items: center;
}

.aquaflow-3d-sync {
    width: 100%;
}

.sync-status-online {
    color: #20c997;
}

.sync-status-offline {
    color: #ff5b6e;
}

.sync-total {
    margin-top: 18px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.sync-total-card {
    padding: 18px;
    border-radius: 16px;
    border: 1px solid rgba(120,90,220,.15);
    background: rgba(120,90,220,.06);
}

.sync-total-card span {
    display: block;
    font-size: 12px;
    opacity: .65;
    margin-bottom: 7px;
}

.sync-total-card strong {
    font-size: 23px;
}

.sync-leak-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px;
    margin-top: 12px;
    border-radius: 16px;
    border: 1px solid rgba(255,70,90,.16);
    background: rgba(255,70,90,.045);
}

.sync-leak-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.sync-leak-icon {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,70,90,.12);
    color: #ff5367;
    flex-shrink: 0;
}

.sync-leak-icon i {
    font-size: 20px;
}

.sync-leak-info strong {
    display: block;
    margin-bottom: 4px;
}

.sync-leak-info span {
    display: block;
    font-size: 13px;
    opacity: .75;
}

.sync-leak-info small {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    opacity: .55;
}

.sync-leak-right {
    text-align: right;
}

.sync-leak-loss {
    display: block;
    font-weight: 800;
    font-size: 17px;
    margin-bottom: 6px;
}

.sync-leak-cost {
    display: block;
    font-size: 12px;
    opacity: .7;
    margin-bottom: 8px;
}

.sync-leak-link {
    color: inherit;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.sync-leak-link:hover {
    text-decoration: underline;
}

@media (max-width: 800px) {

    .sync-total {
        grid-template-columns: 1fr;
    }

    .sync-leak-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .sync-leak-right {
        text-align: left;
        width: 100%;
    }
}



.current-local-card {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    padding:20px 22px;
    border:1px solid rgba(120,90,220,.16);
    border-radius:18px;
    background:linear-gradient(145deg,rgba(120,90,220,.07),rgba(255,255,255,.025));
}

.local-selector-content { min-width:0; }
.local-label {
    display:block;
    font-size:11px;
    letter-spacing:1.5px;
    opacity:.55;
    font-weight:900;
}
.local-current-name {
    display:block;
    font-size:21px;
    margin-top:4px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.local-current-type {
    display:block;
    margin-top:2px;
    opacity:.58;
    font-size:12px;
    text-transform:capitalize;
}
.local-selector-actions {
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    justify-content:flex-end;
}
.local-change-button,
.local-add-button {
    display:inline-flex;
    align-items:center;
    gap:7px;
    min-height:40px;
    padding:0 13px;
    border-radius:11px;
    text-decoration:none;
    font-size:12px;
    font-weight:900;
    transition:.2s ease;
}
.local-change-button {
    color:inherit;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.08);
}
.local-add-button {
    color:#fff;
    background:#7654ff;
}
.local-change-button:hover,
.local-add-button:hover {
    transform:translateY(-1px);
}

@media(max-width:700px) {
    .current-local-card { align-items:flex-start; flex-direction:column; }
    .local-selector-actions { width:100%; justify-content:flex-start; }
}

</style>

</head>

<body>

<aside class="sidebar">

    <div class="logo">
        Aqua<span>Flow</span>
    </div>

    <nav class="menu">

        <a href="dashboard.php" class="active">
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

            <span
                class="menu-badge"
                id="menuAlertBadge"
                style="<?php echo $quantidadeAlertas > 0 ? '' : 'display:none;'; ?>"
            >
                <?php echo $quantidadeAlertas; ?>
            </span>

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

<header class="top">

    <div class="welcome">

        <span class="page-label">
            AQUAFLOW / DASHBOARD
        </span>

        <h1>
            Olá,
            <?php echo htmlspecialchars($nome); ?>!
        </h1>

        <p id="welcomeText">

            <?php if ($modoDemo): ?>

                Aqui está o resumo do seu consumo de água.

            <?php else: ?>

                Seu AquaFlow ainda não possui dados registrados.

            <?php endif; ?>

        </p>

    </div>

    <div class="profile">

        <a
            href="locais.php"
            class="aquaflow-3d-button"
            title="Trocar local"
            aria-label="Trocar local"
        >
            <i class="bi bi-buildings"></i>
        </a>

        <a
            href="3d.php"
            class="aquaflow-3d-button"
            title="Abrir modelos 3D"
            aria-label="Abrir modelos 3D"
        >
            <i class="bi bi-boxes"></i>
        </a>

        <div class="notification-container">

            <button
                type="button"
                class="notification-button"
                id="notificationButton"
            >

                <i class="bi bi-bell"></i>

                <span
                    class="notification-badge"
                    id="notificationBadge"
                    style="<?php echo $quantidadeAlertas > 0 ? '' : 'display:none;'; ?>"
                >
                    <?php echo $quantidadeAlertas; ?>
                </span>

            </button>

            <div
                class="notification-box"
                id="notificationBox"
            >

                <div class="notification-header">

                    <strong>
                        Notificações
                    </strong>

                    <span id="notificationCount">
                        <?php echo $quantidadeAlertas; ?>
                    </span>

                </div>

                <div id="notificationItems">

                    <?php if (count($alertas) > 0): ?>

                        <?php foreach ($alertas as $alert): ?>

                            <a
                                href="alertas.php"
                                class="notification-item"
                            >

                                <div class="notification-icon danger">

                                    <i class="bi <?php echo $alert["icone"]; ?>"></i>

                                </div>

                                <div class="notification-content">

                                    <strong>
                                        <?php echo htmlspecialchars($alert["titulo"]); ?>
                                    </strong>

                                    <p>
                                        <?php echo htmlspecialchars($alert["descricao"]); ?>
                                    </p>

                                    <small>
                                        <?php echo htmlspecialchars($alert["tempo"]); ?>
                                    </small>

                                </div>

                            </a>

                        <?php endforeach; ?>

                        <a
                            href="alertas.php"
                            class="notification-see-all"
                        >
                            Ver todos os alertas
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    <?php else: ?>

                        <div class="notification-empty">

                            <i class="bi bi-check-circle"></i>

                            <strong>
                                Tudo tranquilo!
                            </strong>

                            <span>
                                Nenhuma notificação no momento.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

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

<section class="cards">

    <a href="consumo.php" class="card card-link">

        <div class="card-top">

            <h3>
                Consumo este mês
            </h3>

            <div class="card-icon">
                <i class="bi bi-droplet"></i>
            </div>

        </div>

        <div
            class="card-value"
            id="consumoMes"
        >
            <?php echo $consumoMes; ?>
        </div>

        <div class="card-bottom">

            <?php if ($modoDemo): ?>

                <span class="positive">
                    <?php echo $variacaoConsumo; ?>
                </span>

                comparado ao mês anterior

            <?php else: ?>

                Nenhum consumo registrado

            <?php endif; ?>

        </div>

        <div class="card-action">

            Ver detalhes

            <i class="bi bi-arrow-right"></i>

        </div>

    </a>

    <a href="gastos.php" class="card card-link">

        <div class="card-top">

            <h3>
                Gasto estimado
            </h3>

            <div class="card-icon">
                <i class="bi bi-currency-dollar"></i>
            </div>

        </div>

        <div
            class="card-value"
            id="gastoEstimado"
        >
            <?php echo $gastoEstimado; ?>
        </div>

        <div
            class="card-bottom"
            id="gastoDescricao"
        >

            <?php if ($modoDemo): ?>

                Estimativa deste mês

            <?php else: ?>

                Nenhum gasto calculado

            <?php endif; ?>

        </div>

        <div class="card-action">

            Ver gastos

            <i class="bi bi-arrow-right"></i>

        </div>

    </a>

    <a href="consumo.php" class="card card-link">

        <div class="card-top">

            <h3>
                Economia
            </h3>

            <div class="card-icon">
                <i class="bi bi-graph-down-arrow"></i>
            </div>

        </div>

        <div class="card-value">
            <?php echo $economia; ?>
        </div>

        <div class="card-bottom">

            <?php if ($modoDemo): ?>

                <span class="positive">
                    Boa economia
                </span>

                de água

            <?php else: ?>

                Dados insuficientes

            <?php endif; ?>

        </div>

        <div class="card-action">

            Analisar consumo

            <i class="bi bi-arrow-right"></i>

        </div>

    </a>

    <a href="alertas.php" class="card card-link">

        <div class="card-top">

            <h3>
                Alertas
            </h3>

            <div class="card-icon alert-card-icon">
                <i class="bi bi-bell"></i>
            </div>

        </div>

        <div
            class="card-value"
            id="alertCountCard"
        >
            <?php echo $quantidadeAlertas; ?>
        </div>

        <div
            class="card-bottom"
            id="alertDescription"
        >

            <?php if ($quantidadeAlertas > 0): ?>

                Possíveis problemas detectados

            <?php else: ?>

                Nenhum alerta registrado

            <?php endif; ?>

        </div>

        <div class="card-action">

            Ver alertas

            <i class="bi bi-arrow-right"></i>

        </div>

    </a>

</section>

<section class="content-grid">

<div class="chart-card">

    <div class="chart-header">

        <div>

            <span class="section-mini-title">
                MONITORAMENTO
            </span>

            <h2>
                Consumo de água
            </h2>

        </div>

        <select
            id="periodSelect"
            class="period-select"
        >

            <option value="7">
                Últimos 7 dias
            </option>

            <option value="3">
                Últimos 3 dias
            </option>

            <option value="30">
                Últimos 30 dias
            </option>

        </select>

    </div>

    <div
        id="chart"
        class="chart"
    ></div>

    <?php if ($modoDemo): ?>

        <a href="consumo.php" class="see-more">

            Ver análise completa

            <i class="bi bi-arrow-right"></i>

        </a>

    <?php else: ?>

        <div class="empty-message">

            <i class="bi bi-droplet"></i>

            <strong>
                Nenhum dado de consumo
            </strong>

            <span>
                Conecte seu AquaFlow para começar a monitorar.
            </span>

        </div>

    <?php endif; ?>

</div>

<div class="alerts">

    <div class="section-heading">

        <div>

            <span class="section-mini-title">
                ATENÇÃO
            </span>

            <h2>
                Alertas recentes
            </h2>

        </div>

        <span
            class="alert-count"
            id="alertCount"
            style="<?php echo $quantidadeAlertas > 0 ? '' : 'display:none;'; ?>"
        >
            <?php echo $quantidadeAlertas; ?>
        </span>

    </div>

    <div id="dashboardAlerts">

        <?php if (count($alertas) > 0): ?>

            <?php foreach ($alertas as $alert): ?>

                <div class="alert">

                    <div class="alert-icon">

                        <i class="bi bi-exclamation-triangle"></i>

                    </div>

                    <div>

                        <strong>
                            <?php echo htmlspecialchars($alert["titulo"]); ?>
                        </strong>

                        <span>
                            <?php echo htmlspecialchars($alert["descricao"]); ?>
                        </span>

                        <small>
                            <?php echo htmlspecialchars($alert["tempo"]); ?>
                        </small>

                    </div>

                </div>

            <?php endforeach; ?>

            <a href="alertas.php" class="see-more">

                Ver todos os alertas

                <i class="bi bi-arrow-right"></i>

            </a>

        <?php else: ?>

            <div class="empty-alerts">

                <i class="bi bi-check-circle"></i>

                <strong>
                    Tudo tranquilo!
                </strong>

                <span>
                    Nenhum alerta foi registrado.
                </span>

            </div>

        <?php endif; ?>

    </div>

</div>

</section>

<section class="current-local-card" style="margin-top:24px;">
    <div class="local-selector-content">
        <span class="local-label">LOCAL ATUAL</span>
        <strong class="local-current-name"><?php echo htmlspecialchars($localNome); ?></strong>
        <small class="local-current-type"><?php echo htmlspecialchars($localTipo); ?></small>
    </div>

    <div class="local-selector-actions">
        <a href="locais.php" class="local-change-button">
            <i class="bi bi-chevron-down"></i>
            Trocar local
        </a>
        <a href="adicionar_local.php" class="local-add-button">
            <i class="bi bi-plus-lg"></i>
            Adicionar local
        </a>
    </div>
</section>

<section class="bottom-grid">

    <a href="gastos.php" class="info-card">

        <div class="info-icon">
            <i class="bi bi-wallet2"></i>
        </div>

        <div>

            <span>
                Gasto médio diário
            </span>

            <strong>
                <?php echo $gastoMedio; ?>
            </strong>

            <small>
                <?php if ($modoDemo): ?>
                    Baseado nos últimos 30 dias
                <?php else: ?>
                    Sem dados disponíveis
                <?php endif; ?>
            </small>

        </div>

    </a>

    <a href="dispositivos.php" class="info-card">

        <div class="info-icon">
            <i class="bi bi-cpu"></i>
        </div>

        <div>

            <span>
                Dispositivos conectados
            </span>

            <strong>
                <?php echo $dispositivosConectados; ?>
            </strong>

            <small class="<?php echo $modoDemo ? 'online' : ''; ?>">

                <?php if ($modoDemo): ?>

                    ● Todos online

                <?php else: ?>

                    Nenhum dispositivo conectado

                <?php endif; ?>

            </small>

        </div>

    </a>

    <a href="historico.php" class="info-card">

        <div class="info-icon">
            <i class="bi bi-clock-history"></i>
        </div>

        <div>

            <span>
                Última leitura
            </span>

            <strong>
                <?php echo $ultimaLeitura; ?>
            </strong>

            <small>
                <?php echo $ultimaLeituraHorario; ?>
            </small>

        </div>

    </a>

</section>

<section
    class="aquaflow-3d-sync"
    id="aquaflow3dSync"
    style="margin-top:24px;"
>

    <div class="section-heading">

        <div>

            <span class="section-mini-title">
                TEMPO REAL
            </span>

            <h2>
                Monitoramento 3D
            </h2>

        </div>

        <span
            id="syncStatus"
            style="font-size:12px;opacity:.75;"
        >
            Sincronizando...
        </span>

    </div>

    <div
        class="sync-total"
        id="syncTotal"
    >

        <div class="sync-total-card">

            <span>
                Vazamentos ativos
            </span>

            <strong id="syncActiveCount">
                0
            </strong>

        </div>

        <div class="sync-total-card">

            <span>
                Litros perdidos
            </span>

            <strong id="syncTotalLiters">
                0 L
            </strong>

        </div>

        <div class="sync-total-card">

            <span>
                Prejuízo estimado
            </span>

            <strong id="syncTotalCost">
                R$ 0,00
            </strong>

        </div>

    </div>

    <div id="syncLeaksList">

        <div class="empty-alerts">

            <i class="bi bi-broadcast"></i>

            <strong>
                Conectando ao 3D...
            </strong>

            <span>
                Os vazamentos ativos aparecerão aqui.
            </span>

        </div>

    </div>

</section>

</main>

<script>

const dados = <?php echo json_encode($dadosGrafico); ?>;

const chart = document.getElementById("chart");

const select = document.getElementById("periodSelect");

function gerarGrafico(periodo) {

    chart.innerHTML = "";

    const valores = dados[periodo];

    if (!valores || valores.length === 0) {

        chart.innerHTML = `

            <div class="empty-chart">

                <i class="bi bi-bar-chart"></i>

                <strong>
                    Nenhum dado registrado
                </strong>

                <span>
                    O gráfico aparecerá quando houver consumo.
                </span>

            </div>

        `;

        return;
    }

    const max = Math.max(...valores);

    valores.forEach((valor, index) => {

        const container = document.createElement("div");

        container.className = "bar-container";

        const bar = document.createElement("div");

        bar.className = "bar";

        const altura = (valor / max) * 100;

        bar.style.height = altura + "%";

        bar.title = valor + " litros";

        const day = document.createElement("span");

        day.className = "day";

        if (periodo == 30) {

            day.textContent = index + 1;

        } else {

            const dias = [
                "Seg",
                "Ter",
                "Qua",
                "Qui",
                "Sex",
                "Sáb",
                "Dom"
            ];

            day.textContent = dias[index] || "";
        }

        container.appendChild(bar);

        container.appendChild(day);

        chart.appendChild(container);

    });

}

select.addEventListener("change", () => {

    gerarGrafico(select.value);

});

gerarGrafico(7);

</script>

<script>

const notificationButton =
    document.getElementById("notificationButton");

const notificationBox =
    document.getElementById("notificationBox");

notificationButton.addEventListener("click", function(event) {

    event.stopPropagation();

    notificationBox.classList.toggle("show");

});

document.addEventListener("click", function(event) {

    if (
        !notificationBox.contains(event.target) &&
        !notificationButton.contains(event.target)
    ) {

        notificationBox.classList.remove("show");

    }

});

</script>

<script>

const AQUAFLOW_3D_URL = "3d.php";
const LOCAL_ATUAL_ID = <?php echo json_encode($localId); ?>;

const AQUAFLOW_SYNC_URL = "sync_vazamentos.php";

const TARIFA_AGUA = <?php echo json_encode($tarifaAgua); ?>;

const CONSUMO_BASE = <?php echo json_encode($consumoMesBase); ?>;

const GASTO_BASE = <?php echo json_encode($gastoEstimadoBase); ?>;

function escapeHtml(value) {

    return String(value).replace(/[&<>'"]/g, function(c) {

        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[c];

    });

}

function formatarNumero(numero) {

    return Number(numero || 0)
        .toLocaleString("pt-BR", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });

}

function formatarLitros(numero) {

    return formatarNumero(numero) + " L";

}

function formatarReais(numero) {

    return Number(numero || 0).toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });

}

function atualizarCards(leaks) {

    const quantidade = leaks.length;

    let litrosPerdidos = 0;

    leaks.forEach(function(leak) {

        litrosPerdidos += Number(
            leak.litros_perdidos || 0
        );

    });

    const prejuizo = (litrosPerdidos / 1000) * TARIFA_AGUA;

    const consumoTotal = CONSUMO_BASE + litrosPerdidos;

    const gastoTotal = GASTO_BASE + prejuizo;

    const consumoElemento =
        document.getElementById("consumoMes");

    const gastoElemento =
        document.getElementById("gastoEstimado");

    const gastoDescricao =
        document.getElementById("gastoDescricao");

    const alertaCard =
        document.getElementById("alertCountCard");

    const alertaDescricao =
        document.getElementById("alertDescription");

    const alertaCount =
        document.getElementById("alertCount");

    const menuBadge =
        document.getElementById("menuAlertBadge");

    const notificationBadge =
        document.getElementById("notificationBadge");

    const notificationCount =
        document.getElementById("notificationCount");

    if (consumoElemento) {

        consumoElemento.textContent =
            formatarLitros(consumoTotal);

    }

    if (gastoElemento) {

        gastoElemento.textContent =
            formatarReais(gastoTotal);

    }

    if (gastoDescricao) {

        if (prejuizo > 0) {

            gastoDescricao.textContent =
                "Incluindo R$ " +
                prejuizo.toFixed(2).replace(".", ",") +
                " de perdas";

        } else {

            gastoDescricao.textContent =
                <?php echo $modoDemo ? '"Estimativa deste mês"' : '"Nenhum gasto calculado"'; ?>;

        }

    }

    if (alertaCard) {

        alertaCard.textContent = quantidade;

    }

    if (alertaCount) {

        alertaCount.textContent = quantidade;

        alertaCount.style.display =
            quantidade > 0 ? "" : "none";

    }

    if (menuBadge) {

        menuBadge.textContent = quantidade;

        menuBadge.style.display =
            quantidade > 0 ? "" : "none";

    }

    if (notificationBadge) {

        notificationBadge.textContent = quantidade;

        notificationBadge.style.display =
            quantidade > 0 ? "" : "none";

    }

    if (notificationCount) {

        notificationCount.textContent = quantidade;

    }

    if (alertaDescricao) {

        alertaDescricao.textContent =
            quantidade > 0
                ? "Possíveis problemas detectados"
                : "Nenhum alerta registrado";

    }

}

function atualizarResumo3D(leaks) {

    let litros = 0;

    leaks.forEach(function(leak) {

        litros += Number(
            leak.litros_perdidos || 0
        );

    });

    const prejuizo =
        (litros / 1000) * TARIFA_AGUA;

    const activeElement =
        document.getElementById("syncActiveCount");

    const litersElement =
        document.getElementById("syncTotalLiters");

    const costElement =
        document.getElementById("syncTotalCost");

    if (activeElement) {

        activeElement.textContent =
            leaks.length;

    }

    if (litersElement) {

        litersElement.textContent =
            formatarLitros(litros);

    }

    if (costElement) {

        costElement.textContent =
            formatarReais(prejuizo);

    }

}

function atualizarAlertas(leaks) {

    const container =
        document.getElementById("dashboardAlerts");

    if (!container) return;

    if (!leaks.length) {

        container.innerHTML = `

            <div class="empty-alerts">

                <i class="bi bi-check-circle"></i>

                <strong>
                    Tudo tranquilo!
                </strong>

                <span>
                    Nenhum alerta foi registrado.
                </span>

            </div>

        `;

        return;

    }

    const primeiros =
        leaks.slice(0, 3);

    let html = "";

    primeiros.forEach(function(leak) {

        html += `

            <div class="alert">

                <div class="alert-icon">

                    <i class="bi bi-exclamation-triangle"></i>

                </div>

                <div>

                    <strong>
                        Vazamento detectado
                    </strong>

                    <span>
                        ${escapeHtml(leak.ambiente)}
                        — 
                        ${escapeHtml(leak.ponto)}
                    </span>

                    <small>
                        ${Number(leak.vazao).toFixed(2).replace(".", ",")}
                        L/min • ativo agora
                    </small>

                </div>

            </div>

        `;

    });

    html += `

        <a href="alertas.php" class="see-more">

            Ver todos os alertas

            <i class="bi bi-arrow-right"></i>

        </a>

    `;

    container.innerHTML = html;

}

function atualizarNotificacoes(leaks) {

    const container =
        document.getElementById("notificationItems");

    if (!container) return;

    if (!leaks.length) {

        container.innerHTML = `

            <div class="notification-empty">

                <i class="bi bi-check-circle"></i>

                <strong>
                    Tudo tranquilo!
                </strong>

                <span>
                    Nenhuma notificação no momento.
                </span>

            </div>

        `;

        return;

    }

    let html = "";

    leaks.slice(0, 5).forEach(function(leak) {

        html += `

            <a href="alertas.php" class="notification-item">

                <div class="notification-icon danger">

                    <i class="bi bi-exclamation-triangle"></i>

                </div>

                <div class="notification-content">

                    <strong>
                        Vazamento detectado
                    </strong>

                    <p>
                        ${escapeHtml(leak.ambiente)}
                        —
                        ${escapeHtml(leak.ponto)}
                    </p>

                    <small>
                        Ativo agora
                    </small>

                </div>

            </a>

        `;

    });

    html += `

        <a
            href="alertas.php"
            class="notification-see-all"
        >

            Ver todos os alertas

            <i class="bi bi-arrow-right"></i>

        </a>

    `;

    container.innerHTML = html;

}

function atualizarLista3D(leaks) {

    const list =
        document.getElementById("syncLeaksList");

    if (!list) return;

    if (!leaks.length) {

        list.innerHTML = `

            <div class="empty-alerts">

                <i class="bi bi-check-circle"></i>

                <strong>
                    Nenhum vazamento ativo
                </strong>

                <span>
                    O sistema 3D está sem perdas no momento.
                </span>

            </div>

        `;

        return;

    }

    let html = "";

    leaks.forEach(function(leak) {

        const litros =
            Number(leak.litros_perdidos || 0);

        const custo =
            (litros / 1000) * TARIFA_AGUA;

        const ambiente =
            encodeURIComponent(leak.ambiente);

        const ponto =
            encodeURIComponent(leak.ponto);

        html += `

            <div class="sync-leak-item">

                <div class="sync-leak-left">

                    <div class="sync-leak-icon">

                        <i class="bi bi-exclamation-triangle"></i>

                    </div>

                    <div class="sync-leak-info">

                        <strong>
                            ${escapeHtml(leak.ambiente)}
                            —
                            ${escapeHtml(leak.ponto)}
                        </strong>

                        <span>
                            Vazão:
                            ${Number(leak.vazao)
                                .toFixed(2)
                                .replace(".", ",")}
                            L/min
                        </span>

                        <small>
                            Vazamento ativo em tempo real
                        </small>

                    </div>

                </div>

                <div class="sync-leak-right">

                    <span class="sync-leak-loss">
                        ${formatarLitros(litros)}
                    </span>

                    <span class="sync-leak-cost">
                        Perda:
                        ${formatarReais(custo)}
                    </span>

                    <a
                        href="${AQUAFLOW_3D_URL}?env=${ambiente}&ponto=${ponto}"
                        class="sync-leak-link"
                    >

                        Ver no 3D
                        <i class="bi bi-box-arrow-up-right"></i>

                    </a>

                </div>

            </div>

        `;

    });

    list.innerHTML = html;

}

async function carregarVazamentos3D() {

    const list =
        document.getElementById("syncLeaksList");

    const status =
        document.getElementById("syncStatus");

    if (!list) return;

    try {

        const response =
            await fetch(
                AQUAFLOW_SYNC_URL,
                {
                    credentials: "same-origin",
                    cache: "no-store"
                }
            );

        if (!response.ok) {

            throw new Error(
                "HTTP " + response.status
            );

        }

        const data =
            await response.json();

        const leaks =
            Array.isArray(data.vazamentos)
                ? data.vazamentos
                : [];

        if (status) {

            status.textContent =
                "● 3D conectado";

            status.classList.remove(
                "sync-status-offline"
            );

            status.classList.add(
                "sync-status-online"
            );

        }

        atualizarCards(leaks);

        atualizarResumo3D(leaks);

        atualizarLista3D(leaks);

        atualizarAlertas(leaks);

        atualizarNotificacoes(leaks);

    } catch (error) {

        console.error(
            "Erro ao sincronizar AquaFlow:",
            error
        );

        if (status) {

            status.textContent =
                "● 3D offline";

            status.classList.remove(
                "sync-status-online"
            );

            status.classList.add(
                "sync-status-offline"
            );

        }

    }

}

carregarVazamentos3D();

setInterval(
    carregarVazamentos3D,
    2500
);

</script>

</body>

</html>