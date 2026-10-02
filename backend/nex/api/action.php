<?php

header(
    "Content-Type: application/json; charset=UTF-8"
);


/*
=========================================================
RECEBER AÇÃO
=========================================================
*/

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

$acao = $dados["acao"] ?? "";

$parametros =
    $dados["parametros"] ?? [];


/*
=========================================================
RESPOSTA PADRÃO
=========================================================
*/

function resposta(
    $sucesso,
    $mensagem,
    $dados = []
) {

    echo json_encode([
        "sucesso" => $sucesso,
        "mensagem" => $mensagem,
        "dados" => $dados
    ]);

    exit;
}


/*
=========================================================
ABRIR AQUAFLOW
=========================================================
*/

if ($acao === "abrir_aquaflow") {

    resposta(
        true,
        "Abrindo o AquaFlow.",
        [
            "url" => "/grapedev/public/pages/aquaflow.html"
        ]
    );
}


/*
=========================================================
ABRIR GRAPEDEV CITY
=========================================================
*/

if ($acao === "abrir_city") {

    resposta(
        true,
        "Abrindo a GrapeDev City.",
        [
            "url" => "/grapedev/public/pages/cidade.html"
        ]
    );
}


/*
=========================================================
ABRIR DASHBOARD
=========================================================
*/

if ($acao === "abrir_dashboard") {

    resposta(
        true,
        "Abrindo o dashboard.",
        [
            "url" => "/grapedev/public/pages/dashboard.html"
        ]
    );
}


/*
=========================================================
ABRIR URL
=========================================================
*/

if ($acao === "abrir_url") {

    $url =
        trim(
            $parametros["url"] ?? ""
        );


    if (
        $url === "" ||
        !filter_var(
            $url,
            FILTER_VALIDATE_URL
        )
    ) {

        resposta(
            false,
            "URL inválida."
        );
    }


    if (
        !preg_match(
            "/^https?:\/\//i",
            $url
        )
    ) {

        resposta(
            false,
            "Apenas URLs HTTP ou HTTPS são permitidas."
        );
    }


    resposta(
        true,
        "Abrindo endereço.",
        [
            "url" => $url
        ]
    );
}


/*
=========================================================
AÇÃO DESCONHECIDA
=========================================================
*/

resposta(
    false,
    "Ação não reconhecida."
);