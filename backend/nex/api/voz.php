<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";


/* =====================================================
   VERIFICAR CHAVE DA OPENAI
===================================================== */

if (
    !isset($OPENAI_API_KEY) ||
    empty(trim($OPENAI_API_KEY))
) {

    http_response_code(500);

    echo json_encode(
        [
            "error" =>
                "OPENAI_API_KEY não configurada no config.php."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   RECEBER DADOS DO JAVASCRIPT
===================================================== */

$corpo = file_get_contents(
    "php://input"
);


if (
    $corpo === false ||
    empty(trim($corpo))
) {

    http_response_code(400);

    echo json_encode(
        [
            "error" =>
                "Nenhum dado foi recebido."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   DECODIFICAR JSON
===================================================== */

$dados = json_decode(
    $corpo,
    true
);


if (
    !is_array($dados)
) {

    http_response_code(400);

    echo json_encode(
        [
            "error" =>
                "JSON inválido."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   PEGAR TEXTO
===================================================== */

$texto = trim(
    $dados["text"] ?? ""
);


if (
    $texto === ""
) {

    http_response_code(400);

    echo json_encode(
        [
            "error" =>
                "Texto vazio."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   LIMITE DO TEXTO
===================================================== */

if (
    mb_strlen($texto) > 4096
) {

    http_response_code(400);

    echo json_encode(
        [
            "error" =>
                "O texto para conversão em voz é muito grande."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   CONFIGURAÇÃO DA VOZ
===================================================== */

$payload = [

    "model" =>
        "gpt-4o-mini-tts",

    "voice" =>
        "nova",

    "input" =>
        $texto,

    "instructions" =>
        "Fale em português do Brasil. "
        . "Use uma voz feminina, suave, "
        . "natural e tecnológica. "
        . "Mantenha um tom amigável e confiante, "
        . "com velocidade moderada e boa clareza.",

    "response_format" =>
        "mp3"

];


/* =====================================================
   TRANSFORMAR PAYLOAD EM JSON
===================================================== */

$payloadJson = json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);


if (
    $payloadJson === false
) {

    http_response_code(500);

    echo json_encode(
        [
            "error" =>
                "Não foi possível preparar o áudio."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   CONECTAR COM A OPENAI
===================================================== */

$ch = curl_init(
    "https://api.openai.com/v1/audio/speech"
);


curl_setopt_array(
    $ch,
    [

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_POST =>
            true,

        CURLOPT_HTTPHEADER =>
            [

                "Content-Type: application/json",

                "Authorization: Bearer " .
                    $OPENAI_API_KEY

            ],

        CURLOPT_POSTFIELDS =>
            $payloadJson,

        CURLOPT_CONNECTTIMEOUT =>
            15,

        CURLOPT_TIMEOUT =>
            120

    ]
);


/* =====================================================
   EXECUTAR REQUISIÇÃO
===================================================== */

$audio = curl_exec(
    $ch
);


/* =====================================================
   VERIFICAR ERRO DO CURL
===================================================== */

if (
    $audio === false
) {

    $erro =
        curl_error($ch);

    curl_close($ch);

    http_response_code(500);

    echo json_encode(
        [
            "error" =>
                "Erro de conexão com a OpenAI.",

            "details" =>
                $erro
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   PEGAR STATUS HTTP
===================================================== */

$status = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);


curl_close($ch);


/* =====================================================
   VERIFICAR ERRO DA OPENAI
===================================================== */

if (
    $status < 200 ||
    $status >= 300
) {

    http_response_code($status);

    /*
     * A API normalmente retorna JSON
     * quando ocorre um erro.
     */

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo $audio;

    exit;
}


/* =====================================================
   VERIFICAR SE RECEBEU ÁUDIO
===================================================== */

if (
    empty($audio)
) {

    http_response_code(502);

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        [
            "error" =>
                "A OpenAI não retornou nenhum áudio."
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   ENVIAR ÁUDIO PARA O NAVEGADOR
===================================================== */

header(
    "Content-Type: audio/mpeg"
);

header(
    "Content-Disposition: inline; filename=nex.mp3"
);

header(
    "Content-Length: " .
    strlen($audio)
);

header(
    "Cache-Control: no-cache"
);


echo $audio;