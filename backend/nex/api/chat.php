<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";

/* =====================================================
   CONFIGURAÇÕES
===================================================== */

$modelo = defined("OPENAI_MODEL")
    ? OPENAI_MODEL
    : "gpt-5.6-luna";

$endpoint = "https://api.openai.com/v1/responses";

/* =====================================================
   FUNÇÃO PARA RETORNAR JSON
===================================================== */

function responderJSON($dados, $status = 200)
{
    http_response_code($status);

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/* =====================================================
   VERIFICAR CHAVE
===================================================== */

if (
    !isset($OPENAI_API_KEY) ||
    empty(trim($OPENAI_API_KEY))
) {
    responderJSON(
        [
            "error" => "A chave da OpenAI não foi configurada."
        ],
        500
    );
}

/* =====================================================
   RECEBER JSON DO JAVASCRIPT
===================================================== */

$corpo = file_get_contents("php://input");

if (
    $corpo === false ||
    trim($corpo) === ""
) {
    responderJSON(
        [
            "error" => "Nenhum dado foi recebido."
        ],
        400
    );
}

/* =====================================================
   DECODIFICAR JSON
===================================================== */

$dados = json_decode($corpo, true);

if (
    !is_array($dados)
) {
    responderJSON(
        [
            "error" => "O JSON enviado pelo navegador é inválido."
        ],
        400
    );
}

/* =====================================================
   PEGAR MENSAGEM
===================================================== */

$mensagem = trim(
    $dados["message"] ?? ""
);

if (
    $mensagem === ""
) {
    responderJSON(
        [
            "error" => "Mensagem vazia."
        ],
        400
    );
}

/* =====================================================
   LIMITE DA MENSAGEM
===================================================== */

if (
    mb_strlen($mensagem) > 10000
) {
    responderJSON(
        [
            "error" => "A mensagem é muito grande."
        ],
        400
    );
}

/* =====================================================
   INSTRUÇÕES DO NEX
===================================================== */

$instrucoes = <<<'PROMPT'
Você é o Nex, o assistente virtual oficial da GrapeDev.

IDENTIDADE:
- Seu nome é Nex.
- Você é um assistente tecnológico, amigável,
  educado, inteligente e futurista.
- Você representa a GrapeDev.
- Nunca diga que você é o ChatGPT.
- Responda sempre em português do Brasil.

PERSONALIDADE:
- Fale de maneira natural.
- Seja simpático e confiante.
- Não seja excessivamente formal.
- Evite respostas desnecessariamente longas.
- Suas respostas também podem ser transformadas
  em voz, então prefira frases naturais e fáceis
  de ouvir.
- Não fique repetindo sua apresentação.

GRAPEDEV:
- Você pode explicar a GrapeDev e seus projetos
  quando possuir informações suficientes.
- Você pode explicar o AquaFlow, o Nex,
  a GrapeDev City e outros projetos fornecidos
  pelo sistema.
- Nunca invente informações específicas
  sobre a GrapeDev.
- Se não possuir uma informação interna,
  diga claramente que não possui essa informação.

WEB SEARCH:
- Você possui acesso à pesquisa na internet.
- Use a pesquisa quando a pergunta depender
  de informações atuais, recentes ou que
  possam ter mudado.
- Exemplos: notícias, preços, eventos,
  lançamentos, atualizações de tecnologia,
  versões de softwares, resultados esportivos,
  empresas, produtos, horários e acontecimentos atuais.
- Também pesquise quando o usuário pedir
  explicitamente para procurar algo na internet.
- Não invente resultados de pesquisa.
- Quando utilizar informações da internet,
  baseie a resposta nos resultados encontrados.
- Se as informações encontradas forem
  conflitantes ou insuficientes, deixe isso claro.

RESPOSTAS:
- Responda diretamente à pergunta.
- Seja claro e objetivo.
- Se o usuário pedir uma explicação,
  explique de forma simples e organizada.
- Se o usuário pedir ajuda técnica,
  forneça uma solução prática.
- Não mencione API, PHP, OpenAI ou código interno,
  a menos que o usuário pergunte sobre isso.

APRESENTAÇÕES:
- Você pode ajudar durante apresentações
  da GrapeDev.
- O Nex complementa os integrantes da equipe
  e não substitui os apresentadores.
- Ao explicar um projeto, organize a resposta
  de maneira fácil de apresentar oralmente.

IMPORTANTE:
- Nunca invente informações apenas para parecer confiante.
- Quando não souber algo, diga que não sabe
  ou pesquise quando a informação puder
  ser encontrada na internet.
- Diferencie informações atuais encontradas
  na internet de informações internas da GrapeDev.
PROMPT;

/* =====================================================
   MONTAR REQUISIÇÃO
===================================================== */

$payload = [
    "model" => $modelo,

    "instructions" => $instrucoes,

    "input" => $mensagem,

    "tools" => [
        [
            "type" => "web_search",
            "search_context_size" => "medium"
        ]
    ],

    "tool_choice" => "auto"
];

/* =====================================================
   CONVERTER PARA JSON
===================================================== */

$payloadJson = json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

if (
    $payloadJson === false
) {
    responderJSON(
        [
            "error" => "Não foi possível preparar a requisição."
        ],
        500
    );
}

/* =====================================================
   ENVIAR PARA OPENAI
===================================================== */

$ch = curl_init($endpoint);

curl_setopt_array(
    $ch,
    [
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $OPENAI_API_KEY
        ],

        CURLOPT_POSTFIELDS => $payloadJson,

        CURLOPT_CONNECTTIMEOUT => 15,

        CURLOPT_TIMEOUT => 120
    ]
);

