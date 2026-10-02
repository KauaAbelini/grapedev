<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("local_contexto.php");

$usuarioId = (int)$_SESSION["usuario_id"];
$localAtual = obterLocalAtual($conexao, $usuarioId);

$tipoImovel = $localAtual["tipo_imovel"] ?? ($_SESSION["usuario_tipo_imovel"] ?? "");

$tipoFallback = [
    "residencial" => "casa",
    "comercial" => "empresa"
];

if ($tipoImovel === "") {
    $tipoImovel = $tipoFallback[$_SESSION["usuario_tipo"] ?? ""] ?? "casa";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>AquaFlow 3D</title>

<link
    rel="stylesheet"
    href="/grapedev/nex/assets/nex-jarvis.css"
>

<script
    src="/grapedev/nex/assets/nex-jarvis.js"
    defer
></script>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #050811;
    font-family: Arial, Helvetica, sans-serif;
    color: white;
}

#app {
    width: 100vw;
    height: 100vh;
    position: relative;
}

#canvas-container {
    width: 100%;
    height: 100%;
}


/* =====================================================
   PAINEL PRINCIPAL
===================================================== */

.panel {
    position: absolute;

    top: 24px;
    left: 24px;

    width: 300px;
    max-height: calc(100vh - 48px);

    padding: 22px;

    background: rgba(7, 12, 23, 0.94);

    border: 1px solid rgba(255,255,255,0.08);

    border-radius: 16px;

    backdrop-filter: blur(18px);

    z-index: 20;

    box-shadow:
        0 20px 60px rgba(0,0,0,0.45);

    overflow-y: auto;
}

.panel::-webkit-scrollbar {
    width: 4px;
}

.panel::-webkit-scrollbar-thumb {
    background: #34415d;
    border-radius: 10px;
}


/* =====================================================
   LOGO
===================================================== */

.logo {
    font-size: 23px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.logo span {
    color: #7354ff;
}

.subtitle {
    font-size: 12px;
    color: #7f8ba1;
    margin-bottom: 20px;
}


/* =====================================================
   STATUS
===================================================== */

.status {
    display: flex;
    align-items: center;
    gap: 8px;

    font-size: 12px;

    margin-bottom: 18px;
}

.status-dot {
    width: 9px;
    height: 9px;

    border-radius: 50%;

    background: #2ee875;

    box-shadow:
        0 0 12px #2ee875;
}


/* =====================================================
   BOTÕES
===================================================== */

.xray-button {
    width: 100%;

    padding: 13px;

    border-radius: 10px;

    border: 1px solid #7654ff;

    background:
        linear-gradient(
            135deg,
            rgba(118,84,255,0.30),
            rgba(118,84,255,0.08)
        );

    color: white;

    font-size: 13px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.25s;
}

.xray-button:hover {
    transform: translateY(-1px);

    box-shadow:
        0 0 25px rgba(118,84,255,0.25);
}

.xray-button.active {
    background:
        linear-gradient(
            135deg,
            #7654ff,
            #4d31c7
        );
}


.leak-button {
    width: 100%;

    margin-top: 9px;

    padding: 12px;

    border-radius: 9px;

    border: 1px solid rgba(255,255,255,0.09);

    background: rgba(255,255,255,0.04);

    color: #c2cad7;

    cursor: pointer;

    transition: 0.2s;
}

.leak-button:hover {
    background: rgba(255,255,255,0.08);
}


/* =====================================================
   SELEÇÃO DE VAZAMENTO
===================================================== */

.leak-selector {
    margin-top: 17px;

    border-top: 1px solid rgba(255,255,255,0.07);

    padding-top: 16px;
}

.selector-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 10px;
}

.selector-title {
    font-size: 12px;
    font-weight: bold;

    color: #d9deea;
}

.selector-info {
    font-size: 9px;
    color: #69758a;
}


/* =====================================================
   LISTA DOS CÔMODOS
===================================================== */

.rooms {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 7px;
}

.room-option {
    position: relative;

    padding: 10px 9px;

    border-radius: 8px;

    border: 1px solid rgba(255,255,255,0.07);

    background: rgba(255,255,255,0.035);

    color: #929db0;

    font-size: 10px;

    cursor: pointer;

    transition: 0.2s;

    user-select: none;
}

.room-option:hover {
    background: rgba(255,255,255,0.07);

    color: #dce2ed;
}

.room-option.selected {
    border-color: #7654ff;

    background:
        linear-gradient(
            135deg,
            rgba(118,84,255,0.22),
            rgba(118,84,255,0.08)
        );

    color: white;

    box-shadow:
        inset 0 0 15px rgba(118,84,255,0.06);
}

.room-option.leaking {
    border-color: rgba(255,48,48,0.75);

    background:
        linear-gradient(
            135deg,
            rgba(255,48,48,0.18),
            rgba(255,48,48,0.05)
        );

    color: #ff6969;
}

.room-option.pool-option {
    grid-column: span 2;
}

.room-status {
    display: block;

    margin-top: 3px;

    font-size: 8px;

    color: #69758a;
}

.room-option.leaking .room-status {
    color: #ff5555;
}


/* =====================================================
   BOTÃO LIMPAR SELEÇÃO
===================================================== */

.clear-selection {
    width: 100%;

    margin-top: 8px;

    padding: 9px;

    border-radius: 8px;

    border: 1px solid rgba(255,255,255,0.06);

    background: transparent;

    color: #69758a;

    font-size: 10px;

    cursor: pointer;

    transition: 0.2s;
}

.clear-selection:hover {
    background: rgba(255,255,255,0.04);

    color: #aeb7c7;
}


/* =====================================================
   CONTROLES
===================================================== */

.controls {
    margin-top: 17px;

    padding-top: 15px;

    border-top: 1px solid rgba(255,255,255,0.07);

    color: #78849a;

    font-size: 10px;

    line-height: 1.8;
}


/* =====================================================
   LEGENDA
===================================================== */

.legend {
    margin-top: 16px;

    padding-top: 14px;

    border-top:
        1px solid rgba(255,255,255,0.07);
}

.legend-item {
    display: flex;

    align-items: center;

    gap: 9px;

    margin: 8px 0;

    font-size: 10px;

    color: #aab4c5;
}

.legend-color {
    width: 22px;
    height: 4px;

    border-radius: 5px;
}

.pipe {
    background: #168cff;
    box-shadow: 0 0 8px #168cff;
}

.water {
    background: #55d8ff;
    box-shadow: 0 0 8px #55d8ff;
}

.leak {
    background: #ff3030;
    box-shadow: 0 0 10px #ff3030;
}


/* =====================================================
   ALERTA
===================================================== */

.alert {
    display: none;

    margin-top: 15px;

    padding: 12px;

    border-radius: 9px;

    background: rgba(255,30,30,0.10);

    border: 1px solid rgba(255,40,40,0.4);
}

.alert.show {
    display: block;
}

.alert-title {
    color: #ff4545;

    font-size: 11px;

    font-weight: bold;
}

.alert-text {
    margin-top: 5px;

    font-size: 10px;

    line-height: 1.5;

    color: #b4bdcb;
}


/* =====================================================
   MODO
===================================================== */

.mode {
    position: absolute;

    right: 24px;
    top: 24px;

    padding: 10px 14px;

    border-radius: 9px;

    background: rgba(7,12,23,0.88);

    border:
        1px solid rgba(255,255,255,0.08);

    font-size: 10px;

    color: #8995aa;

    backdrop-filter: blur(10px);

    z-index: 20;
}


/* =====================================================
   CONTADOR DE DESPERDÍCIO
===================================================== */

.water-counter {
    position: absolute;

    right: 24px;
    top: 72px;

    min-width: 190px;

    padding: 15px 18px;

    border-radius: 12px;

    background: rgba(7,12,23,0.92);

    border: 1px solid rgba(255,255,255,0.08);

    backdrop-filter: blur(15px);

    z-index: 20;

    box-shadow:
        0 15px 40px rgba(0,0,0,0.3);
}

.counter-label {
    font-size: 9px;

    color: #727e93;

    text-transform: uppercase;

    letter-spacing: 1px;
}

.counter-value {
    margin-top: 4px;

    font-size: 27px;

    font-weight: bold;

    color: #55d8ff;

    font-variant-numeric: tabular-nums;
}

.counter-unit {
    font-size: 10px;

    color: #758197;
}

.reset-counter {
    width: 100%;

    margin-top: 9px;

    padding: 7px;

    border-radius: 7px;

    border: 1px solid rgba(255,255,255,0.07);

    background: rgba(255,255,255,0.035);

    color: #8d98aa;

    font-size: 9px;

    cursor: pointer;
}

.reset-counter:hover {
    background: rgba(255,255,255,0.08);

    color: white;
}


/* =====================================================
   LABEL
===================================================== */

.house-label {
    position: absolute;

    right: 24px;
    bottom: 24px;

    padding: 10px 14px;

    border-radius: 9px;

    background: rgba(7,12,23,0.82);

    border: 1px solid rgba(255,255,255,0.08);

    color: #8d99ad;

    font-size: 9px;

    z-index: 20;
}


/* =====================================================
   AQUAFLOW — CONTROLES MULTI-AMBIENTE
===================================================== */

.environment-bar {
    position:absolute;
    top:24px;
    left:50%;
    transform:translateX(-50%);
    z-index:30;
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 10px;
    border:1px solid rgba(255,255,255,.09);
    border-radius:13px;
    background:rgba(7,12,23,.90);
    backdrop-filter:blur(18px);
    box-shadow:0 15px 45px rgba(0,0,0,.28);
}
.environment-bar label {
    color:#69758a;
    font-size:9px;
    text-transform:uppercase;
    letter-spacing:1px;
}
#environmentSelect {
    appearance:none;
    border:1px solid rgba(118,84,255,.55);
    background:#10172a;
    color:#fff;
    border-radius:9px;
    padding:10px 36px 10px 12px;
    font-size:11px;
    font-weight:bold;
    outline:none;
    cursor:pointer;
}
#environmentSelect:focus { box-shadow:0 0 0 3px rgba(118,84,255,.14); }
.environment-lock-icon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(118,84,255,.14);color:#9a86ff;font-size:14px;flex-shrink:0}
.environment-bar label{display:block;margin-bottom:2px}
.environment-bar strong{display:block;color:#fff;font-size:12px;letter-spacing:.4px}

.system-stats {
    position:absolute;
    right:24px;
    top:72px;
    z-index:25;
    width:245px;
    padding:15px;
    border:1px solid rgba(255,255,255,.08);
    border-radius:13px;
    background:rgba(7,12,23,.92);
    backdrop-filter:blur(15px);
    box-shadow:0 15px 40px rgba(0,0,0,.3);
}
.stats-title {
    color:#dce2ed;
    font-size:11px;
    font-weight:bold;
    margin-bottom:11px;
}
.stats-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
}
.stat-box {
    padding:9px;
    border-radius:9px;
    background:rgba(255,255,255,.035);
    border:1px solid rgba(255,255,255,.055);
}
.stat-label {
    color:#69758a;
    font-size:8px;
    text-transform:uppercase;
    letter-spacing:.6px;
}
.stat-value {
    margin-top:4px;
    color:#55d8ff;
    font-size:15px;
    font-weight:bold;
    font-variant-numeric:tabular-nums;
}
.stat-value.loss { color:#ff6262; }

.component-card {
    display:none;
    position:absolute;
    right:24px;
    bottom:58px;
    z-index:25;
    width:245px;
    padding:15px;
    border:1px solid rgba(118,84,255,.35);
    border-radius:13px;
    background:rgba(7,12,23,.94);
    backdrop-filter:blur(15px);
    box-shadow:0 15px 40px rgba(0,0,0,.3);
}
.component-card.show { display:block; }
.component-name {
    color:#fff;
    font-size:12px;
    font-weight:bold;
}
.component-type {
    color:#7354ff;
    font-size:8px;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-top:3px;
}
.component-info {
    margin-top:10px;
    color:#aab4c5;
    font-size:9px;
    line-height:1.7;
}
.component-close {
    float:right;
    border:0;
    background:transparent;
    color:#6f7b90;
    cursor:pointer;
    font-size:15px;
}

.flow-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-top:9px;
    padding:6px 8px;
    border-radius:7px;
    background:rgba(46,232,117,.08);
    color:#68e99a;
    font-size:8px;
}
.flow-pill::before {
    content:"";
    width:6px;
    height:6px;
    border-radius:50%;
    background:#2ee875;
    box-shadow:0 0 8px #2ee875;
}

.loss-card {
    margin-top:9px;
    padding:10px;
    border-radius:8px;
    background:rgba(255,48,48,.08);
    border:1px solid rgba(255,48,48,.25);
}
.loss-card strong { color:#ff6565; }
.loss-card span { color:#d5dbe6; }

.environment-description {
    margin-top:9px;
    color:#758197;
    font-size:9px;
    line-height:1.5;
}

@media (max-width: 800px) {
    .environment-bar {
        top:12px;
        width:calc(100% - 24px);
        justify-content:center;
    }
    .system-stats {
        right:12px;
        top:67px;
        width:205px;
    }
    .component-card {
        right:12px;
        bottom:12px;
        width:205px;
    }
    .panel {
        left:12px;
        top:67px;
        width:250px;
        max-height:calc(100vh - 79px);
    }
}

.lighting-button.active {
    border-color: #ffbd66;
    box-shadow: 0 0 18px rgba(255, 189, 102, .28);
}

/* Controle de iluminação discreto: apenas um pequeno ícone. */
.lighting-icon-button {
    width: 42px !important;
    height: 38px !important;
    min-width: 42px !important;
    padding: 0 !important;
    margin-left: auto;
    display: flex !important;
    align-items: center;
    justify-content: center;
    border-radius: 12px !important;
    border: 1px solid rgba(255,255,255,.08) !important;
    background: rgba(255,255,255,.025) !important;
    color: #7f8ba1 !important;
    font-size: 19px !important;
    line-height: 1 !important;
    box-shadow: none !important;
    transition: .2s ease;
}

.lighting-icon-button:hover {
    color: #ffd27a !important;
    border-color: rgba(255,210,122,.35) !important;
    background: rgba(255,210,122,.06) !important;
}

.lighting-icon-button.active {
    color: #ffd27a !important;
    border-color: rgba(255,210,122,.32) !important;
    background: rgba(255,210,122,.07) !important;
    box-shadow: 0 0 12px rgba(255,189,102,.12) !important;
}

/* Botão voltar ao Dashboard — integrado ao painel, sem cobrir os controles do 3D */
.dashboard-back {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 25;
    width: auto;
    height: 34px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(118,84,255,.30);
    border-radius: 10px;
    background: rgba(255,255,255,.035);
    color: #8d98ad;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .2px;
    backdrop-filter: blur(12px);
    box-shadow: none;
    transition: .2s ease;
}
.dashboard-back:hover {
    color: #fff;
    border-color: rgba(118,84,255,.65);
    background: rgba(118,84,255,.10);
    transform: translateY(-1px);
}
.dashboard-back .back-icon { font-size: 12px; line-height: 1; }
@media (max-width: 800px) {
    .dashboard-back { top: 16px; right: 16px; width: auto; height: 32px; padding: 0 10px; }
}

</style>
</head>


<body>

<div id="app">
    <div id="canvas-container"></div>

    <div class="environment-bar">
        <span class="environment-lock-icon">◈</span>
        <div>
            <label>Ambiente configurado</label>
            <strong id="environmentLockedName">CARREGANDO...</strong>
        </div>
    </div>

    <div class="system-stats">
        <div class="stats-title">Monitoramento do ambiente</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Vazão atual</div>
                <div class="stat-value" id="liveFlow">0,0 L/min</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Pontos ativos</div>
                <div class="stat-value" id="activePoints">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Perda</div>
                <div class="stat-value loss" id="liveLoss">0,00 L</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Custo perdido</div>
                <div class="stat-value loss" id="liveCost">R$ 0,00</div>
            </div>
        </div>
        <div class="environment-description" id="environmentDescription">
            Rede residencial com reservatório, pontos de consumo e piscina.
        </div>
    </div>

    <div class="component-card" id="componentCard">
        <button class="component-close" id="componentClose">×</button>
        <div class="component-name" id="componentName">Componente</div>
        <div class="component-type" id="componentType">Sistema</div>
        <div class="component-info" id="componentInfo"></div>
    </div>



    <!-- PAINEL -->

    <div class="panel">

        <a href="backend/dashboard/dashboard.php" class="dashboard-back" aria-label="Voltar para o Dashboard" title="Voltar para o Dashboard">
            <span class="back-icon">Voltar</span>
        </a>

        <div class="logo">
            AQUA<span>FLOW</span>
        </div>

        <div class="subtitle">
            Visualização inteligente do sistema hidráulico
        </div>


        <div class="status">

            <div class="status-dot"></div>

            Sistema hidráulico online

        </div>


        <button
            id="xrayButton"
            class="xray-button">

            ATIVAR RAIO-X

        </button>


        <button
            id="houseLightButton"
            class="leak-button lighting-button lighting-icon-button"
            type="button"
            aria-label="Iluminação"
            title="Iluminação">
            ☼
        </button>


        <button
            id="leakButton"
            class="leak-button">

            Simular vazamento

        </button>


        <!-- SELEÇÃO -->

        <div class="leak-selector">

            <div class="selector-header">

                <div class="selector-title">
                    Local do vazamento
                </div>

                <div class="selector-info">
                    seleção múltipla
                </div>

            </div>


            <div id="rooms" class="rooms">

                <div class="room-option"
                     data-room="sala">

                    Sala
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="cozinha">

                    Cozinha
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="lavanderia">

                    Lavanderia
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="banheiro1">

                    Banheiro 1
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="quarto1">

                    Quarto 1
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="quarto2">

                    Quarto 2
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="banheiro2">

                    Banheiro 2
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option"
                     data-room="escritorio">

                    Escritório
                    <span class="room-status">
                        Desligado
                    </span>

                </div>


                <div class="room-option pool-option"
                     data-room="piscina">

                    Piscina
                    <span class="room-status">
                        Desligado
                    </span>

                </div>

            </div>


            <button
                id="clearSelection"
                class="clear-selection">

                Limpar seleção

            </button>

        </div>


        <div class="controls">

            Arraste para girar a casa<br>
            Scroll para aproximar<br>
            Botão direito para mover

        </div>


        <div class="legend">

            <div class="legend-item">

                <div class="legend-color pipe"></div>

                Tubulação

            </div>


            <div class="legend-item">

                <div class="legend-color water"></div>

                Fluxo de água

            </div>


            <div class="legend-item">

                <div class="legend-color leak"></div>

                Vazamento

            </div>

        </div>


        <div
            id="alert"
            class="alert">

            <div class="alert-title">
                VAZAMENTO DETECTADO
            </div>

            <div
                id="alertText"
                class="alert-text">

                O AquaFlow identificou uma anomalia
                no sistema hidráulico.

            </div>

        </div>

    </div>


    <!-- MODO -->

    <div
        id="mode"
        class="mode">

        MODO NORMAL

    </div>


    <!-- CONTADOR -->

    <div class="water-counter">

        <div class="counter-label">
            Desperdício de água
        </div>

        <div class="counter-value">

            <span id="wasteValue">
                0.00
            </span>

            <span class="counter-unit">
                L
            </span>

        </div>

        <button
            id="resetCounter"
            class="reset-counter">

            Reiniciar contagem

        </button>

    </div>


    <div class="house-label">

        MODELO HIDRÁULICO — AQUAFLOW

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>


<script>

/* =====================================================
   VARIÁVEIS
===================================================== */

let scene;
let camera;
let renderer;
let controls;

let house;

let walls = [];
let roofParts = [];
let houseShellParts = [];
let furniture = [];

let pipes = [];
let water = [];

let pool;
let poolWater;
let poolPipes = [];

let xray = false;

let selectedRooms = new Set();
let activeLeaks = new Set();

let leakObjects = {};

// Iluminação global dos ambientes. Estas variáveis precisam ficar fora de init()
// porque os componentes 3D (casa, garagem, árvores e área gourmet) registram
// suas luzes durante a construção da cena.
let aquaLights = [];
let aquaGlowMaterials = [];
let houseLightsOn = false;

let totalWaste = 0;

let lastTime = performance.now();


/* =====================================================
   CONFIGURAÇÃO DOS CÔMODOS
===================================================== */

let roomData = {

    sala: {
        position: [-4.8, 0.9, -3.2],
        rate: 0.38,
        label: "Sala"
    },

    cozinha: {
        position: [4.5, 0.9, -3.2],
        rate: 0.48,
        label: "Cozinha"
    },

    lavanderia: {
        position: [-6.5, 0.9, 3.5],
        rate: 0.42,
        label: "Lavanderia"
    },

    banheiro1: {
        position: [5.5, 0.9, 2.0],
        rate: 0.55,
        label: "Banheiro 1"
    },

    quarto1: {
        position: [-5.0, 5.45, -1.65],
        rate: 0.32,
        label: "Quarto 1"
    },

    quarto2: {
        position: [4.5, 5.45, -1.65],
        rate: 0.32,
        label: "Quarto 2"
    },

    banheiro2: {
        position: [5.5, 5.45, 3.75],
        rate: 0.55,
        label: "Banheiro 2"
    },

    escritorio: {
        position: [-4.5, 5.45, 4.75],
        rate: 0.34,
        label: "Escritório"
    },

    piscina: {
        position: [13.2, 0.7, -5.0],
        rate: 0.85,
        label: "Piscina"
    }

};


/* =====================================================
   INIT
===================================================== */

function init() {

    const container =
        document.getElementById("canvas-container");


    scene =
        new THREE.Scene();

    scene.background =
        new THREE.Color(0x050811);


    camera =
        new THREE.PerspectiveCamera(
            45,
            window.innerWidth /
            window.innerHeight,
            0.1,
            1000
        );


    camera.position.set(
        24,
        15,
        25
    );


    renderer =
        new THREE.WebGLRenderer({
            antialias: true
        });


    renderer.setPixelRatio(
        Math.min(
            window.devicePixelRatio,
            2
        )
    );


    renderer.setSize(
        window.innerWidth,
        window.innerHeight
    );


    renderer.shadowMap.enabled = true;

    renderer.shadowMap.type =
        THREE.PCFSoftShadowMap;


    container.appendChild(
        renderer.domElement
    );


    controls =
        new THREE.OrbitControls(
            camera,
            renderer.domElement
        );


    controls.enableDamping = true;

    controls.dampingFactor = 0.08;

    controls.target.set(
        1,
        3,
        0
    );

    controls.minDistance = 10;

    controls.maxDistance = 45;


    /* LUZ */

    const ambient =
        new THREE.HemisphereLight(
            0xb9ad98,
            0x17130f,
            1.15
        );

    scene.add(ambient);


    const sun =
        new THREE.DirectionalLight(
            0xffffff,
            2.0
        );

    sun.position.set(
        15,
        25,
        18
    );

    sun.castShadow = true;

    sun.shadow.mapSize.width = 2048;
    sun.shadow.mapSize.height = 2048;

    scene.add(sun);


    const frontLight =
        new THREE.PointLight(
            0x5577ff,
            1.3,
            50
        );

    frontLight.position.set(
        5,
        12,
        18
    );

    scene.add(frontLight);


    // As coleções de iluminação são globais para que a casa, a garagem,
    // a área gourmet e as árvores possam registrar suas luzes.
    aquaLights.length = 0;
    aquaGlowMaterials.length = 0;
    houseLightsOn = false;

    createGround();

    createHouse();

    createFurniture();

    createPlumbing();

    createPool();

    createPoolPlumbing();

    createWater();

    createLeakObjects();

    createAdvancedEnvironments();
    setup3DInteraction();
    setupEnvironmentSelector();
    decorateHouseComponents();


    /* BOTÕES */

    document
        .getElementById("xrayButton")
        .onclick =
        toggleXray;


    document
        .getElementById("leakButton")
        .onclick =
        simulateLeak;


    document
        .getElementById("houseLightButton")
        .onclick =
        toggleHouseLighting;


    document
        .getElementById("clearSelection")
        .onclick =
        clearSelection;


    document
        .getElementById("resetCounter")
        .onclick =
        resetCounter;


    /* CÔMODOS — seleção múltipla */
    setupLeakSelectorEvents();


    window.addEventListener(
        "resize",
        resize
    );


    animate();

}


/* =====================================================
   CHÃO
===================================================== */

function createGround() {

    const ground =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                38,
                0.35,
                30
            ),

            new THREE.MeshStandardMaterial({

                color: 0x111827,

                roughness: 0.7

            })

        );


    ground.position.y = -0.3;

    ground.receiveShadow = true;

    scene.add(ground);


    const grid =
        new THREE.GridHelper(
            38,
            38,
            0x293753,
            0x182236
        );


    grid.position.y = -0.12;

    scene.add(grid);

}


/* =====================================================
   CASA
===================================================== */

