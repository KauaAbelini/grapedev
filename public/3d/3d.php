<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../backend/auth/login.php");
    exit;
}

include("../../backend/database/conexao.php");
include("../../backend/locais/local_contexto.php");

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

        <a href="../../backend/dashboard/dashboard.php" class="dashboard-back" aria-label="Voltar para o Dashboard" title="Voltar para o Dashboard">
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

// Partes animadas (água da piscina, moinho etc.).
var animatedParts = [];


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
    // PISCINA — a piscina real é criada em createPool(); aqui fica só o entorno
    // =====================================================
    const poolX=13.4, poolZ=-8.0;
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


    // =====================================================
    // DETALHAMENTO DA CASA — esquadrias, terraço, cobertura e paisagismo
    // =====================================================
    {
        const H = house;
        const roofTop = 8.50, terraceY = 5.06;

        // Janelas laterais e traseiras (a casa era "cega" fora da fachada).
        aqWindow(H, null, 8.55, 3.05, 1.6, 1.5, .9, { ry: Math.PI / 2, name: "Janela do banheiro", cols: 1 });
        aqWindow(H, null, -8.55, 2.45, -2.4, 2.6, 1.7, { ry: -Math.PI / 2, name: "Janela da sala" });
        aqWindow(H, null, -8.55, 2.95, 2.9, 1.4, .95, { ry: -Math.PI / 2, name: "Janela da lavanderia", cols: 1 });
        aqWindow(H, null, 6.22, 6.45, -1.7, 2.2, 1.5, { ry: Math.PI / 2, name: "Janela do quarto 2" });
        aqWindow(H, null, 6.22, 7.05, 2.8, 1.0, .75, { ry: Math.PI / 2, name: "Janela do banheiro 2", cols: 1 });
        aqWindow(H, null, -6.22, 6.45, -1.7, 2.2, 1.5, { ry: -Math.PI / 2, name: "Janela do quarto 1" });
        aqWindow(H, null, -6.22, 6.45, 2.6, 1.8, 1.4, { ry: -Math.PI / 2, name: "Janela do escritório" });
        aqWindow(H, null, -4.6, 2.5, -5.53, 3.4, 2.0, { ry: Math.PI, name: "Janela traseira da sala" });
        aqWindow(H, null, 2.4, 2.9, -5.53, 2.2, 1.2, { ry: Math.PI, name: "Janela da cozinha" });
        aqWindow(H, null, -3.4, 6.45, -4.07, 2.4, 1.5, { ry: Math.PI, name: "Janela traseira — quarto 1" });
        aqWindow(H, null, 3.4, 6.45, -4.07, 2.4, 1.5, { ry: Math.PI, name: "Janela traseira — quarto 2" });

        // Porta pivotante amadeirada com puxador vertical e batente.
        const veneer = addEnvironmentBox(H, null, "Folha de madeira da porta", "Acesso", 1.5, 2.9, .03, -2.0, 1.55, 5.925, 0x8a5a36);
        aqTexture(veneer, "wood", .8);
        addEnvironmentBox(H, null, "Batente da porta", "Acesso", 1.86, .12, .2, -2.0, 3.08, 5.86, 0x1b1f22);
        [-2.87, -1.13].forEach(x => addEnvironmentBox(H, null, "Batente da porta", "Acesso", .12, 3.0, .2, x, 1.55, 5.86, 0x1b1f22));
        addEnvironmentBox(H, null, "Puxador da porta", "Acesso", .05, 1.3, .05, -1.45, 1.6, 6.02, 0xb9b3a6);
        [1.05, 2.15].forEach(y => addEnvironmentBox(H, null, "Suporte do puxador", "Acesso", .04, .04, .1, -1.45, y, 5.97, 0xb9b3a6));

        // Fita de LED sob a marquise da entrada (acende com a iluminação).
        const led = addEnvironmentBox(H, null, "Fita de LED da marquise", "Iluminação", 4.4, .04, .06, -2.0, 4.41, 7.0, 0xd9c49a, { emissive: 0xffb84a, emissiveIntensity: 0 });
        aquaGlowMaterials.push(led.material);

        // Brises verticais de madeira no painel grafite do térreo.
        for (let i = 0; i < 6; i++) {
            const b = addEnvironmentBox(H, null, "Brise de madeira", "Fachada", .12, 3.5, .22, .2 + i * .3, 2.45, 5.84, 0x7a5236);
            aqTexture(b, "wood", 1.2);
        }

        // Terraço sobre a laje: guarda-corpo de vidro e floreiras nos cantos.
        aqGlassRail(H, null, -8.5, 5.5, .75, 5.5, terraceY, 1.0);
        aqGlassRail(H, null, 5.45, 5.5, 8.5, 5.5, terraceY, 1.0);
        aqGlassRail(H, null, -8.5, 5.5, -8.5, -5.5, terraceY, 1.0);
        aqGlassRail(H, null, 8.5, 5.5, 8.5, -5.5, terraceY, 1.0);
        aqGlassRail(H, null, -8.5, -5.5, 8.5, -5.5, terraceY, 1.0);
        [[-7.5, 4.85], [7.5, 4.85], [-7.5, -4.85], [7.5, -4.85]].forEach(([x, z]) => {
            addEnvironmentBox(H, null, "Floreira do terraço", "Paisagismo", 1.1, .55, .8, x, terraceY + .28, z, 0x3a3d3c);
            aqShrub(H, null, x, terraceY + .5, z, .75);
        });
        const deck = addEnvironmentBox(H, null, "Deck do terraço", "Lazer", 2.0, .05, 9.6, -7.35, terraceY + .03, 0, 0x7a5638);
        aqTexture(deck, "wood", 1);

        // Cobertura: platibanda nos quatro lados, placas solares, caixa d'água e claraboia.
        addEnvironmentBox(H, null, "Platibanda", "Acabamento", 12.95, .55, .22, 0, roofTop + .27, 4.47, 0x66645f);
        addEnvironmentBox(H, null, "Platibanda", "Acabamento", 12.95, .55, .22, 0, roofTop + .27, -4.17, 0x66645f);
        [-6.37, 6.37].forEach(x => addEnvironmentBox(H, null, "Platibanda", "Acabamento", .22, .55, 8.64, x, roofTop + .27, .15, 0x66645f));
        aqSolarArray(H, null, 2.8, roofTop, -.6, 4, 2, .32);
        aqWaterTank(H, null, -4.0, roofTop, -2.3, .85, 1.0, "Caixa d'água da residência");
        const sky = addEnvironmentBox(H, null, "Claraboia", "Cobertura", 1.8, .3, 1.3, -2.6, roofTop + .15, 2.4, 0x6fb0c8);
        sky.material = aqMarkTransparent(new THREE.MeshStandardMaterial({ color: 0x7fc4dc, transparent: true, opacity: .55, roughness: .05, metalness: .4 }));
        addEnvironmentBox(H, null, "Moldura da claraboia", "Cobertura", 2.0, .12, 1.5, -2.6, roofTop + .06, 2.4, 0x2a2d2f);

        // Caminho de pisantes até a porta, balizadores e arbustos no canteiro.
        [6.55, 7.25, 7.95].forEach(z => {
            const st = addEnvironmentBox(property, null, "Pisante de pedra", "Paisagismo", 1.3, .06, .5, -2.0, .17, z, 0xbcb5a8);
            aqTexture(st, "concrete", 1.5);
        });
        [[-3.1, 7.6], [-.9, 7.6]].forEach(([x, z]) => aqBollard(property, x, z, .1));
        [-8.7, -7.1, -5.9].forEach((x, i) => aqShrub(property, null, x, .15, 6.0, .7 + (i % 2) * .15, i === 1 ? 0x3b7d45 : 0x2d6436));
        [[-12.9, -1.5], [-12.9, 1.4]].forEach(([x, z]) => aqShrub(property, null, x, .15, z, .8));
        // Caixa de correio e interfone junto à calçada.
        addEnvironmentBox(property, null, "Pilar do interfone", "Acesso", .45, 1.3, .35, -.4, .7, 8.75, 0x3a3d3c);
        addEnvironmentBox(property, null, "Interfone", "Acesso", .26, .34, .05, -.4, 1.0, 8.95, 0x1b1f22, { emissive: 0x168cff, emissiveIntensity: .6 });
    }

    property.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
    house.add(property);
    houseShellParts.push(property);

    house.traverse(function(object){
        if(object.isMesh && houseShellParts.indexOf(object)===-1 && walls.indexOf(object)===-1) houseShellParts.push(object);
    });

    // Texturas aplicadas por tipo de acabamento (madeira, pedra, concreto, grama e pavimento).
    house.traverse(o => {
        if (!o.isMesh || !o.userData.aquaComponent || !o.material || o.material.map || o.material.transparent) return;
        const n = (o.userData.aquaComponent.name || "").toLowerCase();
        if (/madeira|ripado|amadeirado|sacada superior|deck do terraço|brise/.test(n)) aqTexture(o, "wood", 1.2);
        else if (/painel lateral esquerdo|painel superior direito/.test(n)) aqTexture(o, "stone", 1.6);
        else if (/volume térreo|corpo fechado|painel lateral direito|platibanda|faixa frontal/.test(n)) aqTexture(o, "concrete", 3);
        else if (/gramado|grama/.test(n)) aqTexture(o, "grass", 1.5);
        else if (/calçada|piso de acesso|faixa de acesso|piso da área gourmet/.test(n)) aqTexture(o, "pavers", 1.4);
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
    // Acabamento: borda de pedra clara, escada inox, espreguiçadeiras e água com ondulação animada.
    const coping=new THREE.MeshStandardMaterial({color:0xcfc6b6,roughness:.8});
    coping.map=aqCanvasTexture("concrete").clone(); coping.map.needsUpdate=true; coping.map.repeat.set(3,1);
    [[7.95,.12,.7,0,.76,2.275],[7.95,.12,.7,0,.76,-2.275],[.7,.12,3.85,3.575,.76,0],[.7,.12,3.85,-3.575,.76,0]].forEach(([w,h,d,x,y,z])=>{
        const m=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),coping); m.position.set(x,y,z); m.castShadow=true; m.receiveShadow=true; poolGroup.add(m);
    });
    const waterTex=aqCanvasTexture("water").clone(); waterTex.needsUpdate=true; waterTex.repeat.set(3,2);
    poolWater.material.map=waterTex; poolWater.material.color.set(0x2a8aa0); poolWater.material.needsUpdate=true;
    animatedParts.push(d=>{ waterTex.offset.x+=d*.025; waterTex.offset.y+=d*.012; });
    const inox=new THREE.MeshStandardMaterial({color:0xd5dadf,metalness:.85,roughness:.2});
    [2.0,2.5].forEach(xx=>{
        const v=new THREE.Mesh(new THREE.CylinderGeometry(.03,.03,.8,8),inox); v.position.set(xx,.75,1.75); poolGroup.add(v);
        const arc=new THREE.Mesh(new THREE.TorusGeometry(.25,.03,6,14,Math.PI),inox); arc.rotation.y=Math.PI/2; arc.position.set(xx,1.15,2.0); poolGroup.add(arc);
        const o=new THREE.Mesh(new THREE.CylinderGeometry(.03,.03,.33,8),inox); o.position.set(xx,.985,2.25); poolGroup.add(o);
    });
    [.45,.7].forEach(y=>{const r=new THREE.Mesh(new THREE.BoxGeometry(.5,.04,.12),inox); r.position.set(2.25,y,1.8); poolGroup.add(r);});
    const cushion=new THREE.MeshStandardMaterial({color:0xe9e3d6,roughness:.85}), frameL=new THREE.MeshStandardMaterial({color:0x3a3d3f,roughness:.5,metalness:.4});
    [-1.0,1.2].forEach(lx=>{
        const base=new THREE.Mesh(new THREE.BoxGeometry(1.5,.12,.68),cushion); base.position.set(lx+.15,.42,3.2); poolGroup.add(base);
        const back=new THREE.Mesh(new THREE.BoxGeometry(.6,.1,.66),cushion); back.position.set(lx-.78,.62,3.2); back.rotation.z=.6; poolGroup.add(back);
        const fr=new THREE.Mesh(new THREE.BoxGeometry(1.9,.08,.72),frameL); fr.position.set(lx,.33,3.2); poolGroup.add(fr);
        [-.85,.85].forEach(dx=>[-.3,.3].forEach(dz=>{const l=new THREE.Mesh(new THREE.BoxGeometry(.05,.3,.05),frameL); l.position.set(lx+dx,.15,3.2+dz); poolGroup.add(l);}));
    });
    const table=new THREE.Mesh(new THREE.CylinderGeometry(.22,.22,.45,16),frameL); table.position.set(.1,.25,3.2); poolGroup.add(table);
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

    animatedParts.forEach(fn => fn(delta));


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
        reservatorio: {label:"Reservatório superior", rate:0, pos:[0,11.6,2.8], type:"Reservatório"}
    },
    empresa: {
        banheiro: {label:"Banheiro", rate:1.5, pos:[-4.2,1.0,-3.0], type:"Banheiro"},
        copa: {label:"Copa / cozinha", rate:1.2, pos:[4.2,1.0,-3.0], type:"Copa"},
        limpeza: {label:"Ponto de limpeza", rate:0.9, pos:[-4.0,1.0,3.2], type:"Limpeza"},
        reservatorio: {label:"Reservatório", rate:0, pos:[0,5.6,3.0], type:"Reservatório"}
    },
    predio_comercial: {
        banheiro1: {label:"Banheiro — térreo", rate:2.0, pos:[-4.5,1.0,-3.2], type:"Banheiro"},
        copa1: {label:"Copa — térreo", rate:1.5, pos:[4.5,1.0,-3.2], type:"Copa"},
        banheiro2: {label:"Banheiro — 2º andar", rate:2.0, pos:[-4.5,4.5,-3.2], type:"Banheiro"},
        copa2: {label:"Copa — 2º andar", rate:1.5, pos:[4.5,4.5,-3.2], type:"Copa"},
        banheiro3: {label:"Banheiro — 3º andar", rate:2.0, pos:[-4.5,8.0,-3.2], type:"Banheiro"},
        copa3: {label:"Copa — 3º andar", rate:1.5, pos:[4.5,8.0,-3.2], type:"Copa"},
        reservatorio: {label:"Reservatório superior", rate:0, pos:[0,11.7,2.6], type:"Reservatório"}
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
        maquina1: {label:"Lavadora industrial", rate:7.5, pos:[-7,.92,-4], type:"Máquina industrial"},
        maquina2: {label:"Centro de processamento", rate:10.2, pos:[-2,.92,-4], type:"Máquina industrial"},
        maquina3: {label:"Linha de envase", rate:8.4, pos:[3,.92,-4], type:"Máquina industrial"},
        maquina4: {label:"Prensa hidráulica", rate:9.0, pos:[8,.92,-4], type:"Máquina industrial"},
        lavagem:  {label:"Oficina — lavagem", rate:5.6, pos:[17.2,.92,2], type:"Processo"},
        esteira:  {label:"Linha de montagem", rate:4.2, pos:[6.5,.92,.5], type:"Máquina industrial"},
        torre:    {label:"Torre de resfriamento", rate:4.5, pos:[17.5,1.5,-6.5], type:"Processo"}
    },
    agro: {
        setorA:    {label:"Irrigação — Setor A", rate:12.0, pos:[-2.2,0.8,-5.5], type:"Irrigação"},
        setorB:    {label:"Irrigação — Setor B", rate:10.5, pos:[4.2,0.8,-5.5], type:"Irrigação"},
        setorC:    {label:"Irrigação — Setor C", rate:9.5, pos:[10.6,0.8,-5.5], type:"Irrigação"},
        bebedouro: {label:"Bebedouro animal", rate:2.2, pos:[-8,1.0,13], type:"Ponto de consumo"},
        bomba:     {label:"Bomba", rate:0, pos:[-9,1.3,8], type:"Bomba hidráulica"}
    }
};


/* =====================================================
   DETALHAMENTO 3D — texturas procedurais e peças reutilizáveis
   (funções declaradas: ficam disponíveis antes de init())
===================================================== */