$resposta = curl_exec($ch);

/* =====================================================
   ERRO DO CURL
===================================================== */

if (
    $resposta === false
) {
    $erroCurl = curl_error($ch);

    curl_close($ch);

    responderJSON(
        [
            "error" => "Erro de conexão com a OpenAI.",
            "details" => $erroCurl
        ],
        500
    );
}

/* =====================================================
   STATUS HTTP
===================================================== */

$status = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

/* =====================================================
   DECODIFICAR RESPOSTA
===================================================== */

$json = json_decode(
    $resposta,
    true
);

/* =====================================================
   RESPOSTA INVÁLIDA
===================================================== */

if (
    !is_array($json)
) {
    responderJSON(
        [
            "error" => "A OpenAI retornou uma resposta inválida.",
            "status" => $status,
            "raw" => $resposta
        ],
        502
    );
}

/* =====================================================
   ERRO DA OPENAI
===================================================== */

if (
    $status < 200 ||
    $status >= 300
) {
    $erro = $json["error"] ?? [];

    responderJSON(
        [
            "error" =>
                $erro["message"]
                ??
                "Erro desconhecido da OpenAI.",

            "code" =>
                $erro["code"]
                ??
                null,

            "type" =>
                $erro["type"]
                ??
                null,

            "status" =>
                $status
        ],
        $status
    );
}

/* =====================================================
   EXTRAIR RESPOSTA DO NEX
===================================================== */

$respostaTexto = "";

if (
    isset($json["output"]) &&
    is_array($json["output"])
) {

    foreach (
        $json["output"] as $item
    ) {

        if (
            !isset($item["type"]) ||
            $item["type"] !== "message"
        ) {
            continue;
        }

        if (
            !isset($item["content"]) ||
            !is_array($item["content"])
        ) {
            continue;
        }

        foreach (
            $item["content"] as $content
        ) {

            if (
                isset($content["type"]) &&
                $content["type"] === "output_text" &&
                isset($content["text"])
            ) {

                $respostaTexto .=
                    $content["text"];
            }
        }
    }
}

$respostaTexto = trim($respostaTexto);

/* =====================================================
   VERIFICAR SE RECEBEU TEXTO
===================================================== */

if (
    $respostaTexto === ""
) {
    responderJSON(
        [
            "error" => "A IA não retornou texto.",
            "status" => $status,
            "response_id" => $json["id"] ?? null
        ],
        502
    );
}

/* =====================================================
   PEGAR FONTES DO WEB SEARCH
===================================================== */

$fontes = [];

if (
    isset($json["output"]) &&
    is_array($json["output"])
) {

    foreach (
        $json["output"] as $item
    ) {

        if (
            !isset($item["content"]) ||
            !is_array($item["content"])
        ) {
            continue;
        }

        foreach (
            $item["content"] as $content
        ) {

            if (
                !isset($content["annotations"]) ||
                !is_array($content["annotations"])
            ) {
                continue;
            }

            foreach (
                $content["annotations"] as $annotation
            ) {

                if (
                    isset($annotation["type"]) &&
                    $annotation["type"] === "url_citation" &&
                    isset($annotation["url"])
                ) {

                    $fonte = [
                        "url" => $annotation["url"]
                    ];

                    if (
                        isset($annotation["title"])
                    ) {
                        $fonte["title"] =
                            $annotation["title"];
                    }

                    $jaExiste = false;

                    foreach (
                        $fontes as $fonteExistente
                    ) {

                        if (
                            $fonteExistente["url"] ===
                            $fonte["url"]
                        ) {
                            $jaExiste = true;
                            break;
                        }
                    }

                    if (
                        !$jaExiste
                    ) {
                        $fontes[] = $fonte;
                    }
                }
            }
        }
    }
}

/* =====================================================
   ID DA RESPOSTA
===================================================== */

$responseId =
    $json["id"]
    ??
    null;

/* =====================================================
   RETORNAR AO JAVASCRIPT
===================================================== */

responderJSON(
    [
        "response" => $respostaTexto,

        "response_id" => $responseId,

        "sources" => $fontes
    ],
    200
);