function createHouse() {
    house = new THREE.Group();
    scene.add(house);

    // =====================================================
    // CASA MODERNA DE CONDOMÍNIO — estrutura fechada
    // =====================================================
    // A casa foi reorganizada para que nenhum pavimento fique
    // suspenso: o segundo andar nasce diretamente sobre o térreo.
    const concrete = 0x4a4b49;
    const concreteLight = 0x66645f;
    const dark = 0x242729;
    const warm = 0x5a4639;
    const wood = 0x765033;
    const glass = 0x2f5661;
    const green = 0x3f7b43;

    // Fundação / piso real do lote.
    addEnvironmentBox(house,houseShellParts,"Fundação da casa","Estrutura",17.6,.30,11.6,0,.05,0,0x303331);

    // TÉRREO — bloco fechado, sem buraco abaixo do segundo andar.
    addEnvironmentBox(house,houseShellParts,"Volume térreo principal","Edificação",17.0,4.55,11.0,0,2.38,0,concrete);
    addEnvironmentBox(house,houseShellParts,"Faixa frontal da fachada","Fachada",16.5,3.85,.22,0,2.45,5.58,dark);
    addEnvironmentBox(house,houseShellParts,"Painel lateral esquerdo","Fachada",.22,3.85,10.6,-8.42,2.45,0,warm);
    addEnvironmentBox(house,houseShellParts,"Painel lateral direito","Fachada",.22,3.85,10.6,8.42,2.45,0,concreteLight);

    // Fachada frontal moderna: madeira + grandes esquadrias.
    addEnvironmentBox(house,houseShellParts,"Painel de madeira da entrada","Fachada",3.3,3.65,.16,-2.0,2.45,5.72,wood);
    addEnvironmentBox(house,houseShellParts,"Painel grafite frontal","Fachada",4.0,3.65,.16,3.9,2.45,5.72,0x34383a);
    createDoor(-2.0,1.55,5.82);
    createLargeWindow(-5.25,2.55,5.73,3.7,2.35);
    createLargeWindow(3.95,2.55,5.73,4.0,2.35);

    // Marquise frontal apoiada por dois pilares, integrada ao térreo.
    addEnvironmentBox(house,houseShellParts,"Marquise da entrada","Cobertura",4.8,.26,1.65,-2.0,4.55,6.35,dark);
    [-3.75,-.25].forEach(x=>addEnvironmentBox(house,houseShellParts,"Pilar da entrada","Estrutura",.20,2.0,.20,x,3.55,6.35,dark));

    // Laje REAL entre os pavimentos — cobre toda a casa.
    addEnvironmentBox(house,houseShellParts,"Laje estrutural do segundo pavimento","Estrutura",17.15,.42,11.15,0,4.72,0,0x303332);
    addEnvironmentBox(house,houseShellParts,"Acabamento da laje","Acabamento",17.25,.16,11.25,0,4.98,0,0x1f2324);

    // SEGUNDO ANDAR — menor, centralizado e totalmente apoiado na laje.
    const upperW=12.4, upperD=8.4, upperH=3.15;
    const upperZ=.15;
    const upperY=4.72 + .42/2 + upperH/2;
    const upperFront=upperZ+upperD/2;

    addEnvironmentBox(house,houseShellParts,"Corpo fechado do segundo pavimento","Edificação",upperW,upperH,upperD,0,upperY,upperZ,0x55524d);
    addEnvironmentBox(house,houseShellParts,"Faixa inferior do segundo pavimento","Estrutura",12.65,.34,8.65,0,5.05,upperZ,0x343737);

    // Fachada superior voltada para a frente, sem peças suspensas.
    addEnvironmentBox(house,houseShellParts,"Painel superior esquerdo","Fachada",3.0,2.25,.18,-4.35,6.40,upperFront+.05,0x333638);
    addEnvironmentBox(house,houseShellParts,"Painel superior direito","Fachada",2.55,2.25,.18,4.65,6.40,upperFront+.05,warm);
    addEnvironmentBox(house,houseShellParts,"Grande vidro superior","Fachada",4.15,2.18,.12,.15,6.38,upperFront+.08,glass);
    for(let i=0;i<5;i++) addEnvironmentBox(house,houseShellParts,"Ripado superior","Fachada",.13,2.35,.16,2.75+i*.40,6.40,upperFront+.12,wood);
    createLargeWindow(-4.25,6.35,upperFront+.14,2.35,2.05);
    createLargeWindow(.20,6.35,upperFront+.15,3.15,2.05);

    // Sacada discreta, apoiada sobre a laje do segundo andar.
    addEnvironmentBox(house,houseShellParts,"Sacada superior","Lazer",4.6,.18,1.35,3.1,5.02,4.55,0x66594d);
    addEnvironmentBox(house,houseShellParts,"Guarda-corpo da sacada","Segurança",4.4,.10,.10,3.1,5.72,5.20,dark);
    for(let i=0;i<7;i++) addEnvironmentBox(house,houseShellParts,"Poste do guarda-corpo","Segurança",.07,.82,.07,1.15+i*.65,5.32,5.20,dark);

    // Cobertura superior diretamente sobre o segundo pavimento.
    addEnvironmentBox(house,houseShellParts,"Cobertura plana da residência","Telhado",12.8,.34,8.8,0,8.33,upperZ,dark);
    addEnvironmentBox(house,houseShellParts,"Platibanda superior","Acabamento",12.9,.22,.24,0,8.58,upperFront+.08,concreteLight);

    // Iluminação discreta, sem estourar o branco.
    addFacadeLight(-5.2,2.55,5.80);
    addFacadeLight(4.0,2.55,5.80);
    addFacadeLight(-4.2,6.45,upperFront+.16);
    addFacadeLight(4.5,6.45,upperFront+.16);

    // Pontos de luz externos da fachada e acesso.
    [
        [-6.4,2.2,5.9],[-3.0,2.2,5.9],[1.8,2.2,5.9],[6.2,2.2,5.9],
        [-4.5,6.1,4.5],[0,6.1,4.5],[4.4,6.1,4.5]
    ].forEach(([lx,ly,lz])=>{
        const fl=new THREE.PointLight(0xffc766,0,5.5);
        fl.position.set(lx,ly,lz);
        house.add(fl);
        aquaLights.push(fl);
    });

    // =====================================================
    // ÁREA DE LAZER — quintal gramado, coberto e separado
    // =====================================================
    const property = new THREE.Group();
    property.userData.aquaComponent={name:"Lote da residência",type:"Condomínio",details:"Calçada, muro, garagem, lazer e piscina"};

    addEnvironmentBox(property,null,"Piso do lote","Terreno",36,.12,22,0,-.10,0,0x55544e);

    // Calçada estilo condomínio: faixa contínua e limpa ao redor da residência.
    addEnvironmentBox(property,null,"Calçada frontal","Condomínio",36,.12,2.2,0,.04,9.7,0xa19d94);
    addEnvironmentBox(property,null,"Calçada traseira","Condomínio",36,.12,2.0,0,.04,-9.7,0xa19d94);
    addEnvironmentBox(property,null,"Calçada esquerda","Condomínio",2.0,.12,17.4,-17.0,.04,0,0xa19d94);
    addEnvironmentBox(property,null,"Calçada direita","Condomínio",2.0,.12,17.4,17.0,.04,0,0xa19d94);

    // =====================================================
    // FRENTE DO LOTE — calçada aberta, estilo condomínio
    // =====================================================
    // Sem muro alto: a casa fica integrada ao passeio, como na referência.
    addEnvironmentBox(property,null,"Calçada frontal","Condomínio",36,.16,2.7,0,.08,9.7,0xb1aaa0);
    addEnvironmentBox(property,null,"Faixa de acesso em pedra","Paisagismo",5.2,.10,6.0,10.8,.14,7.2,0x77736c);

    // Calçadas laterais e faixa posterior.
    addEnvironmentBox(property,null,"Calçada esquerda","Condomínio",2.2,.14,17.0,-17.0,.07,0,0x9d9890);
    addEnvironmentBox(property,null,"Calçada direita","Condomínio",2.2,.14,17.0,17.0,.07,0,0x9d9890);
    addEnvironmentBox(property,null,"Calçada traseira","Condomínio",34,.14,2.0,0,.07,-9.7,0x9d9890);

    // Meio-fio discreto, sem criar uma barreira visual.
    addEnvironmentBox(property,null,"Meio fio frontal","Paisagismo",36,.18,.18,0,.18,8.45,0x6d6b67);

    // =====================================================
    // GARAGEM INTEGRADA À CASA — inspirada na referência enviada
    // =====================================================
    // A garagem fica embutida visualmente na fachada direita, sob uma grande marquise.
    const garageX=5.75, garageZ=7.55;
    addEnvironmentBox(property,null,"Piso interno da garagem","Garagem",6.4,.16,4.35,garageX,.10,garageZ,0x3b3c3a);

    // GARAGEM ABERTA: não existe portão nem parede fechando a frente.
    // A parede de fundo fica junto à casa, deixando os carros totalmente expostos.
    addEnvironmentBox(property,null,"Parede interna da garagem","Garagem",6.0,3.35,.18,garageX,1.78,5.48,0x202322);
    addEnvironmentBox(property,null,"Painel lateral da garagem","Arquitetura",.22,3.65,4.35,8.95,1.95,garageZ,0x303230);

    // Grande marquise integrada ao volume da casa.
    addEnvironmentBox(property,null,"Marquise integrada da garagem","Cobertura",7.05,.32,4.65,garageX,4.28,garageZ,0x252725);
    addEnvironmentBox(property,null,"Forro amadeirado da garagem","Acabamento",6.75,.10,4.35,garageX,4.08,garageZ,0x604a3d);

    // Pilar lateral da marquise — poucos elementos, mais parecido com residência moderna.
    [2.35,9.15].forEach(x=>addEnvironmentBox(property,null,"Pilar da garagem","Estrutura",.24,4.0,.24,x,2.05,9.55,0x292c2b));

    // Garagem totalmente aberta na frente — sem portão/porta.
    // O fundo permanece como parede arquitetônica, deixando as duas vagas abertas.

    // =====================================================
    // DOIS CARROS — estacionados dentro da garagem
    // =====================================================
    function createCar(x,z,bodyColor,scale=1){
        const car=new THREE.Group();
        const bodyMat=new THREE.MeshStandardMaterial({color:bodyColor,roughness:.32,metalness:.28});
        const darkMat=new THREE.MeshStandardMaterial({color:0x161a1b,roughness:.3,metalness:.15});
        const glassMat=new THREE.MeshPhysicalMaterial({color:0x25363d,transparent:true,opacity:.72,roughness:.08,metalness:.18});
        const lightMat=new THREE.MeshStandardMaterial({color:0xf6ead0,emissive:0xffd27a,emissiveIntensity:1.5});
        const redMat=new THREE.MeshStandardMaterial({color:0x9d2424,emissive:0x3b0505,emissiveIntensity:.45});

        const base=new THREE.Mesh(new THREE.BoxGeometry(2.45,.62,4.25),bodyMat);
        base.position.y=.78; car.add(base);
        const hood=new THREE.Mesh(new THREE.BoxGeometry(2.28,.28,1.15),bodyMat);
        hood.position.set(0,1.08,1.45); car.add(hood);
        const cabin=new THREE.Mesh(new THREE.BoxGeometry(2.0,1.0,2.05),glassMat);
        cabin.position.set(0,1.45,-.15); car.add(cabin);
        const roof=new THREE.Mesh(new THREE.BoxGeometry(1.88,.16,1.85),bodyMat);
        roof.position.set(0,2.0,-.15); car.add(roof);
        // divisória central dos vidros
        addEnvironmentBox(car,null,"Divisória do vidro","Carro",.07,.82,1.95,0,1.48,-.15,0x222526);
        // para-choques
        const front=new THREE.Mesh(new THREE.BoxGeometry(2.32,.20,.18),darkMat); front.position.set(0,.60,2.15); car.add(front);
        const rear=new THREE.Mesh(new THREE.BoxGeometry(2.32,.20,.18),darkMat); rear.position.set(0,.60,-2.15); car.add(rear);
        // rodas
        [-1.08,1.08].forEach(xx=>[-1.42,1.42].forEach(zz=>{
            const tire=new THREE.Mesh(new THREE.CylinderGeometry(.46,.46,.24,20),darkMat);
            tire.rotation.z=Math.PI/2; tire.position.set(xx,.56,zz); car.add(tire);
            const hub=new THREE.Mesh(new THREE.CylinderGeometry(.19,.19,.27,16),new THREE.MeshStandardMaterial({color:0xb9b7b0,metalness:.7,roughness:.25}));
            hub.rotation.z=Math.PI/2; hub.position.set(xx,.56,zz); car.add(hub);
        }));
        // faróis e lanternas
        [-.72,.72].forEach(xx=>{
            const f=new THREE.Mesh(new THREE.BoxGeometry(.45,.16,.08),lightMat); f.position.set(xx,.93,2.18); car.add(f);
            const r=new THREE.Mesh(new THREE.BoxGeometry(.45,.16,.08),redMat); r.position.set(xx,.92,-2.18); car.add(r);
        });
        car.scale.setScalar(scale);
        car.position.set(x,0,z);
        car.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
        property.add(car);
    }
    createCar(4.05,7.65,0x4b5152,.82);
    createCar(7.05,7.65,0x6b4d3c,.82);

    // Iluminação quente embutida na garagem, seguindo a referência.
    for(let i=0;i<5;i++){
        const gx=2.45+i*1.40;
        const lamp=new THREE.Mesh(new THREE.BoxGeometry(.72,.055,.12),new THREE.MeshStandardMaterial({color:0xffd98a,emissive:0xffb84a,emissiveIntensity:0}));
        lamp.position.set(gx,4.00,5.0); property.add(lamp);
        const glow=new THREE.PointLight(0xffc766,0,4.5); glow.position.set(gx,3.72,5.0); property.add(glow);
        aquaLights.push(glow);
        aquaGlowMaterials.push(lamp.material);
    }

    // Balizadores na entrada da garagem — só acendem quando o sistema é ligado.
    [4.1,7.4].forEach(gx=>{
        const marker=new THREE.Mesh(
            new THREE.CylinderGeometry(.09,.13,.65,12),
            new THREE.MeshStandardMaterial({color:0x4a4741,emissive:0xffb84a,emissiveIntensity:0})
        );
        marker.position.set(gx,.38,9.05); property.add(marker);
        aquaGlowMaterials.push(marker.material);
        const gl=new THREE.PointLight(0xffc766,0,3.2);
        gl.position.set(gx,.65,9.05); property.add(gl);
        aquaLights.push(gl);
    });

    // =====================================================
    // PAISAGISMO — gramado + palmeiras na calçada
    // =====================================================
    addEnvironmentBox(property,null,"Gramado frontal esquerdo","Paisagismo",6.2,.12,2.35,-7.0,.08,7.25,0x315d35);
    addEnvironmentBox(property,null,"Gramado frontal central","Paisagismo",4.0,.12,2.35,-1.0,.08,7.25,0x315d35);
    addEnvironmentBox(property,null,"Gramado lateral da casa","Paisagismo",2.7,.12,7.0,-13.5,.08,0,0x315d35);
    addEnvironmentBox(property,null,"Gramado área lazer esquerda","Paisagismo",6.5,.12,3.0,-3.8,.08,-7.9,0x315d35);
    addEnvironmentBox(property,null,"Gramado área piscina","Paisagismo",4.0,.12,2.8,6.0,.08,-8.0,0x315d35);

    // Faixas de terra para as palmeiras.
    addEnvironmentBox(property,null,"Canteiro frontal","Paisagismo",5.8,.10,1.15,-7.0,.15,6.05,0x594733);
    addEnvironmentBox(property,null,"Canteiro lateral","Paisagismo",1.3,.10,5.2,-12.9,.15,0,0x594733);

    function addPalmTree(x,z,scale=1){
        const palm=new THREE.Group();
        const trunkMat=new THREE.MeshStandardMaterial({color:0x68492f,roughness:.9});
        const leafMat=new THREE.MeshStandardMaterial({color:0x245c34,roughness:.88});
        const leafLight=new THREE.MeshStandardMaterial({color:0x3e7b43,roughness:.86});
        const trunk=new THREE.Mesh(new THREE.CylinderGeometry(.16,.28,4.1,14),trunkMat);
        trunk.position.y=2.05; palm.add(trunk);
        for(let i=0;i<7;i++){
            const ring=new THREE.Mesh(new THREE.TorusGeometry(.20,.025,7,14),trunkMat);
            ring.rotation.x=Math.PI/2; ring.position.y=.55+i*.53; palm.add(ring);
        }
        const crown=new THREE.Group(); crown.position.y=4.0;
        // Folhas largas e arqueadas, formando uma copa mais natural.
        for(let i=0;i<12;i++){
            const a=i*Math.PI*2/12;
            const leaf=new THREE.Group();
            const stem=new THREE.Mesh(new THREE.CylinderGeometry(.025,.07,1.9,8),i%3===0?leafLight:leafMat);
            stem.rotation.z=Math.PI/2-.58;
            leaf.add(stem);
            for(let j=0;j<5;j++){
                const t=j/4;
                const leaflet=new THREE.Mesh(new THREE.BoxGeometry(.08,.035,.72),i%3===0?leafLight:leafMat);
                leaflet.position.set(.48+t*.65, -.06-t*.22, (j-2)*.17);
                leaflet.rotation.y=(j-2)*.16;
                leaf.add(leaflet);
            }
            leaf.rotation.y=a;
            leaf.rotation.z=-.10-Math.sin(a)*.05;
            crown.add(leaf);
        }
        palm.add(crown);
        palm.scale.setScalar(scale);
        palm.position.set(x,.02,z);
        palm.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
        property.add(palm);

        // Pequeno uplight quente em cada palmeira.
        const treeGlow = new THREE.PointLight(0xffc766,0,4.8);
        treeGlow.position.set(x,1.0,z);
        property.add(treeGlow);
        aquaLights.push(treeGlow);
    }
    addPalmTree(-10.8,6.8,1.05);
    addPalmTree(-4.8,7.0,.82);
    addPalmTree(-13.2,3.4,.92);
    addPalmTree(10.6,6.3,.88);
    addPalmTree(15.8,-4.8,.95);


    // =====================================================
    // ÁREA GOURMET + LAZER — integrada à piscina
    // =====================================================
    const lazerX=8.6, lazerZ=-7.0;
    addEnvironmentBox(property,null,"Piso da área gourmet","Lazer",7.2,.16,5.0,lazerX,.16,lazerZ,0x625548);
    addEnvironmentBox(property,null,"Teto da área gourmet","Cobertura",7.5,.24,5.3,lazerX,3.35,lazerZ,0x302b28);
    addEnvironmentBox(property,null,"Forro amadeirado gourmet","Acabamento",7.1,.10,4.9,lazerX,3.19,lazerZ,0x73543c);

    // Estrutura limpa da cobertura, sem fechar a frente.
    [[5.2,-9.15],[12.0,-9.15],[5.2,-4.85],[12.0,-4.85]].forEach(([x,z])=>{
        addEnvironmentBox(property,null,"Pilar da área gourmet","Estrutura",.24,3.25,.24,x,1.72,z,0x393633);
    });

    // Churrasqueira completa.
    addEnvironmentBox(property,null,"Churrasqueira gourmet","Lazer",1.45,1.75,.82,5.45,1.05,lazerZ,0x292725);
    addEnvironmentBox(property,null,"Bancada de pedra","Lazer",2.9,.18,.86,8.0,1.55,lazerZ,0x343330);
    addEnvironmentBox(property,null,"Armário inferior","Mobiliário",2.85,.72,.72,8.0,1.12,lazerZ,0x4d4037);
    addEnvironmentBox(property,null,"Cuba da pia","Lazer",.72,.08,.46,8.35,1.68,lazerZ,0x9b9a92);
    addEnvironmentBox(property,null,"Torneira gourmet","Lazer",.08,.55,.08,8.35,1.98,lazerZ,0x9b9a92);
    addEnvironmentBox(property,null,"Geladeira externa","Lazer",.72,1.75,.72,10.65,1.02,lazerZ,0x747873);

    // Mesa, banco e cadeiras para dar aparência de área gourmet real.
    addEnvironmentBox(property,null,"Mesa gourmet","Mobiliário",2.35,.14,1.25,8.35,.98,-6.15,0x684b37);
    addEnvironmentBox(property,null,"Banco gourmet","Mobiliário",2.0,.55,.42,8.35,.72,-5.38,0x4a382e);
    [-.9,.9].forEach(dx=>addEnvironmentBox(property,null,"Cadeira gourmet","Mobiliário",.55,.75,.55,8.35+dx,.82,-7.05,0x4b3a31));

    // Iluminação quente embutida e pendentes.
    for(let i=0;i<5;i++){
        const gx=5.7+i*1.45;
        const lamp=new THREE.Mesh(new THREE.BoxGeometry(.78,.06,.12),new THREE.MeshStandardMaterial({color:0xffd98a,emissive:0xffa63d,emissiveIntensity:2.6}));
        lamp.position.set(gx,3.12,-7.0); property.add(lamp);
        const glow=new THREE.PointLight(0xffc766,0,4.5); glow.position.set(gx,2.85,-7.0); property.add(glow);
        aquaLights.push(glow);
        aquaGlowMaterials.push(lamp.material);
    }

    // Porta de correr de vidro ligando diretamente a casa à área gourmet.
    const leisureDoorX=8.58, leisureDoorZ=-3.95;
    addEnvironmentBox(property,null,"Esquadria da porta gourmet","Acesso",.16,2.85,2.9,leisureDoorX,1.72,leisureDoorZ,0x242827);
    const leisureMat=new THREE.MeshPhysicalMaterial({color:0x36545a,transparent:true,opacity:.58,roughness:.08,metalness:.18,transmission:.10});
    [-1.05,0,1.05].forEach(dz=>{
        const panel=new THREE.Mesh(new THREE.BoxGeometry(.055,2.48,.92),leisureMat);
        panel.position.set(leisureDoorX-.03,1.72,leisureDoorZ+dz); panel.castShadow=true; property.add(panel);
    });
    addEnvironmentBox(property,null,"Trilho da porta gourmet","Acesso",.10,.07,3.0,leisureDoorX,.37,leisureDoorZ,0x202322);
    addEnvironmentBox(property,null,"Trilho superior da porta gourmet","Acesso",.10,.07,3.0,leisureDoorX,3.07,leisureDoorZ,0x202322);

    // Gramado real ao redor da área gourmet/piscina.
    addEnvironmentBox(property,null,"Gramado gourmet","Paisagismo",7.6,.10,2.8,8.6,.10,-4.35,0x2f6b38);
    addEnvironmentBox(property,null,"Faixa de grama piscina","Paisagismo",4.0,.10,2.6,13.2,.10,-4.45,0x2f6b38);

    // =====================================================
    // PISCINA — integrada ao espaço gourmet, sem atravessar a casa
    // =====================================================
    const poolX=13.4, poolZ=-8.0;
    addEnvironmentBox(property,null,"Deck da piscina","Piscina",7.8,.10,5.2,poolX,.05,poolZ,0x765d49);
    addEnvironmentBox(property,null,"Borda da piscina","Piscina",7.3,.16,4.7,poolX,.28,poolZ,0xb0a18d);
    addEnvironmentBox(property,null,"Água da piscina","Piscina",6.7,.12,4.1,poolX,.43,poolZ,0x1b6575);
    addEnvironmentBox(property,null,"Gramado junto à piscina","Paisagismo",3.2,.10,3.0,9.25,.10,-8.0,0x2f6b38);

    // Canteiro tropical entre lazer e piscina.
    addEnvironmentBox(property,null,"Canteiro tropical","Paisagismo",2.2,.10,4.0,11.0,.14,-6.0,0x594733);

    // Acesso de veículos e acabamento da calçada — sem invadir o volume da casa.
    addEnvironmentBox(property,null,"Piso de acesso da garagem","Condomínio",6.4,.10,1.15,garageX,.12,9.15,0x77736b);
    addEnvironmentBox(property,null,"Faixa gramada junto à calçada","Paisagismo",11.5,.10,1.25,-5.0,.12,8.35,0x315d35);
    addEnvironmentBox(property,null,"Borda do gramado","Paisagismo",11.5,.07,.12,-5.0,.19,8.95,0x594733);

    // GARAGEM ABERTA: remove qualquer portão/porta frontal que possa ter sobrado de versões anteriores.
    // Mantém apenas piso, cobertura, pilares, fundo e carros.
    const removableGarageObjects = [];
    property.traverse(o => {
        const info = o.userData && o.userData.aquaComponent;
        if (!info) return;
        const text = ((info.name || '') + ' ' + (info.type || '')).toLowerCase();
        if ((text.includes('garagem') || text.includes('garage')) &&
            (text.includes('portão') || text.includes('portao') || text.includes('porta'))) {
            removableGarageObjects.push(o);
        }
    });
    removableGarageObjects.forEach(o => {
        if (o.parent) o.parent.remove(o);
    });

    property.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
    house.add(property);
    houseShellParts.push(property);

    house.traverse(function(object){
        if(object.isMesh && houseShellParts.indexOf(object)===-1 && walls.indexOf(object)===-1) houseShellParts.push(object);
    });
}
function makeBox(width, height, depth, x, y, z, color) {
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(width, height, depth),
        new THREE.MeshStandardMaterial({
            color: color,
            roughness: 0.55,
            metalness: color === 0x20252b || color === 0x303840 ? 0.25 : 0.05
        })
    );
    mesh.position.set(x, y, z);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    return mesh;
}

function addFacadePanel(width, height, depth, x, y, z, color) {
    const panel = makeBox(width, height, depth, x, y, z, color);
    house.add(panel);
    return panel;
}

function addRail(width, height, x, y, z, material) {
    const rail = new THREE.Mesh(
        new THREE.BoxGeometry(width, 0.10, 0.10),
        material
    );
    rail.position.set(x, y + height / 2, z);
    rail.castShadow = true;
    house.add(rail);
}