var aqTexCache = {};
function aqCanvasTexture(kind) {
    if (aqTexCache[kind]) return aqTexCache[kind];
    const c = document.createElement("canvas");
    c.width = c.height = 256;
    const g = c.getContext("2d");
    let seed = 0;
    for (let i = 0; i < kind.length; i++) seed = (seed * 31 + kind.charCodeAt(i)) % 2147483647;
    seed = seed || 7;
    const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
    const gray = (v, a = 1) => `rgba(${v},${v},${v},${a})`;
    g.fillStyle = "#ffffff";
    g.fillRect(0, 0, 256, 256);

    if (kind === "wood") {
        // tábuas verticais com veios
        for (let p = 0; p < 4; p++) {
            const x0 = p * 64;
            g.fillStyle = gray(225 + Math.floor(rnd() * 30));
            g.fillRect(x0, 0, 64, 256);
            for (let k = 0; k < 14; k++) {
                g.strokeStyle = gray(150 + Math.floor(rnd() * 60), .35);
                g.lineWidth = 1 + rnd() * 2;
                g.beginPath();
                const x = x0 + 4 + rnd() * 56;
                g.moveTo(x, 0);
                g.bezierCurveTo(x + rnd() * 6 - 3, 80, x + rnd() * 6 - 3, 170, x + rnd() * 4 - 2, 256);
                g.stroke();
            }
            g.fillStyle = gray(90, .55);
            g.fillRect(x0, 0, 2, 256);
        }
    } else if (kind === "planks") {
        // tábuas largas de celeiro, com juntas escuras
        for (let p = 0; p < 4; p++) {
            g.fillStyle = gray(205 + Math.floor(rnd() * 50));
            g.fillRect(p * 64, 0, 64, 256);
            g.fillStyle = gray(60, .7);
            g.fillRect(p * 64, 0, 4, 256);
            for (let k = 0; k < 6; k++) {
                g.fillStyle = gray(140, .18);
                g.fillRect(p * 64 + 6 + rnd() * 52, rnd() * 256, 2, 30 + rnd() * 90);
            }
        }
    } else if (kind === "concrete") {
        for (let i = 0; i < 2600; i++) {
            g.fillStyle = gray(170 + Math.floor(rnd() * 85), .22);
            g.fillRect(rnd() * 256, rnd() * 256, 2, 2);
        }
        g.strokeStyle = gray(120, .25);
        g.lineWidth = 2;
        for (let i = 0; i <= 256; i += 128) {
            g.beginPath(); g.moveTo(0, i); g.lineTo(256, i); g.stroke();
            g.beginPath(); g.moveTo(i, 0); g.lineTo(i, 256); g.stroke();
        }
        for (const [x, y] of [[32, 32], [96, 32], [160, 32], [224, 32], [32, 96], [96, 96], [160, 96], [224, 96]]) {
            g.fillStyle = gray(110, .35);
            g.beginPath(); g.arc(x, y, 3, 0, Math.PI * 2); g.fill();
            g.beginPath(); g.arc(x, y + 128, 3, 0, Math.PI * 2); g.fill();
        }
    } else if (kind === "stone") {
        // pedra filetada / canjiquinha: fiadas irregulares
        g.fillStyle = gray(80);
        g.fillRect(0, 0, 256, 256);
        let y = 0;
        while (y < 256) {
            const h = 10 + Math.floor(rnd() * 14);
            let x = -Math.floor(rnd() * 40);
            while (x < 256) {
                const w = 26 + Math.floor(rnd() * 60);
                g.fillStyle = gray(170 + Math.floor(rnd() * 85));
                g.fillRect(x + 1, y + 1, w - 2, h - 2);
                x += w;
            }
            y += h;
        }
    } else if (kind === "pavers") {
        for (let r = 0; r < 8; r++)
            for (let k = 0; k < 4; k++) {
                g.fillStyle = gray(200 + Math.floor(rnd() * 50));
                g.fillRect(k * 64 + (r % 2) * 32 + 2, r * 32 + 2, 60, 28);
                g.fillRect(k * 64 + (r % 2) * 32 - 256 + 2, r * 32 + 2, 60, 28);
            }
        g.globalCompositeOperation = "destination-over";
        g.fillStyle = gray(120);
        g.fillRect(0, 0, 256, 256);
        g.globalCompositeOperation = "source-over";
    } else if (kind === "grass") {
        for (let i = 0; i < 5000; i++) {
            g.fillStyle = gray(150 + Math.floor(rnd() * 105), .5);
            g.fillRect(rnd() * 256, rnd() * 256, 1, 3 + rnd() * 3);
        }
    } else if (kind === "tiles") {
        // telha cerâmica: fiadas horizontais com sombra
        for (let r = 0; r < 8; r++) {
            const y0 = r * 32;
            for (let k = 0; k < 32; k++) {
                g.fillStyle = gray(255 - Math.floor(110 * Math.pow(k / 32, 2)));
                g.fillRect(0, y0 + k, 256, 1);
            }
            for (let x = (r % 2) * 16; x < 256; x += 32) {
                g.fillStyle = gray(70, .45);
                g.fillRect(x, y0, 2, 32);
            }
        }
    } else if (kind === "corrugated") {
        for (let x = 0; x < 256; x++) {
            const v = 180 + Math.floor(75 * (0.5 + 0.5 * Math.cos((x / 256) * Math.PI * 2 * 16)));
            g.fillStyle = gray(v);
            g.fillRect(x, 0, 1, 256);
        }
    } else if (kind === "water") {
        g.fillStyle = gray(225);
        g.fillRect(0, 0, 256, 256);
        for (let i = 0; i < 70; i++) {
            g.strokeStyle = gray(255, .35 + rnd() * .4);
            g.lineWidth = 1 + rnd() * 2;
            const x = rnd() * 256, y = rnd() * 256, l = 20 + rnd() * 50;
            g.beginPath(); g.moveTo(x, y); g.quadraticCurveTo(x + l / 2, y - 4, x + l, y); g.stroke();
        }
    }
    const t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    t.anisotropy = 4;
    aqTexCache[kind] = t;
    return t;
}

/* Aplica uma textura ao material de um mesh, com repetição proporcional ao tamanho da caixa. */
function aqTexture(mesh, kind, scale = 2) {
    if (!mesh || !mesh.material || !mesh.geometry) return;
    const p = mesh.geometry.parameters || {};
    const dims = [p.width || 1, p.height || 1, p.depth || 1].sort((a, b) => b - a);
    const t = aqCanvasTexture(kind).clone();
    t.needsUpdate = true;
    t.repeat.set(Math.max(1, dims[0] / scale), Math.max(1, dims[1] / scale));
    mesh.material.map = t;
    mesh.material.needsUpdate = true;
}

/* Guarda a transparência original (vidros) para o Raio-X restaurar corretamente. */
function aqMarkTransparent(mat) {
    mat.userData = mat.userData || {};
    mat.userData.aqBase = { transparent: true, opacity: mat.opacity, depthWrite: mat.depthWrite };
    return mat;
}
function aqRestoreMaterial(o) {
    const m = o.material;
    const base = m.userData && m.userData.aqBase;
    m.transparent = base ? base.transparent : false;
    m.opacity = base ? base.opacity : 1;
    m.depthWrite = base ? base.depthWrite : true;
    m.needsUpdate = true;
}

function aqMesh(group, list, geo, mat, x, y, z, name, type, cast = true) {
    const m = new THREE.Mesh(geo, mat);
    m.position.set(x, y, z);
    m.castShadow = cast;
    m.receiveShadow = true;
    if (name) m.userData.aquaComponent = { name, type: type || "Estrutura", details: "" };
    group.add(m);
    if (list) list.push(m);
    return m;
}

/* Janela completa: luz interna (acende com o botão de iluminação), vidro,
   caixilho, montante central e peitoril. Por padrão voltada para +Z. */
function aqWindow(group, list, x, y, z, w, h, opts = {}) {
    const holder = new THREE.Group();
    holder.position.set(x, y, z);
    holder.rotation.y = opts.ry || 0;
    holder.userData.aquaComponent = { name: opts.name || "Janela", type: "Esquadria", details: "Esquadria da fachada" };
    const frameMat = makeMaterial(opts.frame !== undefined ? opts.frame : 0x1b1f22);
    const add = (geo, mat, px, py, pz) => {
        const m = new THREE.Mesh(geo, mat);
        m.position.set(px, py, pz);
        m.castShadow = true;
        m.receiveShadow = true;
        m.userData.aquaComponent = holder.userData.aquaComponent;
        holder.add(m);
        if (list) list.push(m);
        return m;
    };
    const lightMat = new THREE.MeshStandardMaterial({ color: 0x0e1418, roughness: .9, emissive: opts.light || 0x5c3d18, emissiveIntensity: 0 });
    aquaGlowMaterials.push(lightMat);
    add(new THREE.BoxGeometry(w, h, .02), lightMat, 0, 0, 0.0);
    const glassMat = aqMarkTransparent(new THREE.MeshStandardMaterial({ color: opts.glass || 0x2b4a5a, transparent: true, opacity: .5, roughness: .05, metalness: .55 }));
    add(new THREE.BoxGeometry(w, h, .04), glassMat, 0, 0, .06);
    const f = opts.frameW || .09, fd = .16;
    add(new THREE.BoxGeometry(w + f * 2, f, fd), frameMat, 0, h / 2 + f / 2, .06);
    add(new THREE.BoxGeometry(w + f * 2, f, fd), frameMat, 0, -h / 2 - f / 2, .06);
    add(new THREE.BoxGeometry(f, h, fd), frameMat, -w / 2 - f / 2, 0, .06);
    add(new THREE.BoxGeometry(f, h, fd), frameMat, w / 2 + f / 2, 0, .06);
    const cols = opts.cols || (w > 1.7 ? Math.max(2, Math.round(w / 1.4)) : 1);
    for (let i = 1; i < cols; i++) add(new THREE.BoxGeometry(f * .7, h, fd * .8), frameMat, -w / 2 + (w * i) / cols, 0, .07);
    if (opts.transom) add(new THREE.BoxGeometry(w, f * .7, fd * .8), frameMat, 0, h * .22, .07);
    if (opts.sill !== false) add(new THREE.BoxGeometry(w + .3, .07, .26), makeMaterial(opts.sillColor || 0x8f8a80), 0, -h / 2 - f - .03, .13);
    if (opts.shutters) {
        const sm = makeMaterial(opts.shutters);
        for (const s of [-1, 1]) {
            const sh = add(new THREE.BoxGeometry(w * .48, h + .1, .06), sm, s * (w * .74 + f), 0, .05);
            for (let k = -2; k <= 2; k++) add(new THREE.BoxGeometry(w * .44, .03, .08), frameMat, s * (w * .74 + f), k * h * .18, .08);
        }
    }
    group.add(holder);
    return holder;
}

/* Guarda-corpo de vidro com corrimão metálico entre dois pontos (plano XZ). */
function aqGlassRail(group, list, x1, z1, x2, z2, y, h = 1.0) {
    const dx = x2 - x1, dz = z2 - z1, len = Math.hypot(dx, dz), ang = -Math.atan2(dz, dx);
    const glass = aqMarkTransparent(new THREE.MeshStandardMaterial({ color: 0x9fd2e6, transparent: true, opacity: .28, roughness: .05, metalness: .2, depthWrite: false }));
    const steel = makeMaterial(0x9aa3ab);
    const g = aqMesh(group, list, new THREE.BoxGeometry(len, h * .9, .04), glass, (x1 + x2) / 2, y + h * .45, (z1 + z2) / 2, "Guarda-corpo de vidro", "Segurança", false);
    g.rotation.y = ang;
    const r = aqMesh(group, list, new THREE.BoxGeometry(len, .06, .09), steel, (x1 + x2) / 2, y + h, (z1 + z2) / 2, "Corrimão", "Segurança");
    r.rotation.y = ang;
    const n = Math.max(1, Math.round(len / 1.6));
    for (let i = 0; i <= n; i++) aqMesh(group, list, new THREE.BoxGeometry(.06, h, .06), steel, x1 + (dx * i) / n, y + h / 2, z1 + (dz * i) / n, "Montante", "Segurança");
}

/* Arbusto em tufos (low-poly) — paisagismo. */
function aqShrub(group, list, x, y, z, s = 1, color = 0x2f6b3a) {
    const mat = new THREE.MeshStandardMaterial({ color, roughness: .9, flatShading: true });
    const geo = new THREE.IcosahedronGeometry(.42 * s, 0);
    [[0, 0, 0, 1], [.32, -.05, .1, .8], [-.3, -.06, -.08, .78], [.05, .18, -.22, .7]].forEach(([dx, dy, dz, k]) => {
        const m = aqMesh(group, list, geo, mat, x + dx * s, y + .32 * s + dy * s, z + dz * s, "Arbusto", "Paisagismo");
        m.scale.setScalar(k);
    });
}

/* Árvore com copa em blocos irregulares. */
function aqTree(group, list, x, z, s = 1, y = 0) {
    const trunk = aqMesh(group, list, new THREE.CylinderGeometry(.13 * s, .22 * s, 2.2 * s, 8), makeMaterial(0x4f3524), x, y + 1.1 * s, z, "Árvore", "Paisagismo");
    trunk.material.roughness = .95;
    const cols = [0x2f6533, 0x3b7a3c, 0x285a2e];
    const geo = new THREE.IcosahedronGeometry(1, 1);
    [[0, 2.7, 0, 1.15], [.7, 2.35, .3, .82], [-.65, 2.4, -.25, .85], [.1, 3.3, -.35, .75], [-.2, 2.3, .65, .7]].forEach(([dx, dy, dz, k], i) => {
        const mat = new THREE.MeshStandardMaterial({ color: cols[i % 3], roughness: .9, flatShading: true });
        const m = aqMesh(group, list, geo, mat, x + dx * s, y + dy * s, z + dz * s, "Árvore", "Paisagismo");
        m.scale.set(k * s, k * s * .9, k * s);
    });
}

/* Balizador de jardim: só a cúpula acende (sem PointLight extra para não pesar a cena). */
function aqBollard(group, x, z, y = 0) {
    aqMesh(group, null, new THREE.CylinderGeometry(.08, .1, .6, 10), makeMaterial(0x26292b), x, y + .3, z, "Balizador", "Iluminação");
    const lm = new THREE.MeshStandardMaterial({ color: 0xd9c49a, emissive: 0xffb84a, emissiveIntensity: 0 });
    aquaGlowMaterials.push(lm);
    aqMesh(group, null, new THREE.CylinderGeometry(.11, .11, .1, 12), lm, x, y + .62, z, "Balizador", "Iluminação");
}

/* Placas solares inclinadas sobre suportes. */
function aqSolarArray(group, list, x, y, z, cols, rows, tilt = .32) {
    const panelMat = new THREE.MeshStandardMaterial({ color: 0x18233f, roughness: .25, metalness: .6, emissive: 0x0a1430, emissiveIntensity: .25 });
    const frameMat = makeMaterial(0xaab2ba);
    for (let r = 0; r < rows; r++)
        for (let c = 0; c < cols; c++) {
            const px = x + (c - (cols - 1) / 2) * 1.12, pz = z + (r - (rows - 1) / 2) * 1.9;
            const p = aqMesh(group, list, new THREE.BoxGeometry(1.05, .05, 1.6), panelMat, px, y + .55, pz, "Placa solar", "Energia");
            p.rotation.x = tilt;
            const fr = aqMesh(group, list, new THREE.BoxGeometry(1.09, .03, 1.64), frameMat, px, y + .52, pz, "Placa solar", "Energia");
            fr.rotation.x = tilt;
            aqMesh(group, list, new THREE.BoxGeometry(.06, .7, .06), frameMat, px, y + .35, pz - Math.sign(tilt) * .55, null);
        }
}

/* Caixa d'água com sensor AquaFlow (anel azul aceso). */
function aqWaterTank(group, list, x, y, z, r = .9, h = 1.1, name = "Caixa d'água") {
    aqMesh(group, list, new THREE.BoxGeometry(r * 2.3, .14, r * 2.3), makeMaterial(0x3c4044), x, y + .07, z, "Base da caixa d'água", "Estrutura");
    const body = aqMesh(group, list, new THREE.CylinderGeometry(r, r * .92, h, 28), makeMaterial(0x8f99a3), x, y + .14 + h / 2, z, name, "Reservatório");
    body.userData.aquaComponent.details = "Reservatório monitorado por sensor de nível AquaFlow";
    aqMesh(group, list, new THREE.CylinderGeometry(r * 1.04, r * 1.04, .16, 28), makeMaterial(0x6d757d), x, y + .14 + h + .06, z, name, "Reservatório");
    const ring = aqMesh(group, list, new THREE.CylinderGeometry(r * 1.015, r * 1.015, .08, 28, 1, true), new THREE.MeshStandardMaterial({ color: 0x168cff, emissive: 0x168cff, emissiveIntensity: 1.4 }), x, y + .14 + h * .62, z, "Sensor de nível AquaFlow", "Sensor");
    aqMesh(group, list, new THREE.BoxGeometry(.22, .18, .12), makeMaterial(0x7654ff, 0x7654ff, .9), x, y + .14 + h * .62, z + r + .05, "Módulo AquaFlow", "Sensor");
    return body;
}

/* Carro simples (estacionamentos dos prédios). */
function aqCar(group, x, z, ry, color, s = .82) {
    const car = new THREE.Group();
    const body = makeMaterial(color); body.metalness = .45; body.roughness = .3;
    const dark = makeMaterial(0x15181a);
    const glass = makeMaterial(0x24343b); glass.metalness = .5; glass.roughness = .1;
    const parts = [
        [new THREE.BoxGeometry(2.3, .6, 4.3), body, 0, .72, 0],
        [new THREE.BoxGeometry(1.95, .75, 2.2), glass, 0, 1.38, -.2],
        [new THREE.BoxGeometry(1.85, .1, 2.0), body, 0, 1.8, -.2],
        [new THREE.BoxGeometry(2.25, .18, .16), dark, 0, .55, 2.17],
        [new THREE.BoxGeometry(2.25, .18, .16), dark, 0, .55, -2.17],
    ];
    parts.forEach(([g, m, px, py, pz]) => { const o = new THREE.Mesh(g, m); o.position.set(px, py, pz); o.castShadow = true; car.add(o); });
    [-1.02, 1.02].forEach(xx => [-1.35, 1.35].forEach(zz => {
        const t = new THREE.Mesh(new THREE.CylinderGeometry(.42, .42, .26, 16), dark);
        t.rotation.z = Math.PI / 2; t.position.set(xx, .45, zz); car.add(t);
    }));
    car.scale.setScalar(s);
    car.position.set(x, 0, z);
    car.rotation.y = ry;
    car.userData.aquaComponent = { name: "Veículo", type: "Estacionamento", details: "" };
    group.add(car);
    return car;
}

/* Vaca low-poly (fazenda). */
function aqCow(group, x, z, ry) {
    const cow = new THREE.Group();
    const white = makeMaterial(0xe9e4da), black = makeMaterial(0x1f1c1a), pink = makeMaterial(0xd99a94);
    white.roughness = black.roughness = .9;
    const box = (w, h, d, m, px, py, pz) => { const o = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), m); o.position.set(px, py, pz); o.castShadow = true; o.receiveShadow = true; cow.add(o); };
    box(1.7, .8, .78, white, 0, 1.0, 0);
    box(.7, .66, .8, black, -.3, 1.08, 0);
    box(.45, .4, .8, black, .45, .9, 0);
    box(.55, .5, .5, white, 1.05, 1.25, 0);
    box(.22, .3, .48, pink, 1.35, 1.12, 0);
    box(.12, .14, .9, black, .95, 1.5, 0);
    [-.6, .6].forEach(lx => [-.27, .27].forEach(lz => { box(.18, .62, .18, white, lx, .31, lz); box(.2, .12, .2, black, lx, .06, lz); }));
    box(.06, .6, .06, black, -.88, .9, 0);
    cow.position.set(x, 0, z);
    cow.rotation.y = ry;
    cow.userData.aquaComponent = { name: "Gado", type: "Pecuária", details: "Animais abastecidos pelo bebedouro monitorado" };
    group.add(cow);
    return cow;
}

