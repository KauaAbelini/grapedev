
<?php

// Dados demonstrativos exclusivos do usuário ID 8.

function aquaflowDemoConsumos(): array
{
    $dados = [];

    for ($i = 29; $i >= 0; $i--) {
        $dados[] = [
            "id" => 900 + $i,
            "litros" => 428,
            "data_consumo" => date("Y-m-d H:i:s", strtotime("-{$i} days"))
        ];
    }

    return $dados;
}

function aquaflowDemoGastos(): array
{
    $dados = [];

    for ($i = 29; $i >= 0; $i--) {
        $dados[] = [
            "id" => 800 + $i,
            "valor" => 2.88,
            "data_gasto" => date("Y-m-d H:i:s", strtotime("-{$i} days"))
        ];
    }

    return $dados;
}

function aquaflowDemoDispositivos(): array
{
    return [
        [
            "id" => 901,
            "nome" => "AquaFlow Principal",
            "tipo" => "Sensor de fluxo inteligente",
            "status" => "online",
            "localizacao" => "Cozinha",
            "ultima_leitura" => date("Y-m-d H:i:s")
        ],
        [
            "id" => 902,
            "nome" => "AquaFlow Banheiro",
            "tipo" => "Sensor de fluxo inteligente",
            "status" => "online",
            "localizacao" => "Banheiro",
            "ultima_leitura" => date("Y-m-d H:i:s", strtotime("-5 minutes"))
        ],
        [
            "id" => 903,
            "nome" => "AquaFlow Jardim",
            "tipo" => "Sensor de fluxo inteligente",
            "status" => "online",
            "localizacao" => "Jardim",
            "ultima_leitura" => date("Y-m-d H:i:s", strtotime("-12 minutes"))
        ]
    ];
}

function aquaflowDemoAlertas(): array
{
    if (!isset($_SESSION["aquaflow_demo_alertas_resolvidos"])) {
        $_SESSION["aquaflow_demo_alertas_resolvidos"] = [];
    }

    $resolvidos = $_SESSION["aquaflow_demo_alertas_resolvidos"];

    $alertas = [
        [
            "id" => 9901,
            "titulo" => "Possível vazamento detectado",
            "descricao" => "Foi identificado um fluxo incomum de água na cozinha.",
            "tipo" => "danger",
            "resolvido" => 0,
            "data_alerta" => date("Y-m-d H:i:s", strtotime("-15 minutes"))
        ],
        [
            "id" => 9902,
            "titulo" => "Consumo acima do esperado",
            "descricao" => "O consumo registrado no banheiro está acima da média.",
            "tipo" => "warning",
            "resolvido" => 0,
            "data_alerta" => date("Y-m-d H:i:s", strtotime("-2 hours"))
        ],
        [
            "id" => 9903,
            "titulo" => "Verificação do dispositivo",
            "descricao" => "O sistema recomenda verificar as conexões do jardim.",
            "tipo" => "warning",
            "resolvido" => 0,
            "data_alerta" => date("Y-m-d H:i:s", strtotime("-1 day"))
        ],
        [
            "id" => 9904,
            "titulo" => "Leitura atualizada",
            "descricao" => "O dispositivo principal enviou uma nova leitura.",
            "tipo" => "success",
            "resolvido" => 1,
            "data_alerta" => date("Y-m-d H:i:s", strtotime("-2 days"))
        ]
    ];

    foreach ($alertas as &$alerta) {
        if (in_array($alerta["id"], $resolvidos)) {
            $alerta["resolvido"] = 1;
        }
    }

    unset($alerta);

    return $alertas;
}

function aquaflowDemoHistorico(): array
{
    return [
        [
            "id" => 9701,
            "tipo" => "Consumo",
            "descricao" => "Leitura de consumo registrada na cozinha.",
            "valor" => 428,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-10 minutes"))
        ],
        [
            "id" => 9702,
            "tipo" => "Consumo",
            "descricao" => "Leitura de consumo registrada no banheiro.",
            "valor" => 215,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-1 hour"))
        ],
        [
            "id" => 9703,
            "tipo" => "Alerta",
            "descricao" => "Possível vazamento detectado na cozinha.",
            "valor" => null,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-2 hours"))
        ],
        [
            "id" => 9704,
            "tipo" => "Dispositivo",
            "descricao" => "AquaFlow Principal conectado ao sistema.",
            "valor" => null,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-5 hours"))
        ],
        [
            "id" => 9705,
            "tipo" => "Consumo",
            "descricao" => "Consumo diário registrado.",
            "valor" => 428,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-1 day"))
        ],
        [
            "id" => 9706,
            "tipo" => "Alerta",
            "descricao" => "Verificação de consumo acima da média.",
            "valor" => null,
            "data_registro" => date("Y-m-d H:i:s", strtotime("-2 days"))
        ]
    ];
}
