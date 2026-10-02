<?php

header(
    "Content-Type: application/json; charset=UTF-8"
);

require_once "config.php";


/*
=========================================================
CONFIGURAÇÃO
=========================================================
*/

$modelo = defined("OPENAI_MODEL")
    ? OPENAI_MODEL
    : "gpt-5.6-luna";


/*
=========================================================
RESPOSTA JSON
=========================================================
*/

function responderJSON(
    $dados,
    $status = 200
) {

    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
=========================================================
RECEBER MENSAGEM
=========================================================
*/

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);


$mensagem =
    trim(
        $entrada["mensagem"] ?? ""
    );


if ($mensagem === "") {

    responderJSON([
        "sucesso" => false,
        "erro" => "Mensagem vazia."
    ], 400);
}


/*
=========================================================
MEMÓRIA
=========================================================
*/

$memorias = [];

try {

    $pdo = conectarBanco();

    $stmt = $pdo->query("
        SELECT chave, valor
        FROM nex_memoria
        ORDER BY id DESC
        LIMIT 30
    ");

    $memorias =
        $stmt->fetchAll();

} catch (Exception $e) {

    $memorias = [];
}


/*
=========================================================
CRIAR CONTEXTO DA MEMÓRIA
=========================================================
*/

$contextoMemoria = "";

foreach (
    $memorias as $memoria
) {

    $contextoMemoria .=
        $memoria["chave"] .
        ": " .
        $memoria["valor"] .
        "\n";
}


/*
=========================================================
PROMPT DO NEX
=========================================================
*/

$sistema = <<<PROMPT

Você é o Nex.

Você é o assistente virtual da GrapeDev.

Seu comportamento deve lembrar um assistente
futurista semelhante a um JARVIS.

Você deve ser:

- inteligente
- objetivo
- educado
- natural
- prestativo
- técnico quando necessário

Você conhece os projetos:

GrapeDev
AquaFlow
GrapeDev City

Você também pode planejar tarefas.

Quando o usuário pedir uma ação,
identifique se existe uma ferramenta apropriada.

Ferramentas disponíveis:

ABRIR_AQUAFLOW
ABRIR_CITY
ABRIR_DASHBOARD
ABRIR_URL

Se o usuário apenas estiver conversando,
responda normalmente.

Nunca invente que uma ação foi executada
se ela não foi realmente executada.

MEMÓRIA DO USUÁRIO:

$contextoMemoria

PROMPT;


/*
=========================================================
REQUISIÇÃO OPENAI
=========================================================
*/

$payload = [

    "model" => $modelo,

    "input" => [

        [
            "role" => "system",
            "content" => $sistema
        ],

        [
            "role" => "user",
            "content" => $mensagem
        ]

    ]

];


$ch = curl_init(
    "https://api.openai.com/v1/responses"
);


curl_setopt_array(
    $ch,
    [

        CURLOPT_POST => true,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            "Content-Type: application/json",

            "Authorization: Bearer " .
            OPENAI_API_KEY

        ],

        CURLOPT_POSTFIELDS =>
            json_encode($payload)

    ]
);


$resposta = curl_exec($ch);

$erroCurl =
    curl_error($ch);

$status =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


if ($erroCurl) {

    responderJSON([
        "sucesso" => false,
        "erro" => $erroCurl
    ], 500);
}


$json =
    json_decode(
        $resposta,
        true
    );


if (
    !isset(
        $json["output"]
    )
) {

    responderJSON([
        "sucesso" => false,
        "erro" => "Resposta inválida da IA.",
        "resposta_api" => $json
    ], 500);
}


/*
=========================================================
EXTRAIR TEXTO
=========================================================
*/

$texto = "";


foreach (
    $json["output"] as $item
) {

    if (
        isset(
            $item["content"]
        )
    ) {

        foreach (
            $item["content"] as $content
        ) {

            if (
                isset(
                    $content["text"]
                )
            ) {

                $texto .=
                    $content["text"];
            }
        }
    }
}


$texto =
    trim($texto);


/*
=========================================================
IDENTIFICAR AÇÃO
=========================================================
*/

$acao = null;


$textoBusca =
    mb_strtolower(
        $mensagem,
        "UTF-8"
    );


if (
    str_contains(
        $textoBusca,
        "abrir aquaflow"
    )
) {

    $acao =
        "abrir_aquaflow";

}

elseif (
    str_contains(
        $textoBusca,
        "abrir cidade"
    ) ||
    str_contains(
        $textoBusca,
        "abrir city"
    ) ||
    str_contains(
        $textoBusca,
        "abrir grapedev city"
    )
) {

    $acao =
        "abrir_city";

}

elseif (
    str_contains(
        $textoBusca,
        "abrir dashboard"
    )
) {

    $acao =
        "abrir_dashboard";
}


/*
=========================================================
RETORNAR
=========================================================
*/

responderJSON([

    "sucesso" => true,

    "resposta" =>
        $texto !== ""
            ? $texto
            : "Entendido.",

    "acao" =>
        $acao

]);