/* Telhado de duas águas com cumeeira ao longo de X, beiral e oitões na cor da parede. */
function aqGableRoof(group, list, cx, y, cz, len, depth, rise, o, roofMat, wallColor, t = .16) {
    const a = Math.atan2(rise, depth / 2);
    const L = Math.hypot(depth / 2, rise) + o / Math.cos(a);
    for (const s of [1, -1]) {
        const u = [0, -Math.sin(a), s * Math.cos(a)];
        const n = [0, Math.cos(a), s * Math.sin(a)];
        const m = aqMesh(group, list, new THREE.BoxGeometry(len + 2 * o, t, L), roofMat, cx, y + rise + (u[1] * L) / 2 + (n[1] * t) / 2, cz + (u[2] * L) / 2 + (n[2] * t) / 2, "Telhado", "Cobertura");
        m.rotation.x = s * a;
    }
    aqMesh(group, list, new THREE.BoxGeometry(len + 2 * o + .1, .14, .22), makeMaterial(0x4a2c20), cx, y + rise + t / Math.cos(a), cz, "Cumeeira", "Cobertura");
    const shape = new THREE.Shape();
    shape.moveTo(-depth / 2, 0); shape.lineTo(depth / 2, 0); shape.lineTo(0, rise); shape.closePath();
    const geo = new THREE.ExtrudeGeometry(shape, { depth: .12, bevelEnabled: false });
    for (const s of [-1, 1]) {
        const gm = aqMesh(group, list, geo, makeMaterial(wallColor), cx + s * len / 2 - .06, y, cz, "Oitão", "Fachada");
        gm.rotation.y = Math.PI / 2;
    }
}

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