function addPlanter(x, y, z, width, height, depth) {
    const planter = makeBox(width, height, depth, x, y, z, 0x2f343d);
    house.add(planter);
    const leaves = new THREE.Group();
    for (let i = 0; i < 5; i++) {
        const leaf = new THREE.Mesh(
            new THREE.SphereGeometry(0.32, 10, 10),
            new THREE.MeshStandardMaterial({ color: 0x2f6b45, roughness: 0.85 })
        );
        leaf.position.set(x - width * 0.35 + i * width * 0.18, y + height + 0.34 + (i % 2) * 0.12, z);
        leaf.scale.set(0.75, 1.2, 0.75);
        leaf.castShadow = true;
        leaves.add(leaf);
    }
    house.add(leaves);
}

function addFacadeLight(x, y, z) {
    // A luminária física foi removida da fachada para não criar os pontos
    // brancos que poluíam visualmente as janelas. A iluminação continua
    // existindo através da PointLight abaixo.
    const light = new THREE.PointLight(0xffc766, 0, 5.5);
    light.position.set(x, y, z);
    house.add(light);
    aquaLights.push(light);
}


/* =====================================================
   PAREDES
===================================================== */

function addWall(
    width,
    height,
    depth,
    x,
    y,
    z,
    color
) {

    const material =
        new THREE.MeshStandardMaterial({

            color: color,

            roughness: 0.78,

            metalness: 0.02

        });


    const wall =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                width,
                height,
                depth
            ),

            material

        );


    wall.position.set(
        x,
        y,
        z
    );


    wall.castShadow = true;

    wall.receiveShadow = true;


    walls.push(wall);

    house.add(wall);

}


/* =====================================================
   PORTAS
===================================================== */

function createDoor(
    x,
    y,
    z
) {

    const door =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                1.6,
                3,
                0.18
            ),

            new THREE.MeshStandardMaterial({

                color: 0x252b31,

                roughness: 0.55

            })

        );


    door.position.set(
        x,
        y,
        z
    );


    door.castShadow = true;

    house.add(door);


    const handle =
        new THREE.Mesh(

            new THREE.SphereGeometry(
                0.07,
                12,
                12
            ),

            new THREE.MeshStandardMaterial({

                color: 0xc8a65b,

                metalness: 0.8,

                roughness: 0.2

            })

        );


    handle.position.set(
        x + 0.45,
        y,
        z - 0.12
    );


    house.add(handle);

}


/* =====================================================
   JANELAS
===================================================== */

function createLargeWindow(
    x,
    y,
    z,
    width,
    height
) {

    const glass =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                width,
                height,
                0.08
            ),

            new THREE.MeshStandardMaterial({

                color: 0x263e50,

                transparent: true,

                opacity: 0.78,

                roughness: 0.08,

                metalness: 0.3

            })

        );


    glass.position.set(
        x,
        y,
        z
    );


    house.add(glass);


    const frameMaterial =
        new THREE.MeshStandardMaterial({

            color: 0x20252c,

            roughness: 0.35

        });


    const top =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                width + 0.12,
                0.10,
                0.18
            ),

            frameMaterial

        );


    top.position.set(
        x,
        y + height / 2,
        z
    );


    house.add(top);


    const bottom = top.clone();

    bottom.position.y =
        y - height / 2;

    house.add(bottom);


    const left =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                0.10,
                height,
                0.18
            ),

            frameMaterial

        );


    left.position.set(
        x - width / 2,
        y,
        z
    );


    house.add(left);


    const right = left.clone();

    right.position.x =
        x + width / 2;

    house.add(right);


    /* divisão central */

    const center =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                0.08,
                height,
                0.18
            ),

            frameMaterial

        );


    center.position.set(
        x,
        y,
        z
    );


    house.add(center);

}


/* =====================================================
   MÓVEIS
===================================================== */

function createFurniture() {

    /* sofá */

    addFurniture(
        3.8,
        0.9,
        1.5,
        -5.3,
        0.7,
        -3.6,
        0x4a3d50
    );


    /* mesa cozinha */

    addFurniture(
        3.4,
        0.9,
        1.5,
        4.7,
        0.7,
        -3.6,
        0x75543b
    );


    /* bancada */

    addFurniture(
        4.5,
        1,
        0.9,
        4.5,
        0.8,
        3.7,
        0x343b46
    );


    /* cama quarto 1 */

    addFurniture(
        3.3,
        0.7,
        4,
        -5,
        5.55,
        -2.8,
        0x505b6a
    );


    /* cama quarto 2 */

    addFurniture(
        3.3,
        0.7,
        4,
        5,
        5.55,
        -2.8,
        0x505b6a
    );


    /* mesa escritório */

    addFurniture(
        3,
        0.8,
        1.4,
        -4.8,
        5.55,
        3.4,
        0x674a35
    );

}


/* =====================================================
   MÓVEL GENÉRICO
===================================================== */

function addFurniture(
    width,
    height,
    depth,
    x,
    y,
    z,
    color
) {

    const object =
        new THREE.Mesh(

            new THREE.BoxGeometry(
                width,
                height,
                depth
            ),

            new THREE.MeshStandardMaterial({

                color: color,

                roughness: 0.65

            })

        );


    object.position.set(
        x,
        y,
        z
    );


    object.castShadow = true;

    furniture.push(object);

    house.add(object);

}


/* =====================================================
   TUBULAÇÃO DA CASA
===================================================== */

function createPlumbing() {

    // =====================================================
    // REDE HIDRÁULICA RESIDENCIAL REALISTA
    // =====================================================
    // A tubulação fica escondida no modo normal e aparece no
    // Raio-X. A ideia é representar o que existiria de verdade:
    // alimentação principal na parede técnica, subida vertical,
    // distribuição pelo teto/laje e descidas dentro das paredes
    // até os pontos de consumo.

    const pipeMaterial = new THREE.MeshStandardMaterial({
        color: 0x168cff,
        emissive: 0x006cff,
        emissiveIntensity: 2,
        metalness: 0.45,
        roughness: 0.2
    });

    // Pequenos registros/junções visuais para as mudanças de direção.
    const junctionMaterial = new THREE.MeshStandardMaterial({
        color: 0x168cff,
        emissive: 0x006cff,
        emissiveIntensity: 2,
        metalness: 0.45,
        roughness: 0.2
    });

    function addJunction(x, y, z, radius = 0.16) {
        const joint = new THREE.Mesh(
            new THREE.SphereGeometry(radius, 12, 12),
            junctionMaterial
        );
        joint.position.set(x, y, z);
        joint.visible = false;
        joint.userData.aquaComponent = {
            name: "Junção hidráulica",
            type: "Tubulação",
            details: "Conexão da rede hidráulica monitorada pelo AquaFlow."
        };
        pipes.push(joint);
        house.add(joint);
    }

    // -----------------------------------------------------
    // TÉRREO — alimentação principal dentro da parede lateral
    // -----------------------------------------------------
    createPipe([
        [-7.8, 0.55, 5.0],
        [-7.8, 0.55, 0.0],
        [-7.8, 0.55, -4.2],
        [-7.8, 3.90, -4.2]
    ], pipeMaterial);

    // Distribuição horizontal escondida no teto do térreo.
    createPipe([
        [-7.8, 3.90, -4.2],
        [6.2, 3.90, -4.2]
    ], pipeMaterial);

    // Sala — descida dentro da parede e pequena alimentação do ponto.
    createPipe([
        [-5.0, 3.90, -4.2],
        [-5.0, 0.95, -4.2],
        [-4.8, 0.95, -3.2]
    ], pipeMaterial);

    // Cozinha — ramal passando pelo teto e descendo atrás da bancada.
    createPipe([
        [4.5, 3.90, -4.2],
        [4.5, 0.95, -4.2],
        [4.5, 0.95, -3.2]
    ], pipeMaterial);

    // Lavanderia — ramal curto pela parede lateral.
    createPipe([
        [-7.8, 3.90, 3.5],
        [-6.5, 3.90, 3.5],
        [-6.5, 0.95, 3.5]
    ], pipeMaterial);

    // Banheiro térreo — ramal pela parede/teto até a coluna hidráulica.
    createPipe([
        [5.5, 3.90, -4.2],
        [5.5, 3.90, 2.0],
        [5.5, 0.95, 2.0]
    ], pipeMaterial);

    // -----------------------------------------------------
    // COLUNA HIDRÁULICA — atravessa a laje até o 2º andar
    // -----------------------------------------------------
    createPipe([
        [-7.8, 0.55, 0.0],
        [-7.8, 7.55, 0.0]
    ], pipeMaterial);

    // Distribuição escondida no teto do segundo pavimento.
    createPipe([
        [-7.8, 7.55, 0.0],
        [5.5, 7.55, 0.0]
    ], pipeMaterial);

    // -----------------------------------------------------
    // 2º ANDAR — ramais descendo pelas paredes
    // -----------------------------------------------------

    // Quarto 1
    createPipe([
        [-5.0, 7.55, 0.0],
        [-5.0, 5.55, 0.0],
        [-5.0, 5.45, -1.65]
    ], pipeMaterial);

    // Quarto 2
    createPipe([
        [4.5, 7.55, 0.0],
        [4.5, 5.55, 0.0],
        [4.5, 5.45, -1.65]
    ], pipeMaterial);

    // Banheiro superior — coluna descendo pela parede hidráulica.
    createPipe([
        [5.5, 7.55, 0.0],
        [5.5, 5.55, 0.0],
        [5.5, 5.45, 3.75]
    ], pipeMaterial);

    // Escritório
    createPipe([
        [-4.5, 7.55, 0.0],
        [-4.5, 5.55, 0.0],
        [-4.5, 5.45, 4.75]
    ], pipeMaterial);

    // Junções principais da rede.
    [
        [-7.8,3.9,-4.2],
        [4.5,3.9,-4.2],
        [5.5,3.9,2.0],
        [-7.8,7.55,0.0],
        [4.5,7.55,0.0],
        [5.5,7.55,0.0]
    ].forEach(p => addJunction(p[0],p[1],p[2]));
}


/* =====================================================
   CRIA CANO RETO
===================================================== */

function createPipe(
    points,
    material
) {

    for (
        let i = 0;
        i < points.length - 1;
        i++
    ) {

        const a =
            new THREE.Vector3(
                points[i][0],
                points[i][1],
                points[i][2]
            );


        const b =
            new THREE.Vector3(
                points[i + 1][0],
                points[i + 1][1],
                points[i + 1][2]
            );


        const direction =
            new THREE.Vector3()
            .subVectors(b, a);


        const length =
            direction.length();


        const geometry =
            new THREE.CylinderGeometry(
                0.10,
                0.10,
                length,
                14
            );


        const pipe =
            new THREE.Mesh(
                geometry,
                material
            );


        pipe.position
            .copy(a)
            .add(b)
            .multiplyScalar(0.5);


        pipe.quaternion.setFromUnitVectors(
            new THREE.Vector3(0, 1, 0),
            direction.normalize()
        );


        pipe.visible = false;


        pipes.push(pipe);

        house.add(pipe);

    }

}


/* =====================================================
   PISCINA
===================================================== */

function createPool() {
    const poolGroup=new THREE.Group();
    // Piscina posicionada no fundo/lateral do lote, sem atravessar a casa ou o muro.
    poolGroup.position.set(13.1,0,-7.1);
    scene.add(poolGroup);

    // Deck seco ao redor da piscina.
    const deck=new THREE.Mesh(new THREE.BoxGeometry(8.2,.10,5.5),new THREE.MeshStandardMaterial({color:0x6f5b49,roughness:.78}));
    deck.position.set(0,.02,0);
    deck.receiveShadow=true;
    poolGroup.add(deck);
    poolGroup.children.splice(poolGroup.children.indexOf(deck),1);
    poolGroup.add(deck);

    const base=new THREE.Mesh(new THREE.BoxGeometry(7.0,.55,4.4),new THREE.MeshStandardMaterial({color:0x81786c,roughness:.72}));
    base.position.y=.18; base.receiveShadow=true; poolGroup.add(base);

    poolWater=new THREE.Mesh(new THREE.BoxGeometry(6.45,.24,3.85),new THREE.MeshPhysicalMaterial({color:0x1b6575,transparent:true,opacity:.68,roughness:.12,metalness:.02,transmission:.08}));
    poolWater.position.y=.58; poolWater.receiveShadow=true; poolGroup.add(poolWater);

    const borderMaterial=new THREE.MeshStandardMaterial({color:0xb3a58f,roughness:.72});
    const b1=new THREE.Mesh(new THREE.BoxGeometry(7.5,.32,.38),borderMaterial); b1.position.set(0,.48,2.38); poolGroup.add(b1);
    const b2=b1.clone(); b2.position.z=-2.38; poolGroup.add(b2);
    const b3=new THREE.Mesh(new THREE.BoxGeometry(.38,.32,4.4),borderMaterial); b3.position.set(3.68,.48,0); poolGroup.add(b3);
    const b4=b3.clone(); b4.position.x=-3.68; poolGroup.add(b4);
    poolGroup.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
    pool=poolGroup;

    // iluminação cênica da piscina
    const poolLightMat=new THREE.MeshStandardMaterial({color:0xffd98a,emissive:0xffb84a,emissiveIntensity:2.2});
    [-2.7,0,2.7].forEach(px=>{
        const light=new THREE.Mesh(new THREE.SphereGeometry(.07,12,12),poolLightMat);
        light.position.set(px,1.0,-2.15); poolGroup.add(light);
        const pl=new THREE.PointLight(0,0,3.2); pl.position.set(px,1.0,-2.15); poolGroup.add(pl);
        aquaLights.push(pl);
        aquaGlowMaterials.push(light.material);
    });
}

function createPoolPlumbing() {
    const pipeMaterial=new THREE.MeshStandardMaterial({color:0x168cff,emissive:0x006cff,emissiveIntensity:2,metalness:.45,roughness:.2});
    // Tubulação chega pela lateral da casa e entra na piscina sem atravessar muros.
    createPoolPipe([[7.8,.35,5.0],[10.4,.35,5.0],[10.4,.35,-8.0],[13.4,.35,-8.0]],pipeMaterial);
    createPoolPipe([[13.4,.35,-8.0],[13.4,.65,-8.0]],pipeMaterial);
    createPoolPipe([[13.4,.65,-8.0],[10.4,.65,-8.0]],pipeMaterial);
    createPoolPipe([[13.4,.65,-8.0],[16.4,.65,-8.0]],pipeMaterial);
}

function createPoolPipe(
    points,
    material
) {

    for (
        let i = 0;
        i < points.length - 1;
        i++
    ) {

        const a =
            new THREE.Vector3(
                points[i][0],
                points[i][1],
                points[i][2]
            );


        const b =
            new THREE.Vector3(
                points[i + 1][0],
                points[i + 1][1],
                points[i + 1][2]
            );


        const direction =
            new THREE.Vector3()
            .subVectors(b, a);


        const length =
            direction.length();


        const geometry =
            new THREE.CylinderGeometry(
                0.10,
                0.10,
                length,
                14
            );


        const pipe =
            new THREE.Mesh(
                geometry,
                material
            );


        pipe.position
            .copy(a)
            .add(b)
            .multiplyScalar(0.5);


        pipe.quaternion.setFromUnitVectors(
            new THREE.Vector3(0, 1, 0),
            direction.normalize()
        );


        pipe.visible = false;


        poolPipes.push(pipe);

        scene.add(pipe);

    }

}


/* =====================================================
   ÁGUA CORRENDO NOS CANOS
===================================================== */

function createWater() {

    const material =
        new THREE.MeshBasicMaterial({

            color: 0x55d8ff

        });


    for (
        let i = 0;
        i < 60;
        i++
    ) {

        const particle =
            new THREE.Mesh(

                new THREE.SphereGeometry(
                    0.065,
                    8,
                    8
                ),

                material

            );


        particle.visible = false;


        particle.userData.progress =
            i / 60;


        house.add(particle);

        water.push(particle);

    }

}


/* =====================================================
   OBJETOS DE VAZAMENTO
===================================================== */

function createLeakObjects() {

    Object.keys(roomData)
        .forEach(function(roomName) {

            const data =
                roomData[roomName];


            const marker =
                new THREE.Mesh(

                    new THREE.SphereGeometry(
                        0.28,
                        20,
                        20
                    ),

                    new THREE.MeshBasicMaterial({

                        color: 0xff2020

                    })

                );


            marker.position.set(
                data.position[0],
                data.position[1],
                data.position[2]
            );


            marker.visible = false;


            const light =
                new THREE.PointLight(
                    0xff2020,
                    0,
                    5
                );


            light.position.copy(
                marker.position
            );


            const particles = [];


            for (
                let i = 0;
                i < 14;
                i++
            ) {

                const particle =
                    new THREE.Mesh(

                        new THREE.SphereGeometry(
                            0.055,
                            8,
                            8
                        ),

                        new THREE.MeshBasicMaterial({

                            color: 0x55d8ff

                        })

                    );


                particle.position.copy(
                    marker.position
                );


                particle.visible = false;


                particle.userData.vx =
                    (Math.random() - 0.5) * 0.035;


                particle.userData.vy =
                    Math.random() * 0.045;


                particle.userData.vz =
                    (Math.random() - 0.5) * 0.035;


                if (
                    roomName === "piscina"
                ) {

                    particle.userData.vx =
                        (Math.random() - 0.5) * 0.025;

                    particle.userData.vy =
                        Math.random() * 0.018;

                    particle.userData.vz =
                        (Math.random() - 0.5) * 0.025;

                }


                scene.add(particle);

                particles.push(particle);

            }


            leakObjects[roomName] = {

                marker: marker,

                light: light,

                particles: particles

            };


            if (
                roomName === "piscina"
            ) {

                scene.add(marker);

                scene.add(light);

            }
            else {

                house.add(marker);

                house.add(light);

            }

        });

}


/* =====================================================
   SELECIONAR CÔMODO
===================================================== */




/* =====================================================
   INICIAR VAZAMENTO
===================================================== */







/* =====================================================
   PARAR TODOS
===================================================== */




/* =====================================================
   LIMPAR SELEÇÃO
===================================================== */




/* =====================================================
   ATUALIZA BOTÕES
===================================================== */




/* =====================================================
   ALERTA
===================================================== */




/* =====================================================
   RAIO-X
===================================================== */

function toggleHouseLighting() {
    houseLightsOn = !houseLightsOn;
    const button = document.getElementById("houseLightButton");

    aquaLights.forEach(light => {
        // A intensidade é propositalmente suave para não estourar a cena.
        if (light === undefined) return;
        if (light.color.getHex() === 0xffc766) light.intensity = houseLightsOn ? 1.15 : 0;
        else light.intensity = houseLightsOn ? 0.95 : 0;
    });

    aquaGlowMaterials.forEach(mat => {
        if (!mat) return;
        mat.emissiveIntensity = houseLightsOn ? 2.6 : 0;
    });

    if (button) {
        button.textContent = houseLightsOn ? "☀" : "☼";
        button.title = houseLightsOn ? "Apagar iluminação" : "Acender iluminação";
        button.setAttribute("aria-label", houseLightsOn ? "Apagar iluminação" : "Acender iluminação");
        button.classList.toggle("active", houseLightsOn);
    }
}





/* =====================================================
   ÁGUA CORRENDO
===================================================== */




/* =====================================================
   ATUALIZA VAZAMENTOS
===================================================== */




/* =====================================================
   CONTADOR DE DESPERDÍCIO
===================================================== */




/* =====================================================
   REINICIAR CONTAGEM
===================================================== */




/* =====================================================
   ANIMAÇÃO
===================================================== */

function animate() {

    requestAnimationFrame(
        animate
    );


    const now =
        performance.now();


    const delta =
        Math.min(
            (now - lastTime) / 1000,
            0.1
        );


    lastTime = now;


    updateWater();

    updateLeaks(delta);

    updateWaste(delta);


    controls.update();


    renderer.render(
        scene,
        camera
    );

}


/* =====================================================
   RESIZE
===================================================== */

function resize() {

    camera.aspect =
        window.innerWidth /
        window.innerHeight;


    camera.updateProjectionMatrix();


    renderer.setSize(
        window.innerWidth,
        window.innerHeight
    );

}


/* =====================================================
   AQUAFLOW 2.0 — AMBIENTES
===================================================== */

const TIPO_IMOVEL_USUARIO = <?php echo json_encode($tipoImovel, JSON_UNESCAPED_UNICODE); ?>;
const LOCAL_ATUAL_ID = <?php echo json_encode($localAtual ? (int)$localAtual["id"] : 0); ?>;
const LOCAL_ATUAL_NOME = <?php echo json_encode($localAtual["nome"] ?? "Meu local", JSON_UNESCAPED_UNICODE); ?>;

const AMBIENTE_POR_TIPO = {
    casa: "casa",
    predio_residencial: "predio_residencial",
    empresa: "empresa",
    industria: "industria",
    predio_comercial: "predio_comercial",
    escola: "escola",
    agro: "agro"
};

const NOME_AMBIENTE = {
    casa: "CASA",
    predio_residencial: "PRÉDIO RESIDENCIAL",
    empresa: "EMPRESA / ESCRITÓRIO",
    industria: "INDÚSTRIA",
    predio_comercial: "PRÉDIO COMERCIAL / INDUSTRIAL",
    escola: "ESCOLA",
    agro: "AGRONEGÓCIO / FAZENDA"
};

let currentEnvironment = AMBIENTE_POR_TIPO[TIPO_IMOVEL_USUARIO] || "casa";

let environmentGroups = {};
let environmentSystems = {};
let advancedFlowParticles = [];
let selectedComponent = null;
let waterTariff = 7.50; // R$ por 1.000 litros (valor demonstrativo)

const environmentDescriptions = {
    casa: "Rede residencial com reservatório, pontos de consumo e piscina.",
    predio_residencial: "Edifício residencial com múltiplos pavimentos, reservatório e prumadas hidráulicas.",
    empresa: "Ambiente corporativo com copa, banheiros e pontos hidráulicos de escritório.",
    predio_comercial: "Edifício comercial com múltiplos pavimentos, banheiros, copa e prumadas hidráulicas.",
    escola: "Rede escolar com reservatórios, banheiros, cozinha e bebedouros.",
    industria: "Rede industrial com máquinas consumidoras, bombas e linhas de processo.",
    agro: "Rede rural com reservatório, bomba e setores de irrigação."
};

