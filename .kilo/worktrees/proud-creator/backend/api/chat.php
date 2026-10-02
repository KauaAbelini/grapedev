<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";


/* =====================================================
   VERIFICAR SE A CHAVE EXISTE
===================================================== */

if (
    !isset($OPENAI_API_KEY) ||
    empty($OPENAI_API_KEY)
) {

    echo json_encode([
        "error" => "A chave da OpenAI não foi configurada."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   RECEBER DADOS DO JAVASCRIPT
===================================================== */

$dados = json_decode(
    file_get_contents("php://input"),
    true
);


$mensagem =
    $dados["message"] ?? "";


if (empty(trim($mensagem))) {

    echo json_encode([
        "error" => "Mensagem vazia."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   CONFIGURAÇÃO DA IA
===================================================== */

$payload = [

    "model" => "gpt-5.6-luna",

    "input" => [

        [
            "role" => "system",

            "content" =>
                "Você é o Nex, assistente virtual " .
                "oficial da GrapeDev. " .

                "Seu nome é Nex. " .

                "Você possui uma personalidade " .
                "tecnológica, amigável, educada " .
                "e futurista. " .

                "Responda sempre em português do Brasil. " .

                "Seja natural e relativamente breve, " .
                "porque suas respostas serão transformadas " .
                "em voz. " .

                "Você pode explicar a GrapeDev, " .
                "o AquaFlow, os projetos da equipe " .
                "e ajudar durante apresentações. " .

                "Não diga que você é o ChatGPT. " .
                "Você é o Nex."
        ],

        [
            "role" => "user",

            "content" => $mensagem
        ]

    ]

];


/* =====================================================
   CONECTAR COM A OPENAI
===================================================== */

$ch = curl_init(
    "https://api.openai.com/v1/responses"
);


curl_setopt_array(
    $ch,
    [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_HTTPHEADER => [

            "Content-Type: application/json",

            "Authorization: Bearer " .
            $OPENAI_API_KEY

        ],

        CURLOPT_POSTFIELDS =>
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            )

    ]
);


$resposta =
    curl_exec($ch);


if ($resposta === false) {

    echo json_encode([

        "error" =>
            "Erro de conexão: " .
            curl_error($ch)

    ], JSON_UNESCAPED_UNICODE);

    curl_close($ch);

    exit;
}

$status =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);



$json =
    json_decode(
        $resposta,
        true
    );

if (
    isset($json["error"])
) {

    echo json_encode([

        "error" =>
            $json["error"]["message"]
            ??
            "Erro desconhecido da OpenAI.",

        "status" =>
            $status

    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$respostaTexto = "";


if (
    isset($json["output"]) &&
    is_array($json["output"])
) {

    foreach (
        $json["output"]
        as $item
    ) {

        if (
            isset($item["content"]) &&
            is_array($item["content"])
        ) {

            foreach (
                $item["content"]
                as $content
            ) {

                if (
                    isset($content["text"])
                ) {

                    $respostaTexto .=
                        $content["text"];

                }

            }

        }

    }

}

$respostaTexto =
    trim($respostaTexto);


if (
    empty($respostaTexto)
) {

    echo json_encode([

        "error" =>
            "A IA não retornou texto.",

        "status" =>
            $status,

        "debug" =>
            $json

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   DEVOLVER PARA O JAVASCRIPT
===================================================== */

echo json_encode(

    [

        "response" =>
            $respostaTexto

    ],

    JSON_UNESCAPED_UNICODE

);