function createResidentialBuildingEnvironment() {
    const group=new THREE.Group();
    const shell=[],equipment=[],systems=[];
    const W=15.5,D=10.5,H=3.35;
    const body=0x2f3540, slabColor=0xb7b9bb, dark=0x14191f, wood=0x7a5236, purple=0x7654ff;
    const front=-D/2;

    // Terreno, calçada, jardim frontal e acesso
    addEnvironmentBox(group,shell,"Terreno residencial","Terreno",32,.2,28,0,.02,0,0x20262f);
    aqTexture(addEnvironmentBox(group,shell,"Calçada frontal","Acesso",27,.1,4,0,.18,-10.5,0x6a6f75),"pavers",1.4);
    aqTexture(addEnvironmentBox(group,shell,"Jardim frontal esquerdo","Paisagismo",11.9,.08,3.0,-7.55,.17,-6.8,0x2f5a35),"grass",1.5);
    aqTexture(addEnvironmentBox(group,shell,"Jardim frontal direito","Paisagismo",11.9,.08,3.0,7.55,.17,-6.8,0x2f5a35),"grass",1.5);
    aqTexture(addEnvironmentBox(group,shell,"Passeio de entrada","Acesso",3.2,.1,3.1,0,.18,-6.8,0x8c8a85),"pavers",1.2);

    // Muro baixo de pedra com gradil metálico e portão de pedestres
    const fenceMat=makeMaterial(0x23282e);
    [[-7.75,11.5],[7.75,11.5]].forEach(([x,w])=>{
        aqTexture(addEnvironmentBox(group,shell,"Mureta de pedra","Fechamento",w,.6,.35,x,.5,-8.35,0x6f675c),"stone",1.2);
        addEnvironmentBox(group,shell,"Gradil","Fechamento",w,.06,.06,x,1.75,-8.35,0x23282e);
        for(let k=-w/2+.15;k<=w/2-.1;k+=.28){
            const bar=new THREE.Mesh(new THREE.BoxGeometry(.04,1.0,.04),fenceMat); bar.position.set(x+k,1.25,-8.35); bar.castShadow=true; group.add(bar); shell.push(bar);
        }
    });
    [-1.75,1.75].forEach(x=>aqTexture(addEnvironmentBox(group,shell,"Pilar do portão","Fechamento",.45,1.9,.45,x,.95,-8.35,0x6f675c),"stone",1));
    addEnvironmentBox(group,equipment,"Portão de pedestres","Acesso",3.0,1.5,.05,0,1.0,-8.3,0x23282e,{emissive:0x168cff,emissiveIntensity:.08});

    // Base do edifício
    addEnvironmentBox(group,shell,"Base do edifício","Estrutura",W+.5,.3,D+.5,0,.35,0,0x1a212a);

    for(let f=0;f<3;f++){
        const y0=.5+f*H, cy=y0+.3+(H-.3)/2;
        const vol=addEnvironmentBox(group,shell,`Pavimento ${f+1}`,"Estrutura",W,H-.3,D,0,cy,0,body);
        aqTexture(vol,"concrete",4);
        // Laje aparente: faixa clara que marca cada pavimento.
        aqTexture(addEnvironmentBox(group,shell,`Laje do ${f+1}º pavimento`,"Estrutura",W+.5,.3,D+.5,0,y0+.15,0,slabColor),"concrete",4);

        // Revestimento amadeirado nas extremidades da fachada.
        [-1,1].forEach(s=>aqTexture(addEnvironmentBox(group,shell,"Revestimento de madeira","Fachada",1.4,H-.3,.12,s*(W/2-.7),cy,front-.07,wood),"wood",1.2));

        // Esquadrias de cada apartamento (x01 à esquerda, x02 à direita).
        [-1,1].forEach(s=>{
            aqWindow(group,equipment,s*4.2,y0+1.5,front-.02,3.2,2.3,{ry:Math.PI,name:`Porta de correr — apto ${f+1}0${s<0?1:2}`,cols:3});
            if(f>0) aqWindow(group,equipment,s*1.6,y0+1.95,front-.02,1.2,1.2,{ry:Math.PI,name:`Janela — apto ${f+1}0${s<0?1:2}`,cols:1});
        });

        if(f>0){
            // Sacadas com piso, guarda-corpo de vidro, floreira e divisória central.
            [-1,1].forEach(s=>{
                const bx=s*3.525;
                addEnvironmentBox(group,equipment,`Sacada ${f+1}0${s<0?1:2}`,"Sacada",6.75,.3,1.6,bx,y0+.15,front-.8,slabColor);
                aqGlassRail(group,equipment,s*.15,front-1.55,s*6.85,front-1.55,y0+.3,1.0);
                aqGlassRail(group,equipment,s*6.85,front-.05,s*6.85,front-1.55,y0+.3,1.0);
                addEnvironmentBox(group,equipment,"Floreira da sacada","Paisagismo",1.0,.45,.5,s*6.1,y0+.53,front-.45,0x3a3d3c);
                aqShrub(group,equipment,s*6.1,y0+.65,front-.45,.55);
            });
            addEnvironmentBox(group,shell,"Divisória entre sacadas","Fachada",.3,H-.3,1.65,0,cy,front-.82,slabColor);
        }

        // Laterais e fundos
        [-1,1].forEach(s=>{
            aqWindow(group,equipment,s*(W/2+.02),y0+1.85,-2.0,1.9,1.5,{ry:s*Math.PI/2,name:"Janela lateral"});
            aqWindow(group,equipment,s*(W/2+.02),y0+1.85,2.4,1.9,1.5,{ry:s*Math.PI/2,name:"Janela lateral"});
            aqWindow(group,equipment,s*4.6,y0+1.85,D/2+.02,2.6,1.5,{name:"Janela dos fundos"});
        });
        aqWindow(group,equipment,0,y0+2.3,D/2+.02,.9,.7,{name:"Janela do banheiro",cols:1});
        addEnvironmentBox(group,equipment,"Linha de iluminação","Iluminação",f>0?13.4:W*.84,.05,.06,0,y0+.15,f>0?front-1.63:front-.28,purple,{emissive:purple,emissiveIntensity:1.6});
    }

    // Térreo: hall de entrada com porta de vidro, marquise iluminada e totem
    aqWindow(group,equipment,0,1.95,front-.04,2.4,2.7,{ry:Math.PI,name:"Porta do hall",cols:2,light:0x6a4a20,sill:false});
    addEnvironmentBox(group,shell,"Moldura do hall","Fachada",3.1,.3,.2,0,3.5,front-.1,dark);
    [-1.4,1.4].forEach(x=>addEnvironmentBox(group,shell,"Moldura do hall","Fachada",.3,3.0,.2,x,2.0,front-.1,dark));
    addEnvironmentBox(group,equipment,"Marquise do hall","Cobertura",4.6,.2,2.3,0,3.55,front-1.15,slabColor);
    const canopyLed=addEnvironmentBox(group,equipment,"Fita de LED da marquise","Iluminação",4.2,.04,.06,0,3.43,front-2.1,0xd9c49a,{emissive:0xffb84a,emissiveIntensity:0});
    aquaGlowMaterials.push(canopyLed.material);
    [-2.0,2.0].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da marquise","Estrutura",.12,3.0,.12,x,1.95,front-2.2,0x9aa3ab));
    addEnvironmentBox(group,equipment,"Totem do condomínio","Identificação",.5,1.6,.25,-2.6,.95,-7.9,dark);
    addEnvironmentBox(group,equipment,"Logo AquaFlow","Identificação",.3,.3,.03,-2.6,1.4,-8.04,purple,{emissive:purple,emissiveIntensity:1.4});
    [[-1.3,-6.2],[1.3,-6.2],[-1.3,-7.6],[1.3,-7.6]].forEach(([x,z])=>aqBollard(group,x,z,.2));
    [-12,-9.5,-6.8,6.8,9.5,12].forEach((x,i)=>aqShrub(group,shell,x,.2,-7.7,.85,i%2?0x3b7d45:0x2d6436));

    // Cobertura: laje, platibanda, casa de máquinas, reservatório, placas solares e terraço com pergolado
    const roofY=.5+3*H+.3;
    [[W+.5,.9,.25,0,-D/2-.12],[W+.5,.9,.25,0,D/2+.12]].forEach(([w,h,d,x,z])=>aqTexture(addEnvironmentBox(group,shell,"Platibanda","Cobertura",w,h,d,x,roofY+h/2,z,slabColor),"concrete",3));
    [-1,1].forEach(s=>aqTexture(addEnvironmentBox(group,shell,"Platibanda","Cobertura",.25,.9,D+.5,s*(W/2+.12),roofY+.45,0,slabColor),"concrete",3));
    addEnvironmentBox(group,equipment,"Linha de iluminação da cobertura","Iluminação",W+.4,.06,.08,0,roofY+.92,-D/2-.27,purple,{emissive:purple,emissiveIntensity:1.8});
    aqTexture(addEnvironmentBox(group,shell,"Casa de máquinas","Estrutura",3.2,2.4,3.0,-4.8,roofY+1.2,2.6,0x5b616a),"concrete",2);
    addEnvironmentBox(group,shell,"Laje da casa de máquinas","Cobertura",3.5,.15,3.3,-4.8,roofY+2.47,2.6,dark);
    addEnvironmentBox(group,equipment,"Porta da casa de máquinas","Acesso",.9,1.9,.06,-4.8,roofY+.95,1.07,0x2c3036);
    const tank=aqWaterTank(group,equipment,0,roofY,2.8,1.15,1.3,"Reservatório superior");
    tank.userData.aquaComponent={name:"Reservatório superior",type:"Reservatório",details:"Reservatório de abastecimento do edifício residencial, com sensor de nível AquaFlow."};
    aqSolarArray(group,equipment,-4.6,roofY,-2.6,3,1,-.32);
    const deck=addEnvironmentBox(group,equipment,"Deck do terraço","Lazer",6.4,.1,3.8,3.8,roofY+.05,-1.6,0x6a4b33);
    aqTexture(deck,"wood",1);
    [[1.0,-3.2],[6.6,-3.2],[1.0,0],[6.6,0]].forEach(([x,z])=>aqTexture(addEnvironmentBox(group,equipment,"Pilar do pergolado","Estrutura",.16,2.3,.16,x,roofY+1.15,z,wood),"wood",1));
    [-3.2,0].forEach(z=>addEnvironmentBox(group,equipment,"Viga do pergolado","Estrutura",5.9,.16,.16,3.8,roofY+2.3,z,wood));
    for(let x=1.2;x<=6.4;x+=.55) addEnvironmentBox(group,equipment,"Ripa do pergolado","Estrutura",.08,.14,3.6,x,roofY+2.42,-1.6,wood);
    addEnvironmentBox(group,equipment,"Sofá externo","Lazer",3.0,.45,.8,3.8,roofY+.32,-.5,0x4b4f55);
    addEnvironmentBox(group,equipment,"Encosto do sofá","Lazer",3.0,.45,.18,3.8,roofY+.7,-.15,0x4b4f55);
    addEnvironmentBox(group,equipment,"Mesa de centro","Lazer",1.2,.3,.6,3.8,roofY+.25,-1.9,wood);
    [[1.5,-2.6],[6.1,-2.6]].forEach(([x,z])=>aqShrub(group,equipment,x,roofY+.1,z,.75));

    // Estacionamento lateral com vagas demarcadas
    aqTexture(addEnvironmentBox(group,shell,"Estacionamento","Estacionamento",6.4,.06,9.6,12.4,.15,.8,0x2a2e33),"concrete",3);
    [-3.2,-.6,2.0,4.6].forEach(z=>addEnvironmentBox(group,shell,"Faixa de vaga","Estacionamento",4.6,.02,.08,12.9,.19,z+.8-.8,0xe8e6e0));
    aqCar(group,12.9,-1.9,Math.PI/2,0x3c4248);
    aqCar(group,12.9,3.3,Math.PI/2,0x8a8f96);

    // Piscina nos fundos, com borda de pedra e espreguiçadeiras
    aqTexture(addEnvironmentBox(group,equipment,"Deck da piscina","Lazer",15,.16,6,0,.3,9.5,0x6b5d50),"wood",1.2);
    const pool=new THREE.Mesh(new THREE.BoxGeometry(9.4,.55,4.5),makeMaterial(0x168cff,0x006cff,.55));
    pool.position.set(0,.64,10); pool.receiveShadow=true; pool.castShadow=true; group.add(pool); equipment.push(pool);
    const pTex=aqCanvasTexture("water").clone(); pTex.needsUpdate=true; pTex.repeat.set(3,2); pool.material.map=pTex;
    animatedParts.push(d=>{ pTex.offset.x+=d*.025; });
    pool.userData.aquaComponent={name:"Piscina residencial",type:"Lazer",details:"Área de lazer do edifício residencial."};
    [[10.2,.6,10,7.6],[10.2,.6,10,12.4]].forEach(([w,d,x,z])=>aqTexture(addEnvironmentBox(group,equipment,"Borda da piscina","Lazer",w,.12,d,0,.97,z,0xcfc6b6),"concrete",2));
    [-1,1].forEach(s=>aqTexture(addEnvironmentBox(group,equipment,"Borda da piscina","Lazer",.6,.12,4.2,s*5.0,.97,10,0xcfc6b6),"concrete",2));
    [-4.5,-2.8,2.8,4.5].forEach(x=>{
        addEnvironmentBox(group,equipment,"Espreguiçadeira","Lazer",.7,.14,1.5,x,.35,13.2,0xe9e3d6);
        const back=addEnvironmentBox(group,equipment,"Encosto da espreguiçadeira","Lazer",.66,.1,.6,x,.55,12.65,0xe9e3d6);
        back.rotation.x=-.6;
    });

    // Paisagismo traseiro
    [[-11.5,7.5],[-9.6,11.8],[9.6,11.8],[11.5,7.5],[-13.5,-2],[-13.5,3]].forEach(([x,z])=>aqTree(group,shell,x,z,.9,.1));
    [-6.5,6.5].forEach(x=>addEnvironmentLightPole(group,x,-9.2,4.7,"Iluminação frontal residencial"));
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

function createSimpleBuilding(group, shell, w, d, h, color=0xcfd4dc) {
    // Escritório térreo moderno: paredes reais (o Raio-X mostra o interior),
    // janelas em fita, laje plana com platibanda e entrada envidraçada.
    aqTexture(addEnvironmentBox(group,shell,"Piso da edificação","Estrutura",w+.4,.35,d+.4,0,.18,0,0x3a414b),"concrete",3);
    [["Parede frontal",w,h,.28,0,h/2+.35,-d/2],["Parede traseira",w,h,.28,0,h/2+.35,d/2],
     ["Parede esquerda",.28,h,d,-w/2,h/2+.35,0],["Parede direita",.28,h,d,w/2,h/2+.35,0]].forEach(([n,ww,hh,dd,x,y,z])=>
        aqTexture(addEnvironmentBox(group,shell,n,"Estrutura",ww,hh,dd,x,y,z,color),"concrete",3));
    const top=h+.35;
    aqTexture(addEnvironmentBox(group,shell,"Laje de cobertura","Cobertura",w+.8,.32,d+.8,0,top+.16,0,0x2b313a),"concrete",3);
    [[w+.8,.6,.2,0,-d/2-.3],[w+.8,.6,.2,0,d/2+.3]].forEach(([ww,hh,dd,x,z])=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",ww,hh,dd,x,top+.62,z,0x3a414b));
    [-1,1].forEach(s=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",.2,.6,d+.8,s*(w/2+.3),top+.62,0,0x3a414b));
    addEnvironmentBox(group,shell,"Linha de iluminação","Iluminação",w+.8,.05,.06,0,top+.05,-d/2-.42,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.6});
    addEnvironmentBox(group,shell,"Linha de iluminação","Iluminação",w+.8,.05,.06,0,top+.05,d/2+.42,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.6});

    // Janelas em fita
    const wy=.35+h*.56;
    [-4.3,4.3].forEach(x=>aqWindow(group,shell,x,wy,-d/2-.15,3.6,1.8,{ry:Math.PI,name:"Janela frontal",frame:0x20262d}));
    [-4.4,0,4.4].forEach(x=>aqWindow(group,shell,x,wy,d/2+.15,3.4,1.8,{name:"Janela dos fundos",frame:0x20262d}));
    [-1,1].forEach(s=>[-2.2,2.2].forEach(z=>aqWindow(group,shell,s*(w/2+.15),wy,z,2.6,1.8,{ry:s*Math.PI/2,name:"Janela lateral",frame:0x20262d})));

    // Entrada envidraçada com marquise iluminada e degraus
    aqWindow(group,shell,0,.35+1.4,-d/2-.15,2.4,2.7,{ry:Math.PI,name:"Entrada principal",cols:2,sill:false,frame:0x20262d,light:0x6a4a20});
    addEnvironmentBox(group,shell,"Marquise da entrada","Cobertura",4.4,.2,2.2,0,3.55,-d/2-1.2,0x2b313a);
    const led=addEnvironmentBox(group,shell,"Fita de LED da marquise","Iluminação",4.0,.04,.06,0,3.43,-d/2-2.2,0xd9c49a,{emissive:0xffb84a,emissiveIntensity:0});
    aquaGlowMaterials.push(led.material);
    [-1.9,1.9].forEach(x=>addEnvironmentBox(group,shell,"Pilar da marquise","Estrutura",.12,3.2,.12,x,1.8,-d/2-2.2,0x9aa3ab));
    [0,1].forEach(i=>addEnvironmentBox(group,shell,"Degrau","Acesso",3.4,.14,.5,0,.07+i*.14,-d/2-.9+i*.35,0x8c8a85));
}

function createMultiFloorBuilding(group, shell, equipment, options = {}) {
    const floors = options.floors || 3;
    const width = options.width || 13;
    const depth = options.depth || 9;
    const floorHeight = options.floorHeight || 3.4;
    const accent = options.accent || 0x7654ff;
    const title = options.title || "Edifício";
    const systems = options.systems || [];
    const front = -depth/2;
    const glass = 0x355f86, metal = 0x2b323d, light = 0x9aa3ab;

    aqTexture(addEnvironmentBox(group,shell,"Embasamento","Estrutura",width+.8,.3,depth+.8,0,.15,0,0x1d232b),"stone",1.5);

    for(let floor=0; floor<floors; floor++) {
        const y = floor * floorHeight + .3;
        const cy = y + .3 + (floorHeight-.3)/2, gh = floorHeight-.55;
        addEnvironmentBox(group,shell,`${title} — piso ${floor+1}`,"Estrutura",width+.3,.3,depth+.3,0,y+.15,0,metal);
        addEnvironmentBox(group,shell,`${title} — núcleo do piso ${floor+1}`,"Estrutura",width-.3,floorHeight-.3,depth-.3,0,cy,0,0x161c26);
        // Pele de vidro nas quatro faces (com luz interna que acende à noite)
        aqWindow(group,equipment,0,cy,front+.13,width-.7,gh,{ry:Math.PI,name:`Fachada de vidro — piso ${floor+1}`,cols:Math.round(width/1.4),frame:metal,glass,sill:false,light:0x4a3a20});
        aqWindow(group,equipment,0,cy,-front-.13,width-.7,gh,{name:`Fachada de vidro — piso ${floor+1}`,cols:Math.round(width/1.4),frame:metal,glass,sill:false,light:0x4a3a20});
        [-1,1].forEach(s=>aqWindow(group,equipment,s*(width/2-.13),cy,0,depth-.7,gh,{ry:s*Math.PI/2,name:`Fachada lateral — piso ${floor+1}`,cols:Math.round(depth/1.4),frame:metal,glass,sill:false,light:0x4a3a20}));
    }
    const topY = floors*floorHeight + .3;
    addEnvironmentBox(group,shell,"Laje de cobertura","Cobertura",width+.3,.3,depth+.3,0,topY+.15,0,metal);

    // Pilares de canto e brises verticais em toda a altura da fachada principal
    const fullH = topY + .3;
    [-1,1].forEach(sx=>[-1,1].forEach(sz=>addEnvironmentBox(group,shell,"Pilar de canto","Estrutura",.45,fullH,.45,sx*(width/2),fullH/2,sz*(depth/2),light)));
    for(let x=-width/2+1.4; x<width/2-.6; x+=1.4) if(Math.abs(x)>2)
        addEnvironmentBox(group,equipment,"Brise vertical","Fachada",.08,fullH-.3,.4,x,(fullH-.3)/2+.3,front-.32,light);

    // Platibanda com linha iluminada na cor da marca
    [[width+.5,front-.05],[width+.5,-front+.05]].forEach(([w,z])=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",w,1.0,.2,0,topY+.8,z,0x3a414b));
    [-1,1].forEach(s=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",.2,1.0,depth+.5,s*(width/2+.15),topY+.8,0,0x3a414b));
    addEnvironmentBox(group,equipment,"Coroamento iluminado","Iluminação",width+.55,.08,.08,0,topY+1.33,front-.17,accent,{emissive:accent,emissiveIntensity:1.8});

    // Entrada principal envidraçada e recepção
    aqWindow(group,equipment,0,1.75,front-.45,3.0,2.7,{ry:Math.PI,name:"Entrada principal",cols:2,frame:0x20262d,sill:false,light:0x6a4a20});
    addEnvironmentBox(group,equipment,"Portaria / recepção","Recepção",5.4,2.1,2.2,0,1.35,depth/2-1.8,0x242d3b);

    // Prumada central com ramais até as copas e banheiros de cada andar
    const z = 1.8;
    const pts = [];
    for(let f=0; f<floors; f++) pts.push([0, f*floorHeight+.75, z]);
    pts.push([0, topY+.5, z]);
    for(let i=0;i<pts.length-1;i++) createSystemPipe(group,systems,[pts[i],pts[i+1]]);
    for(let f=0; f<floors; f++){
        const py=f*floorHeight+.75;
        [-4.5,4.5].forEach(x=>createSystemPipe(group,systems,[[0,py,z],[x,py,z],[x,py,-3.2]]));
    }

    // Cobertura técnica: reservatório com sensor AquaFlow, casa de máquinas e condensadoras
    const tank=aqWaterTank(group,equipment,0,topY+.3,1.8,1.45,1.6,"Reservatório superior");
    tank.userData.aquaComponent={name:"Reservatório superior",type:"Reservatório",details:"Reservatório de abastecimento do edifício, com sensor de nível AquaFlow."};
    aqTexture(addEnvironmentBox(group,shell,"Casa de máquinas","Estrutura",3.0,2.2,2.6,4.4,topY+1.4,1.6,0x4a515b),"concrete",2);
    [-4.6,-2.6].forEach(x=>{
        addEnvironmentBox(group,equipment,"Condensadora","Climatização",1.6,1.0,1.2,x,topY+.8,-2.4,0x8e969e);
        const fan=new THREE.Mesh(new THREE.CylinderGeometry(.48,.48,.06,20),makeMaterial(0x1f2428));
        fan.position.set(x,topY+1.33,-2.4); group.add(fan); equipment.push(fan);
    });
}

function createCompanyEnvironment() {
    const group=new THREE.Group();
    const shell=[],equipment=[],systems=[];
    aqTexture(addEnvironmentBox(group,shell,"Pátio do escritório","Área externa",26,.1,20,0,.02,0,0x4a4f55),"pavers",1.6);
    aqTexture(addEnvironmentBox(group,shell,"Gramado","Paisagismo",6,.06,18,-10.5,.09,0,0x2f5a35),"grass",1.5);
    createSimpleBuilding(group,shell,14,9,4.2,0x4d5665);
    // Interior (visível no Raio-X): banheiro, copa, recepção, estações de trabalho, sala de reunião e DML
    const F0=.35, white=0xe8e6e0, wood=0x8a6a48;
    const part=(n,w,h,d,x,z,c=0xcfd2d4)=>addEnvironmentBox(group,equipment,n,"Divisória",w,h,d,x,F0+h/2,z,c);
    part("Parede do banheiro",5.5,2.8,.1,-4.25,-1.3); part("Parede do banheiro",.1,2.8,1.6,-1.5,-3.7);
    const glassP=aqMarkTransparent(new THREE.MeshStandardMaterial({color:0x9fd2e6,transparent:true,opacity:.25,roughness:.05,depthWrite:false}));
    [[5.5,.06,4.25,-1.3],[.06,3.2,1.5,-2.9]].forEach(([w,d,x,z])=>aqMesh(group,equipment,new THREE.BoxGeometry(w,2.8,d),glassP,x,F0+1.4,z,"Divisória de vidro da copa","Divisória",false));
    // Banheiro: bancada com cubas, cabines e vasos
    addEnvironmentBox(group,equipment,"Bancada do banheiro","Banheiro",2.4,.12,.55,-4.2,F0+.85,-2.9,0x3a3d3f);
    [-4.8,-3.6].forEach(x=>addEnvironmentBox(group,equipment,"Cuba","Ponto de consumo",.45,.06,.35,x,F0+.93,-2.9,white));
    addEnvironmentBox(group,equipment,"Espelho","Banheiro",2.2,.8,.03,-4.2,F0+1.6,-1.37,0xb9c6cc);
    [-6.4,-5.3].forEach(x=>{
        addEnvironmentBox(group,equipment,"Vaso sanitário","Banheiro",.4,.4,.55,x,F0+.2,-4.1,white);
        addEnvironmentBox(group,equipment,"Caixa acoplada","Banheiro",.4,.35,.15,x,F0+.55,-4.38,white);
        addEnvironmentBox(group,equipment,"Divisória da cabine","Banheiro",.04,1.9,1.1,x+.55,F0+1.0,-3.9,0x7a8288);
    });
    // Copa: bancada com pia, geladeira, mesa e cadeiras
    addEnvironmentBox(group,equipment,"Bancada da copa","Copa",3.0,.9,.6,4.2,F0+.45,-3.0,0x3a3d3f);
    addEnvironmentBox(group,equipment,"Cuba da copa","Ponto de consumo",.6,.06,.4,4.2,F0+.93,-3.0,0xc7ccd0);
    addEnvironmentBox(group,equipment,"Geladeira","Copa",.75,1.8,.7,6.4,F0+.9,-3.9,0xd8dadc);
    addEnvironmentBox(group,equipment,"Mesa da copa","Copa",1.2,.06,.8,2.6,F0+.75,-2.1,wood);
    [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Cadeira da copa","Copa",.4,.45,.4,2.6+s*.8,F0+.23,-2.1,0x2f3438));
    // Recepção e estações de trabalho com monitores
    addEnvironmentBox(group,equipment,"Balcão da recepção","Administrativo",2.4,1.05,.6,0,F0+.52,-.4,0x2a3038);
    addEnvironmentBox(group,equipment,"Tampo da recepção","Administrativo",2.5,.06,.7,0,F0+1.08,-.4,wood);
    [-1,1].forEach(s=>[.5,2.0].forEach(z=>[1.9,3.4,4.9].forEach(dx=>{
        const x=s*dx;
        addEnvironmentBox(group,equipment,"Estação de trabalho","Mobiliário",1.4,.05,.7,x,F0+.74,z,0xd8d4cc);
        addEnvironmentBox(group,equipment,"Monitor","Mobiliário",.6,.36,.04,x,F0+1.05,z+.22,0x14191f,{emissive:0x1d4a6a,emissiveIntensity:.6});
        addEnvironmentBox(group,equipment,"Cadeira","Mobiliário",.45,.5,.45,x,F0+.3,z-.55,0x2f3438);
    })));
    // Sala de reunião envidraçada e depósito de limpeza (DML)
    [[3.5,.06,5.25,2.6],[.06,1.9,3.5,3.55]].forEach(([w,d,x,z])=>aqMesh(group,equipment,new THREE.BoxGeometry(w,2.8,d),glassP,x,F0+1.4,z,"Divisória da sala de reunião","Divisória",false));
    addEnvironmentBox(group,equipment,"Mesa de reunião","Administrativo",2.4,.06,1.0,5.25,F0+.75,3.55,wood);
    [-.8,0,.8].forEach(dx=>[-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Cadeira de reunião","Administrativo",.4,.45,.4,5.25+dx,F0+.23,3.55+s*.75,0x2f3438)));
    part("Parede do DML",2.8,2.8,.1,-4.0,2.5); part("Parede do DML",.1,2.8,1.9,-2.6,3.5);
    addEnvironmentBox(group,equipment,"Tanque de limpeza","Ponto de consumo",.7,.8,.55,-4.0,F0+.4,3.3,0xc7ccd0);
    const tank=aqWaterTank(group,equipment,0,4.87,3.0,.8,1.2,"Reservatório");
    tank.userData.aquaComponent={name:"Reservatório",type:"Reservatório",details:"Caixa d'água do escritório com sensor de nível AquaFlow."};
    [-4.2,4.2].forEach(x=>{
        addEnvironmentBox(group,equipment,"Condensadora","Climatização",1.4,.9,1.0,x,5.35,2.6,0x8e969e);
        const fan=new THREE.Mesh(new THREE.CylinderGeometry(.4,.4,.06,20),makeMaterial(0x1f2428)); fan.position.set(x,5.83,2.6); group.add(fan); equipment.push(fan);
    });
    createSystemPipe(group,systems,[[0,5.0,3.0],[0,3.5,3.0],[0,3.5,3.5]]);
    createSystemPipe(group,systems,[[0,3.5,3.5],[0,1.0,3.5],[-4.2,1.0,3.5]]);
    createSystemPipe(group,systems,[[0,1.0,3.5],[4.2,1.0,3.5]]);

    // Cisterna de água de chuva com calha e condutor vindo da cobertura
    const cis=aqWaterTank(group,equipment,-8.7,.07,3.0,.95,1.7,"Cisterna de água de chuva");
    cis.userData.aquaComponent.details="Reaproveitamento de água da chuva para limpeza e jardim, monitorado pelo AquaFlow";
    addEnvironmentBox(group,shell,"Calha","Cobertura",.2,.15,9.6,-7.5,4.85,0,0x8a9096);
    const dp=new THREE.Mesh(new THREE.CylinderGeometry(.07,.07,3.5,10),makeMaterial(0x8a9096)); dp.position.set(-7.5,3.1,3.0); group.add(dp); shell.push(dp);
    addEnvironmentBox(group,shell,"Condutor até a cisterna","Cobertura",.9,.12,.12,-7.95,1.4,3.0,0x8a9096);

    // Carport com placas solares sobre as vagas
    [[8.7,-2.0],[12.5,-2.0],[8.7,5.2],[12.5,5.2]].forEach(([x,z])=>addEnvironmentBox(group,shell,"Pilar do carport","Estrutura",.18,2.9,.18,x,1.5,z,0x5a6168));
    [8.7,12.5].forEach(x=>addEnvironmentBox(group,shell,"Viga do carport","Estrutura",.2,.25,7.6,x,3.0,1.6,0x5a6168));
    aqSolarArray(group,equipment,10.6,2.6,1.6,3,3,.18);

    // Deck com pergolado e mesas na área de convivência dos fundos
    aqTexture(addEnvironmentBox(group,shell,"Deck de convivência","Lazer",8,.12,3.2,0,.12,6.9,0x6a4b33),"wood",1);
    [[-3.8,5.5],[3.8,5.5],[-3.8,8.3],[3.8,8.3]].forEach(([x,z])=>addEnvironmentBox(group,shell,"Pilar do pergolado","Estrutura",.14,2.7,.14,x,1.45,z,0x6a4b33));
    [5.5,8.3].forEach(z=>addEnvironmentBox(group,shell,"Viga do pergolado","Estrutura",7.8,.14,.14,0,2.8,z,0x6a4b33));
    for(let x=-3.6;x<=3.6;x+=.6) addEnvironmentBox(group,shell,"Ripa do pergolado","Estrutura",.08,.12,3.0,x,2.92,6.9,0x7a5236);
    [-2,2].forEach(x=>{
        const t=new THREE.Mesh(new THREE.CylinderGeometry(.5,.5,.06,20),makeMaterial(0xd8d4cc)); t.position.set(x,.95,6.9); group.add(t); equipment.push(t);
        const leg=new THREE.Mesh(new THREE.CylinderGeometry(.05,.05,.75,8),makeMaterial(0x2f3438)); leg.position.set(x,.55,6.9); group.add(leg); equipment.push(leg);
        [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Cadeira externa","Lazer",.4,.45,.4,x+s*.75,.4,6.9,0x2f3438));
    });
    [-5.5,5.5].forEach(x=>aqShrub(group,shell,x,.1,6.9,.9));

    // Estacionamento, paisagismo e iluminação
    [-1.5,1.5,4.5].forEach(z=>addEnvironmentBox(group,shell,"Faixa de vaga","Estacionamento",4.2,.02,.08,10.6,.08,z,0xe8e6e0));
    aqCar(group,10.6,0,Math.PI/2,0x3c4248);
    aqCar(group,10.6,3.0,Math.PI/2,0x7a2e2e);
    [[-10.5,-6],[-10.5,0],[-10.5,6]].forEach(([x,z])=>aqTree(group,shell,x,z,.85,.1));
    [-8.6,-7.4].forEach(x=>aqShrub(group,shell,x,.1,-5.6,.8));
    addEnvironmentBox(group,equipment,"Totem da empresa","Identificação",.5,1.8,.3,4.2,1.0,-7.6,0x14191f);
    addEnvironmentBox(group,equipment,"Logo AquaFlow","Identificação",.32,.32,.03,4.2,1.5,-7.77,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.4});
    [[-3.2,-8.2],[3.2,-8.2]].forEach(([x,z])=>aqBollard(group,x,z,.07));
    [-6,6].forEach(x=>addEnvironmentLightPole(group,x,-9.0,4.6,"Iluminação do escritório"));

    scene.add(group);
    environmentGroups.empresa=group;
    environmentSystems.empresa={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("empresa",group,environmentPoints.empresa);
}

function createCommercialBuildingEnvironment() {
    const group=new THREE.Group();
    const shell=[],equipment=[],systems=[];
    createMultiFloorBuilding(group,shell,equipment,{floors:3,width:14,depth:9.5,floorHeight:3.4,facade:0x465267,accent:0x7654ff,title:"Prédio comercial",systems});
    aqTexture(addEnvironmentBox(group,equipment,"Praça de acesso","Área externa",22,.12,5,0,.06,-7.6,0x5a5f66),"pavers",1.6);
    aqTexture(addEnvironmentBox(group,equipment,"Calçada lateral","Área externa",2.2,.1,12,8.4,.05,-.5,0x5a5f66),"pavers",1.6);
    // Marquise de vidro com borda iluminada
    const canopy=addEnvironmentBox(group,equipment,"Marquise de entrada","Cobertura",6.4,.16,2.6,0,3.35,-6.3,0x9fd2e6);
    canopy.material=aqMarkTransparent(new THREE.MeshStandardMaterial({color:0x9fd2e6,transparent:true,opacity:.45,roughness:.05,metalness:.3}));
    addEnvironmentBox(group,equipment,"Borda da marquise","Iluminação",6.5,.08,.08,0,3.35,-7.62,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.6});
    [-2.9,2.9].forEach(x=>addEnvironmentBox(group,equipment,"Tirante da marquise","Estrutura",.06,.06,2.7,x,3.48,-6.3,0x9aa3ab));
    // Floreiras com árvores, bicicletário e totem
    [-7.5,7.5].forEach(x=>{
        aqTexture(addEnvironmentBox(group,equipment,"Floreira","Paisagismo",2.2,.6,2.2,x,.42,-7.4,0x3a3f45),"concrete",1);
        aqTree(group,equipment,x,-7.4,.75,.7);
    });
    for(let i=0;i<5;i++){
        const ring=new THREE.Mesh(new THREE.TorusGeometry(.35,.03,6,16,Math.PI),makeMaterial(0x9aa3ab));
        ring.position.set(-4.6+i*.55,.12,-9.4); ring.rotation.y=Math.PI/2; group.add(ring); equipment.push(ring);
    }
    addEnvironmentBox(group,equipment,"Totem do edifício","Identificação",.6,2.4,.35,4.4,1.3,-9.3,0x14191f);
    addEnvironmentBox(group,equipment,"Logo AquaFlow","Identificação",.36,.36,.03,4.4,2.0,-9.49,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.4});
    [-6,0,6].forEach(x=>addEnvironmentLightPole(group,x,-10.6,5.0,"Poste do prédio comercial"));

    // Interiores por andar (Raio-X): banheiro e copa nos pontos monitorados + estações de trabalho
    for(let f=0;f<3;f++){
        const y=f*3.4+.6;
        addEnvironmentBox(group,equipment,"Bancada do banheiro","Banheiro",2.2,.12,.5,-4.5,y+.85,-3.2,0x3a3d3f);
        [-5.0,-4.0].forEach(x=>addEnvironmentBox(group,equipment,"Cuba","Ponto de consumo",.42,.06,.32,x,y+.93,-3.2,0xe8e6e0));
        addEnvironmentBox(group,equipment,"Parede do banheiro","Divisória",3.4,2.6,.08,-4.5,y+1.3,-2.3,0xcfd2d4);
        addEnvironmentBox(group,equipment,"Bancada da copa","Copa",2.6,.9,.55,4.5,y+.45,-3.2,0x3a3d3f);
        addEnvironmentBox(group,equipment,"Geladeira","Copa",.7,1.7,.65,6.1,y+.85,-3.6,0xd8dadc);
        if(f>0) [-3.6,-1.2,1.2,3.6].forEach(x=>{
            addEnvironmentBox(group,equipment,"Estação de trabalho","Mobiliário",1.6,.05,.8,x,y+.74,.3,0xd8d4cc);
            addEnvironmentBox(group,equipment,"Monitor","Mobiliário",.6,.36,.04,x,y+1.05,.6,0x14191f,{emissive:0x1d4a6a,emissiveIntensity:.6});
        });
    }
    // Telhado verde sobre a cobertura
    const roofTop=3*3.4+.6;
    aqTexture(addEnvironmentBox(group,equipment,"Telhado verde","Paisagismo",4.0,.25,2.4,-4.2,roofTop+.12,2.6,0x2f5a35),"grass",1);
    [-5.5,-4.2,-2.9].forEach(x=>aqShrub(group,equipment,x,roofTop+.2,2.6,.6));
    // Cisterna de água de chuva na lateral esquerda
    const cis=aqWaterTank(group,equipment,-9.3,.05,1.5,1.1,2.0,"Cisterna de água de chuva");
    cis.userData.aquaComponent.details="Reaproveitamento de água da chuva para descargas e jardim";
    const dp=new THREE.Mesh(new THREE.CylinderGeometry(.08,.08,roofTop-1.0,10),makeMaterial(0x8a9096)); dp.position.set(-7.35,(roofTop-1.0)/2+1.0,1.5); group.add(dp); shell.push(dp);
    addEnvironmentBox(group,shell,"Condutor até a cisterna","Cobertura",.9,.12,.12,-7.8,1.6,1.5,0x8a9096);

    // Galpão anexo (logística) com portão, doca e caminhão — leitura comercial/industrial
    const gx=13, gz=4.5, GW=7, GD=7, GH=4.6;
    aqTexture(addEnvironmentBox(group,shell,"Pátio de carga","Logística",8,.06,9,gx,.03,gz-8,0x2b2f33),"concrete",3);
    aqTexture(addEnvironmentBox(group,shell,"Piso do galpão anexo","Logística",GW+.3,.25,GD+.3,gx,.12,gz,0x55524c),"concrete",2);
    [["Fachada do galpão",GW,.2,gx,gz-GD/2],["Fundo do galpão",GW,.2,gx,gz+GD/2],["Lateral do galpão",.2,GD,gx-GW/2,gz],["Lateral do galpão",.2,GD,gx+GW/2,gz]].forEach(([n,w,d,x,z])=>{
        aqTexture(addEnvironmentBox(group,shell,n,"Logística",w,GH,d,x,GH/2+.25,z,0x6f7a82),"corrugated",2.5);
    });
    const gr=addEnvironmentBox(group,shell,"Cobertura do galpão","Cobertura",GW+.6,.18,GD+.6,gx,GH+.35,gz,0x4a5055);
    aqTexture(gr,"corrugated",1.2);
    addEnvironmentBox(group,equipment,"Portão de enrolar","Logística",3.4,3.6,.06,gx,2.05,gz-GD/2-.12,0x9aa3ab);
    for(let k=1;k<12;k++) addEnvironmentBox(group,equipment,"Lâmina do portão","Logística",3.4,.04,.08,gx,.25+k*.3,gz-GD/2-.14,0x6a7177);
    addEnvironmentBox(group,equipment,"Caixa do portão","Logística",3.7,.4,.35,gx,4.05,gz-GD/2-.2,0x2a2f33);
    [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Proteção do portão","Segurança",.25,1.1,.25,gx+s*2.1,.8,gz-GD/2-.4,0xd9a92b));
    addEnvironmentBox(group,equipment,"Placa do galpão","Identificação",3.0,.5,.05,gx,4.4,gz-GD/2-.13,0x14191f);
    addEnvironmentBox(group,equipment,"Logo AquaFlow","Identificação",.36,.36,.03,gx-1.1,4.4,gz-GD/2-.17,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.4});
    aqTruck(group,equipment,gx,gz-GD/2-4.6,Math.PI,0x2c4a6e);
    [[gx+2.6,gz-GD/2-1.2],[gx+2.6,gz-GD/2-2.4]].forEach(([x,z])=>{
        addEnvironmentBox(group,equipment,"Palete","Logística",1.1,.15,1.1,x,.12,z,0x8a6a48);
        addEnvironmentBox(group,equipment,"Carga paletizada","Logística",1.0,.8,1.0,x,.6,z,0xb08a5a);
    });
    scene.add(group);
    environmentGroups.predio_comercial=group;
    environmentSystems.predio_comercial={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("predio_comercial",group,environmentPoints.predio_comercial);
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
    // ESCOLA — volume horizontal com duas alas e hall central,
    // faixa amarela, base vermelha, brises sobre as janelas,
    // jardim com mastros, quadra poliesportiva cercada,
    // placas solares e caixa d'água elevada com sensor AquaFlow.
    // =====================================================
    const wall = 0xc8b99c, wallDark = 0x6b5140, yellow = 0xe4b72d, red = 0xa92f2a, roof = 0x55504a;

    // Terreno, calçada, acesso e jardins
    aqTexture(addEnvironmentBox(group,shell,"Campus escolar","Terreno",42,.25,30,0,.05,0,0x567844),"grass",2);
    aqTexture(addEnvironmentBox(group,shell,"Calçada frontal","Acesso",42,.10,1.8,0,.22,14.1,0x9a9080),"pavers",1.4);
    aqTexture(addEnvironmentBox(group,shell,"Caminho de entrada","Acesso",4.0,.10,8.4,0,.22,9.4,0xb0a58e),"pavers",1.2);
    aqTexture(addEnvironmentBox(group,shell,"Jardim frontal","Área verde",17,.08,7.6,-10.5,.2,9.4,0x638b4d),"grass",1.5);
    aqTexture(addEnvironmentBox(group,shell,"Passeio lateral","Acesso",42,.08,1.2,0,.2,-12.4,0x9a9080),"pavers",1.4);

    // Blocos principais
    [["Ala A — salas de aula",13.5,5.0,8.0,-9.2,2.65,1.2,wall],["Ala B — salas de aula",13.5,5.0,8.0,9.2,2.65,1.2,wall],
     ["Bloco central — hall",5.2,5.2,8.4,0,2.75,1.0,wallDark]].forEach(([n,w,h,d,x,y,z,c])=>aqTexture(addEnvironmentBox(group,shell,n,"Edificação",w,h,d,x,y,z,c),"concrete",3));

    // Faixa amarela (topo) e base vermelha da fachada
    addEnvironmentBox(group,equipment,"Faixa amarela da fachada","Identidade visual",26.8,.95,.22,0,4.5,5.30,yellow);
    [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Faixa inferior da fachada","Acabamento",10.9,.75,.24,s*8.05,.85,5.31,red));

    // Coberturas com platibanda, placas solares e claraboias
    addEnvironmentBox(group,shell,"Cobertura ala A","Cobertura",14.1,.32,8.5,-9.2,5.18,1.2,roof);
    addEnvironmentBox(group,shell,"Cobertura ala B","Cobertura",14.1,.32,8.5,9.2,5.18,1.2,roof);
    addEnvironmentBox(group,shell,"Cobertura bloco central","Cobertura",5.8,.32,8.9,0,5.38,1.0,roof);
    [-9.2,9.2].forEach(x=>{
        [[14.1,.5,.2,x,5.55,5.35],[14.1,.5,.2,x,5.55,-2.95],[.2,.5,8.5,x-6.95,5.55,1.2],[.2,.5,8.5,x+6.95,5.55,1.2]].forEach(([w,h,d,px,py,pz])=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",w,h,d,px,py,pz,wallDark));
        aqSolarArray(group,equipment,x-1.2,5.34,1.2,8,2,.3);
        addEnvironmentBox(group,equipment,"Claraboia do corredor","Cobertura",1.0,.25,6.0,x+5.4,5.46,1.2,0x7fc4dc,{emissive:0x1d4a5a,emissiveIntensity:.3});
    });

    // Janelas das salas com brises horizontais
    [-13.8,-10.65,-7.5,-4.35,6.3,8.95,11.6,14.25].forEach(x=>{
        aqWindow(group,equipment,x,2.75,5.21,2.35,1.85,{name:"Janela da sala",cols:2,frame:0x2a2d2b});
        addEnvironmentBox(group,equipment,"Brise horizontal","Fachada",2.5,.1,.75,x,3.85,5.6,wallDark);
    });
    [[-12.8,-2.81],[-9.4,-2.81],[9.4,-2.81],[12.8,-2.81]].forEach(([x,z])=>aqWindow(group,equipment,x,2.75,z,2.35,1.85,{ry:Math.PI,name:"Janela do corredor",cols:2,frame:0x2a2d2b}));
    [-1,1].forEach(s=>[-0.8,3.2].forEach(z=>aqWindow(group,equipment,s*15.96,2.75,z,2.2,1.7,{ry:s*Math.PI/2,name:"Janela lateral",cols:2,frame:0x2a2d2b})));
    [-15.7,-2.9,2.9,15.7].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da fachada","Estrutura",.28,4.9,.34,x,2.65,5.05,wallDark));

    // Hall: porta de vidro, painel superior, marquise com pilares e placa da escola
    aqWindow(group,equipment,0,1.85,5.21,3.0,2.7,{name:"Porta principal",cols:2,sill:false,frame:0x1f2220,light:0x6a4a20});
    aqWindow(group,equipment,0,3.65,5.21,4.0,.6,{name:"Visor do hall",cols:4,sill:false,frame:0x1f2220});
    addEnvironmentBox(group,equipment,"Marquise da entrada","Cobertura",7.0,.28,2.2,0,5.0,6.45,red);
    const led=addEnvironmentBox(group,equipment,"Fita de LED da marquise","Iluminação",6.4,.04,.06,0,4.85,7.5,0xd9c49a,{emissive:0xffb84a,emissiveIntensity:0});
    aquaGlowMaterials.push(led.material);
    [-3.2,3.2].forEach(x=>addEnvironmentBox(group,equipment,"Pilar da marquise","Estrutura",.22,4.7,.22,x,2.6,7.35,wallDark));
    addEnvironmentBox(group,equipment,"Placa vertical SESI","Identificação",1.0,5.0,.28,4.25,2.7,5.38,red,{details:"Escola SESI-SP Pedro Sukadolnik — CE 357"});
    addEnvironmentBox(group,equipment,"Letreiro da placa","Identificação",.6,3.2,.04,4.25,2.9,5.54,0xf2efe8,{emissive:0xffffff,emissiveIntensity:.15});

    // Pátio coberto central
    addEnvironmentBox(group,shell,"Pátio coberto central","Pátio",15.5,.20,7.0,0,.38,-5.4,0x776d5f);
    addEnvironmentBox(group,shell,"Cobertura do pátio","Cobertura",15.0,.30,6.5,0,5.05,-5.4,roof);
    [-6,-3,3,6].forEach(x=>addEnvironmentBox(group,shell,"Pilar do pátio","Estrutura",.28,4.7,.28,x,2.65,-5.4,wallDark));
    [-4.5,4.5].forEach(x=>{
        addEnvironmentBox(group,equipment,"Mesa do refeitório","Mobiliário",3.0,.1,1.0,x,1.25,-3.4,0x8a6a48);
        [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Banco do refeitório","Mobiliário",3.0,.08,.35,x,.8,-3.4+s*.8,0x6a4a34));
    });

    // Bloco de serviços ao fundo
    addEnvironmentBox(group,shell,"Cozinha escolar","Serviço",7.0,3.5,4.0,-9.0,2.0,-7.5,0x8b806d,{details:"Cozinha com pias e pontos de água"});
    addEnvironmentBox(group,shell,"Depósito e limpeza","Serviço",4.0,3.5,4.0,-2.5,2.0,-7.5,0x756d61);
    addEnvironmentBox(group,shell,"Administração","Administrativo",7.0,3.5,4.0,9.0,2.0,-7.5,0x887b69);
    [-9.0,9.0].forEach(x=>aqWindow(group,equipment,x,2.3,-9.52,2.4,1.2,{ry:Math.PI,name:"Janela de serviço",cols:2,frame:0x2a2d2b}));

    // Banheiros (interior, visíveis no Raio-X)
    const bathrooms = [[-13.0,-1.7],[-8.8,-1.7],[8.8,-1.7],[13.0,-1.7],[-13.0,2.5],[13.0,2.5],[-2.5,-7.5],[2.5,-7.5]];
    bathrooms.forEach((v,i)=>{
        addEnvironmentBox(group,equipment,"Banheiro "+(i+1),"Banheiros",3.0,2.35,2.5,v[0],1.38,v[1],0x70685d,{details:"Conjunto de vasos, lavatórios e pontos de água"});
        addEnvironmentBox(group,equipment,"Parede divisória sanitária","Banheiros",.12,2.0,2.2,v[0],1.35,v[1],0x514b44);
        addEnvironmentBox(group,equipment,"Lavatório","Ponto de consumo",.9,.55,.45,v[0],.95,v[1]+.75,0x9a9b93);
    });

    // Bebedouros nos corredores, no pátio e no hall
    [[-5.8,4.7],[5.8,4.7],[-5.8,-4.8],[5.8,-4.8],[0,3.9]].forEach((v,i)=>{
        addEnvironmentBox(group,equipment,"Bebedouro "+(i+1),"Bebedouro",1.0,1.25,.55,v[0],.92,v[1],0x8a9096,{details:"Ponto de água para alunos"});
        addEnvironmentBox(group,equipment,"Cuba do bebedouro","Bebedouro",.8,.06,.4,v[0],1.57,v[1],0xc7ccd0);
    });

    // Cozinha: bancadas e pias
    addEnvironmentBox(group,equipment,"Bancada da cozinha","Cozinha",5.2,.85,.85,-9.0,1.0,-5.9,0x5d554b);
    addEnvironmentBox(group,equipment,"Pia da cozinha","Ponto de consumo",1.3,.55,.65,-7.2,1.45,-5.9,0x9b9d95);
    addEnvironmentBox(group,equipment,"Lavagem de utensílios","Ponto de consumo",1.3,.55,.65,-10.8,1.45,-5.9,0x9b9d95);
    addEnvironmentBox(group,equipment,"Tanque de limpeza","Ponto de consumo",1.2,.55,.60,-2.5,1.45,-5.9,0x9b9d95,{details:"Ponto de água para limpeza e manutenção"});

    // Caixa d'água elevada com torre treliçada, escada e sensor AquaFlow
    aqTexture(addEnvironmentBox(group,shell,"Base técnica","Estrutura",6.0,.25,5.0,15.0,.18,-7.5,0x4e514b),"concrete",2);
    addEnvironmentBox(group,shell,"Plataforma da caixa d'água","Estrutura",4.2,.28,4.2,15.0,4.02,-7.5,0x454a45);
    const legs=[[13.55,-8.85],[16.45,-8.85],[13.55,-6.15],[16.45,-6.15]];
    legs.forEach(([x,z])=>addEnvironmentBox(group,shell,"Pé da torre do reservatório","Estrutura",.28,3.75,.28,x,2.0,z,0x555a54));
    [[15,-8.85,2.9,.08],[15,-6.15,2.9,.08]].forEach(([x,z,w,d])=>addEnvironmentBox(group,shell,"Travamento da torre","Estrutura",w,.12,d,x,2.1,z,0x555a54));
    [13.55,16.45].forEach(x=>addEnvironmentBox(group,shell,"Travamento da torre","Estrutura",.08,.12,2.7,x,2.1,-7.5,0x555a54));
    const tankMaterial = makeMaterial(0xb8beb8);
    const tank = new THREE.Mesh(new THREE.CylinderGeometry(1.72,1.82,2.65,32), tankMaterial);
    tank.position.set(15,5.48,-7.5); tank.castShadow=true; tank.receiveShadow=true;
    group.add(tank); equipment.push(tank);
    tank.userData.aquaComponent={name:"Reservatório escolar",type:"Reservatório",details:"Caixa d'água elevada com sensor de nível AquaFlow — 10.000 L"};
    const tankTop = new THREE.Mesh(new THREE.SphereGeometry(1.73,32,16,0,Math.PI*2,0,Math.PI/2), tankMaterial);
    tankTop.position.set(15,6.80,-7.5); tankTop.scale.y=.32; tankTop.castShadow=true; group.add(tankTop); equipment.push(tankTop);
    const tankBase = new THREE.Mesh(new THREE.CylinderGeometry(1.78,1.88,.18,32), makeMaterial(0x747b73));
    tankBase.position.set(15,4.15,-7.5); tankBase.castShadow=true; group.add(tankBase); shell.push(tankBase);
    aqMesh(group,equipment,new THREE.CylinderGeometry(1.79,1.79,.14,32,1,true),new THREE.MeshStandardMaterial({color:0x168cff,emissive:0x168cff,emissiveIntensity:1.4}),15,5.9,-7.5,"Sensor de nível AquaFlow","Sensor");
    [-1,1].forEach(s=>addEnvironmentBox(group,shell,"Escada da torre","Acesso",.05,4.0,.05,16.6+s*.22,2.15,-5.95,0x3a3d3a));
    for(let y=.6;y<4.1;y+=.35) addEnvironmentBox(group,shell,"Degrau","Acesso",.44,.03,.04,16.6,y,-5.95,0x3a3d3a);
    addEnvironmentBox(group,equipment,"Bomba de recalque","Bomba",2.2,1.35,1.8,11.8,.95,-7.5,0x30342f,{details:"Pressurização da rede hidráulica escolar"});

    // Quadra poliesportiva cercada (alambrado), com traves e linhas
    const cx=11.6, cz=9.6, CW=13, CD=5.6;
    aqTexture(addEnvironmentBox(group,shell,"Piso externo da quadra","Esporte",15,.08,7.2,cx,.2,cz,0x8a8a84),"concrete",2);
    addEnvironmentBox(group,shell,"Quadra poliesportiva","Esporte",CW,.06,CD,cx,.27,cz,0x2f6f8f);
    const line=(w,d,x,z)=>addEnvironmentBox(group,shell,"Linha da quadra","Esporte",w,.02,d,x,.31,z,0xf2efe8);
    line(CW,.08,cx,cz-CD/2); line(CW,.08,cx,cz+CD/2); line(.08,CD,cx-CW/2,cz); line(.08,CD,cx+CW/2,cz); line(.08,CD,cx,cz);
    const circle=new THREE.Mesh(new THREE.RingGeometry(1.0,1.08,40),makeMaterial(0xf2efe8)); circle.rotation.x=-Math.PI/2; circle.position.set(cx,.315,cz); group.add(circle); shell.push(circle);
    [-1,1].forEach(s=>{
        const ga=new THREE.Mesh(new THREE.RingGeometry(1.6,1.68,24,1,-Math.PI/2,Math.PI),makeMaterial(0xf2efe8));
        ga.rotation.x=-Math.PI/2; ga.rotation.z=s>0?Math.PI:0; ga.position.set(cx+s*CW/2,.315,cz); group.add(ga); shell.push(ga);
        const gx=cx+s*(CW/2+.05);
        [-1,1].forEach(t=>addEnvironmentBox(group,equipment,"Trave","Esporte",.1,1.8,.1,gx,1.2,cz+t*1.2,0xf2efe8));
        addEnvironmentBox(group,equipment,"Travessão","Esporte",.1,.1,2.5,gx,2.1,cz,0xf2efe8);
        const net=aqMesh(group,equipment,new THREE.BoxGeometry(.8,1.8,2.4),aqMarkTransparent(new THREE.MeshStandardMaterial({color:0xdddddd,transparent:true,opacity:.18,depthWrite:false})),gx+s*.4,1.2,cz,"Rede do gol","Esporte",false);
    });
    const fenceMat=aqMarkTransparent(new THREE.MeshStandardMaterial({color:0x8a9096,transparent:true,opacity:.22,roughness:.6,metalness:.5,depthWrite:false}));
    const fx0=cx-7.4, fx1=cx+7.4, fz0=cz-3.5, fz1=cz+3.5, FH=3.0;
    [[fx0,fz0,fx1,fz0],[fx0,fz1,fx1,fz1],[fx1,fz0,fx1,fz1],[fx0,fz0,fx0,cz-1],[fx0,cz+1,fx0,fz1]].forEach(([x1,z1,x2,z2])=>{
        const len=Math.hypot(x2-x1,z2-z1), horiz=Math.abs(z2-z1)<.01;
        const panel=aqMesh(group,shell,new THREE.BoxGeometry(horiz?len:.03,FH,horiz?.03:len),fenceMat,(x1+x2)/2,.25+FH/2,(z1+z2)/2,"Alambrado da quadra","Esporte",false);
        const n=Math.max(1,Math.round(len/2.5));
        for(let i=0;i<=n;i++) addEnvironmentBox(group,shell,"Poste do alambrado","Esporte",.08,FH+.1,.08,x1+(x2-x1)*i/n,.25+FH/2,z1+(z2-z1)*i/n,0x5a6168);
        addEnvironmentBox(group,shell,"Travessa do alambrado","Esporte",horiz?len:.06,.06,horiz?.06:len,(x1+x2)/2,.25+FH,(z1+z2)/2,0x5a6168);
    });

    // Jardim: árvores, arbustos, bancos, mastros com bandeiras e bicicletário
    [[-18,8],[-15,12.2],[-9.5,12.4],[-18.5,-11],[18.5,-11.5],[-19,1]].forEach(([x,z])=>aqTree(group,shell,x,z,.95,.1));
    [-13,-11.5,-7.5,-6].forEach((x,i)=>aqShrub(group,shell,x,.2,6.3,.8,i%2?0x3b7d45:0x2d6436));
    [-12,-7].forEach(x=>{
        aqTexture(addEnvironmentBox(group,equipment,"Banco do jardim","Mobiliário",2.0,.1,.5,x,.62,9.0,0x8a6a48),"wood",1);
        [-.8,.8].forEach(dx=>addEnvironmentBox(group,equipment,"Pé do banco","Mobiliário",.1,.45,.45,x+dx,.4,9.0,0x2a2d2f));
    });
    [[-3.4,0x2e8b3a],[-4.4,0xf2efe8],[-5.4,red]].forEach(([x,c])=>{
        addEnvironmentBox(group,equipment,"Mastro","Identificação",.08,6.2,.08,x,3.3,7.4,0xc0c4c8);
        addEnvironmentBox(group,equipment,"Bandeira","Identificação",.03,.6,.9,x,5.95,7.4+.45,c);
    });
    for(let i=0;i<5;i++){
        const ring=new THREE.Mesh(new THREE.TorusGeometry(.35,.03,6,16,Math.PI),makeMaterial(0x9aa3ab));
        ring.position.set(2.8,.25,10.5+i*.55); ring.rotation.y=Math.PI/2; group.add(ring); equipment.push(ring);
    }

    // =====================================================
    // REDE HIDRÁULICA DA ESCOLA (inalterada)
    // =====================================================
    const mainY=.92, ceilingY=4.65;
    createSystemPipe(group,systems,[[15,4.15,-7.5],[15,ceilingY,-7.5],[0,ceilingY,-7.5],[0,ceilingY,4.7],[0,mainY,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[0,mainY,-1.7]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[-8.8,mainY,-1.7],[-13,mainY,-1.7]]);
    createSystemPipe(group,systems,[[-13,mainY,-1.7],[-13,1.45,-0.95]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,1.45,-0.95]]);
    createSystemPipe(group,systems,[[-13,mainY,2.5],[-13,1.45,3.25]]);
    createSystemPipe(group,systems,[[-13,mainY,-1.7],[-13,mainY,2.5]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,mainY,2.5]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[8.8,mainY,-1.7],[13,mainY,-1.7]]);
    createSystemPipe(group,systems,[[8.8,mainY,-1.7],[8.8,1.45,-0.95]]);
    createSystemPipe(group,systems,[[13,mainY,-1.7],[13,1.45,-0.95]]);
    createSystemPipe(group,systems,[[13,mainY,2.5],[13,1.45,3.25]]);
    createSystemPipe(group,systems,[[8.8,mainY,-1.7],[8.8,mainY,2.5]]);
    createSystemPipe(group,systems,[[13,mainY,-1.7],[13,mainY,2.5]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[-5.8,mainY,4.7],[-5.8,1.55,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[5.8,mainY,4.7],[5.8,1.55,4.7]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[0,mainY,-4.8],[-5.8,mainY,-4.8],[-5.8,1.55,-4.8]]);
    createSystemPipe(group,systems,[[0,mainY,-1.7],[0,mainY,-4.8],[5.8,mainY,-4.8],[5.8,1.55,-4.8]]);
    createSystemPipe(group,systems,[[0,mainY,4.7],[0,mainY,3.9],[0,1.55,3.9]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-1.7],[-8.8,mainY,-5.9],[-7.2,mainY,-5.9],[-7.2,1.45,-5.9]]);
    createSystemPipe(group,systems,[[-8.8,mainY,-5.9],[-2.5,mainY,-5.9],[-2.5,1.45,-5.9]]);

    // POSTES DE ILUMINAÇÃO — calçada, quadra e fundos
    [-14,-6.5,-2.8].forEach(x=>addEnvironmentLightPole(group,x,13.3,4.8,"Poste do pátio escolar"));
    [[3.6,5.6],[19.6,13.2]].forEach(([x,z])=>addEnvironmentLightPole(group,x,z,6.0,"Refletor da quadra"));
    [-12.5,0,9].forEach(x=>addEnvironmentLightPole(group,x,-11.6,4.5,"Poste da área externa escolar"));

    scene.add(group);
    environmentGroups.escola=group;
    environmentSystems.escola={shell,equipment,systems,flow:[]};
    addEnvironmentMarkers("escola",group,environmentPoints.escola);
}

/* Caminhão baú com cabine avançada (frente voltada para +Z local). */
function aqTruck(group, list, x, z, ry, cabColor = 0x8f2924) {
    const t = new THREE.Group();
    const add = (geo, mat, px, py, pz, cast = true) => {
        const m = new THREE.Mesh(geo, mat); m.position.set(px, py, pz); m.castShadow = cast; m.receiveShadow = true;
        m.userData.aquaComponent = { name: "Caminhão de carga", type: "Veículo industrial", details: "Caminhão encostado na doca de carregamento" };
        t.add(m); if (list) list.push(m); return m;
    };
    const cab = makeMaterial(cabColor), dark = makeMaterial(0x1c1f21), metal = makeMaterial(0x8a9096), white = makeMaterial(0xd8d6cf);
    const glass = makeMaterial(0x24343b); glass.metalness = .5; glass.roughness = .1;
    add(new THREE.BoxGeometry(2.2, .35, 8.2), dark, 0, .8, 0);
    add(new THREE.BoxGeometry(2.45, 2.5, 2.2), cab, 0, 2.2, 2.95);
    add(new THREE.BoxGeometry(2.2, 1.0, .06), glass, 0, 2.75, 4.06, false);
    [-1.23, 1.23].forEach(sx => add(new THREE.BoxGeometry(.06, .9, 1.2), glass, sx, 2.75, 3.2, false));
    add(new THREE.BoxGeometry(1.6, .5, .08), dark, 0, 1.55, 4.07);
    add(new THREE.BoxGeometry(2.55, .3, .3), metal, 0, 1.0, 4.15);
    [-1.4, 1.4].forEach(sx => add(new THREE.BoxGeometry(.1, .45, .3), dark, sx, 2.7, 3.9));
    const box = add(new THREE.BoxGeometry(2.55, 3.0, 5.5), white, 0, 2.6, -1.1);
    aqTexture(box, "planks", 2.5);
    add(new THREE.BoxGeometry(2.58, .35, 5.52), makeMaterial(0x7654ff, 0x7654ff, .5), 0, 2.2, -1.1);
    add(new THREE.BoxGeometry(2.6, .12, 5.6), dark, 0, 4.15, -1.1);
    add(new THREE.BoxGeometry(2.5, .25, .2), makeMaterial(0xd9a92b), 0, .85, -3.95);
    const hl = new THREE.MeshStandardMaterial({ color: 0xffd27a, emissive: 0xffa83d, emissiveIntensity: 0 });
    aquaGlowMaterials.push(hl);
    [-.85, .85].forEach(sx => add(new THREE.BoxGeometry(.4, .22, .08), hl, sx, 1.45, 4.08, false));
    [[2.95, .55], [-2.0, .58], [-3.2, .58]].forEach(([wz, r]) => [-1.12, 1.12].forEach(sx => {
        const w = add(new THREE.CylinderGeometry(r, r, .42, 20), dark, sx, r, wz); w.rotation.z = Math.PI / 2;
        const h = add(new THREE.CylinderGeometry(r * .4, r * .4, .45, 14), metal, sx, r, wz); h.rotation.z = Math.PI / 2;
    }));
    t.position.set(x, 0, z); t.rotation.y = ry;
    group.add(t);
    return t;
}

function createIndustryEnvironment() {
    const group = new THREE.Group();
    const shell = [], equipment = [], systems = [];
    activeBuildGroup = group;
    activeBuildEquipment = equipment;

    // =====================================================
    // INDÚSTRIA — galpão com cobertura em shed, prédio administrativo,
    // área de utilidades (torre de resfriamento, tanques, chaminé),
    // lavagem de veículos, doca com caminhão e reservatório AquaFlow.
    // Frente voltada para +Z (câmera).
    // =====================================================
    const metalWall=0x6f7a82, concrete=0x77736b, dark=0x202522, steel=0x414a47, roofC=0x4a5055;
    const yellow=0xd9a92b, red=0x9f352b, purple=0x7654ff;

    // Terreno, pátio de manobras e calçada
    aqTexture(addEnvironmentBox(group,shell,"Terreno industrial","Terreno",42,.3,30,0,0,0,0x3d4440),"concrete",4);
    aqTexture(addEnvironmentBox(group,shell,"Pátio de manobras","Acesso",42,.06,9.4,0,.18,10.0,0x2b2f33),"concrete",3);
    for(let x=-8;x<20;x+=3) addEnvironmentBox(group,shell,"Faixa de rolamento","Sinalização",1.6,.02,.16,x,.22,11.6,0xe8e6e0);
    [-19.5,-16.6,-13.7,-10.8].forEach(x=>addEnvironmentBox(group,shell,"Faixa de vaga","Estacionamento",.12,.02,4.4,x,.22,8.9,0xe8e6e0));
    aqCar(group,-18.05,9.0,Math.PI,0x3c4248);
    aqCar(group,-15.15,9.0,Math.PI,0x8a8f96);
    aqCar(group,-12.25,9.0,Math.PI,0x2c4a6e);

    // -------------------------
    // GALPÃO PRINCIPAL — x -10..12, z -11..5
    // -------------------------
    const nx=1, nz=-3, NW=22, ND=16, NH=7.5, fz=nz+ND/2, bz=nz-ND/2, lx=nx-NW/2, rx=nx+NW/2;
    aqTexture(addEnvironmentBox(group,shell,"Fundação do galpão","Fundação",NW+.4,.3,ND+.4,nx,.15,nz,0x55524c),"concrete",3);
    addEnvironmentBox(group,shell,"Piso epóxi","Piso",NW-.4,.04,ND-.4,nx,.32,nz,0x4f5a55);
    const wallPart=(name,w,d,x,z)=>{
        aqTexture(addEnvironmentBox(group,shell,name+" — base de concreto","Edificação",w,1.2,d,x,.9,z,concrete),"concrete",2);
        const m=addEnvironmentBox(group,shell,name,"Edificação",w,6.0,d,x,4.5,z,metalWall);
        aqTexture(m,"corrugated",3);
    };
    wallPart("Fachada frontal do galpão",NW,.25,nx,fz);
    wallPart("Fachada traseira do galpão",NW,.25,nx,bz);
    wallPart("Lateral esquerda do galpão",.25,ND,lx,nz);
    wallPart("Lateral direita do galpão",.25,ND,rx,nz);
    // Pilares metálicos aparentes
    [-10,-4.5,1,6.5,12].forEach(x=>[fz,bz].forEach(z=>addEnvironmentBox(group,shell,"Pilar metálico","Estrutura",.4,7.4,.4,x,3.95,z+(z>nz?.12:-.12),steel)));
    [-11,-7,-3,1,5].forEach(z=>[lx,rx].forEach(x=>addEnvironmentBox(group,shell,"Pilar metálico","Estrutura",.4,7.4,.4,x+(x>nx?.12:-.12),3.95,z,steel)));
    // Janelas altas (lanternim lateral) entre os pilares
    [-7.25,-1.75,3.75,9.25].forEach(x=>{
        aqWindow(group,equipment,x,6.65,fz+.14,4.4,.9,{name:"Janela alta do galpão",cols:4,sill:false,frame:dark});
        aqWindow(group,equipment,x,6.65,bz-.14,4.4,.9,{ry:Math.PI,name:"Janela alta do galpão",cols:4,sill:false,frame:dark});
    });
    [-9,-5,-1,3].forEach(z=>[-1,1].forEach(s=>aqWindow(group,equipment,s<0?lx-.14:rx+.14,6.65,z,3.2,.9,{ry:s*Math.PI/2,name:"Janela alta do galpão",cols:3,sill:false,frame:dark})));

    // Cobertura em shed (dentes de serra) com vidros voltados para a frente
    addEnvironmentBox(group,shell,"Laje da cobertura","Cobertura",NW+.6,.25,ND+.6,nx,NH+.12,nz,roofC);
    const teeth=4, tDepth=ND/teeth, rise=1.6, ang=Math.atan2(rise,tDepth), slopeL=Math.hypot(tDepth,rise);
    const triShape=new THREE.Shape(); triShape.moveTo(0,0); triShape.lineTo(tDepth,0); triShape.lineTo(0,rise); triShape.closePath();
    const triGeo=new THREE.ExtrudeGeometry(triShape,{depth:.2,bevelEnabled:false});
    for(let k=0;k<teeth;k++){
        const z0=bz+k*tDepth, base=NH+.25;
        const sl=addEnvironmentBox(group,shell,"Telhado em shed","Cobertura",NW+.6,.16,slopeL+.1,nx,base+rise/2+.08*Math.cos(ang),z0+tDepth/2-.08*Math.sin(ang),roofC);
        sl.rotation.x=-ang;
        aqTexture(sl,"corrugated",1.2);
        aqWindow(group,shell,nx,base+rise/2,z0+tDepth-.06,NW-.6,rise-.2,{name:"Vidro do shed",cols:11,sill:false,frame:dark,light:0x4a4a3a});
        [lx-.3,rx+.1].forEach(x=>{const tri=aqMesh(group,shell,triGeo,makeMaterial(metalWall),x,base,z0+tDepth,"Oitão do shed","Cobertura"); tri.rotation.y=Math.PI/2;});
    }

    // Fachada: identificação, portões seccionados, doca e porta de funcionários
    addEnvironmentBox(group,equipment,"Painel de identificação","Identificação",4.6,.95,.12,-1.75,5.55,fz+.2,0x14191f);
    addEnvironmentBox(group,equipment,"Logo AquaFlow","Identificação",.55,.55,.04,-3.5,5.55,fz+.28,purple,{emissive:purple,emissiveIntensity:1.5});
    addEnvironmentBox(group,equipment,"Faixa iluminada","Identificação",2.8,.12,.04,-1.4,5.55,fz+.28,0xb46cff,{emissive:0xb46cff,emissiveIntensity:1.2});
    const sectional=(x,w,h,name)=>{
        addEnvironmentBox(group,equipment,name,"Logística",w,h,.08,x,.3+h/2,fz+.17,0x9aa3ab);
        for(let k=1;k<Math.round(h/.6);k++) addEnvironmentBox(group,equipment,"Gomo do portão","Logística",w,.05,.1,x,.3+k*.6,fz+.19,0x6a7177);
        addEnvironmentBox(group,equipment,"Batente do portão","Logística",w+.4,.25,.2,x,.3+h+.12,fz+.17,dark);
        [-1,1].forEach(s=>{
            addEnvironmentBox(group,equipment,"Proteção de pilar","Segurança",.3,1.2,.3,x+s*(w/2+.45),.9,fz+.6,yellow);
            addEnvironmentBox(group,equipment,"Faixa zebrada","Segurança",.32,.12,.32,x+s*(w/2+.45),1.1,fz+.6,dark);
        });
    };
    sectional(-7.25,4.0,4.2,"Portão de expedição");
    sectional(3.75,4.0,4.6,"Portão da doca");
    addEnvironmentBox(group,equipment,"Abrigo de doca","Logística",4.8,5.0,.5,3.75,2.8,fz+.4,0x18191a);
    addEnvironmentBox(group,equipment,"Cobertura da doca","Cobertura",5.2,.18,2.4,3.75,5.45,fz+1.3,roofC);
    [-2.0,2.0].forEach(dx=>addEnvironmentBox(group,equipment,"Batente de borracha","Logística",.4,.6,.3,3.75+dx,1.1,fz+.8,0x111111));
    addEnvironmentBox(group,equipment,"Porta de funcionários","Acesso",1.1,2.3,.08,10.5,1.45,fz+.17,red);
    addEnvironmentBox(group,equipment,"Marquise da porta","Cobertura",1.8,.12,1.0,10.5,2.85,fz+.6,roofC);
    aqTruck(group,equipment,3.75,fz+4.5,0);

    // -------------------------
    // INTERIOR (visível no Raio-X)
    // -------------------------
    [-1.5,2.6].forEach(z=>addEnvironmentBox(group,equipment,"Faixa de circulação","Sinalização",NW-1.2,.02,.14,nx,.35,z,yellow));
    function factoryMachine(x,z,w,d,h,bodyColor,name,accent){
        addEnvironmentBox(group,equipment,"Demarcação — "+name,"Sinalização",w+1.4,.02,d+1.4,x,.345,z,yellow);
        addEnvironmentBox(group,equipment,"Base — "+name,"Estrutura",w+.5,.25,d+.5,x,.47,z,dark);
        addEnvironmentBox(group,equipment,name,"Máquina industrial",w,h,d,x,.6+h/2,z,bodyColor,{details:"Equipamento de produção com consumo de água monitorado"});
        addEnvironmentBox(group,equipment,"Faixa — "+name,"Máquina",w+.02,.18,d+.02,x,.6+h*.72,z,accent);
        addEnvironmentBox(group,equipment,"Módulo superior — "+name,"Máquina",w*.55,.6,d*.5,x,.6+h+.3,z,0x3a403e);
        addEnvironmentBox(group,equipment,"Painel de controle — "+name,"Controle",.8,1.2,.35,x+w/2-.6,1.4,z+d/2+.25,0x2a2f33);
        addEnvironmentBox(group,equipment,"Tela — "+name,"Controle",.55,.4,.03,x+w/2-.6,1.7,z+d/2+.44,0x3fd08a,{emissive:0x22aa66,emissiveIntensity:1.1});
        const motor=new THREE.Mesh(new THREE.CylinderGeometry(.38,.38,.9,16),makeMaterial(0x2f5d8a)); motor.rotation.z=Math.PI/2;
        motor.position.set(x-w/2-.45,1.0,z); motor.castShadow=true; group.add(motor); equipment.push(motor);
    }
    factoryMachine(-7,-4,3.4,2.8,2.4,0x6e5542,"Lavadora industrial",red);
    factoryMachine(-2,-4,4.0,3.0,3.0,0x4d5551,"Centro de processamento",yellow);
    factoryMachine(3,-4,3.6,2.6,2.4,0x6b4c3c,"Linha de envase",red);
    factoryMachine(8,-4,3.0,2.6,3.4,0x4a514d,"Prensa hidráulica",yellow);

    // Esteira com caixas em movimento e estação de montagem com braço robótico
    addEnvironmentBox(group,equipment,"Esteira de produção","Máquina",10.5,.3,1.2,0,1.05,.5,dark,{details:"Linha transportadora da fábrica"});
    for(let x=-4.8;x<=4.8;x+=1.6) [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Pé da esteira","Estrutura",.12,.8,.12,x,.7,.5+s*.5,steel));
    for(let x=-5;x<=5;x+=.5){const r=new THREE.Mesh(new THREE.CylinderGeometry(.09,.09,1.15,10),makeMaterial(0x7f878c)); r.rotation.x=Math.PI/2; r.position.set(x,1.22,.5); group.add(r); equipment.push(r);}
    const boxes=[];
    for(let i=0;i<5;i++){const b=addEnvironmentBox(group,equipment,"Caixa em produção","Produto",.7,.5,.7,-5+i*2.1,1.55,.5,0xb08a5a); boxes.push(b);}
    animatedParts.push(d=>boxes.forEach(b=>{b.position.x+=d*.9; if(b.position.x>5) b.position.x-=10.5;}));
    addEnvironmentBox(group,equipment,"Estação de montagem","Máquina",2.6,.9,2.0,7.6,.8,.5,0x555b56);
    const armMat=makeMaterial(0xe0782b);
    const armBase=new THREE.Mesh(new THREE.CylinderGeometry(.35,.45,.5,16),armMat); armBase.position.set(7.6,1.5,.5); group.add(armBase); equipment.push(armBase);
    const arm=new THREE.Group(); arm.position.set(7.6,1.75,.5); group.add(arm);
    const l1=new THREE.Mesh(new THREE.BoxGeometry(.25,1.4,.25),armMat); l1.position.y=.7; l1.rotation.z=.4; arm.add(l1); equipment.push(l1);
    const l2=new THREE.Mesh(new THREE.BoxGeometry(1.2,.2,.2),armMat); l2.position.set(-.55,1.3,0); arm.add(l2); equipment.push(l2);
    animatedParts.push(d=>{arm.rotation.y+=d*.6;});

    // Ponte rolante e porta-paletes
    [-10.4,4.4].forEach(z=>addEnvironmentBox(group,equipment,"Trilho da ponte rolante","Estrutura",NW-.6,.3,.3,nx,6.4,z,steel));
    addEnvironmentBox(group,equipment,"Ponte rolante","Movimentação",.6,.5,15,2.0,6.1,-3,yellow);
    addEnvironmentBox(group,equipment,"Talha","Movimentação",.7,.6,.7,2.0,5.6,-3,dark);
    [-8,-5,-2].forEach(x=>{
        [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Montante do porta-palete","Armazenagem",.12,4.2,.12,x+s*1.4,2.4,-9.8,0xe0782b));
        [1.2,2.6,4.0].forEach(y=>{
            addEnvironmentBox(group,equipment,"Longarina","Armazenagem",2.9,.12,1.0,x,y,-9.8,0x2f5d8a);
            addEnvironmentBox(group,equipment,"Carga paletizada","Armazenagem",2.4,.7,.85,x,y+.42,-9.8,0xb08a5a);
        });
    });

    // -------------------------
    // PRÉDIO ADMINISTRATIVO — x -19.5..-11, z -2..5
    // -------------------------
    const ax=-15.25, az=1.5, AW=8.5, AD=7, AF=3.3;
    for(let f=0;f<2;f++){
        const y0=.3+f*AF;
        addEnvironmentBox(group,shell,"Laje administrativa","Estrutura",AW+.3,.3,AD+.3,ax,y0+.15,az,0xb7b9bb);
        aqTexture(addEnvironmentBox(group,shell,"Prédio administrativo","Administrativo",AW,AF-.3,AD,ax,y0+.3+(AF-.3)/2,az,0x3a414b),"concrete",3);
        aqWindow(group,equipment,ax+.4,y0+1.75,az+AD/2+.02,AW-1.6,2.1,{name:"Fachada de vidro administrativa",cols:5,sill:false,frame:dark,glass:0x355f86});
        aqWindow(group,equipment,ax-AW/2-.02,y0+1.75,az,AD-1.4,1.6,{ry:-Math.PI/2,name:"Janela administrativa",cols:4,frame:dark});
    }
    addEnvironmentBox(group,shell,"Laje de cobertura","Cobertura",AW+.3,.3,AD+.3,ax,.3+2*AF+.15,az,0xb7b9bb);
    [[AW+.3,az+AD/2+.1],[AW+.3,az-AD/2-.1]].forEach(([w,z])=>addEnvironmentBox(group,shell,"Platibanda","Cobertura",w,.7,.2,ax,.3+2*AF+.65,z,0x3a414b));
    addEnvironmentBox(group,equipment,"Linha de iluminação","Iluminação",AW+.3,.06,.06,ax,.3+2*AF+.95,az+AD/2+.22,purple,{emissive:purple,emissiveIntensity:1.6});
    addEnvironmentBox(group,equipment,"Marquise da recepção","Cobertura",3.6,.18,2.2,ax-2.6,3.2,az+AD/2+1.1,0xb7b9bb);
    [-4.2,-1.0].forEach(dx=>addEnvironmentBox(group,equipment,"Pilar da marquise","Estrutura",.1,2.9,.1,ax+dx,1.75,az+AD/2+2.05,0x9aa3ab));
    aqWindow(group,equipment,ax-2.6,1.6,az+AD/2+.06,1.8,2.3,{name:"Porta da recepção",cols:2,sill:false,frame:dark,light:0x6a4a20});
    [-1,1].forEach(s=>{
        addEnvironmentBox(group,equipment,"Condensadora","Climatização",1.3,.8,1.0,ax+s*2.2,.3+2*AF+.7,az-1.5,0x8e969e);
        const fan=new THREE.Mesh(new THREE.CylinderGeometry(.38,.38,.06,18),makeMaterial(0x1f2428)); fan.position.set(ax+s*2.2,.3+2*AF+1.13,az-1.5); group.add(fan); equipment.push(fan);
    });

    // -------------------------
    // LAVAGEM DE VEÍCULOS — x 14..20.5, z -1..5
    // -------------------------
    aqTexture(addEnvironmentBox(group,shell,"Plataforma de lavagem","Processo",6.5,.18,6.0,17.25,.24,2.0,0x8a8780),"concrete",2);
    addEnvironmentBox(group,equipment,"Grelha de drenagem","Processo",5.6,.03,.35,17.25,.35,2.0,0x2a2d2f);
    [[14.2,-.8],[20.3,-.8],[14.2,4.8],[20.3,4.8]].forEach(([x,z])=>addEnvironmentBox(group,shell,"Pilar da lavagem","Estrutura",.28,4.8,.28,x,2.6,z,steel));
    addEnvironmentBox(group,shell,"Cobertura da lavagem","Cobertura",6.9,.22,6.4,17.25,5.1,2.0,roofC);
    addEnvironmentBox(group,equipment,"Lavadora de alta pressão","Processo",.9,1.1,.7,20.0,.9,.2,0x2f6fb0,{details:"Ponto de lavagem com consumo de água monitorado"});
    const reel=new THREE.Mesh(new THREE.TorusGeometry(.35,.08,8,20),makeMaterial(0xd9a92b)); reel.position.set(20.25,1.9,2.0); reel.rotation.y=Math.PI/2; group.add(reel); equipment.push(reel);
    addEnvironmentBox(group,equipment,"Placa de reúso de água","Sinalização",.05,.6,.9,20.42,2.9,2.0,0x2a8aa0,{emissive:0x0b3f55,emissiveIntensity:.6});

    // -------------------------
    // UTILIDADES — torre de resfriamento, tanques, chaminé e rack de tubos
    // -------------------------
    aqTexture(addEnvironmentBox(group,shell,"Pátio de utilidades","Utilidades",8,.18,11.5,16.8,.24,-8.6,0x5a5852),"concrete",2);
    // Torre de resfriamento com venezianas e ventilador girando
    const tx=17.5, tz=-6.5;
    addEnvironmentBox(group,equipment,"Torre de resfriamento","Resfriamento",4.2,3.8,3.6,tx,2.25,tz,0x515955,{details:"Circuito de água de resfriamento"});
    for(let y=.8;y<3.6;y+=.35) [-1,1].forEach(s=>{
        addEnvironmentBox(group,equipment,"Veneziana da torre","Resfriamento",4.25,.08,.12,tx,y,tz+s*1.82,0x3a403e);
        addEnvironmentBox(group,equipment,"Veneziana da torre","Resfriamento",.12,.08,3.65,tx+s*2.12,y,tz,0x3a403e);
    });
    const stack=new THREE.Mesh(new THREE.CylinderGeometry(1.35,1.5,1.0,28,1,true),makeMaterial(0x6d7570)); stack.position.set(tx,4.65,tz); stack.material.side=THREE.DoubleSide; group.add(stack); equipment.push(stack);
    const fan=new THREE.Group(); fan.position.set(tx,4.5,tz); group.add(fan);
    for(let i=0;i<5;i++){const blade=new THREE.Mesh(new THREE.BoxGeometry(1.2,.04,.3),makeMaterial(0x23272a)); blade.position.x=.62; const h=new THREE.Group(); h.rotation.y=i*Math.PI*2/5; h.add(blade); fan.add(h); equipment.push(blade);}
    animatedParts.push(d=>{fan.rotation.y+=d*4;});
    // Tanques de processo verticais
    [[15.2,-12.3,1.35],[18.7,-12.3,1.35]].forEach(([x,z,r],i)=>{
        const tm=makeMaterial(0xaab2b8); tm.metalness=.6; tm.roughness=.3;
        const body=aqMesh(group,equipment,new THREE.CylinderGeometry(r,r,4.6,28),tm,x,2.6,z,"Tanque de processo 0"+(i+1),"Reservatório");
        aqMesh(group,equipment,new THREE.SphereGeometry(r,24,10,0,Math.PI*2,0,Math.PI/2),tm,x,4.9,z,"Tanque de processo","Reservatório").scale.y=.4;
        [1.3,2.6,3.9].forEach(y=>{const ring=aqMesh(group,equipment,new THREE.TorusGeometry(r+.01,.04,6,28),makeMaterial(0x5a6168),x,y,z,null); ring.rotation.x=Math.PI/2;});
        [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Escada do tanque","Acesso",.05,4.6,.05,x+s*.2,2.6,z+r+.1,0x3a403e));
        for(let y=.7;y<4.8;y+=.35) addEnvironmentBox(group,equipment,"Degrau","Acesso",.4,.03,.04,x,y,z+r+.1,0x3a403e);
        body.userData.aquaComponent.details="Tanque de água de processo";
    });
    // Rack de tubulações até o galpão (cores de processo)
    [13.2,16.9].forEach(x=>{
        [-1,1].forEach(s=>addEnvironmentBox(group,shell,"Coluna do rack de tubos","Estrutura",.2,4.2,.2,x,2.4,-9.6+s*.6,steel));
        addEnvironmentBox(group,shell,"Travessa do rack","Estrutura",.2,.15,1.4,x,4.4,-9.6,steel);
    });
    [[-.35,0x8b8f94],[0,0x3c7a4a],[.35,0x2f5d8a]].forEach(([dz,c])=>{
        const p=new THREE.Mesh(new THREE.CylinderGeometry(.12,.12,6.2,12),makeMaterial(c)); p.rotation.z=Math.PI/2; p.position.set(15.0,4.6,-9.6+dz); p.castShadow=true; group.add(p); equipment.push(p);
    });
    // Chaminé com faixas de sinalização aérea
    for(let k=0;k<6;k++){
        const seg=new THREE.Mesh(new THREE.CylinderGeometry(.55,.62,2.2,20),makeMaterial(k%2?0xe8e6e0:0xb33a2e));
        seg.position.set(20.2,1.1+k*2.2,-14.2); seg.castShadow=true; group.add(seg); equipment.push(seg);
        seg.userData.aquaComponent={name:"Chaminé industrial",type:"Utilidade",details:"Exaustão da planta"};
    }

    // -------------------------
    // RESERVATÓRIO AQUAFLOW + CASA DE BOMBAS (fundos, à esquerda)
    // -------------------------
    aqTexture(addEnvironmentBox(group,shell,"Base do reservatório","Estrutura",6,.3,6,-16,.3,-9,0x55524c),"concrete",2);
    const res=aqWaterTank(group,equipment,-16,.45,-9,2.3,4.0,"Reservatório industrial");
    res.userData.aquaComponent={name:"Reservatório industrial",type:"Reservatório",details:"Água para processo, com sensor de nível AquaFlow"};
    aqTexture(addEnvironmentBox(group,shell,"Casa de bombas","Utilidades",2.6,2.6,2.8,-11.7,1.6,-9,0x5b616a),"concrete",2);
    addEnvironmentBox(group,shell,"Laje da casa de bombas","Cobertura",2.9,.15,3.1,-11.7,2.98,-9,dark);
    addEnvironmentBox(group,equipment,"Bomba principal","Bomba",1.4,.9,.9,-11.0,.75,-7.2,0x2f6fb0,{details:"Bombeamento da rede de processo"});
    addEnvironmentBox(group,equipment,"Porta da casa de bombas","Acesso",.9,1.9,.06,-12.5,1.3,-7.58,0x2c3036);

    // Portaria com cancela e alambrado frontal
    aqTexture(addEnvironmentBox(group,shell,"Guarita","Acesso",2.6,2.6,2.6,17.5,1.5,13.0,0xb7b9bb),"concrete",1.5);
    addEnvironmentBox(group,shell,"Cobertura da guarita","Cobertura",3.4,.18,3.4,17.5,2.9,13.0,dark);
    aqWindow(group,equipment,17.5,1.8,14.32,1.8,1.0,{name:"Janela da guarita",cols:2,frame:dark});
    aqWindow(group,equipment,16.18,1.8,13.0,1.6,1.0,{ry:-Math.PI/2,name:"Janela da guarita",cols:2,frame:dark});
    addEnvironmentBox(group,equipment,"Cancela","Acesso",4.6,.12,.12,13.6,1.1,12.2,0xe8e6e0);
    for(let k=0;k<4;k++) addEnvironmentBox(group,equipment,"Faixa da cancela","Acesso",.5,.13,.13,11.9+k*1.15,1.1,12.2,0xb33a2e);
    const meshMat=aqMarkTransparent(new THREE.MeshStandardMaterial({color:0x8a9096,transparent:true,opacity:.25,roughness:.6,metalness:.5,depthWrite:false}));
    for(let x=-20.5;x<10.5;x+=2.5){
        addEnvironmentBox(group,shell,"Poste do alambrado","Fechamento",.08,2.2,.08,x,1.25,14.6,0x5a6168);
        const m=aqMesh(group,shell,new THREE.BoxGeometry(2.5,2.0,.03),meshMat,x+1.25,1.2,14.6,"Alambrado","Fechamento",false);
    }

    // -------------------------
    // REDE HIDRÁULICA — reservatório → bomba → barrilete do galpão → pontos
    // -------------------------
    const y=.92;
    createSystemPipe(group,systems,[[-16,y,-9],[-11,y,-9],[-11,y,-6.6],[12.5,y,-6.6]]);
    [-7,-2,3,8].forEach(x=>createSystemPipe(group,systems,[[x,y,-6.6],[x,y,-4]]));
    createSystemPipe(group,systems,[[6.5,y,-6.6],[6.5,y,.5]]);
    createSystemPipe(group,systems,[[12.5,y,-6.6],[17.5,y,-6.6],[17.5,y,-6.5]]);
    createSystemPipe(group,systems,[[12.5,y,-6.6],[14.5,y,-6.6],[14.5,y,2],[17.2,y,2]]);

    // POSTES DE ILUMINAÇÃO — pátio frontal e fundos
    [-14,-4,2].forEach(x=>addEnvironmentLightPole(group,x,13.2,5.0,"Poste do pátio industrial"));
    [-6,4].forEach(x=>addEnvironmentLightPole(group,x,-13.6,4.8,"Poste da área externa industrial"));

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
    // Segunda régua da cerca, lado do portão e gado no pasto
    addEnvironmentBox(group,shell,"Cerca frontal","Estrutura",12,.14,.14,-9,.38,3,0x4f3523);
    addEnvironmentBox(group,shell,"Cerca traseira","Estrutura",12,.14,.14,-9,.38,15,0x4f3523);
    addEnvironmentBox(group,shell,"Cerca lateral","Estrutura",.14,.14,12,-15,.38,9,0x4f3523);
    [5,13].forEach(zc=>[.38,.72].forEach(y=>addEnvironmentBox(group,shell,"Cerca do portão","Estrutura",.14,.14,4,-3,y,zc,0x4f3523)));
    [3,5,7,11,13,15].forEach(z=>{const p=new THREE.Mesh(new THREE.BoxGeometry(.18,1.15,.18),fenceMat); p.position.set(-3,.67,z); p.castShadow=true; group.add(p); shell.push(p);});
    [[-4.8,4.4,.4],[-6.4,6.4,2.4],[-4.6,13.1,Math.PI],[-12.2,13.6,-1.2]].forEach(([x,z,r])=>aqCow(group,x,z,r));

    // =====================================================
    // CASA-SEDE — casa de fazenda com paredes caiadas, telhado
    // cerâmico de duas águas, varanda com guarda-corpo e janelas
    // com venezianas (frente voltada para a câmera e fundos detalhados)
    // =====================================================
    {
        const sx=-9, sz=-5.2, sw=7.6, sd=5.4, wallTop=3.9, wallC=0xd8c8a8;
        aqTexture(addEnvironmentBox(group,shell,"Rodapé da sede","Estrutura",sw+.4,.5,sd+.4,sx,.35,sz,0x6b5a48),"stone",1);
        addEnvironmentBox(group,shell,"Casa sede","Edificação",sw,wallTop-.6,sd,sx,.6+(wallTop-.6)/2,sz,wallC);
        [-1,1].forEach(a=>[-1,1].forEach(b=>addEnvironmentBox(group,shell,"Cunhal","Estrutura",.22,wallTop-.6,.22,sx+a*sw/2,.6+(wallTop-.6)/2,sz+b*sd/2,0xf1e9da)));
        addEnvironmentBox(group,shell,"Cinta de madeira","Estrutura",sw+.1,.18,sd+.1,sx,wallTop-.09,sz,0x4a2c20);
        const tileMat=makeMaterial(0xa4532f); tileMat.roughness=.85;
        const tTex=aqCanvasTexture("tiles").clone(); tTex.needsUpdate=true; tTex.repeat.set(5,2.5); tileMat.map=tTex;
        aqGableRoof(group,shell,sx,wallTop,sz,sw,sd,1.7,.55,tileMat,wallC);
        // Chaminé de tijolos
        addEnvironmentBox(group,shell,"Chaminé da sede","Estrutura",.6,2.4,.6,-6.6,4.8,-6.2,0x8c4a32);
        addEnvironmentBox(group,shell,"Chapéu da chaminé","Estrutura",.8,.12,.8,-6.6,6.06,-6.2,0x3a2922);

        // Varanda frontal (lado +Z local) com deck, pilares, guarda-corpo e cobertura
        const pz=sz+sd/2;
        aqTexture(addEnvironmentBox(group,equipment,"Varanda da sede","Estrutura",sw+.4,.2,1.7,sx,.5,pz+.85,0x654127),"wood",1);
        [-3.6,-1.4,1.4,3.6].forEach(dx=>addEnvironmentBox(group,equipment,"Pilar da varanda","Estrutura",.18,2.55,.18,sx+dx,1.875,pz+1.55,0x4a2c20));
        [[-3.6,-1.4],[1.4,3.6]].forEach(([a,b])=>{
            addEnvironmentBox(group,equipment,"Guarda-corpo da varanda","Segurança",b-a,.08,.1,sx+(a+b)/2,1.45,pz+1.55,0x4a2c20);
            for(let x=a+.3;x<b-.1;x+=.3) addEnvironmentBox(group,equipment,"Balaústre","Segurança",.05,.8,.05,sx+x,1.0,pz+1.55,0x6a4630);
        });
        const pr=addEnvironmentBox(group,shell,"Cobertura da varanda","Cobertura",sw+.8,.12,1.95,sx,3.36,pz+.95,0xa4532f);
        pr.rotation.x=Math.atan2(.5,1.75);
        const prTex=aqCanvasTexture("tiles").clone(); prTex.needsUpdate=true; prTex.repeat.set(5,1.2); pr.material.map=prTex;
        addEnvironmentBox(group,equipment,"Porta da sede","Acesso",1.2,2.3,.1,sx,1.75,pz+.03,0x3b281e);
        addEnvironmentBox(group,equipment,"Batente da porta","Acesso",1.45,.14,.14,sx,2.95,pz+.04,0xf1e9da);
        [-1,1].forEach(s=>addEnvironmentBox(group,equipment,"Batente da porta","Acesso",.14,2.4,.14,sx+s*.68,1.8,pz+.04,0xf1e9da));
        [-2.4,2.4].forEach(dx=>aqWindow(group,equipment,sx+dx,2.3,pz+.02,1.25,1.3,{name:"Janela da sede",frame:0xf1e9da,shutters:0x2f5d50,cols:2,transom:true}));
        // Fundos (lado -Z local, voltado para a câmera após a rotação do cenário)
        [-2.6,0,2.6].forEach(dx=>aqWindow(group,equipment,sx+dx,2.3,sz-sd/2-.02,1.25,1.3,{ry:Math.PI,name:"Janela da sede",frame:0xf1e9da,shutters:0x2f5d50,cols:2,transom:true}));
        [-1,1].forEach(s=>aqWindow(group,equipment,sx+s*(sw/2+.02),2.3,sz,1.25,1.3,{ry:s*Math.PI/2,name:"Janela lateral da sede",frame:0xf1e9da,shutters:0x2f5d50,cols:2,transom:true}));
        // Jardim da sede
        [-3.2,3.2].forEach(dx=>aqShrub(group,shell,sx+dx,.12,pz+2.2,.7,0x3b7d45));
        aqShrub(group,shell,sx-3.2,.12,sz-sd/2-.7,.75);
        aqShrub(group,shell,sx+3.2,.12,sz-sd/2-.7,.75);
    }

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

    // Detalhes do celeiro: tábuas, telha metálica, porta do palheiro, cúpula e cata-vento
    body.material=bwall.clone();
    const plk=aqCanvasTexture("planks").clone(); plk.needsUpdate=true; plk.repeat.set(5,2); body.material.map=plk;
    const corr=aqCanvasTexture("corrugated").clone(); corr.needsUpdate=true; corr.center.set(.5,.5); corr.rotation=Math.PI/2; corr.repeat.set(4,1);
    broof.map=corr; broof.needsUpdate=true;
    addEnvironmentBox(barn,null,"Porta do palheiro","Acesso",1.2,1.0,.1,0,5.75,-3.68,0x6e1717);
    [[1.4,.1,0,6.3],[1.4,.1,0,5.2],[.1,1.1,-.65,5.75],[.1,1.1,.65,5.75]].forEach(([w,h,x,y])=>addEnvironmentBox(barn,null,"Moldura do palheiro","Estrutura",w,h,.14,x,y,-3.72,0xf0dfc0));
    [-.66,.66].forEach(r=>{const d=addEnvironmentBox(barn,null,"Diagonal do palheiro","Estrutura",1.45,.08,.14,0,5.75,-3.74,0xf0dfc0); d.rotation.z=r;});
    addEnvironmentBox(barn,null,"Viga da roldana","Estrutura",.2,.2,.9,0,6.55,-4.0,0x4a2b20);
    [-1,1].forEach(s=>{const t=addEnvironmentBox(barn,null,"Testeira do oitão","Estrutura",roofLength,.14,.08,s*2.38,5.92,-3.82,0xf0dfc0); t.rotation.z=-s*roofAngle;});
    addEnvironmentBox(barn,null,"Cúpula de ventilação","Estrutura",.95,.8,.95,0,7.6,0,0xc62828);
    [-1,1].forEach(s=>addEnvironmentBox(barn,null,"Veneziana da cúpula","Estrutura",.6,.45,.05,0,7.62,s*.49,0xf0dfc0));
    const cupRoof=new THREE.Mesh(new THREE.ConeGeometry(.8,.6,4),broof); cupRoof.rotation.y=Math.PI/4; cupRoof.position.set(0,8.3,0); cupRoof.castShadow=true; barn.add(cupRoof);
    addEnvironmentBox(barn,null,"Haste do cata-vento","Estrutura",.04,.8,.04,0,8.95,0,0x2a2a2a);
    addEnvironmentBox(barn,null,"Seta do cata-vento","Estrutura",.7,.05,.08,0,9.2,0,0x2a2a2a).rotation.y=.5;

    group.add(barn); equipment.push(barn);

    // =====================================================
    // SILOS E ÁREA DE ARMAZENAMENTO
    // =====================================================
    [15.0,18.0].forEach((x,i)=>{
        const silo=new THREE.Group();
        const metal=makeMaterial(0x7d8577); metal.metalness=.55; metal.roughness=.38;
        const sTex=aqCanvasTexture("corrugated").clone(); sTex.needsUpdate=true; sTex.repeat.set(6,1); metal.map=sTex;
        const dark=makeMaterial(0x3d4038);
        const sbase=new THREE.Mesh(new THREE.CylinderGeometry(1.5,1.55,.3,24),makeMaterial(0x5b554c)); sbase.position.y=.2; silo.add(sbase);
        const sb=new THREE.Mesh(new THREE.CylinderGeometry(1.3,1.3,4.1,28),metal); sb.position.y=2.4; silo.add(sb);
        [1.0,2.0,3.0,4.0].forEach(y=>{const r=new THREE.Mesh(new THREE.TorusGeometry(1.31,.035,6,28),dark); r.rotation.x=Math.PI/2; r.position.y=y+.35; silo.add(r);});
        const dome=new THREE.Mesh(new THREE.SphereGeometry(1.36,24,10,0,Math.PI*2,0,Math.PI/2),metal); dome.scale.y=.55; dome.position.y=4.45; silo.add(dome);
        const vent=new THREE.Mesh(new THREE.CylinderGeometry(.22,.22,.3,12),dark); vent.position.y=5.3; silo.add(vent);
        const cap=new THREE.Mesh(new THREE.ConeGeometry(.34,.25,12),dark); cap.position.y=5.55; silo.add(cap);
        // escada de acesso voltada para a frente da cena
        [-.22,.22].forEach(dx=>{const rail=new THREE.Mesh(new THREE.BoxGeometry(.05,4.3,.05),dark); rail.position.set(dx,2.55,-1.36); silo.add(rail);});
        for(let y=.7;y<4.5;y+=.32){const rung=new THREE.Mesh(new THREE.BoxGeometry(.44,.035,.04),dark); rung.position.set(0,y,-1.36); silo.add(rung);}
        silo.traverse(o=>{if(o.isMesh){o.castShadow=true;o.receiveShadow=true;}});
        silo.position.set(x,0,8.0); silo.userData.aquaComponent={name:"Silo "+(i+1),type:"Armazenamento",details:"Estrutura de armazenamento agrícola"};
        group.add(silo); equipment.push(silo);
    });

    // Fardos de feno redondos perto dos silos
    [[15.4,.55,4.4],[16.5,.55,4.4],[15.95,1.5,4.4],[13.9,.55,3.4]].forEach(([x,y,z])=>{
        const hay=new THREE.Mesh(new THREE.CylinderGeometry(.55,.55,.95,18),makeMaterial(0xc9a24f));
        hay.rotation.x=Math.PI/2; hay.position.set(x,y+.05,z); hay.castShadow=true; hay.receiveShadow=true;
        hay.userData.aquaComponent={name:"Fardo de feno",type:"Armazenamento",details:""};
        group.add(hay); shell.push(hay);
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
    // Pás montadas em um rotor que gira continuamente.
    const rotor=new THREE.Group(); rotor.position.set(0,7.55,1.18); windmill.add(rotor);
    const sail=makeMaterial(0xcdbb98);
    for(let i=0;i<4;i++){const blade=new THREE.Group(); const arm=new THREE.Mesh(new THREE.BoxGeometry(.18,3.0,.12),bladeMat); arm.position.y=1.45; arm.castShadow=true; blade.add(arm); const brace=new THREE.Mesh(new THREE.BoxGeometry(.55,.12,.12),bladeMat); brace.position.set(.18,2.5,0); blade.add(brace); const cloth=new THREE.Mesh(new THREE.BoxGeometry(.62,2.1,.04),sail); cloth.position.set(.38,1.75,.02); cloth.castShadow=true; blade.add(cloth); blade.rotation.z=i*Math.PI/2; rotor.add(blade);}
    animatedParts.push(d=>{ rotor.rotation.z+=d*1.1; });
    group.add(windmill); equipment.push(windmill);

    // Árvores distribuídas nas bordas
    [[-19,-11],[-19,16],[20,15],[20,-11],[-2,15]].forEach(([x,z])=>aqTree(group,shell,x,z,1.25,.05));

    // =====================================================
    // RESERVATÓRIO + BOMBA + IRRIGAÇÃO
    // =====================================================
    addEnvironmentBox(group,shell,"Base do reservatório","Estrutura",5.5,.3,5.5,-14,.5,8.0,0x334b32);
    addEnvironmentBox(group,equipment,"Reservatório rural","Reservatório",4.5,3.5,4.2,-14,2.35,8.0,0x5f7656,{details:"Armazena água para irrigação e uso da propriedade"});
    addEnvironmentBox(group,equipment,"Bomba de irrigação","Bomba",2.5,1.8,2.2,-9,1.05,8.0,0x2d3425,{details:"Bombeamento do sistema de irrigação"});
    addEnvironmentBox(group,equipment,"Bebedouro animal","Ponto de consumo",4,1.2,2.4,-8,.7,13.0,0x5b5141);
    addEnvironmentBox(group,equipment,"Água do bebedouro","Ponto de consumo",3.6,.04,2.0,-8,1.31,13.0,0x2a8aa0,{emissive:0x0b3f55,emissiveIntensity:.5});
    addEnvironmentBox(group,equipment,"Sensor AquaFlow do reservatório","Sensor",.5,.35,.08,-14,2.6,5.86,0x7654ff,{emissive:0x7654ff,emissiveIntensity:1.2});

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
                   env === "industria" ? [-11,.92,-9] :
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
        empresa:[0,2.5,0],
        predio_comercial:[0,5,0],
        escola:[0,3.2,0],
        industria:[0,3,0],
        agro:[0,2,0]
    };
    controls.target.set(...(targets[env] || [0,3,0]));

    if(env === "predio_residencial" || env === "predio_comercial") {
        camera.position.set(22,11,-26);
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
                aqRestoreMaterial(w);
            });
            houseShellParts.forEach(o=>{
                if(!o.material) return;
                aqRestoreMaterial(o);
            });
            roofParts.forEach(p=>p.visible=true);
            furniture.forEach(o=>{
                aqRestoreMaterial(o);
            });
            hideOriginalHouseSystems();
        } else {
            state.shell.forEach(o=>{
                if(!o.material) return;
                aqRestoreMaterial(o);
            });
            state.equipment.forEach(o=>{
                if(!o.material) return;
                aqRestoreMaterial(o);
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