const environmentPoints = {
    casa: {
        sala:       {label:"Sala", rate:0.38, pos:[-4.8,0.9,-3.2], type:"Ponto de consumo"},
        cozinha:    {label:"Cozinha", rate:0.48, pos:[4.5,0.9,-3.2], type:"Ponto de consumo"},
        lavanderia: {label:"Lavanderia", rate:0.42, pos:[-6.5,0.9,3.5], type:"Ponto de consumo"},
        banheiro1:  {label:"Banheiro 1", rate:0.55, pos:[5.5,0.9,2.0], type:"Ponto de consumo"},
        quarto1:    {label:"Quarto 1", rate:0.32, pos:[-5,5.45,-3], type:"Ponto de consumo"},
        quarto2:    {label:"Quarto 2", rate:0.32, pos:[5,5.45,-3], type:"Ponto de consumo"},
        banheiro2:  {label:"Banheiro 2", rate:0.55, pos:[5.5,5.45,2.4], type:"Ponto de consumo"},
        escritorio: {label:"Escritório", rate:0.34, pos:[-4.5,5.45,3.4], type:"Ponto de consumo"},
        piscina:    {label:"Piscina", rate:0.85, pos:[13.4,0.7,-8.0], type:"Sistema da piscina"}
    },
    predio_residencial: {
        apartamento1: {label:"Apartamento 101 — cozinha", rate:0.55, pos:[-4.5,1.1,-3.5], type:"Ponto de consumo"},
        apartamento2: {label:"Apartamento 102 — banheiro", rate:0.60, pos:[4.5,1.1,-3.5], type:"Banheiro"},
        apartamento3: {label:"Apartamento 201 — cozinha", rate:0.55, pos:[-4.5,4.5,-3.5], type:"Ponto de consumo"},
        apartamento4: {label:"Apartamento 202 — banheiro", rate:0.60, pos:[4.5,4.5,-3.5], type:"Banheiro"},
        apartamento5: {label:"Apartamento 301 — cozinha", rate:0.55, pos:[-4.5,7.9,-3.5], type:"Ponto de consumo"},
        apartamento6: {label:"Apartamento 302 — banheiro", rate:0.60, pos:[4.5,7.9,-3.5], type:"Banheiro"},
        reservatorio: {label:"Reservatório superior", rate:0, pos:[0,11.0,2.8], type:"Reservatório"}
    },
    empresa: {
        banheiro: {label:"Banheiro", rate:1.5, pos:[-4.2,1.0,-3.0], type:"Banheiro"},
        copa: {label:"Copa / cozinha", rate:1.2, pos:[4.2,1.0,-3.0], type:"Copa"},
        limpeza: {label:"Ponto de limpeza", rate:0.9, pos:[-4.0,1.0,3.2], type:"Limpeza"},
        reservatorio: {label:"Reservatório", rate:0, pos:[0,5.2,3.2], type:"Reservatório"}
    },
    predio_comercial: {
        banheiro1: {label:"Banheiro — térreo", rate:2.0, pos:[-4.5,1.0,-3.2], type:"Banheiro"},
        copa1: {label:"Copa — térreo", rate:1.5, pos:[4.5,1.0,-3.2], type:"Copa"},
        banheiro2: {label:"Banheiro — 2º andar", rate:2.0, pos:[-4.5,4.5,-3.2], type:"Banheiro"},
        copa2: {label:"Copa — 2º andar", rate:1.5, pos:[4.5,4.5,-3.2], type:"Copa"},
        banheiro3: {label:"Banheiro — 3º andar", rate:2.0, pos:[-4.5,8.0,-3.2], type:"Banheiro"},
        copa3: {label:"Copa — 3º andar", rate:1.5, pos:[4.5,8.0,-3.2], type:"Copa"},
        reservatorio: {label:"Reservatório superior", rate:0, pos:[0,11.0,3.0], type:"Reservatório"}
    },
    escola: {
        // Os pontos agora ficam exatamente sobre torneiras/lavatórios,
        // e não no centro vazio dos blocos.
        banheiro1:  {label:"Banheiro Ala A — masculino", rate:2.8, pos:[-13,1.45,-0.95], type:"Banheiro"},
        banheiro2:  {label:"Banheiro Ala A — feminino", rate:2.8, pos:[-8.8,1.45,-0.95], type:"Banheiro"},
        banheiro3:  {label:"Banheiro Ala B — masculino", rate:2.8, pos:[8.8,1.45,-0.95], type:"Banheiro"},
        banheiro4:  {label:"Banheiro Ala B — feminino", rate:2.8, pos:[13,1.45,-0.95], type:"Banheiro"},
        banheiro5:  {label:"Banheiro acessível A", rate:2.2, pos:[-13,1.45,3.25], type:"Banheiro"},
        banheiro6:  {label:"Banheiro acessível B", rate:2.2, pos:[13,1.45,3.25], type:"Banheiro"},
        cozinha:    {label:"Cozinha escolar", rate:3.6, pos:[-7.2,1.45,-5.9], type:"Cozinha"},
        bebedouro1: {label:"Bebedouro corredor A", rate:1.7, pos:[-5.8,1.55,4.7], type:"Bebedouro"},
        bebedouro2: {label:"Bebedouro corredor B", rate:1.7, pos:[5.8,1.55,4.7], type:"Bebedouro"},
        bebedouro3: {label:"Bebedouro pátio A", rate:1.7, pos:[-5.8,1.55,-4.8], type:"Bebedouro"},
        bebedouro4: {label:"Bebedouro pátio B", rate:1.7, pos:[5.8,1.55,-4.8], type:"Bebedouro"},
        bebedouro5: {label:"Bebedouro entrada", rate:1.7, pos:[0,1.55,3.9], type:"Bebedouro"},
        limpeza:    {label:"Ponto de limpeza", rate:1.2, pos:[-2.5,1.45,-5.9], type:"Limpeza"},
        reservatorio:{label:"Reservatório", rate:0, pos:[15,5.45,-7.5], type:"Reservatório"}
    },
    industria: {
        maquina1: {label:"Lavadora industrial", rate:7.5, pos:[-7,.92,-2.5], type:"Máquina industrial"},
        maquina2: {label:"Centro de processamento", rate:10.2, pos:[-1.8,.92,-2.5], type:"Máquina industrial"},
        maquina3: {label:"Linha de envase", rate:8.4, pos:[4,.92,-2.5], type:"Máquina industrial"},
        maquina4: {label:"Prensa hidráulica", rate:9.0, pos:[9,.92,-2.5], type:"Máquina industrial"},
        lavagem:  {label:"Oficina — lavagem", rate:5.6, pos:[-7.5,.92,7.2], type:"Processo"},
        esteira:  {label:"Linha de montagem", rate:4.2, pos:[6.5,.92,3.6], type:"Máquina industrial"},
        torre:    {label:"Torre de resfriamento", rate:4.5, pos:[18,.92,-6], type:"Processo"}
    },
    agro: {
        setorA:    {label:"Irrigação — Setor A", rate:12.0, pos:[-9,0.8,-4], type:"Irrigação"},
        setorB:    {label:"Irrigação — Setor B", rate:10.5, pos:[0,0.8,-4], type:"Irrigação"},
        setorC:    {label:"Irrigação — Setor C", rate:9.5, pos:[9,0.8,-4], type:"Irrigação"},
        bebedouro: {label:"Bebedouro animal", rate:2.2, pos:[7,0.9,6], type:"Ponto de consumo"},
        bomba:     {label:"Bomba", rate:0, pos:[-8,1,6], type:"Bomba hidráulica"}
    }
};

function makeMaterial(color, emissive = 0x000000, intensity = 0) {
    return new THREE.MeshStandardMaterial({
        color,
        roughness:0.52,
        metalness:0.18,
        emissive,
        emissiveIntensity:intensity
    });
}

function addEnvironmentBox(group, list, name, type, w,h,d,x,y,z,color, extra={}) {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(w,h,d), makeMaterial(color, extra.emissive || 0, extra.emissiveIntensity || 0));
    mesh.position.set(x,y,z);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    mesh.userData.aquaComponent = {name, type, details: extra.details || ""};
    group.add(mesh);
    if (list) list.push(mesh);
    return mesh;
}

function createSimpleBuilding(group, shell, w, d, h, color=0xcfd4dc) {
    // Base estrutural mais detalhada: paredes, pilares, marquise e telhado.
    addEnvironmentBox(group,shell,"Piso da edificação","Estrutura",w,.35,d,0,.18,0,0x59636e);
    addEnvironmentBox(group,shell,"Parede frontal","Estrutura",w,h,.28,0,h/2,-d/2,color);
    addEnvironmentBox(group,shell,"Parede traseira","Estrutura",w,h,.28,0,h/2,d/2,color);
    addEnvironmentBox(group,shell,"Parede esquerda","Estrutura",.28,h,d,-w/2,h/2,0,color);
    addEnvironmentBox(group,shell,"Parede direita","Estrutura",.28,h,d,w/2,h/2,0,color);

    // Telhado de duas águas usando dois planos inclinados.
    const roofMat=makeMaterial(0x343a42);
    const roofA=new THREE.Mesh(new THREE.BoxGeometry(w+1,.35,d/2+1),roofMat);
    roofA.position.set(0,h+.55,-d/4);
    roofA.rotation.x=-Math.PI*.12;
    roofA.castShadow=true; roofA.receiveShadow=true;
    group.add(roofA); shell.push(roofA);
    const roofB=roofA.clone();
    roofB.position.z=d/4; roofB.rotation.x=Math.PI*.12;
    group.add(roofB); shell.push(roofB);

    // Porta principal.
    addEnvironmentBox(group,shell,"Entrada principal","Acesso",2.8,4.3,.18,0,2.15,-d/2-.16,0x28303a);

    // Janelas frontais para a leitura imediata de escola.
    for(let x=-w*.32;x<=w*.32;x+=w*.32){
        const win=addEnvironmentBox(group,shell,"Janela","Esquadria",4.0,2.2,.12,x,h*.55,-d/2-.2,0x7dc8e8,{emissive:0x164c68,emissiveIntensity:.25});
        win.userData.aquaComponent={name:"Janela",type:"Esquadria",details:"Elemento da fachada"};
    }
}

let activeBuildGroup = null;
let activeBuildEquipment = null;

function addCylinder(name, type, x, y, z, radius, height, color, details = "") {
    // Helper usado pela indústria para criar cilindros sempre dentro do grupo correto.
    if (!activeBuildGroup) return null;
    const mesh = new THREE.Mesh(
        new THREE.CylinderGeometry(radius, radius, height, 24),
        makeMaterial(color)
    );
    mesh.position.set(x, y, z);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    mesh.userData.aquaComponent = { name, type, details };
    activeBuildGroup.add(mesh);
    if (activeBuildEquipment) activeBuildEquipment.push(mesh);
    return mesh;
}

function createMultiFloorBuilding(group, shell, equipment, options = {}) {
    const floors = options.floors || 3;
    const width = options.width || 13;
    const depth = options.depth || 9;
    const floorHeight = options.floorHeight || 3.4;
    const facade = options.facade || 0x596273;
    const accent = options.accent || 0x7654ff;
    const title = options.title || "Edifício";

    for(let floor=0; floor<floors; floor++) {
        const y = floor * floorHeight;
        addEnvironmentBox(group,shell,`${title} — piso ${floor+1}`,"Estrutura",width,.25,depth,0,y+.12,0,0x343c4a);
        addEnvironmentBox(group,shell,"Fachada frontal","Estrutura",width,3.05,.22,0,y+1.65,-depth/2,facade);
        addEnvironmentBox(group,shell,"Fachada traseira","Estrutura",width,3.05,.22,0,y+1.65,depth/2,facade);
        addEnvironmentBox(group,shell,"Parede lateral","Estrutura",.22,3.05,depth,-width/2,y+1.65,0,facade);
        addEnvironmentBox(group,shell,"Parede lateral","Estrutura",.22,3.05,depth,width/2,y+1.65,0,facade);

        [-4.2,0,4.2].forEach(x=>{
            const win=addEnvironmentBox(group,equipment,"Janela panorâmica","Esquadria",3.0,1.55,.10,x,y+1.8,-depth/2-.13,0x6ebcff,{emissive:0x163c68,emissiveIntensity:.32});
            win.userData.aquaComponent={name:`Janela do piso ${floor+1}`,type:"Esquadria",details:"Esquadria da fachada monitorada."};
        });

        addEnvironmentBox(group,equipment,"Sacada / circulação","Estrutura",width*.86,.16,1.05,0,y+.38,-depth/2-.58,0x4b5363);

        if(floor < floors-1){
            addEnvironmentBox(group,equipment,"Laje","Estrutura",width,.18,depth,0,y+floorHeight-.08,0,0x454d5c);
        }
    }

    addEnvironmentBox(group,shell,"Entrada principal","Acesso",3.2,3.0,.22,0,1.5,-depth/2-.15,0x202734);
    addEnvironmentBox(group,equipment,"Portaria / recepção","Recepção",5.4,2.1,2.2,0,1.05,depth/2-1.8,0x242d3b);

    // Núcleo central com prumadas hidráulicas aparentes no Raio-X.
    const pipeMat = new THREE.MeshStandardMaterial({color:0x168cff,emissive:0x006cff,emissiveIntensity:1.6,metalness:.35,roughness:.2});
    const baseX = 0;
    const z = 1.8;
    const pts = [];
    for(let f=0; f<floors; f++) pts.push([baseX, f*floorHeight+.75, z]);
    for(let i=0;i<pts.length-1;i++) createSystemPipe(group,[],[pts[i],pts[i+1]]);

    // reservatório superior
    const tank = new THREE.Mesh(new THREE.CylinderGeometry(1.45,1.45,1.9,24),makeMaterial(0x7d8798,0x15263c,.2));
    tank.position.set(0,floors*floorHeight+1.05,1.8);
    tank.castShadow=true;
    group.add(tank);
    equipment.push(tank);
    tank.userData.aquaComponent={name:"Reservatório superior",type:"Reservatório",details:"Reservatório de abastecimento do edifício."};

    // Cobertura técnica.
    addEnvironmentBox(group,shell,"Cobertura técnica","Cobertura",width+1,.25,depth+1,0,floors*floorHeight+2.15,0,0x252c38);
}

function createResidentialBuildingEnvironment() {
    const group=new THREE.Group();
    const shell=[],equipment=[],systems=[];
    const W=15.5,D=10.5,H=3.35;
    const body=0x303744, dark=0x121923, concrete=0x747d89, glass=0x3d9ee8, purple=0x7654ff;

    // Terreno e acesso frontal
    addEnvironmentBox(group,shell,"Terreno residencial","Terreno",32,.2,28,0,.02,0,0x20262f);
    addEnvironmentBox(group,shell,"Calçada frontal","Acesso",27,.1,4,0,.18,-10.5,0x59616c);
    addEnvironmentBox(group,shell,"Jardim esquerdo","Paisagismo",5,.08,8,-10.2,.27,-7,0x304535);
    addEnvironmentBox(group,shell,"Jardim direito","Paisagismo",5,.08,8,10.2,.27,-7,0x304535);

    // Edifício moderno: três volumes exatamente alinhados sobre a mesma base.
    addEnvironmentBox(group,shell,"Base do edifício","Estrutura",W+.5,.3,D+.5,0,.35,0,0x1a212a);
    for(let f=0;f<3;f++){
        const y=.5+f*H, cy=y+H/2;
        addEnvironmentBox(group,shell,`Pavimento ${f+1}`,"Estrutura",W,3.05,D,0,cy,0,body);
        addEnvironmentBox(group,shell,"Painel arquitetônico","Fachada",3.2,3.05,.28,-5.8,cy,-D/2-.08,concrete);
        addEnvironmentBox(group,shell,"Painel escuro","Fachada",2.2,3.05,.28,6.55,cy,-D/2-.08,dark);
        [-3.25,.05,3.35].forEach((x,i)=>addEnvironmentBox(group,equipment,`Janela ${f+1}-${i+1}`,"Esquadria",2.55,1.72,.1,x,cy+.05,-D/2-.25,glass,{emissive:0x07599b,emissiveIntensity:.5}));
        addEnvironmentBox(group,equipment,`Varanda ${f+1}º pavimento`,"Sacada",9.4,.18,1.35,-.25,y+.35,-D/2-.7,dark);
        addEnvironmentBox(group,equipment,`Guarda-corpo ${f+1}º pavimento`,"Sacada",9.0,.82,.08,-.25,y+.95,-D/2-1.2,glass,{emissive:0x07599b,emissiveIntensity:.3});
        [-4.5,-2.25,0,2.25,4.5].forEach(x=>addEnvironmentBox(group,equipment,"Perfil do guarda-corpo","Sacada",.055,.88,.055,x,y+.96,-D/2-1.2,0x9aa4b2));
        addEnvironmentBox(group,equipment,"Linha de iluminação","Iluminação",W*.84,.06,.08,0,y+3.02,-D/2-.18,purple,{emissive:purple,emissiveIntensity:1.8});
        addEnvironmentBox(group,equipment,"Friso entre pavimentos","Fachada",W+.08,.08,.1,0,y-.02,-D/2-.16,0x5b6472);
    }

    // Fachada frontal: entrada central + garagem lateral
    addEnvironmentBox(group,shell,"Hall de entrada","Acesso",4.2,2.75,.24,-3.7,1.9,-D/2-.08,dark);
    addEnvironmentBox(group,equipment,"Porta principal","Acesso",1.55,2.35,.1,-3.7,1.42,-D/2-.32,glass,{emissive:0x07599b,emissiveIntensity:.7});
    addEnvironmentBox(group,equipment,"Moldura da entrada","Fachada",2.05,2.75,.12,-3.7,1.45,-D/2-.36,concrete);
    addEnvironmentBox(group,equipment,"Garagem","Garagem",4.8,2.55,.22,3.9,1.45,-D/2-.14,dark);
    addEnvironmentBox(group,equipment,"Portão da garagem","Garagem",4.25,2.25,.1,3.9,1.3,-D/2-.32,0x4b5664);

    // Cobertura única, baixa e apoiada diretamente no terceiro pavimento.
    const roofY=.5+3*H+.08;
    addEnvironmentBox(group,shell,"Cobertura superior","Cobertura",W+.45,.24,D+.45,0,roofY,0,dark);
    addEnvironmentBox(group,shell,"Platibanda frontal","Cobertura",W+.35,.5,.25,0,roofY+.34,-D/2-.1,body);
    addEnvironmentBox(group,shell,"Platibanda traseira","Cobertura",W+.35,.5,.25,0,roofY+.34,D/2+.1,body);

    // Terraço com pergolado
    addEnvironmentBox(group,equipment,"Deck do terraço","Lazer",6.3,.12,3.6,3.5,roofY+.2,1.2,0x555e6a);
    addEnvironmentBox(group,equipment,"Pergolado","Lazer",6.1,.14,3.4,3.5,roofY+2.1,1.2,dark);
    [-2.9,2.9].forEach(x=>addEnvironmentBox(group,equipment,"Pilar do pergolado","Estrutura",.14,1.9,.14,3.5+x/2,roofY+1.15,1.2,purple,{emissive:purple,emissiveIntensity:1.2}));
    [-.8,.4,1.6,2.8].forEach(z=>addEnvironmentBox(group,equipment,"Viga do pergolado","Estrutura",6.2,.1,.13,3.5,roofY+2.17,z,0x8e98a7));

    // Reservatório discreto atrás da platibanda
    const tank=new THREE.Mesh(new THREE.CylinderGeometry(1.15,1.15,1.5,28),makeMaterial(0x687487,0x142238,.25));
    tank.position.set(-4.2,roofY+1,2.6); tank.castShadow=true; group.add(tank); equipment.push(tank);
    tank.userData.aquaComponent={name:"Reservatório superior",type:"Reservatório",details:"Reservatório de abastecimento do edifício residencial."};

    // Piscina nos fundos, longe da fachada frontal
    addEnvironmentBox(group,equipment,"Deck da piscina","Lazer",15,.16,6,0,.3,9.5,0x555d68);
    const pool=new THREE.Mesh(new THREE.BoxGeometry(9.4,.55,4.5),makeMaterial(0x168cff,0x006cff,.55));
    pool.position.set(0,.64,10); pool.receiveShadow=true; pool.castShadow=true; group.add(pool); equipment.push(pool);
    pool.userData.aquaComponent={name:"Piscina residencial",type:"Lazer",details:"Área de lazer do edifício residencial."};
    addEnvironmentBox(group,equipment,"Borda da piscina","Lazer",9.9,.12,.2,0,.94,7.68,0x858d98);
    addEnvironmentBox(group,equipment,"Borda da piscina","Lazer",9.9,.12,.2,0,.94,12.32,0x858d98);

    // Paisagismo traseiro
    [-10,-7,7,10].forEach((x,i)=>{
        const tr=new THREE.Mesh(new THREE.CylinderGeometry(.14,.2,1.5,8),makeMaterial(0x503522)); tr.position.set(x,.98,6.8+(i%2)*3.8); tr.castShadow=true; group.add(tr); shell.push(tr);
        const cr=new THREE.Mesh(new THREE.SphereGeometry(.85,12,10),makeMaterial(0x2f6533)); cr.position.set(x,2.05,6.8+(i%2)*3.8); cr.scale.y=1.15; cr.castShadow=true; group.add(cr); shell.push(cr);
    });
    [-6.5,6.5].forEach(x=>addEnvironmentLightPole(group,x,-7.2,4.7,"Iluminação frontal residencial"));
    [-9,9].forEach(x=>addEnvironmentLightPole(group,x,6.5,3.8,"Iluminação da área de lazer"));

    // Prumada e ramais hidráulicos
    const pipeMat=new THREE.MeshStandardMaterial({color:0x168cff,emissive:0x006cff,emissiveIntensity:1.8,metalness:.3,roughness:.2});
    const addPipe=(a,b,r=.09)=>{const A=new THREE.Vector3(...a),B=new THREE.Vector3(...b),d=new THREE.Vector3().subVectors(B,A);const q=new THREE.Mesh(new THREE.CylinderGeometry(r,r,d.length(),14),pipeMat);q.position.copy(A).add(B).multiplyScalar(.5);q.quaternion.setFromUnitVectors(new THREE.Vector3(0,1,0),d.normalize());q.visible=false;group.add(q);systems.push(q);return q;};
    [[0,.55,1.7],[0,3.82,1.7],[0,7.17,1.7],[0,10.52,1.7],[0,11.55,1.7]].reduce((a,b)=>{if(a)addPipe(a,b);return b},null);
    [[[0,3.82,1.7],[-3.25,3.82,-5.25]],[[0,3.82,1.7],[3.35,3.82,-5.25]],[[0,7.17,1.7],[-3.25,7.17,-5.25]],[[0,7.17,1.7],[3.35,7.17,-5.25]],[[0,10.52,1.7],[-3.25,10.52,-5.25]],[[0,10.52,1.7],[3.35,10.52,-5.25]]].forEach(x=>addPipe(x[0],x[1],.065));

    scene.add(group);
    environmentGroups.predio_residencial=group;
    environmentSystems.predio_residencial={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("predio_residencial",group,environmentPoints.predio_residencial);
}
function createCompanyEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];

    const concrete = 0x6f7785;
    const dark = 0x0d1420;
    const wall = 0x182333;
    const glass = 0x2aa9e8;
    const glassDark = 0x123b58;
    const purple = 0x7654ff;
    const floor = 0x303b4b;
    const green = 0x274b35;
    const wood = 0x735b43;

    // Terreno e fachada frontal (frente = eixo Z negativo)
    addEnvironmentBox(group, shell, "Terreno da empresa", "Área externa", 32, .20, 26, 0, .02, 0, 0x111a27);
    addEnvironmentBox(group, shell, "Calçada frontal", "Acesso", 27, .10, 4.5, 0, .18, -11.0, 0x68727f);
    addEnvironmentBox(group, shell, "Entrada de veículos", "Acesso", 5.0, .08, 7.5, -9.0, .25, -7.0, 0x4e5968);

    // Estacionamento lateral
    addEnvironmentBox(group, shell, "Estacionamento", "Área externa", 10.0, .08, 9.0, -10.0, .25, -2.5, 0x202a38);
    [-12.5, -9.5, -6.5].forEach(x => {
        addEnvironmentBox(group, equipment, "Vaga de estacionamento", "Estacionamento", 2.35, .035, 6.2, x, .30, -2.5, 0x3b4655);
    });

    // Jardim e paisagismo
    addEnvironmentBox(group, shell, "Jardim frontal", "Paisagismo", 9.0, .08, 4.0, 9.5, .25, -8.2, green);
    [7.0, 9.5, 12.0].forEach((x, i) => {
        const trunk = new THREE.Mesh(new THREE.CylinderGeometry(.13, .18, 1.35, 10), makeMaterial(0x4b3526));
        trunk.position.set(x, .95, -8.1 + i * .6);
        trunk.castShadow = true;
        group.add(trunk); shell.push(trunk);
        const crown = new THREE.Mesh(new THREE.SphereGeometry(.78, 14, 10), makeMaterial(0x35663e));
        crown.position.set(x, 2.0, -8.1 + i * .6);
        crown.scale.y = 1.15;
        crown.castShadow = true;
        group.add(crown); shell.push(crown);
    });

    // Edifício térreo contemporâneo
    addEnvironmentBox(group, shell, "Base da empresa", "Estrutura", 19.0, .30, 11.0, 1.0, .35, 0, dark);
    addEnvironmentBox(group, shell, "Piso interno", "Piso", 18.5, .12, 10.5, 1.0, .58, 0, floor);
    addEnvironmentBox(group, shell, "Parede traseira", "Estrutura", 18.5, 4.9, .28, 1.0, 2.9, 5.1, wall);
    addEnvironmentBox(group, shell, "Parede lateral esquerda", "Estrutura", .28, 4.9, 10.2, -8.0, 2.9, 0, wall);
    addEnvironmentBox(group, shell, "Parede lateral direita", "Estrutura", .28, 4.9, 10.2, 10.0, 2.9, 0, wall);

    // Fachada frontal limpa e envidraçada
    addEnvironmentBox(group, shell, "Moldura superior", "Fachada", 18.9, .25, .30, 1.0, 5.25, -5.15, dark);
    addEnvironmentBox(group, shell, "Pilar esquerdo", "Estrutura", .28, 4.65, .34, -7.8, 2.9, -5.12, concrete);
    addEnvironmentBox(group, shell, "Pilar direito", "Estrutura", .28, 4.65, .34, 9.8, 2.9, -5.12, concrete);

    [-6.0, -3.8, -1.6, .6, 2.8, 5.0, 7.2, 9.0].forEach(x => {
        addEnvironmentBox(group, equipment, "Painel de vidro da fachada", "Esquadria", 1.95, 3.85, .10, x, 2.95, -5.28, glassDark, {
            emissive: 0x07599b, emissiveIntensity: .38
        });
    });

    // Entrada central destacada
    addEnvironmentBox(group, equipment, "Porta principal", "Acesso", 2.6, 3.55, .12, 1.0, 2.35, -5.38, glass, {
        emissive: 0x0878bd, emissiveIntensity: .7
    });
    addEnvironmentBox(group, equipment, "Moldura da entrada", "Acesso", 3.25, 3.95, .16, 1.0, 2.65, -5.48, purple, {
        emissive: purple, emissiveIntensity: 1.2
    });
    addEnvironmentBox(group, equipment, "Marquise", "Cobertura", 5.8, .22, 2.4, 1.0, 5.25, -5.85, dark);
    addEnvironmentBox(group, equipment, "Faixa de identidade", "Identificação", 8.5, .12, .12, 1.0, 4.72, -5.47, purple, {
        emissive: purple, emissiveIntensity: 2.0,
        details: "Identidade visual GrapeDev / AquaFlow."
    });

    // Hall e divisórias de vidro
    addEnvironmentBox(group, shell, "Divisória do hall", "Divisória", .12, 2.7, 7.0, -3.5, 1.9, -.2, glassDark, {
        emissive: 0x07599b, emissiveIntensity: .15
    });
    addEnvironmentBox(group, shell, "Divisória sala de reunião", "Divisória", 6.4, 2.7, .12, 5.0, 1.9, 2.1, glassDark, {
        emissive: 0x07599b, emissiveIntensity: .15
    });

    // Recepção premium
    addEnvironmentBox(group, equipment, "Balcão da recepção", "Administrativo", 3.5, 1.05, 1.0, -5.3, 1.15, -2.9, 0x344253);
    addEnvironmentBox(group, equipment, "Painel da recepção", "Administrativo", 4.0, 2.65, .10, -5.3, 2.25, 3.95, dark);
    addEnvironmentBox(group, equipment, "Logo da recepção", "Identificação", 2.2, .55, .08, -5.3, 2.65, 3.88, purple, {
        emissive: purple, emissiveIntensity: 1.4
    });
    addEnvironmentBox(group, equipment, "Sofá", "Mobiliário", 3.1, .72, 1.0, -5.4, 1.0, 1.8, 0x4c5969);
    addEnvironmentBox(group, equipment, "Mesa de apoio", "Mobiliário", 1.2, .70, .7, -2.7, .96, 2.1, wood);

    // Área de trabalho aberta
    addEnvironmentBox(group, equipment, "Mesa de trabalho 1", "Administrativo", 6.2, .72, 1.25, 2.0, .98, -2.6, 0x465364);
    addEnvironmentBox(group, equipment, "Mesa de trabalho 2", "Administrativo", 6.2, .72, 1.25, 2.0, .98, .8, 0x465364);
    [[-.2,-2.6],[2.0,-2.6],[4.2,-2.6],[-.2,.8],[2.0,.8],[4.2,.8]].forEach(([x,z]) => {
        addEnvironmentBox(group, equipment, "Estação de trabalho", "Mobiliário", .85, .78, .65, x, 1.42, z, 0x202a37);
        addEnvironmentBox(group, equipment, "Monitor", "Equipamento", .50, .34, .08, x, 1.95, z - .06, glass, {
            emissive: 0x0878bd, emissiveIntensity: .7
        });
    });

    // Sala de reunião executiva
    addEnvironmentBox(group, equipment, "Mesa de reunião", "Administrativo", 4.5, .72, 2.1, 5.0, .98, 3.65, wood);
    [-1.8, 0, 1.8].forEach(x => {
        addEnvironmentBox(group, equipment, "Cadeira de reunião", "Mobiliário", .55, .72, .55, 5.0 + x, 1.0, 2.15, 0x303b4a);
        addEnvironmentBox(group, equipment, "Cadeira de reunião", "Mobiliário", .55, .72, .55, 5.0 + x, 1.0, 5.15, 0x303b4a);
    });
    addEnvironmentBox(group, equipment, "Tela da sala de reunião", "Equipamento", 3.0, 1.5, .08, 5.0, 2.4, 4.98, glass, {
        emissive: 0x0878bd, emissiveIntensity: .65
    });

    // Copa moderna
    addEnvironmentBox(group, equipment, "Bancada da copa", "Copa", 3.6, .85, 1.0, 5.8, 1.02, -2.7, 0x65717f);
    addEnvironmentBox(group, equipment, "Armário da copa", "Copa", 3.6, 1.9, .65, 5.8, 1.75, -4.05, 0x242e3c);
    addEnvironmentBox(group, equipment, "Geladeira", "Copa", 1.0, 2.2, .85, 8.0, 1.7, -2.7, 0x7a8591);
    addEnvironmentBox(group, equipment, "Mesa da copa", "Copa", 2.2, .72, 1.2, 5.7, .98, -.95, wood);

    // Banheiro
    addEnvironmentBox(group, equipment, "Bancada do banheiro", "Banheiro", 2.5, .82, .7, -5.3, 1.0, -1.0, 0x7a8490);
    addEnvironmentBox(group, equipment, "Cabine sanitária", "Banheiro", 2.5, 2.1, .12, -5.3, 1.65, .2, 0x3f4956);
    addEnvironmentBox(group, equipment, "Espelho", "Banheiro", 1.7, 1.1, .06, -5.3, 2.05, -1.38, glass, {
        emissive: 0x0878bd, emissiveIntensity: .25
    });

    // Iluminação linear
    [[-3.5,-2.0],[1.0,-2.0],[5.5,-2.0],[-3.5,2.6],[1.0,2.6],[5.5,2.6]].forEach(([x,z]) => {
        const mat = new THREE.MeshStandardMaterial({color: 0xdcecff, emissive: 0x6caeff, emissiveIntensity: 1.6, roughness: .25, metalness: .05});
        const lamp = new THREE.Mesh(new THREE.BoxGeometry(2.3, .06, .12), mat);
        lamp.position.set(x, 4.72, z);
        group.add(lamp); equipment.push(lamp); aquaGlowMaterials.push(mat);
    });

    // Cobertura e reservatório visível
    addEnvironmentBox(group, shell, "Cobertura técnica", "Cobertura", 19.0, .20, 11.0, 1.0, 5.30, 0, dark);
    addEnvironmentBox(group, shell, "Platibanda frontal", "Cobertura", 19.2, .55, .25, 1.0, 5.55, -5.22, wall);
    const tank = new THREE.Mesh(new THREE.CylinderGeometry(1.05, 1.05, 1.45, 24), makeMaterial(0x6d7887, 0x142238, .25));
    tank.position.set(7.2, 6.15, 3.4); tank.castShadow = true; group.add(tank); equipment.push(tank);
    tank.userData.aquaComponent = {name:"Reservatório", type:"Reservatório", details:"Reservatório de abastecimento do escritório."};

    [-11.5, 12.5].forEach(x => addEnvironmentLightPole(group, x, -9.3, 4.5, "Iluminação externa do escritório"));

    // Hidráulica
    createSystemPipe(group, systems, [[7.2, 5.9, 3.4], [7.2, 1.0, 3.4]]);
    createSystemPipe(group, systems, [[7.2, 1.0, 3.4], [-5.3, 1.0, 3.4]]);
    createSystemPipe(group, systems, [[-5.3, 1.0, 3.4], [-5.3, 1.0, -1.0]]);
    createSystemPipe(group, systems, [[7.2, 1.0, 3.4], [5.8, 1.0, 3.4]]);
    createSystemPipe(group, systems, [[5.8, 1.0, 3.4], [5.8, 1.0, -2.7]]);

    scene.add(group);
    environmentGroups.empresa = group;
    environmentSystems.empresa = {shell, equipment, systems, flow: []};
    addEnvironmentMarkers("empresa", group, environmentPoints.empresa);
}

function createCommercialBuildingEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];

    const W = 16;
    const D = 10;
    const H = 3.25;
    const floors = 3;
    const body = 0x3a4656;
    const dark = 0x101722;
    const concrete = 0x687585;
    const glass = 0x248fd1;
    const glassDark = 0x173b56;
    const purple = 0x7654ff;
    const floorMat = 0x252f3c;
    const sidewalk = 0x4b535e;

    // Terreno e acesso: tudo apoiado no mesmo nível para evitar peças flutuando.
    addEnvironmentBox(group, shell, "Terreno comercial", "Terreno", 34, .20, 30, 0, .10, 0, 0x20262f);
    addEnvironmentBox(group, shell, "Calçada frontal", "Acesso", 28, .12, 4.2, 0, .26, -12.1, sidewalk);
    addEnvironmentBox(group, shell, "Calçada lateral", "Acesso", 4.0, .12, 18, 10.1, .26, -1.5, sidewalk);
    addEnvironmentBox(group, shell, "Faixa de jardim", "Paisagismo", 7.0, .08, 8.5, -11.0, .22, -7.0, 0x294631);
    addEnvironmentBox(group, shell, "Estacionamento", "Área externa", 12.0, .08, 7.0, 9.0, .21, -7.0, 0x303740);

    // Base única do edifício. Os três pavimentos começam exatamente sobre ela.
    const baseY = .30;
    addEnvironmentBox(group, shell, "Base estrutural", "Estrutura", W + .50, .35, D + .50, 0, baseY, 0, 0x1a212a);

    for (let f = 0; f < floors; f++) {
        const yBottom = baseY + .35 + f * H;
        const yCenter = yBottom + H / 2;
        const floorName = f === 0 ? "Térreo" : `${f + 1}º andar`;

        // Volume estrutural fechado, sem vãos entre os pavimentos.
        addEnvironmentBox(group, shell, `Estrutura — ${floorName}`, "Estrutura", W, H, D, 0, yCenter, 0, body);

        // Lajes reais entre os pavimentos, encaixadas na altura correta.
        if (f > 0) {
            addEnvironmentBox(group, shell, `Laje — ${floorName}`, "Estrutura", W + .08, .16, D + .08, 0, yBottom, 0, floorMat);
        }

        // Fachada frontal moderna (frente = Z negativo).
        addEnvironmentBox(group, equipment, `Faixa de vidro — ${floorName}`, "Fachada", 13.8, 2.05, .12, 0, yCenter + .05, -D/2 - .08, glassDark, {
            emissive: 0x07599b, emissiveIntensity: .28
        });
        [-5.3, -1.8, 1.8, 5.3].forEach((x, i) => {
            addEnvironmentBox(group, equipment, `Janela ${f + 1}-${i + 1}`, "Esquadria", 2.75, 1.55, .10, x, yCenter + .08, -D/2 - .18, glass, {
                emissive: 0x0878bd, emissiveIntensity: .65
            });
        });

        // Laterais envidraçadas para dar profundidade sem deixar paredes soltas.
        addEnvironmentBox(group, equipment, `Vidro lateral esquerdo — ${floorName}`, "Esquadria", .10, 1.75, 6.6, -W/2 - .08, yCenter + .05, 0, glassDark, {
            emissive: 0x07599b, emissiveIntensity: .22
        });
        addEnvironmentBox(group, equipment, `Vidro lateral direito — ${floorName}`, "Esquadria", .10, 1.75, 6.6, W/2 + .08, yCenter + .05, 0, glassDark, {
            emissive: 0x07599b, emissiveIntensity: .22
        });

        // Friso luminoso apoiado na própria fachada.
        addEnvironmentBox(group, equipment, `Iluminação da fachada — ${floorName}`, "Iluminação", 14.2, .07, .10, 0, yBottom + H - .16, -D/2 - .22, purple, {
            emissive: purple, emissiveIntensity: 1.8
        });

        // Sacada técnica estreita, apoiada no pavimento e com guarda-corpo.
        addEnvironmentBox(group, equipment, `Sacada técnica — ${floorName}`, "Sacada", 14.0, .16, 1.0, 0, yBottom + .10, -D/2 - .55, floorMat);
        addEnvironmentBox(group, equipment, `Guarda-corpo — ${floorName}`, "Sacada", 13.7, .72, .07, 0, yBottom + .52, -D/2 - 1.02, glass, {
            emissive: 0x07599b, emissiveIntensity: .28
        });

        // Pequenas divisórias internas para o prédio não parecer um bloco vazio.
        addEnvironmentBox(group, shell, `Núcleo interno — ${floorName}`, "Divisória", .16, H - .35, 5.8, 0, yCenter, 1.25, dark);
        addEnvironmentBox(group, shell, `Divisória esquerda — ${floorName}`, "Divisória", 5.0, H - .35, .12, -5.0, yCenter, 1.25, dark);
        addEnvironmentBox(group, shell, `Divisória direita — ${floorName}`, "Divisória", 5.0, H - .35, .12, 5.0, yCenter, 1.25, dark);
    }

    // Entrada principal: todos os elementos encostados no térreo e na fachada.
    const frontZ = -D/2 - .18;
    addEnvironmentBox(group, shell, "Hall de entrada", "Acesso", 5.8, 3.0, .24, 0, baseY + 1.75, frontZ, dark);
    addEnvironmentBox(group, equipment, "Portal da entrada", "Acesso", 4.3, 2.95, .14, 0, baseY + 1.72, frontZ - .10, concrete);
    addEnvironmentBox(group, equipment, "Porta automática", "Acesso", 2.25, 2.55, .08, 0, baseY + 1.48, frontZ - .20, glass, {
        emissive: 0x0878bd, emissiveIntensity: .8
    });
    addEnvironmentBox(group, equipment, "Marquise principal", "Cobertura", 7.0, .22, 2.0, 0, baseY + 3.15, frontZ - .75, purple, {
        emissive: purple, emissiveIntensity: .45
    });
    addEnvironmentBox(group, equipment, "Letreiro do prédio", "Identificação", 5.6, .58, .10, 0, baseY + 2.75, frontZ - .28, dark);
    addEnvironmentBox(group, equipment, "Letreiro GRAPEDEV", "Identificação", 3.9, .18, .06, 0, baseY + 2.86, frontZ - .35, glass, {
        emissive: 0x168cff, emissiveIntensity: 1.2
    });

    // Portaria/receptivo no térreo.
    addEnvironmentBox(group, equipment, "Balcão da portaria", "Recepção", 4.6, .82, 1.1, 0, baseY + 1.05, -1.8, 0x586474);
    addEnvironmentBox(group, equipment, "Painel da recepção", "Recepção", 3.5, 1.7, .10, 0, baseY + 2.0, 1.0, glassDark, {
        emissive: 0x0878bd, emissiveIntensity: .4
    });

    // Cobertura correta: fica imediatamente acima do terceiro pavimento.
    const topY = baseY + .35 + floors * H;
    const roofY = topY + .16;
    addEnvironmentBox(group, shell, "Laje da cobertura", "Cobertura", W + .40, .24, D + .40, 0, roofY, 0, dark);
    addEnvironmentBox(group, shell, "Platibanda frontal", "Cobertura", W + .35, .55, .24, 0, roofY + .38, -D/2 - .08, body);
    addEnvironmentBox(group, shell, "Platibanda traseira", "Cobertura", W + .35, .55, .24, 0, roofY + .38, D/2 + .08, body);
    addEnvironmentBox(group, shell, "Platibanda esquerda", "Cobertura", .24, .55, D + .35, -W/2 - .08, roofY + .38, 0, body);
    addEnvironmentBox(group, shell, "Platibanda direita", "Cobertura", .24, .55, D + .35, W/2 + .08, roofY + .38, 0, body);

    // Casa de máquinas e reservatório apoiados diretamente na cobertura.
    addEnvironmentBox(group, equipment, "Casa de máquinas", "Cobertura", 4.2, 1.35, 3.4, -4.0, roofY + .78, 1.0, floorMat);
    const tank = new THREE.Mesh(
        new THREE.CylinderGeometry(1.25, 1.25, 1.65, 28),
        makeMaterial(0x718092, 0x142238, .25)
    );
    tank.position.set(4.6, roofY + 1.0, 1.8);
    tank.castShadow = true;
    tank.receiveShadow = true;
    group.add(tank);
    equipment.push(tank);
    tank.userData.aquaComponent = {
        name: "Reservatório superior",
        type: "Reservatório",
        details: "Reservatório principal de abastecimento do prédio comercial."
    };

    // Área externa funcional: estacionamento, jardim e postes.
    [-7.5, -2.5, 2.5, 7.5].forEach(x => {
        addEnvironmentBox(group, equipment, "Vaga de estacionamento", "Estacionamento", 3.8, .04, 5.2, x, .25, -7.0, 0x3a414b);
    });
    [-10.5, 10.5].forEach(x => addEnvironmentLightPole(group, x, -9.0, 5.0, "Poste do prédio comercial"));
    [-11.5, 11.5].forEach(x => addEnvironmentLightPole(group, x, 7.0, 4.0, "Iluminação lateral"));

    // Rede hidráulica completa: prumada vertical + ramais em cada pavimento + pontos monitorados.
    const mainX = 3.8;
    const mainZ = 2.0;
    createSystemPipe(group, systems, [
        [mainX, baseY + .35, mainZ],
        [mainX, topY + .10, mainZ]
    ]);

    for (let f = 0; f < floors; f++) {
        const y = baseY + .35 + f * H + .95;
        // Ramal principal do pavimento.
        createSystemPipe(group, systems, [[mainX, y, mainZ], [-4.8, y, mainZ]]);
        createSystemPipe(group, systems, [[-4.8, y, mainZ], [-4.8, y, -3.2]]);
        createSystemPipe(group, systems, [[mainX, y, mainZ], [4.8, y, mainZ]]);
        createSystemPipe(group, systems, [[4.8, y, mainZ], [4.8, y, -3.2]]);
        // Ramais visíveis no Raio-X até os pontos da fachada.
        createSystemPipe(group, systems, [[-4.8, y, -3.2], [-4.8, y, -4.85]],);
        createSystemPipe(group, systems, [[4.8, y, -3.2], [4.8, y, -4.85]],);
    }
    // Alimentação do reservatório.
    createSystemPipe(group, systems, [[mainX, topY + .10, mainZ], [4.6, topY + .18, 1.8]]);

    scene.add(group);
    environmentGroups.predio_comercial = group;
    environmentSystems.predio_comercial = {shell, equipment, systems, flow: []};
    addEnvironmentMarkers("predio_comercial", group, environmentPoints.predio_comercial);
}

function createAdvancedEnvironments() {
    environmentGroups.casa = house;
    environmentSystems.casa = {shell:houseShellParts, systems:pipes.concat(poolPipes), equipment:furniture, flow:[]};

    createResidentialBuildingEnvironment();
    createCompanyEnvironment();
    createCommercialBuildingEnvironment();
    createSchoolEnvironment();
    createIndustryEnvironment();
    createAgroEnvironment();

    Object.keys(environmentGroups).forEach(function(key){
        environmentGroups[key].visible = key === "casa";
    });

    buildEnvironmentFlow("casa");
    setEnvironment("casa");
}


function addEnvironmentLightPole(group, x, z, height=5.2, label="Poste de iluminação") {
    const poleMat = new THREE.MeshStandardMaterial({color:0x252a2a, roughness:.55, metalness:.35});
    const lampMat = new THREE.MeshStandardMaterial({color:0xc99552, emissive:0xffb84d, emissiveIntensity:0});
    const pole = new THREE.Mesh(new THREE.CylinderGeometry(.11,.15,height,12), poleMat);
    pole.position.set(x,height/2,z); pole.castShadow=true; group.add(pole);
    const arm = new THREE.Mesh(new THREE.BoxGeometry(1.15,.12,.12), poleMat);
    arm.position.set(x+.42,height-.18,z); arm.castShadow=true; group.add(arm);
    const fixture = new THREE.Mesh(new THREE.CylinderGeometry(.22,.28,.16,16), lampMat);
    fixture.position.set(x+.92,height-.28,z); fixture.rotation.z=Math.PI/2; fixture.castShadow=true; group.add(fixture);
    const light = new THREE.PointLight(0xffc766,0,10,2);
    light.position.set(x+.92,height-.42,z); group.add(light); aquaLights.push(light); aquaGlowMaterials.push(lampMat);
    pole.userData.aquaComponent={name:label,type:"Iluminação",details:"Poste com iluminação controlada pelo sistema AquaFlow."};
    return pole;
}

function createSchoolEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];

    // =====================================================
    // ESCOLA — referência visual na Escola SESI-SP Pedro
    // Sukadolnik (CE 357), em Mococa: volume horizontal,
    // fachada com faixa amarela, base escura, jardim frontal,
    // acesso central e áreas esportivas separadas.
    // =====================================================
    const wall = 0xc8b99c;
    const wallDark = 0x6b5140;
    const yellow = 0xe4b72d;
    const red = 0xa92f2a;
    const glass = 0x416d73;
    const roof = 0x55504a;
    const floor = 0x8a806f;
    const green = 0x4e7b42;

    // Terreno e jardim frontal.
    addEnvironmentBox(group,shell,"Campus escolar","Terreno",42,.25,30,0,.05,0,0x567844);
    addEnvironmentBox(group,shell,"Calçada frontal","Acesso",32,.10,4,0,.22,13.0,0x9a9080);
    addEnvironmentBox(group,shell,"Caminho de entrada","Acesso",4.0,.10,10,0,.25,8.0,0xb0a58e);
    addEnvironmentBox(group,shell,"Jardim frontal","Área verde",30,.08,7,0,.18,10.0,0x638b4d);

    // Bloco principal horizontal, com duas alas e acesso central.
    addEnvironmentBox(group,shell,"Ala A — salas de aula","Edificação",13.5,5.0,8.0,-9.2,2.65,1.2,wall);
    addEnvironmentBox(group,shell,"Ala B — salas de aula","Edificação",13.5,5.0,8.0,9.2,2.65,1.2,wall);
    addEnvironmentBox(group,shell,"Bloco central — hall","Edificação",5.2,5.2,8.4,0,2.75,1.0,wallDark);

    // Faixa amarela característica da fachada e base escura.
    addEnvironmentBox(group,equipment,"Faixa amarela da fachada","Identidade visual",26.8,1.0,.22,0,3.55,5.30,yellow);
    addEnvironmentBox(group,equipment,"Faixa inferior da fachada","Acabamento",27.0,.75,.24,0,.85,5.31,red);

    // Coberturas baixas, assentadas diretamente nos blocos.
    addEnvironmentBox(group,shell,"Cobertura ala A","Cobertura",14.1,.32,8.5,-9.2,5.18,1.2,roof);
    addEnvironmentBox(group,shell,"Cobertura ala B","Cobertura",14.1,.32,8.5,9.2,5.18,1.2,roof);
    addEnvironmentBox(group,shell,"Cobertura bloco central","Cobertura",5.8,.32,8.9,0,5.38,1.0,roof);

    // Fachada frontal: vidro, portas, pilares e marquise.
    addEnvironmentBox(group,equipment,"Marquise da entrada","Cobertura",7.0,.28,2.2,0,5.0,6.45,red);
    addEnvironmentBox(group,equipment,"Porta principal","Acesso",3.2,3.1,.20,0,1.85,5.36,0x30332f);
    addEnvironmentBox(group,equipment,"Placa vertical SESI","Identificação",1.0,5.0,.28,4.25,2.7,5.38,red,{details:"Escola SESI-SP Pedro Sukadolnik — CE 357"});
    [-12.8,-9.4,-6.0,-2.5,2.5,6.0,9.4,12.8].forEach(x=>{
        addEnvironmentBox(group,equipment,"Janela da sala","Esquadria",2.35,1.85,.16,x,3.0,5.35,glass);
    });
    [-15.7,-2.9,2.9,15.7].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da fachada","Estrutura",.28,4.9,.34,x,2.65,5.05,wallDark));

    // Pátio coberto CENTRAL atrás do hall, não no canto.
    addEnvironmentBox(group,shell,"Pátio coberto central","Pátio",15.5,.20,7.0,0,.38,-5.4,0x776d5f);
    addEnvironmentBox(group,shell,"Cobertura do pátio","Cobertura",15.0,.30,6.5,0,5.05,-5.4,roof);
    [-6,-3,3,6].forEach(x=>addEnvironmentBox(group,shell,"Pilar do pátio","Estrutura",.28,4.7,.28,x,2.65,-5.4,wallDark));

    // Bloco de serviços ao fundo: cozinha, depósito e apoio.
    addEnvironmentBox(group,shell,"Cozinha escolar","Serviço",7.0,3.5,4.0,-9.0,2.0,-7.5,0x8b806d,{details:"Cozinha com pias e pontos de água"});
    addEnvironmentBox(group,shell,"Depósito e limpeza","Serviço",4.0,3.5,4.0,-2.5,2.0,-7.5,0x756d61);
    addEnvironmentBox(group,shell,"Administração","Administrativo",7.0,3.5,4.0,9.0,2.0,-7.5,0x887b69);

    // Banheiros agrupados nas extremidades das alas, próximos aos corredores.
    const bathrooms = [
        [-13.0,-1.7],[ -8.8,-1.7 ],
        [  8.8,-1.7],[ 13.0,-1.7 ],
        [-13.0, 2.5],[ 13.0, 2.5 ],
        [-2.5,-7.5],[ 2.5,-7.5 ]
    ];
    bathrooms.forEach((v,i)=>{
        addEnvironmentBox(group,equipment,"Banheiro "+(i+1),"Banheiros",3.0,2.35,2.5,v[0],1.38,v[1],0x70685d,{details:"Conjunto de vasos, lavatórios e pontos de água"});
        // divisórias internas para a leitura do banheiro no Raio-X
        addEnvironmentBox(group,equipment,"Parede divisória sanitária","Banheiros",.12,2.0,2.2,v[0],1.35,v[1],0x514b44);
        addEnvironmentBox(group,equipment,"Lavatório","Ponto de consumo",.9,.55,.45,v[0],.95,v[1]+.75,0x9a9b93);
    });

    // Bebedouros distribuídos nos corredores/pátio, não aleatoriamente.
    const fountains=[[-5.8,4.7],[5.8,4.7],[-5.8,-4.8],[5.8,-4.8],[0,9.0]];
    fountains.forEach((v,i)=>addEnvironmentBox(group,equipment,"Bebedouro "+(i+1),"Bebedouro",1.0,1.25,.55,v[0],.92,v[1],0x72766d,{details:"Ponto de água para alunos"}));

    // Cozinha: bancadas e pias, deixando claro que existe consumo.
    addEnvironmentBox(group,equipment,"Bancada da cozinha","Cozinha",5.2,.85,.85,-9.0,1.0,-5.9,0x5d554b);
    addEnvironmentBox(group,equipment,"Pia da cozinha","Ponto de consumo",1.3,.55,.65,-7.2,1.45,-5.9,0x9b9d95);
    addEnvironmentBox(group,equipment,"Lavagem de utensílios","Ponto de consumo",1.3,.55,.65,-10.8,1.45,-5.9,0x9b9d95);
    addEnvironmentBox(group,equipment,"Tanque de limpeza","Ponto de consumo",1.2,.55,.60,-2.5,1.45,-5.9,0x9b9d95,{details:"Ponto de água para limpeza e manutenção"});

    // Reservatório e casa de bombas em uma área técnica externa.
    // O reservatório agora é uma caixa d'água elevada, com torre,
    // plataforma, pernas de sustentação e tampa — sem ficar flutuando.
    addEnvironmentBox(group,shell,"Base técnica","Estrutura",6.0,.25,5.0,15.0,.18,-7.5,0x4e514b);
    addEnvironmentBox(group,shell,"Plataforma da caixa d'água","Estrutura",4.2,.28,4.2,15.0,4.02,-7.5,0x454a45);
    [[13.55,-8.85],[16.45,-8.85],[13.55,-6.15],[16.45,-6.15]].forEach(([x,z])=>{
        addEnvironmentBox(group,shell,"Pé da torre do reservatório","Estrutura",.28,3.75,.28,x,2.0,z,0x555a54);
    });

    const tankMaterial = makeMaterial(0xb8beb8,0x000000,0);
    const tank = new THREE.Mesh(
        new THREE.CylinderGeometry(1.72,1.82,2.65,32),
        tankMaterial
    );
    tank.position.set(15,5.48,-7.5);
    tank.castShadow=true;
    tank.receiveShadow=true;
    group.add(tank);
    equipment.push(tank);
    tank.userData.aquaComponent={
        name:"Reservatório escolar",
        type:"Reservatório",
        details:"Caixa d'água elevada — capacidade demonstrativa: 10.000 L"
    };

    // Tampa superior levemente abaulada e anel de acabamento.
    const tankTop = new THREE.Mesh(
        new THREE.SphereGeometry(1.73,.70,32,16,0,Math.PI*2,0,Math.PI/2),
        tankMaterial
    );
    tankTop.position.set(15,6.80,-7.5);
    tankTop.scale.y=.72;
    tankTop.castShadow=true;
    group.add(tankTop);
    equipment.push(tankTop);

    const tankBase = new THREE.Mesh(
        new THREE.CylinderGeometry(1.78,1.88,.18,32),
        makeMaterial(0x747b73)
    );
    tankBase.position.set(15,4.15,-7.5);
    tankBase.castShadow=true;
    group.add(tankBase);
    shell.push(tankBase);

    // Bomba de recalque protegida dentro da área técnica.
    addEnvironmentBox(group,equipment,"Bomba de recalque","Bomba",2.2,1.35,1.8,11.8,.95,-7.5,0x30342f,{details:"Pressurização da rede hidráulica escolar"});

    // Área esportiva separada do prédio, na lateral direita.
    addEnvironmentBox(group,shell,"Quadra esportiva","Esporte",15.5,.08,9.5,10.5,.45,10.0,0x477049);
    addEnvironmentBox(group,shell,"Linha central da quadra","Esporte",.08,.04,9.0,10.5,.53,10.0,0xd5cdbd);
    addEnvironmentBox(group,shell,"Linha lateral da quadra","Esporte",14.8,.04,.08,10.5,.53,5.5,0xd5cdbd);
    addEnvironmentBox(group,shell,"Linha lateral da quadra","Esporte",14.8,.04,.08,10.5,.53,14.5,0xd5cdbd);
    [-1.8,22.8].forEach(x=>{
        addEnvironmentBox(group,equipment,"Tabela de basquete","Esporte",.22,3.2,.22,x,2.0,10.0,0x292d2b);
        addEnvironmentBox(group,equipment,"Aro de basquete","Esporte",1.2,.12,.12,x,3.55,9.25,0xc28f32);
    });

    // Jardim e árvores como na leitura de campus real.
    [[-18,10],[-15,10],[18,10],[20,7],[-18,-5],[19,-5]].forEach(([x,z])=>{
        const trunk=new THREE.Mesh(new THREE.CylinderGeometry(.11,.15,1.5,8),makeMaterial(0x5a3d2a));
        trunk.position.set(x,.95,z); trunk.castShadow=true; group.add(trunk); shell.push(trunk);
        const crown=new THREE.Mesh(new THREE.SphereGeometry(.9,10,8),makeMaterial(0x3e6d3c));
        crown.position.set(x,2.0,z); crown.castShadow=true; group.add(crown); shell.push(crown);
    });

    // =====================================================
    // REDE HIDRÁULICA REALISTA DA ESCOLA
    // A tubulação acompanha corredores e paredes, com
    // descidas verticais até cada ponto de consumo.
    // =====================================================
    const mainY=.92;
    const ceilingY=4.65;

    // Alimentação principal: reservatório -> prumada -> corredor central.
    createSystemPipe(group,systems,[[15,4.15,-7.5],[15,ceilingY,-7.5],[0,ceilingY,-7.5],[0,ceilingY,4.7],[0,mainY,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[0,mainY,-1.7]]);

    // Ala A: linha de distribuição junto à parede/corredor.
    createSystemPipe(group,systems,[[0,mainY,-1.7],[-8.8,mainY,-1.7],[-13,mainY,-1.7]]);
    createSystemPipe(group,systems,[[-13,mainY,-1.7],[-13,1.45,-0.95]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,1.45,-0.95]]);
    createSystemPipe(group,systems,[[-13,mainY,2.5],[-13,1.45,3.25]]);
    createSystemPipe(group,systems,[[-13,mainY,-1.7],[-13,mainY,2.5]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,mainY,2.5]]);

    // Ala B.
    createSystemPipe(group,systems,[[0,mainY,-1.7],[8.8,mainY,-1.7],[13,mainY,-1.7]]);
    createSystemPipe(group,systems,[[8.8,mainY,-1.7],[8.8,1.45,-0.95]]);
    createSystemPipe(group,systems,[[13,mainY,-1.7],[13,1.45,-0.95]]);
    createSystemPipe(group,systems,[[13,mainY,2.5],[13,1.45,3.25]]);
    createSystemPipe(group,systems,[[8.8,mainY,-1.7],[8.8,mainY,2.5]]);
    createSystemPipe(group,systems,[[13,mainY,-1.7],[13,mainY,2.5]]);

    // Bebedouros: ramais curtos saindo da linha principal e descendo
    // exatamente até a altura das torneiras.
    createSystemPipe(group,systems,[[0,mainY,4.7],[-5.8,mainY,4.7],[-5.8,1.55,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[5.8,mainY,4.7],[5.8,1.55,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[0,mainY,-4.8],[-5.8,mainY,-4.8],[-5.8,1.55,-4.8]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[0,mainY,-4.8],[5.8,mainY,-4.8],[5.8,1.55,-4.8]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[0,mainY,3.9],[0,1.55,3.9]]);

    // Cozinha: ramal pela parede até a pia e ponto de lavagem.
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,mainY,-5.9],[-7.2,mainY,-5.9],[-7.2,1.45,-5.9]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-5.9],[-2.5,mainY,-5.9],[-2.5,1.45,-5.9]]);

    // POSTES DE ILUMINAÇÃO — distribuídos pelo pátio e acesso.
    [-13.8,-7.0,0,7.0,13.8].forEach(x=>addEnvironmentLightPole(group,x,8.0,4.8,"Poste do pátio escolar"));
    [-12.5,0,12.5].forEach(x=>addEnvironmentLightPole(group,x,-9.0,4.5,"Poste da área externa escolar"));

    group.rotation.y=0;
    scene.add(group);
    environmentGroups.escola=group;
    environmentSystems.escola={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("escola",group,environmentPoints.escola);
}

function createIndustryEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];
    activeBuildGroup = group;
    activeBuildEquipment = equipment;

    // =====================================================
    // INDÚSTRIA — reconstruída para ficar apoiada no terreno.
    // Nada de volumes atravessando paredes ou telhados soltos.
    // =====================================================
    const concrete=0x68655e, wall=0x505654, dark=0x202522, steel=0x414a47;
    const roof=0x292d2c, glass=0x315a5a, red=0x9f352b, yellow=0xc59d32;

    // Terreno e piso do pátio.
    addEnvironmentBox(group,shell,"Terreno industrial","Terreno",42,.30,30,0,.00,0,0x454b47);
    addEnvironmentBox(group,shell,"Pátio de manobra","Acesso",40,.10,8,0,.20,10.0,0x77736a);
    addEnvironmentBox(group,shell,"Faixa de segurança","Sinalização",30,.06,.5,0,.31,5.8,yellow);

    // -------------------------
    // ADMINISTRAÇÃO
    // -------------------------
    addEnvironmentBox(group,shell,"Fundação administrativa","Fundação",11.2,.25,7.4,-14,.17,1.5,0x3c403d);
    addEnvironmentBox(group,shell,"Piso administrativo","Piso",10.7,.12,7.0,-14,.36,1.5,0x66635c);
    addEnvironmentBox(group,shell,"Prédio administrativo","Administrativo",10.5,4.3,6.8,-14,2.56,1.5,0x77746b);
    // Telhado diretamente apoiado sobre as paredes.
    addEnvironmentBox(group,shell,"Cobertura administrativa","Cobertura",11.0,.28,7.3,-14,4.83,1.5,roof);
    addEnvironmentBox(group,equipment,"Recepção","Acesso",3.6,2.7,.16,-14,1.65,-1.96,dark);
    [-17,-14,-11].forEach(x=>addEnvironmentBox(group,equipment,"Janela administrativa","Esquadria",2.0,1.35,.12,x,2.9,-1.97,glass));
    addEnvironmentBox(group,equipment,"Porta administrativa","Acesso",1.5,2.8,.18,-14,1.55,-1.99,red);

    // -------------------------
    // NAVE PRINCIPAL
    // -------------------------
    // Fundação, piso e paredes têm a mesma projeção: o prédio nasce do chão.
    const naveX=2.5, naveZ=-1.2, naveW=25.0, naveD=22.0, naveH=7.0;
    addEnvironmentBox(group,shell,"Fundação da nave","Fundação",naveW+.5,.28,naveD+.5,naveX,.14,naveZ,0x343937);
    addEnvironmentBox(group,shell,"Piso da nave","Piso",naveW,.16,naveD,naveX,.36,naveZ,0x555650);

    // Parede frontal dividida em trechos, deixando três vãos reais de garagem.
    // Os dois vãos laterais permanecem fechados por portões; o vão central fica aberto para o caminhão.
    const frontZ = naveZ - naveD/2;
    const bayW = 5.0;
    const gapW = (naveW - bayW*3) / 4;
    const frontSegments = [
        {x:naveX-naveW/2+gapW/2, w:gapW},
        {x:naveX-naveW/2+gapW+bayW+gapW/2, w:gapW},
        {x:naveX-naveW/2+gapW*2+bayW*2+gapW/2, w:gapW},
        {x:naveX-naveW/2+gapW*3+bayW*3+gapW/2, w:gapW}
    ];
    frontSegments.forEach((seg,i)=>{
        addEnvironmentBox(group,shell,"Parede frontal — trecho "+(i+1),"Edificação",seg.w,naveH,.28,seg.x,naveH/2+.44,frontZ,wall);
    });
    addEnvironmentBox(group,shell,"Parede traseira da nave","Edificação",naveW,naveH,.28,naveX,naveH/2+.44,naveZ+naveD/2,wall);
    addEnvironmentBox(group,shell,"Parede esquerda da nave","Edificação",.28,naveH,naveD,naveX-naveW/2,naveH/2+.44,naveZ,wall);
    addEnvironmentBox(group,shell,"Parede direita da nave","Edificação",.28,naveH,naveD,naveX+naveW/2,naveH/2+.44,naveZ,wall);

    // Cobertura única, apoiada sobre o topo das paredes.
    addEnvironmentBox(group,shell,"Telhado principal da fábrica","Cobertura",naveW+.45,.34,naveD+.45,naveX,naveH+.61,naveZ,roof);
    // Faixa superior/fachada, presa à parede frontal.
    addEnvironmentBox(group,equipment,"Faixa de identificação","Identificação",12,.85,.16,naveX,5.65,naveZ-naveD/2-.16,red,{details:"Planta industrial monitorada pelo AquaFlow"});

    // =====================================================
    // FACHADA DA FÁBRICA — UMA ÚNICA GARAGEM ABERTA
    // Os três vãos têm exatamente a mesma largura e ficam alinhados
    // com a estrutura da nave. Apenas o vão central permanece aberto.
    // =====================================================
    const bayCenters = [naveX-7.5, naveX, naveX+7.5];

    // Fecha completamente as duas baias laterais.
    [bayCenters[0], bayCenters[2]].forEach((x,i)=>{
        addEnvironmentBox(group,shell,"Parede de fechamento da baia "+(i===0?1:3),"Edificação",bayW-.12,naveH,.24,x,naveH/2+.44,frontZ,wall);
        // Porta metálica desenhada na parede, sem criar um segundo vão.
        addEnvironmentBox(group,equipment,"Porta metálica da baia "+(i===0?1:3),"Logística",bayW-.35,4.35,.035,x,2.55,frontZ-.145,dark);
        addEnvironmentBox(group,equipment,"Faixa da porta da baia "+(i===0?1:3),"Sinalização",bayW-.75,.12,.05,x,4.62,frontZ-.18,yellow);
    });

    // Uma única garagem aberta: o vão central.
    addEnvironmentBox(group,equipment,"Faixa de segurança da garagem central","Sinalização",bayW+.5,.10,.25,naveX,.58,frontZ-.30,yellow);
    addEnvironmentBox(group,equipment,"Piso da garagem central","Logística",bayW,.12,5.8,naveX,.50,naveZ-7.9,0x4b4a45);

    // Parede imediatamente atrás do caminhão: fecha visualmente a garagem
    // sem deixar a área de máquinas aparecendo pelo vão frontal.
    const garageBackZ = naveZ - 5.15;
    addEnvironmentBox(group,shell,"Parede de fundo da garagem central","Edificação",bayW-.12,5.2,.24,naveX,2.95,garageBackZ,wall);

    // Janelas laterais — presas às paredes, sem atravessar a estrutura.
    // Elas ficam no lado externo da nave, em vez de ocupar a fachada da garagem.
    [-5.8,0.5,6.8].forEach(z=>{
        addEnvironmentBox(group,equipment,"Janela lateral esquerda da produção","Esquadria",.10,1.20,3.0,naveX-naveW/2-.17,5.15,z,glass);
        addEnvironmentBox(group,equipment,"Janela lateral direita da produção","Esquadria",.10,1.20,3.0,naveX+naveW/2+.17,5.15,z,glass);
    });
    addEnvironmentBox(group,equipment,"Entrada de funcionários","Acesso",2.0,3.0,.18,naveX+naveW/2+.20,1.9,naveZ-naveD/2-.18,red);

    // Marquise frontal: pequena, horizontal e sustentada por postes.
    addEnvironmentBox(group,equipment,"Marquise industrial","Cobertura",24.2,.28,1.7,naveX,6.10,naveZ-naveD/2-1.05,roof);
    [-9,-1,7,14].forEach(x=>addEnvironmentBox(group,equipment,"Suporte da marquise","Estrutura",.22,1.55,.22,x,5.28,naveZ-naveD/2-1.05,dark));

    // -------------------------
    // OFICINA E UTILIDADES
    // -------------------------
    addEnvironmentBox(group,shell,"Fundação da oficina","Fundação",9.7,.25,8.7,-7.5,.17,7.2,0x393e3b);
    addEnvironmentBox(group,shell,"Piso da oficina","Piso",9.2,.12,8.2,-7.5,.36,7.2,0x5c5a53);
    addEnvironmentBox(group,shell,"Oficina e manutenção","Oficina",9.0,4.8,8.0,-7.5,2.77,7.2,concrete);
    addEnvironmentBox(group,shell,"Telhado da oficina","Cobertura",9.5,.30,8.5,-7.5,5.35,7.2,roof);

    addEnvironmentBox(group,shell,"Fundação das utilidades","Fundação",7.7,.25,6.7,12.8,.17,7.2,0x393e3b);
    addEnvironmentBox(group,shell,"Piso das utilidades","Piso",7.2,.12,6.2,12.8,.36,7.2,0x555851);
    addEnvironmentBox(group,shell,"Casa de utilidades","Utilidades",7.0,5.2,6.0,12.8,2.96,7.2,steel);
    addEnvironmentBox(group,shell,"Telhado das utilidades","Cobertura",7.5,.30,6.5,12.8,5.72,7.2,roof);

    // -------------------------
    // MÁQUINAS — todas dentro do piso da nave.
    // -------------------------
    function factoryMachine(x,z,w,d,h,bodyColor,name,accent){
        addEnvironmentBox(group,equipment,"Base — "+name,"Estrutura",w+.65,.22,d+.65,x,.56,z,dark);
        addEnvironmentBox(group,equipment,name,"Máquina industrial",w,h,d,x,.68+h/2,z,bodyColor,{details:"Equipamento de produção com consumo de água monitorado"});
        addEnvironmentBox(group,equipment,"Painel de controle — "+name,"Controle",.75,1.15,.16,x,1.45,z-d/2-.10,accent);
        addEnvironmentBox(group,equipment,"Módulo superior — "+name,"Máquina",w*.55,.55,d*.45,x,.68+h+.28,z,accent);
    }
    factoryMachine(-7,1.8,3.2,2.6,2.5,0x6e5542,"Lavadora industrial",red);
    factoryMachine(-1.8,1.8,4.0,3.0,3.0,0x4d5551,"Centro de processamento",yellow);
    factoryMachine(4.0,1.8,3.4,2.7,2.6,0x6b4c3c,"Linha de envase",red);
    factoryMachine(9.0,1.8,3.0,2.5,2.8,0x4a514d,"Prensa hidráulica",yellow);

    addEnvironmentBox(group,equipment,"Esteira de produção","Máquina",11.5,.42,1.2,0,1.15,6.0,dark,{details:"Linha transportadora da fábrica"});
    for(let x=-4.8;x<=4.8;x+=2.4){
        const roller=new THREE.Mesh(new THREE.CylinderGeometry(.24,.24,1.0,16),makeMaterial(0x181d1b));
        roller.rotation.z=Math.PI/2; roller.position.set(x,1.40,6.0); roller.castShadow=true; group.add(roller); equipment.push(roller);
    }
    addEnvironmentBox(group,equipment,"Estação de montagem","Máquina",3.3,2.3,2.6,6.5,1.72,6.0,0x555b56);

    // -------------------------
    // UTILIDADES EXTERNAS — todos os equipamentos apoiados no pátio.
    // -------------------------
    addEnvironmentBox(group,shell,"Pátio de utilidades","Utilidades",15,.25,8,13,.34,-7.0,0x343936);
    addCylinder("Tanque de processo 01","Reservatório",9.5,2.35,-7.0,1.55,4.1,0x68706b,"Tanque de processo");
    addCylinder("Tanque de processo 02","Reservatório",13.5,2.15,-7.0,1.35,3.7,0x5b625d,"Tanque auxiliar");
    addCylinder("Torre de resfriamento","Resfriamento",18.0,3.0,-6.0,2.0,5.5,0x515955,"Circuito de água de resfriamento");
    addEnvironmentBox(group,equipment,"Chaminé industrial","Utilidade",1.35,8,1.35,18,4.0,.6,dark,{details:"Exaustão da planta"});
    addEnvironmentBox(group,equipment,"Bomba principal","Bomba",2.4,1.5,2.0,8.2,1.0,6.7,0x282c2a,{details:"Bombeamento da rede de processo"});

    // Doca traseira: cobertura apoiada em pilares, sem teto flutuante.
    addEnvironmentBox(group,shell,"Doca de carga","Logística",13,.55,4,-10,.66,9.0,0x625e55);
    [-14,-10,-6].forEach(x=>addEnvironmentBox(group,equipment,"Porta da doca","Logística",2.6,3.6,.20,x,2.45,7.0,dark));
    addEnvironmentBox(group,equipment,"Cobertura da doca","Cobertura",13.6,.30,3.3,-10,4.90,9.0,roof);
    [-15.5,-10,-4.5].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da doca","Estrutura",.24,4.2,.24,x,2.75,10.25,dark));

    // -------------------------
    // GARAGEM CENTRAL ABERTA + CAMINHÃO INDUSTRIAL
    // -------------------------
    // O caminhão ocupa exatamente o vão central da fachada.
    // Não existe uma segunda garagem sobreposta ao prédio. A cobertura é a própria marquise frontal.
    const truck = new THREE.Group();
    truck.position.set(naveX, .62, naveZ-8.8);
    truck.rotation.y = 0;
    truck.userData.aquaComponent = {
        name: "Caminhão de abastecimento",
        type: "Veículo industrial",
        details: "Caminhão de apoio estacionado na garagem central aberta."
    };

    const truckRed = makeMaterial(0x8f2924);
    const truckDark = makeMaterial(0x202522);
    const truckMetal = makeMaterial(0x666c67);
    const truckGlass = makeMaterial(0x31535a);
    const truckWhite = makeMaterial(0xb9b5aa);

    function truckPart(geo, mat, x,y,z, cast=true){
        const m=new THREE.Mesh(geo,mat);
        m.position.set(x,y,z); m.castShadow=cast; m.receiveShadow=true; truck.add(m);
        return m;
    }

    // Chassi
    truckPart(new THREE.BoxGeometry(2.25,.35,6.7),truckDark,0,.38,0);
    // Cabine na frente, voltada para fora da garagem.
    truckPart(new THREE.BoxGeometry(2.35,2.65,2.35),truckRed,0,1.73,-2.05);
    truckPart(new THREE.BoxGeometry(2.15,.75,1.05),truckRed,0,1.03,-3.42);
    truckPart(new THREE.BoxGeometry(2.0,1.0,.08),truckGlass,0,2.10,-3.25,false);
    [-1.18,1.18].forEach(x=>truckPart(new THREE.BoxGeometry(.08,1.0,1.25),truckGlass,x,2.10,-2.10,false));

    // Espelhos, para-choque e grade.
    [-1.45,1.45].forEach(x=>truckPart(new THREE.BoxGeometry(.12,.35,.42),truckDark,x,1.95,-3.35));
    truckPart(new THREE.BoxGeometry(2.5,.32,.32),truckMetal,0,.73,-3.92);
    truckPart(new THREE.BoxGeometry(1.55,.32,.12),truckDark,0,1.05,-3.98);

    // Carroceria de carga, alinhada atrás da cabine.
    truckPart(new THREE.BoxGeometry(2.45,3.05,3.65),truckWhite,0,1.93,1.15);
    truckPart(new THREE.BoxGeometry(2.58,.18,3.78),truckDark,0,3.50,1.15);
    [-1.28,1.28].forEach(x=>truckPart(new THREE.BoxGeometry(.04,.20,3.35),truckRed,x,2.43,1.15));

    function truckWheel(x,z,r){
        const wheel=truckPart(new THREE.CylinderGeometry(r,r,.46,24),truckDark,x,.16,z);
        wheel.rotation.z=Math.PI/2;
        const hub=truckPart(new THREE.CylinderGeometry(r*.30,r*.30,.50,16),truckMetal,x,.16,z);
        hub.rotation.z=Math.PI/2;
    }
    truckWheel(-1.30,-2.65,.62); truckWheel(1.30,-2.65,.62);
    truckWheel(-1.30,1.35,.72); truckWheel(1.30,1.35,.72);
    truckWheel(-1.30,2.45,.72); truckWheel(1.30,2.45,.72);

    // Faróis controlados pela iluminação geral.
    const headlightMat = new THREE.MeshStandardMaterial({color:0xffd27a,emissive:0xffa83d,emissiveIntensity:0});
    [-.72,.72].forEach(x=>{
        truckPart(new THREE.BoxGeometry(.38,.28,.10),headlightMat,x,1.08,-3.96,false);
        aquaGlowMaterials.push(headlightMat);
    });

    // Iluminação discreta dentro da garagem central.
    [-1.5,1.5].forEach(x=>{
        const fixtureMat=new THREE.MeshStandardMaterial({color:0xb98a4d,emissive:0xffb84d,emissiveIntensity:0});
        truckPart(new THREE.CylinderGeometry(.20,.24,.12,16),fixtureMat,x,5.92,frontZ-naveZ+1.0,false);
        aquaGlowMaterials.push(fixtureMat);
        const light=new THREE.PointLight(0xffc766,0,10,2);
        light.position.set(x,5.70,frontZ-naveZ+1.0);
        truck.add(light); aquaLights.push(light);
    });

    group.add(truck); equipment.push(truck);

    // -------------------------
    // REDE HIDRÁULICA — mantida como sistema do AquaFlow.
    // -------------------------
    const y=.92;
    createSystemPipe(group,systems,[[8.2,y,6.7],[8.2,y,-2.5],[4.0,y,-2.5],[-1.8,y,-2.5],[-7,y,-2.5]]);
    createSystemPipe(group,systems,[[4.0,y,-2.5],[9.0,y,-2.5]]);
    createSystemPipe(group,systems,[[8.2,y,-2.5],[9.5,y,-7.0],[13.5,y,-7.0],[18,y,-6.0]]);
    createSystemPipe(group,systems,[[8.2,y,6.7],[0,y,6.7],[-7.5,y,7.2]]);
    createSystemPipe(group,systems,[[0,y,3.6],[6.5,y,3.6]]);

    // POSTES — ficam somente no pátio externo, fora das paredes da nave.
    // A posição é mantida fora do volume do prédio para evitar qualquer interseção.
    [-12,-4,4,12].forEach(x=>addEnvironmentLightPole(group,x,10.8,4.8,"Poste do pátio industrial"));
    [-10,0,10].forEach(x=>addEnvironmentLightPole(group,x,-14.0,4.8,"Poste da área externa industrial"));

    group.rotation.y=Math.PI;
    scene.add(group);
    environmentGroups.industria=group;
    environmentSystems.industria={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("industria",group,environmentPoints.industria);
    activeBuildGroup = null;
    activeBuildEquipment = null;
}
function createAgroEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];

    // =====================================================
    // FAZENDA — cenário rural integrado, escuro e terroso
    // =====================================================
    const ground = addEnvironmentBox(group,shell,"Terreno da fazenda","Terreno",42,.35,31,0,-.18,0,0x3f7130);
    ground.receiveShadow = true;

    // =====================================================
    // RIO — colocado em uma faixa livre, separado dos talhões.
    // A fazenda é rotacionada 180° no final; por isso usamos o
    // lado -Z local para o rio aparecer na frente (+Z) da cena.
    // =====================================================
    const riverGroup = new THREE.Group();
    riverGroup.userData.aquaComponent={name:"Rio da propriedade",type:"Fonte de água",details:"Curso d'água natural em área livre da propriedade rural"};
    // Rio sinuoso em uma faixa livre da propriedade, fora dos talhões.
    // Após a rotação da fazenda, ele fica no lado direito da cena.
    const riverShape=new THREE.Shape();
    // Curso d'água largo e suave na faixa lateral vazia, sem atravessar os talhões.
    riverShape.moveTo(-20.0,-15.0);
    riverShape.bezierCurveTo(-18.2,-12.6,-19.0,-10.0,-17.4,-7.5);
    riverShape.bezierCurveTo(-15.8,-5.0,-18.0,-2.2,-16.5,0.4);
    riverShape.bezierCurveTo(-15.0,3.0,-17.0,5.8,-15.6,8.5);
    riverShape.bezierCurveTo(-14.4,10.8,-15.0,13.2,-13.4,15.0);
    riverShape.lineTo(-9.8,15.0);
    riverShape.bezierCurveTo(-11.0,12.8,-11.4,10.7,-12.0,8.4);
    riverShape.bezierCurveTo(-12.8,5.6,-11.0,3.0,-12.4,0.2);
    riverShape.bezierCurveTo(-13.7,-2.5,-11.8,-5.0,-13.2,-7.8);
    riverShape.bezierCurveTo(-14.6,-10.5,-14.2,-12.8,-16.2,-15.0);
    riverShape.closePath();
    const river=new THREE.Mesh(new THREE.ShapeGeometry(riverShape),makeMaterial(0x2f6f64));
    river.rotation.x=-Math.PI/2;
    river.position.y=.085;
    river.receiveShadow=true;
    riverGroup.add(river);

    // Margem marrom acompanhando o rio, mantendo a água livre no centro.
    const bankShape=new THREE.Shape();
    bankShape.moveTo(-21.0,-15.5);
    bankShape.bezierCurveTo(-19.1,-12.7,-20.0,-9.8,-18.4,-7.3);
    bankShape.bezierCurveTo(-16.6,-4.8,-18.9,-2.0,-17.4,0.7);
    bankShape.bezierCurveTo(-15.8,3.4,-17.9,6.1,-16.5,8.8);
    bankShape.bezierCurveTo(-15.2,11.3,-15.9,13.5,-14.2,15.5);
    bankShape.lineTo(-8.9,15.5);
    bankShape.bezierCurveTo(-10.2,13.0,-10.7,10.7,-11.3,8.3);
    bankShape.bezierCurveTo(-12.2,5.5,-10.3,2.9,-11.8,0.0);
    bankShape.bezierCurveTo(-13.2,-2.8,-11.2,-5.1,-12.7,-8.1);
    bankShape.bezierCurveTo(-14.1,-10.9,-13.7,-13.0,-15.7,-15.5);
    bankShape.closePath();
    const bank=new THREE.Mesh(new THREE.ShapeGeometry(bankShape),makeMaterial(0x705039));
    bank.rotation.x=-Math.PI/2;
    bank.position.y=.045;
    bank.receiveShadow=true;
    riverGroup.add(bank);

    // Pedras e pequenos arbustos nas margens.
    [[-18.8,-11.8],[-17.1,-5.1],[-15.8,1.7],[-15.1,8.0],[-13.8,12.5]].forEach(([x,z])=>{
        const rock=new THREE.Mesh(new THREE.DodecahedronGeometry(.46,0),makeMaterial(0x5a5145));
        rock.position.set(x,.38,z); rock.scale.set(1,.55,1.15); rock.castShadow=true; riverGroup.add(rock);
    });
    group.add(riverGroup);
    shell.push(riverGroup);

    // Caminhos de terra
    addEnvironmentBox(group,shell,"Estrada principal","Acesso",5,.10,31,-17,.08,0,0x765638);
    addEnvironmentBox(group,shell,"Estrada da sede","Acesso",28,.10,3.0,1,.09,6.2,0x765638);
    addEnvironmentBox(group,shell,"Caminho do celeiro","Acesso",3.0,.10,16,8,.09,0,0x765638);

    // Campos agrícolas — talhões organizados, com canteiros e fileiras de culturas
    // alinhadas para parecer uma plantação real, sem blocos verdes chapados.
    const fields = [
        {x:4.0,z:-9.0,w:17.5,d:6.6,rows:8},
        {x:4.0,z:-1.2,w:17.5,d:6.2,rows:8},
        {x:14.8,z:-9.0,w:5.2,d:6.6,rows:4}
    ];

    fields.forEach((f, fi) => {
        // Base de terra do talhão.
        addEnvironmentBox(group,shell,"Talhão agrícola "+(fi+1),"Plantação",f.w,.12,f.d,f.x,.13,f.z,0x5d472f);

        const soilMat=makeMaterial(0x6b4b2d);
        const leafMat=makeMaterial(0x477f2d);
        const leafDark=makeMaterial(0x315d25);

        // Canteiros de terra separados por pequenos corredores de cultivo.
        const spacing=f.w/(f.rows+1);
        for(let r=0; r<f.rows; r++){
            const x=f.x-f.w/2+spacing*(r+1);

            // Linha de solo mais escura para dar profundidade ao plantio.
            const bed=new THREE.Mesh(
                new THREE.BoxGeometry(.72,.08,f.d-.35),
                soilMat
            );
            bed.position.set(x,.23,f.z);
            bed.castShadow=true; bed.receiveShadow=true;
            group.add(bed); shell.push(bed);

            // Plantas em pequenos conjuntos, acompanhando toda a linha.
            for(let z=f.z-f.d/2+.65; z<f.z+f.d/2-.35; z+=.72){
                const stem=new THREE.Mesh(
                    new THREE.CylinderGeometry(.035,.055,.42,7),
                    leafDark
                );
                stem.position.set(x,.50,z);
                stem.castShadow=true;
                group.add(stem); shell.push(stem);

                const crop=new THREE.Group();
                crop.position.set(x,.68,z);

                const leaf1=new THREE.Mesh(
                    new THREE.ConeGeometry(.13,.34,7),
                    leafMat
                );
                leaf1.rotation.z=-.28;
                leaf1.position.set(-.10,.02,0);
                leaf1.castShadow=true;
                crop.add(leaf1);

                const leaf2=new THREE.Mesh(
                    new THREE.ConeGeometry(.13,.34,7),
                    leafMat
                );
                leaf2.rotation.z=.28;
                leaf2.position.set(.10,.02,0);
                leaf2.castShadow=true;
                crop.add(leaf2);

                const leaf3=new THREE.Mesh(
                    new THREE.ConeGeometry(.10,.28,7),
                    leafDark
                );
                leaf3.position.y=.13;
                leaf3.castShadow=true;
                crop.add(leaf3);

                group.add(crop); shell.push(crop);
            }
        }

        // Irrigação ficará representada pelos ramais hidráulicos, sem uma faixa verde no meio da plantação.
    });

    // Pasto com cercas de madeira
    addEnvironmentBox(group,shell,"Pasto","Pasto",12,.08,12,-9,.13,9,0x47743a);
    const fenceMat=makeMaterial(0x4f3523);
    for(let x=-14;x<=-4;x+=2.5){
        const post=new THREE.Mesh(new THREE.BoxGeometry(.18,1.15,.18),fenceMat);
        post.position.set(x,.67,3); post.castShadow=true; group.add(post); shell.push(post);
        const post2=post.clone(); post2.position.z=15; group.add(post2); shell.push(post2);
    }
    addEnvironmentBox(group,shell,"Cerca frontal","Estrutura",12,.16,.16,-9,.72,3,0x4f3523);
    addEnvironmentBox(group,shell,"Cerca traseira","Estrutura",12,.16,.16,-9,.72,15,0x4f3523);
    addEnvironmentBox(group,shell,"Cerca lateral","Estrutura",.16,.16,12,-15,.72,9,0x4f3523);

    // =====================================================
    // CASA-SEDE — arquitetura rural, cores quentes e foscas
    // =====================================================
    const houseMat=makeMaterial(0x9a6742), trimMat=makeMaterial(0x493022), roofMat=makeMaterial(0x3a2922);
    addEnvironmentBox(group,shell,"Casa sede","Edificação",7.6,4.0,5.4,-9,2.2,-5.2,0x9a6742);
    addEnvironmentBox(group,shell,"Rodapé da sede","Estrutura",8.0,.35,5.7,-9,.42,-5.2,0x584332);
    // telhado baixo e assentado sobre a casa
    addEnvironmentBox(group,shell,"Telhado da sede","Cobertura",8.5,.45,6.2,-9,4.45,-5.2,0x3a2922);
    addEnvironmentBox(group,equipment,"Varanda da sede","Estrutura",8.0,.22,1.35,-9,.65,-2.35,0x654127);
    addEnvironmentBox(group,equipment,"Porta da sede","Acesso",1.45,2.35,.18,-9,1.75,-2.55,0x3b281e);
    [-11.6,-6.4].forEach(x=>addEnvironmentBox(group,equipment,"Janela da sede","Esquadria",1.55,1.25,.12,x,2.45,-2.57,0x6f684f));
    [-12.35,-5.65].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da varanda","Estrutura",.22,2.3,.22,x,1.65,-1.8,0x513421));
    addEnvironmentBox(group,equipment,"Chaminé da sede","Estrutura",.6,1.35,.6,-6.2,5.0,-4.3,0x4b3022);

    // =====================================================
    // CELEIRO — celeiro rural vermelho, com telhado apoiado
    // =====================================================
    const barn=new THREE.Group();
    barn.position.set(9,0,7.2);
    barn.userData.aquaComponent={name:"Celeiro rural",type:"Edificação",details:"Celeiro principal da propriedade"};

    // Cores mais fortes e foscas: vermelho de celeiro + madeira escura.
    const bwall=makeMaterial(0xc62828);
    const bred=makeMaterial(0xa91f1f);
    const bwood=makeMaterial(0x4a2b20);
    const btrim=makeMaterial(0xf0dfc0);
    const broof=makeMaterial(0x242426);
    const bglass=makeMaterial(0x35565a);

    // Corpo principal apoiado diretamente no piso.
    const body=new THREE.Mesh(new THREE.BoxGeometry(9.5,5.0,7.0),bwall);
    body.position.y=2.65;
    body.castShadow=true; body.receiveShadow=true;
    barn.add(body);

    // Faixas de madeira e pilares dão a leitura de celeiro real.
    [-4.35,4.35].forEach(x=>{
        const pilar=new THREE.Mesh(new THREE.BoxGeometry(.32,4.75,.38),bwood);
        pilar.position.set(x,2.55,-3.60); pilar.castShadow=true; barn.add(pilar);
    });
    [-2.8,-1.4,1.4,2.8].forEach(x=>{
        const faixa=new THREE.Mesh(new THREE.BoxGeometry(.18,4.55,.18),btrim);
        faixa.position.set(x,2.55,-3.62); faixa.castShadow=true; barn.add(faixa);
    });

    // Gable frontal/traseiro: fecha o vão triangular sob o telhado.
    function createBarnGable(z){
        const shape=new THREE.Shape();
        shape.moveTo(-4.75,5.05);
        shape.lineTo(0,7.0);
        shape.lineTo(4.75,5.05);
        shape.lineTo(-4.75,5.05);
        const geo=new THREE.ExtrudeGeometry(shape,{depth:.24,bevelEnabled:false});
        const mesh=new THREE.Mesh(geo,bwall);
        mesh.position.z=z;
        mesh.castShadow=true; mesh.receiveShadow=true;
        barn.add(mesh);
        return mesh;
    }
    createBarnGable(-3.62);
    createBarnGable(3.38);

    // Telhado de duas águas calculado para encostar exatamente nas paredes.
    const roofAngle=Math.atan2(1.95,4.75);
    const roofLength=Math.sqrt(4.75*4.75+1.95*1.95)+.35;
    const roofLeft=new THREE.Mesh(new THREE.BoxGeometry(roofLength,.38,7.55),broof);
    roofLeft.position.set(-2.38,6.02,0);
    roofLeft.rotation.z=roofAngle;
    roofLeft.castShadow=true; roofLeft.receiveShadow=true;
    barn.add(roofLeft);

    const roofRight=new THREE.Mesh(new THREE.BoxGeometry(roofLength,.38,7.55),broof);
    roofRight.position.set(2.38,6.02,0);
    roofRight.rotation.z=-roofAngle;
    roofRight.castShadow=true; roofRight.receiveShadow=true;
    barn.add(roofRight);

    // Cumeeira pequena, apoiada exatamente no encontro dos dois planos.
    const ridge=new THREE.Mesh(new THREE.BoxGeometry(.38,.42,7.65),bwood);
    ridge.position.set(0,7.02,0);
    ridge.castShadow=true;
    barn.add(ridge);

    // Beirais frontais/traseiros para esconder qualquer abertura do telhado.
    const eaveFront=new THREE.Mesh(new THREE.BoxGeometry(9.9,.22,.28),bwood);
    eaveFront.position.set(0,5.08,-3.78); eaveFront.castShadow=true; barn.add(eaveFront);
    const eaveBack=eaveFront.clone(); eaveBack.position.z=3.78; barn.add(eaveBack);

    // Portão frontal grande, dividido em duas folhas com travessas brancas.
    addEnvironmentBox(barn,null,"Portão do celeiro","Acesso",4.5,3.55,.18,0,2.0,-3.76,0x6e1717);
    [-2.15,2.15].forEach(x=>{
        addEnvironmentBox(barn,null,"Travessa do portão","Estrutura",.14,3.3,.20,x,2.0,-3.90,btrim.color.getHex());
    });
    const gateTop=addEnvironmentBox(barn,null,"Travessa superior do portão","Estrutura",4.5,.16,.20,0,3.62,-3.90,btrim.color.getHex());
    gateTop.rotation.z=0;
    [-1.35,1.35].forEach(x=>{
        addEnvironmentBox(barn,null,"Diagonal do portão","Estrutura",2.0,.13,.20,x,2.0,-3.91,btrim.color.getHex()).rotation.z=(x<0?-.65:.65);
    });

    // Janelas laterais e frontais com molduras claras.
    [-3.25,3.25].forEach(x=>{
        addEnvironmentBox(barn,null,"Janela do celeiro","Esquadria",1.55,1.15,.14,x,3.35,-3.79,bglass);
        addEnvironmentBox(barn,null,"Moldura superior","Esquadria",1.75,.12,.18,x,4.0,-3.9,btrim.color.getHex());
    });

    // Pequenas ripas horizontais nas laterais.
    [-2.0,0,2.0].forEach(z=>{
        addEnvironmentBox(barn,null,"Ripa lateral","Estrutura",9.15,.12,.18,0,1.2,z,bwood.color.getHex());
        addEnvironmentBox(barn,null,"Ripa lateral","Estrutura",9.15,.12,.18,0,2.65,z,bwood.color.getHex());
    });

    // Fardos de feno apoiados no piso, dentro do celeiro.
    [-2.8,-1.0,1.0,2.8].forEach((x,i)=>{
        const hay=addEnvironmentBox(barn,null,"Fardo de feno","Armazenamento",1.35,1.0,1.15,x,.55,1.8+(i%2)*1.35,0xc49a4d);
        hay.rotation.y=(i%2)*.12;
    });

    group.add(barn); equipment.push(barn);

    // =====================================================
    // SILOS E ÁREA DE ARMAZENAMENTO
    // =====================================================
    [15.0,18.0].forEach((x,i)=>{
        const silo=new THREE.Group();
        const sbase=new THREE.Mesh(new THREE.CylinderGeometry(1.45,1.45,.25,20),makeMaterial(0x4b4338)); sbase.position.y=.25; sbase.castShadow=true; silo.add(sbase);
        const sb=new THREE.Mesh(new THREE.CylinderGeometry(1.3,1.3,4.1,20),makeMaterial(0x6d7560)); sb.position.y=2.35; sb.castShadow=true; sb.receiveShadow=true; silo.add(sb);
        const sr=new THREE.Mesh(new THREE.ConeGeometry(1.48,1.05,20),makeMaterial(0x403c34)); sr.position.y=4.92; sr.castShadow=true; silo.add(sr);
        silo.position.set(x,0,8.0); silo.userData.aquaComponent={name:"Silo "+(i+1),type:"Armazenamento",details:"Estrutura de armazenamento agrícola"};
        group.add(silo); equipment.push(silo);
    });

    // =====================================================
    // TRATOR — mantém o modelo aprovado
    // =====================================================
    const tractor=new THREE.Group();
    tractor.position.set(13.2,.35,-5.0); tractor.rotation.y=Math.PI;
    tractor.userData.aquaComponent={name:"Trator agrícola",type:"Equipamento",details:"Máquina agrícola utilizada nas operações da propriedade rural"};
    const tRed=makeMaterial(0xb62f2f), tDark=makeMaterial(0x20252a), tGlass=makeMaterial(0x4f8087), tMetal=makeMaterial(0x676b69);
    const tBody=new THREE.Mesh(new THREE.BoxGeometry(3.8,1.15,2.15),tRed); tBody.position.y=1.05; tBody.castShadow=true; tractor.add(tBody);
    const tHood=new THREE.Mesh(new THREE.BoxGeometry(1.65,1.0,1.9),tRed); tHood.position.set(1.55,1.72,0); tHood.castShadow=true; tractor.add(tHood);
    const tNose=new THREE.Mesh(new THREE.BoxGeometry(.35,.8,1.95),tRed); tNose.position.set(2.48,1.62,0); tNose.castShadow=true; tractor.add(tNose);
    const cabFloor=new THREE.Mesh(new THREE.BoxGeometry(1.65,.18,1.75),tDark); cabFloor.position.set(-.65,1.65,0); tractor.add(cabFloor);
    [[-.65,-.72],[-.65,.72],[.35,-.72],[.35,.72]].forEach(([x,z])=>{const pillar=new THREE.Mesh(new THREE.BoxGeometry(.12,2.15,.12),tDark); pillar.position.set(x,2.75,z); pillar.castShadow=true; tractor.add(pillar);});
    const cabRoof=new THREE.Mesh(new THREE.BoxGeometry(2.05,.22,2.05),tRed); cabRoof.position.set(-.15,3.86,0); cabRoof.castShadow=true; tractor.add(cabRoof);
    const windshield=new THREE.Mesh(new THREE.BoxGeometry(.08,1.35,1.35),tGlass); windshield.position.set(.43,2.75,0); tractor.add(windshield);
    const rearWindow=new THREE.Mesh(new THREE.BoxGeometry(.08,1.35,1.35),tGlass); rearWindow.position.set(-1.23,2.75,0); tractor.add(rearWindow);
    const seat=new THREE.Mesh(new THREE.BoxGeometry(.62,.16,.7),tDark); seat.position.set(-.65,2.0,0); tractor.add(seat);
    function tractorWheel(x,z,r){const w=new THREE.Mesh(new THREE.CylinderGeometry(r,r,.58,20),tDark); w.rotation.x=Math.PI/2; w.position.set(x,.78,z); w.castShadow=true; w.receiveShadow=true; tractor.add(w); const hub=new THREE.Mesh(new THREE.CylinderGeometry(r*.28,r*.28,.62,16),tMetal); hub.rotation.x=Math.PI/2; hub.position.set(x,.78,z); tractor.add(hub);}
    tractorWheel(-1.25,-1.18,1.12); tractorWheel(-1.25,1.18,1.12); tractorWheel(1.55,-1.03,.68); tractorWheel(1.55,1.03,.68);
    [-.65,.65].forEach(z=>addEnvironmentBox(tractor,null,"Farol do trator","Equipamento",.12,.28,.42,2.68,1.92,z,0xd1c06b));
    const exhaust=new THREE.Mesh(new THREE.CylinderGeometry(.11,.14,1.65,12),tDark); exhaust.position.set(2.0,2.95,-.62); exhaust.castShadow=true; tractor.add(exhaust);
    const hitch=new THREE.Mesh(new THREE.BoxGeometry(.35,.55,.8),tDark); hitch.position.set(-2.0,.9,0); tractor.add(hitch);
    group.add(tractor); equipment.push(tractor);

    // =====================================================
    // MOINHO DE VENTO — estrutura de madeira/metal, sem branco estourado
    // =====================================================
    const windmill=new THREE.Group(); windmill.position.set(-14.2,.15,-4.5);
    windmill.userData.aquaComponent={name:"Moinho de vento",type:"Estrutura rural",details:"Moinho de vento da propriedade rural"};
    const towerMat=makeMaterial(0x77654d), bladeMat=makeMaterial(0x9a835f), headMat=makeMaterial(0x5c3c29);
    [[-1,-.7],[1,-.7],[-.7,.7],[.7,.7]].forEach(([x,z])=>{const leg=new THREE.Mesh(new THREE.CylinderGeometry(.10,.16,6.8,10),towerMat); leg.position.set(x,3.55,z); leg.castShadow=true; windmill.add(leg);});
    const platform=new THREE.Mesh(new THREE.BoxGeometry(2.8,.24,2.3),towerMat); platform.position.y=6.85; platform.castShadow=true; windmill.add(platform);
    const head=new THREE.Mesh(new THREE.CylinderGeometry(.72,.82,1.5,12),headMat); head.rotation.z=Math.PI/2; head.position.set(0,7.55,0); head.castShadow=true; windmill.add(head);
    const hub=new THREE.Mesh(new THREE.CylinderGeometry(.20,.20,.48,16),tDark); hub.rotation.x=Math.PI/2; hub.position.set(0,7.55,1.0); windmill.add(hub);
    for(let i=0;i<4;i++){const blade=new THREE.Group(); const arm=new THREE.Mesh(new THREE.BoxGeometry(.18,3.0,.12),bladeMat); arm.position.y=1.45; arm.castShadow=true; blade.add(arm); const brace=new THREE.Mesh(new THREE.BoxGeometry(.55,.12,.12),bladeMat); brace.position.set(.18,2.5,0); blade.add(brace); blade.rotation.z=i*Math.PI/2; blade.position.set(0,7.55,1.18); windmill.add(blade);}
    group.add(windmill); equipment.push(windmill);

    // Árvores distribuídas nas bordas
    [[-19,-11],[-19,16],[20,15],[20,-11],[-2,15]].forEach(([x,z])=>{
        const trunk=new THREE.Mesh(new THREE.CylinderGeometry(.18,.28,2.3,9),makeMaterial(0x503522)); trunk.position.set(x,1.25,z); trunk.castShadow=true; group.add(trunk); shell.push(trunk);
        const crown=new THREE.Mesh(new THREE.SphereGeometry(1.45,12,10),makeMaterial(0x2f6533)); crown.position.set(x,3.05,z); crown.scale.y=1.05; crown.castShadow=true; group.add(crown); shell.push(crown);
    });

    // =====================================================
    // RESERVATÓRIO + BOMBA + IRRIGAÇÃO
    // =====================================================
    addEnvironmentBox(group,shell,"Base do reservatório","Estrutura",5.5,.3,5.5,-14,.5,8.0,0x334b32);
    addEnvironmentBox(group,equipment,"Reservatório rural","Reservatório",4.5,3.5,4.2,-14,2.35,8.0,0x5f7656,{details:"Armazena água para irrigação e uso da propriedade"});
    addEnvironmentBox(group,equipment,"Bomba de irrigação","Bomba",2.5,1.8,2.2,-9,1.05,8.0,0x2d3425,{details:"Bombeamento do sistema de irrigação"});
    addEnvironmentBox(group,equipment,"Bebedouro animal","Ponto de consumo",4,1.2,2.4,-8,.7,13.0,0x5b5141);

    [-2.2,4.2,10.6].forEach((x,i)=>{
        const label=String.fromCharCode(65+i);
        for(let z=-9;z<=-2;z+=2.2){
            const pipe=new THREE.Mesh(new THREE.CylinderGeometry(.07,.07,5.2,10),makeMaterial(0x168cff,0x006cff,1.4));
            pipe.rotation.z=Math.PI/2; pipe.position.set(x,.55,z); pipe.visible=false; pipe.userData.aquaComponent={name:"Linha de irrigação "+label,type:"Tubulação",details:"Linha secundária do setor agrícola"}; group.add(pipe); systems.push(pipe);
            const riser=new THREE.Mesh(new THREE.CylinderGeometry(.09,.09,.65,10),makeMaterial(0x55d8ff,0x006cff,1.7));
            riser.position.set(x,.72,z); riser.visible=false; riser.userData.aquaComponent={name:"Aspersor "+label,type:"Irrigação",details:"Ponto de distribuição de água"}; group.add(riser); equipment.push(riser);
        }
    });

    createSystemPipe(group,systems,[[-14,.9,8],[-9,.9,8],[-9,.9,0],[-2.2,.9,0]]);
    createSystemPipe(group,systems,[[-9,.9,0],[4.2,.9,0],[10.6,.9,0]]);
    createSystemPipe(group,systems,[[-2.2,.9,0],[-2.2,.9,-9]]);
    createSystemPipe(group,systems,[[4.2,.9,0],[4.2,.9,-9]]);
    createSystemPipe(group,systems,[[10.6,.9,0],[10.6,.9,-9]]);
    createSystemPipe(group,systems,[[-9,.9,8],[-8,.9,13]]);

    // Frente da fazenda voltada para a câmera (+Z).
    // O cenário foi construído com a fachada principal em -Z,
    // então giramos o conjunto 180° para mostrar a frente ao usuário.
    // POSTES DE ILUMINAÇÃO — estrada, celeiro e área de irrigação.
    [-15,-5,5,15].forEach(x=>addEnvironmentLightPole(group,x,11.5,5.4,"Poste da estrada da fazenda"));
    [-13,0,13].forEach(x=>addEnvironmentLightPole(group,x,-13.2,5.8,"Poste do setor rural"));

    group.rotation.y=Math.PI;
    scene.add(group);
    environmentGroups.agro=group;
    environmentSystems.agro={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("agro",group,environmentPoints.agro);
}
function createSystemPipe(group, list, points) {
    const material = new THREE.MeshStandardMaterial({
        color:0x168cff,
        emissive:0x006cff,
        emissiveIntensity:2,
        metalness:.45,
        roughness:.2
    });
    for(let i=0;i<points.length-1;i++){
        const a = new THREE.Vector3(...points[i]);
        const b = new THREE.Vector3(...points[i+1]);
        const dir = new THREE.Vector3().subVectors(b,a);
        const pipe = new THREE.Mesh(
            new THREE.CylinderGeometry(.12,.12,dir.length(),14),
            material
        );
        pipe.position.copy(a).add(b).multiplyScalar(.5);
        pipe.quaternion.setFromUnitVectors(new THREE.Vector3(0,1,0),dir.normalize());
        pipe.visible=false;
        pipe.userData.aquaComponent = {
            name:"Tubulação hidráulica",
            type:"Tubulação",
            details:"Linha monitorada pelo AquaFlow"
        };
        group.add(pipe);
        list.push(pipe);
    }
}

function addEnvironmentMarkers(env, group, points) {
    const state = environmentSystems[env];
    state.leaks = {};
    Object.keys(points).forEach(function(key){
        const p = points[key];
        const marker = new THREE.Mesh(
            new THREE.SphereGeometry(.3,18,18),
            new THREE.MeshBasicMaterial({color:0xff2020})
        );
        marker.position.set(...p.pos);
        marker.visible=false;
        marker.userData.aquaComponent = {
            name:p.label,
            type:p.type,
            details:"Ponto monitorado pelo AquaFlow"
        };
        group.add(marker);
        state.leaks[key] = {marker, particles:[], point:p};
        for(let i=0;i<12;i++){
            const drop = new THREE.Mesh(
                new THREE.SphereGeometry(.055,8,8),
                new THREE.MeshBasicMaterial({color:0x55d8ff})
            );
            drop.visible=false;
            drop.position.copy(marker.position);
            drop.userData.vx=(Math.random()-.5)*.035;
            drop.userData.vy=Math.random()*.045;
            drop.userData.vz=(Math.random()-.5)*.035;
            group.add(drop);
            state.leaks[key].particles.push(drop);
        }
    });
}

function buildEnvironmentFlow(env) {
    const state = environmentSystems[env];
    if(!state) return;

    state.flow = [];
    const all = Object.keys(environmentPoints[env]);
    const visiblePoints = all.filter(k => environmentPoints[env][k].rate > 0);
    if(!visiblePoints.length) return;

    const source = env === "agro" ? [-11,.8,7] :
                   env === "industria" ? [-10,.8,6] :
                   env === "escola" ? [0,6,0] :
                   env === "predio_residencial" ? [0,10.5,1.8] :
                   env === "predio_comercial" ? [0,10.5,1.8] :
                   env === "empresa" ? [0,3.5,3.5] :
                   [-8.3,.65,5];

    visiblePoints.forEach(function(key,index){
        const target = environmentPoints[env][key].pos;
        const path = [
            new THREE.Vector3(...source),
            new THREE.Vector3(source[0],source[1],target[2]),
            new THREE.Vector3(target[0],source[1],target[2]),
            new THREE.Vector3(...target)
        ];
        state.flow.push(path);
    });
}

function setupEnvironmentSelector() {
    const label = document.getElementById("environmentLockedName");
    const allowed = currentEnvironment;

    if (label) {
        label.textContent = NOME_AMBIENTE[allowed] || "AMBIENTE";
    }
}

function ambientePermitido(env) {
    return env === currentEnvironment;
}

function setEnvironment(env) {
    if(!ambientePermitido(env)) {
        env = currentEnvironment;
    }

    if(!environmentGroups[env]) return;

    stopAllLeaks(true, false);
    currentEnvironment = env;
    xray = false;

    Object.keys(environmentGroups).forEach(function(key){
        environmentGroups[key].visible = key === env;
    });
    if (pool) pool.visible = env === "casa";

    const state = environmentSystems[env];
    if(state) {
        state.systems.forEach(p => p.visible=false);
        if(state.leaks) Object.values(state.leaks).forEach(o => {
            o.marker.visible=false;
            o.particles.forEach(p=>p.visible=false);
        });
    }

    if(env === "casa"){
        roomData = environmentPoints.casa;
        selectedRooms.clear();
        hideOriginalHouseSystems();
    } else {
        roomData = environmentPoints[env] || {};
    }

    document.getElementById("environmentDescription").textContent =
        environmentDescriptions[env] || "Ambiente monitorado pelo AquaFlow.";

    document.getElementById("mode").textContent = "MODO NORMAL";
    const xb = document.getElementById("xrayButton");
    xb.classList.remove("active");
    xb.textContent = "ATIVAR RAIO-X";

    rebuildPointSelector();
    updateStats();
    updateComponentCard(null);

    const targets = {
        casa:[1,3,0],
        predio_residencial:[0,5.5,-1],
        empresa:[0,2.1,-0.5],
        predio_comercial:[0,5,0],
        escola:[0,3.2,0],
        industria:[0,3,0],
        agro:[0,2,0]
    };
    controls.target.set(...(targets[env] || [0,3,0]));

    if(env === "predio_residencial") {
        camera.position.set(22,11,-26);
    } else if(env === "predio_comercial") {
        camera.position.set(0,9.5,-30);
    } else if(env === "empresa") {
        camera.position.set(0,9,-29);
    } else {
        camera.position.set(24,15,25);
    }
}

function rebuildPointSelector() {
    const container = document.getElementById("rooms");
    if (!container) return;

    const data = environmentPoints[currentEnvironment] || {};

    container.innerHTML = Object.keys(data).map(function(key) {
        return `<div class="room-option" data-room="${key}" role="button" tabindex="0">
            ${data[key].label}
            <span class="room-status">${selectedRooms.has(key) ? "Selecionado" : "Desligado"}</span>
        </div>`;
    }).join("");

    updateRoomButtons();
}

function setupLeakSelectorEvents() {
    const container = document.getElementById("rooms");
    if (!container || container.dataset.eventsReady === "1") return;

    container.dataset.eventsReady = "1";

    // Delegação de eventos: continua funcionando mesmo depois que
    // rebuildPointSelector() recriar os botões dos ambientes.
    container.addEventListener("click", function(event) {
        const button = event.target.closest(".room-option");
        if (!button || !container.contains(button)) return;
        event.preventDefault();
        event.stopPropagation();
        toggleRoom(button.dataset.room);
    });

    container.addEventListener("keydown", function(event) {
        if (event.key !== "Enter" && event.key !== " ") return;
        const button = event.target.closest(".room-option");
        if (!button || !container.contains(button)) return;
        event.preventDefault();
        toggleRoom(button.dataset.room);
    });
}

function hideOriginalHouseSystems() {
    pipes.forEach(p=>p.visible=false);
    poolPipes.forEach(p=>p.visible=false);
    water.forEach(p=>p.visible=false);
}

function showCurrentSystems() {
    const state = environmentSystems[currentEnvironment];
    if(!state) return;

    if(currentEnvironment === "casa"){
        pipes.forEach(p=>p.visible=true);
        poolPipes.forEach(p=>p.visible=true);
        water.forEach(p=>p.visible=true);
    } else {
        state.systems.forEach(p=>p.visible=true);
        buildEnvironmentFlow(currentEnvironment);
        createAdvancedFlowParticles(currentEnvironment);
    }
}

function toggleXray() {
    xray = !xray;
    const button = document.getElementById("xrayButton");
    const mode = document.getElementById("mode");
    const state = environmentSystems[currentEnvironment];

    if(xray){
        button.classList.add("active");
        button.textContent="DESATIVAR RAIO-X";
        mode.textContent="MODO RAIO-X ATIVO";

        if(currentEnvironment === "casa"){
            walls.forEach(w=>{
                w.material.transparent=true; w.material.opacity=.10; w.material.depthWrite=false; w.material.needsUpdate=true;
            });
            houseShellParts.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=true; o.material.opacity=.08; o.material.depthWrite=false; o.material.needsUpdate=true;
            });
            roofParts.forEach(p=>p.visible=false);
            furniture.forEach(o=>{
                o.material.transparent=true; o.material.opacity=.10; o.material.depthWrite=false; o.material.needsUpdate=true;
            });
        } else {
            state.shell.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=true; o.material.opacity=.07; o.material.depthWrite=false; o.material.needsUpdate=true;
            });
            state.equipment.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=true; o.material.opacity=.13; o.material.depthWrite=false; o.material.needsUpdate=true;
            });
        }
        showCurrentSystems();
    } else {
        button.classList.remove("active");
        button.textContent="ATIVAR RAIO-X";
        mode.textContent="MODO NORMAL";

        if(currentEnvironment === "casa"){
            walls.forEach(w=>{
                w.material.transparent=false; w.material.opacity=1; w.material.depthWrite=true; w.material.needsUpdate=true;
            });
            houseShellParts.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=false; o.material.opacity=1; o.material.depthWrite=true; o.material.needsUpdate=true;
            });
            roofParts.forEach(p=>p.visible=true);
            furniture.forEach(o=>{
                o.material.transparent=false; o.material.opacity=1; o.material.depthWrite=true; o.material.needsUpdate=true;
            });
            hideOriginalHouseSystems();
        } else {
            state.shell.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=false; o.material.opacity=1; o.material.depthWrite=true; o.material.needsUpdate=true;
            });
            state.equipment.forEach(o=>{
                if(!o.material) return;
                o.material.transparent=false; o.material.opacity=1; o.material.depthWrite=true; o.material.needsUpdate=true;
            });
            state.systems.forEach(p=>p.visible=false);
            advancedFlowParticles.forEach(p=>p.visible=false);
        }
    }

    // Vazamentos permanecem visíveis nos dois modos.
    activeLeaks.forEach(key=>{
        const leak = getLeakObject(key);
        if(leak) leak.marker.visible=true;
    });

    const activeState=environmentSystems[currentEnvironment];
    if(activeState && activeState.systems){
        activeState.systems.forEach(pipe=>{
            if(pipe.userData.leakHighlight) pipe.visible=true;
        });
    }
}

function getLeakObject(key) {
    // A casa usa os objetos criados pelo sistema hidráulico residencial.
    if (currentEnvironment === "casa") {
        return leakObjects[key] || null;
    }

    const state = environmentSystems[currentEnvironment];
    return state && state.leaks ? state.leaks[key] : null;
}

/* =====================================================
   SELEÇÃO DE VAZAMENTOS — MULTISELECT
===================================================== */

function toggleRoom(roomName) {
    const data = environmentPoints[currentEnvironment];
    if (!data || !data[roomName]) return;

    // Clique em um ponto: liga/desliga SOMENTE aquele vazamento.
    // Isso permite ter vários vazamentos ativos ao mesmo tempo.
    if (activeLeaks.has(roomName)) {
        stopRoomLeak(roomName);
        removerVazamentoServidor(roomName);
    } else {
        selectedRooms.add(roomName);
        if (startRoomLeak(roomName)) {
            registrarVazamentoServidor(roomName);
        }
    }

    updateRoomButtons();
    updateAlert();
    updateStats();
}

const AQUAFLOW_REGISTRAR_URL = "registrar_vazamento.php";
const AQUAFLOW_REMOVER_URL = "remover_vazamento.php";
const AQUAFLOW_SYNC_URL = "sync_vazamentos.php";

async function registrarVazamentoServidor(roomName) {
    const data = environmentPoints[currentEnvironment]?.[roomName];
    if (!data || !data.rate) return;

    try {
        const resposta = await fetch(AQUAFLOW_REGISTRAR_URL, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                ambiente: currentEnvironment,
                ponto: roomName,
                vazao: data.rate
            })
        });

        const resultado = await resposta.json();
        if (!resultado.sucesso) {
            console.error("Erro ao registrar vazamento:", resultado.mensagem);
        }
    } catch (erro) {
        console.error("Erro de conexão ao registrar vazamento:", erro);
    }
}

async function removerVazamentoServidor(roomName, ambiente = currentEnvironment) {
    try {
        const resposta = await fetch(AQUAFLOW_REMOVER_URL, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                ambiente: ambiente,
                ponto: roomName
            })
        });

        const resultado = await resposta.json();
        if (!resultado.sucesso) {
            console.error("Erro ao remover vazamento:", resultado.mensagem);
        }
    } catch (erro) {
        console.error("Erro de conexão ao remover vazamento:", erro);
    }
}

async function carregarVazamentosServidor() {
    try {
        const resposta = await fetch(AQUAFLOW_SYNC_URL, {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store"
        });

        if (!resposta.ok) throw new Error("HTTP " + resposta.status);

        const dados = await resposta.json();
        if (!dados.sucesso) return;

        const vazamentos = dados.vazamentos || [];
        const ambienteAtual = currentEnvironment;
        const vazamentosDoAmbiente = vazamentos.filter(function(v) {
            return v.ambiente === ambienteAtual;
        });

        const pontosAtivosNoServidor = new Set(
            vazamentosDoAmbiente.map(function(v) { return v.ponto; })
        );

        Array.from(activeLeaks).forEach(function(roomName) {
            if (!pontosAtivosNoServidor.has(roomName)) {
                stopRoomLeak(roomName);
            }
        });

        vazamentosDoAmbiente.forEach(function(vazamento) {
            const data = environmentPoints[ambienteAtual]?.[vazamento.ponto];
            if (!data) return;

            if (!activeLeaks.has(vazamento.ponto)) {
                startRoomLeak(vazamento.ponto);
            }
        });

        totalWaste = vazamentosDoAmbiente.reduce(function(total, vazamento) {
            return total + Number(vazamento.litros_perdidos || 0);
        }, 0);

        updateRoomButtons();
        updateAlert();
        updateStats();
    } catch (erro) {
        console.error("Erro ao sincronizar vazamentos:", erro);
    }
}

function startRoomLeak(roomName) {
    const leak = getLeakObject(roomName);
    if (!leak) return false;

    const data = environmentPoints[currentEnvironment]?.[roomName];
    if (!data) return false;

    activeLeaks.add(roomName);
    selectedRooms.add(roomName);

    leak.marker.visible = true;
    leak.marker.userData.baseScale = 1;

    leak.particles.forEach(function(p) {
        p.visible = true;
        p.position.copy(leak.marker.position);
        p.userData.vx = (Math.random() - 0.5) * 0.035;
        p.userData.vy = Math.random() * 0.045 + 0.012;
        p.userData.vz = (Math.random() - 0.5) * 0.035;
    });

    return true;
}

function stopRoomLeak(roomName) {
    const leak = getLeakObject(roomName);
    activeLeaks.delete(roomName);
    selectedRooms.delete(roomName);

    if (!leak) return;

    leak.marker.visible = false;
    if (leak.light) leak.light.intensity = 0;

    leak.particles.forEach(function(p) {
        p.visible = false;
    });
}

function simulateLeak() {
    const button = document.getElementById("leakButton");

    // PRIMEIRO CLIQUE: sempre inicia 1 vazamento aleatório.
    if (activeLeaks.size === 0) {
        const data = environmentPoints[currentEnvironment] || {};
        const available = Object.keys(data).filter(function(key) {
            return data[key] && data[key].rate > 0 && getLeakObject(key);
        });

        if (!available.length) {
            if (button) button.textContent = "Sem pontos disponíveis";
            return;
        }

        const randomRoom = available[Math.floor(Math.random() * available.length)];
        selectedRooms.clear();
        selectedRooms.add(randomRoom);
        if (startRoomLeak(randomRoom)) {
            registrarVazamentoServidor(randomRoom);
        }

        if (button) button.textContent = "Parar todos";
        button.title = "Parar todos os vazamentos";
    } else {
        // Botão novamente: para TODOS os vazamentos ativos.
        stopAllLeaks(false);
        if (button) button.textContent = "Simular vazamento";
    }

    updateRoomButtons();
    updateAlert();
    updateStats();
}

function stopAllLeaks(clearSelected, sincronizarServidor = true) {
    const vazamentosParaRemover = Array.from(activeLeaks);

    vazamentosParaRemover.forEach(function(roomName) {
        stopRoomLeak(roomName);
        if (sincronizarServidor) {
            removerVazamentoServidor(roomName);
        }
    });

    activeLeaks.clear();

    if (clearSelected !== false) {
        selectedRooms.clear();
    }

    const leakButton = document.getElementById("leakButton");
    if (leakButton) {
        leakButton.textContent = "Simular vazamento";
        leakButton.title = "Simular vazamento";
    }

    updateRoomButtons();
    updateAlert();
    updateStats();
}

function clearSelection() {
    // Limpa seleção e para os pontos ativos.
    stopAllLeaks(true);
}

function updateRoomButtons() {
    document.querySelectorAll("#rooms .room-option").forEach(function(button) {
        const roomName = button.dataset.room;
        const selected = selectedRooms.has(roomName);
        const leaking = activeLeaks.has(roomName);

        button.classList.toggle("selected", selected);
        button.classList.toggle("leaking", leaking);

        const status = button.querySelector(".room-status");
        if (!status) return;

        if (leaking) {
            status.textContent = "Vazamento ativo";
        } else if (selected) {
            status.textContent = "Selecionado";
        } else {
            status.textContent = "Desligado";
        }
    });
}

function updateAlert() {
    const alert = document.getElementById("alert");
    const text = document.getElementById("alertText");

    if (!alert || !text) return;

    if (activeLeaks.size === 0) {
        alert.classList.remove("show");
        return;
    }

    const data = environmentPoints[currentEnvironment] || {};
    const names = Array.from(activeLeaks)
        .filter(function(k) { return data[k]; })
        .map(function(k) { return data[k].label; });

    const rate = Array.from(activeLeaks).reduce(function(sum, k) {
        return sum + ((data[k] && data[k].rate) || 0);
    }, 0);

    const costPerHour = (rate * 60 / 1000) * waterTariff;

    alert.classList.add("show");
    text.innerHTML =
        "Vazamento em: <strong>" + names.join(", ") + "</strong><br>" +
        "Perda atual: <strong>" + rate.toFixed(2) + " L/min</strong><br>" +
        "Custo estimado: <strong>R$ " + costPerHour.toFixed(2) + "/hora</strong>";
}

function updateStats() {
    const data = environmentPoints[currentEnvironment] || {};
    const rate = Array.from(activeLeaks).reduce(function(sum, k) {
        return sum + ((data[k] && data[k].rate) || 0);
    }, 0);

    const liveFlow = document.getElementById("liveFlow");
    const activePoints = document.getElementById("activePoints");
    const liveLoss = document.getElementById("liveLoss");
    const liveCost = document.getElementById("liveCost");

    if (liveFlow) liveFlow.textContent = rate.toFixed(1).replace(".", ",") + " L/min";
    if (activePoints) activePoints.textContent = activeLeaks.size;
    if (liveLoss) liveLoss.textContent = totalWaste.toFixed(2).replace(".", ",") + " L";
    if (liveCost) liveCost.textContent = "R$ " + (totalWaste / 1000 * waterTariff).toFixed(2).replace(".", ",");
}

function updateWaste(delta) {
    if (!activeLeaks.size) {
        updateStats();
        return;
    }

    const data = environmentPoints[currentEnvironment] || {};
    let litersPerMinute = 0;

    activeLeaks.forEach(function(k) {
        litersPerMinute += (data[k] && data[k].rate) || 0;
    });

    // delta está em segundos; converte L/min para litros no intervalo.
    totalWaste += (litersPerMinute / 60) * delta;
    updateStats();
}

function resetCounter() {
    totalWaste = 0;
    updateStats();
}

function createAdvancedFlowParticles(env) {
    advancedFlowParticles.forEach(p=>{
        if(p.parent) p.parent.remove(p);
    });
    advancedFlowParticles=[];

    const state=environmentSystems[env];
    if(!state || !state.flow) return;

    state.flow.forEach(function(path){
        for(let i=0;i<5;i++){
            const particle=new THREE.Mesh(
                new THREE.SphereGeometry(.075,8,8),
                new THREE.MeshBasicMaterial({color:0x55d8ff})
            );
            particle.userData={path,progress:i/5};
            particle.visible=xray;
            environmentGroups[env].add(particle);
            advancedFlowParticles.push(particle);
        }
    });
}

function updateWater() {
    // Fluxo da casa
    if(!xray || currentEnvironment!=="casa"){
        water.forEach(p=>p.visible=false);
    } else {
        const points=[
            new THREE.Vector3(-8.3,.65,5),
            new THREE.Vector3(-8.3,.65,0),
            new THREE.Vector3(-8.3,.65,-4.5),
            new THREE.Vector3(-5.3,.65,-4.5),
            new THREE.Vector3(4.5,.65,-4.5),
            new THREE.Vector3(4.5,.95,-3.6)
        ];
        water.forEach(function(p){
            p.visible=true;
            p.userData.progress=(p.userData.progress+.0025)%1;
            const scaled=p.userData.progress*(points.length-1);
            const seg=Math.min(Math.floor(scaled),points.length-2);
            p.position.lerpVectors(points[seg],points[seg+1],scaled-seg);
        });
    }

    if(xray && currentEnvironment!=="casa"){
        advancedFlowParticles.forEach(function(p){
            p.visible=true;
            p.userData.progress=(p.userData.progress+.0025)%1;
            const path=p.userData.path;
            const scaled=p.userData.progress*(path.length-1);
            const seg=Math.min(Math.floor(scaled),path.length-2);
            p.position.lerpVectors(path[seg],path[seg+1],scaled-seg);
        });
    } else {
        advancedFlowParticles.forEach(p=>p.visible=false);
    }
}

function updateLeaks(delta) {
    const data=environmentPoints[currentEnvironment];
    activeLeaks.forEach(function(key){
        const leak=getLeakObject(key);
        if(!leak) return;

        const pulse=(Math.sin(performance.now()*.012)+1)/2;
        leak.marker.scale.setScalar(.8+pulse*.7);

        leak.particles.forEach(function(p){
            p.position.x+=p.userData.vx;
            p.position.y+=p.userData.vy;
            p.position.z+=p.userData.vz;

            if(p.position.y > leak.marker.position.y+1.2){
                p.position.copy(leak.marker.position);
            }
        });
    });
}

function decorateHouseComponents() {
    pipes.forEach(function(p,i){
        p.userData.aquaComponent={
            name:"Tubulação "+String(i+1).padStart(2,"0"),
            type:"Tubulação",
            details:"Linha hidráulica residencial monitorada pelo AquaFlow."
        };
    });
    poolPipes.forEach(function(p,i){
        p.userData.aquaComponent={
            name:"Tubulação da piscina "+String(i+1),
            type:"Piscina",
            details:"Linha de circulação/abastecimento da piscina."
        };
    });
}

function setup3DInteraction() {
    const raycaster=new THREE.Raycaster();
    const pointer=new THREE.Vector2();
    const canvas=renderer.domElement;

    canvas.addEventListener("click",function(event){
        const rect=canvas.getBoundingClientRect();
        pointer.x=((event.clientX-rect.left)/rect.width)*2-1;
        pointer.y=-((event.clientY-rect.top)/rect.height)*2+1;

        raycaster.setFromCamera(pointer,camera);

        const objects=[];
        const group=environmentGroups[currentEnvironment];
        if(group) group.traverse(o=>{
            if(o.visible && o.userData.aquaComponent) objects.push(o);
        });

        const hits=raycaster.intersectObjects(objects,false);
        if(!hits.length){
            updateComponentCard(null);
            return;
        }

        updateComponentCard(hits[0].object.userData.aquaComponent);
    });
}

function updateComponentCard(component) {
    const card=document.getElementById("componentCard");
    if(!component){
        card.classList.remove("show");
        return;
    }

    card.classList.add("show");
    document.getElementById("componentName").textContent=component.name;
    document.getElementById("componentType").textContent=component.type;

    const rate=component.rate || 0;
    let html=component.details || "Componente monitorado pelo AquaFlow.";

    if(rate){
        html += "<br><br>Consumo de referência: <strong>"+rate.toFixed(1)+" L/min</strong>";
    }

    if(activeLeaks.size){
        const leakingName=Array.from(activeLeaks).map(k=>environmentPoints[currentEnvironment][k]?.label).includes(component.name);
        if(leakingName){
            html += `<div class="loss-card"><strong>⚠ Vazamento ativo</strong><br>
            <span>${rate.toFixed(2)} L/min sendo perdido</span></div>`;
        }
    }

    html += '<div class="flow-pill">Monitoramento ativo</div>';
    document.getElementById("componentInfo").innerHTML=html;
}

document.getElementById("componentClose").addEventListener("click",function(){
    updateComponentCard(null);
});


/* =====================================================
   INICIAR
===================================================== */

init();

const parametros3D = new URLSearchParams(window.location.search);
const ambienteInicial = parametros3D.get("env");
const pontoInicial = parametros3D.get("ponto");

if (ambienteInicial && environmentGroups[ambienteInicial]) {
    setEnvironment(ambienteInicial);
}

setTimeout(function() {
    carregarVazamentosServidor().then(function() {
        if (pontoInicial && environmentPoints[currentEnvironment]?.[pontoInicial]) {
            const pontoAtivo = activeLeaks.has(pontoInicial);
            if (!pontoAtivo) {
                startRoomLeak(pontoInicial);
                selectedRooms.add(pontoInicial);
            }
            updateRoomButtons();
            updateAlert();
            updateStats();
        }
    });
}, 500);

setInterval(carregarVazamentosServidor, 2500);

</script>

</body>
</html>