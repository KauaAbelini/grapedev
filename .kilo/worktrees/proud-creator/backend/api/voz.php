<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

$texto = trim(
    $dados["text"] ?? ""
);


if ($texto === "") {

    http_response_code(400);

    echo json_encode([
        "error" => "Texto vazio."
    ]);

    exit;
}

if (
    !isset($OPENAI_API_KEY) ||
    empty($OPENAI_API_KEY)
) {

    http_response_code(500);

    echo json_encode([
        "error" => "OPENAI_API_KEY não configurada no config.php."
    ]);

    exit;
}


$payload = [

    "model" => "gpt-4o-mini-tts",

    "voice" => "nova",

    "input" => $texto,

    "response_format" => "mp3"

];

$ch = curl_init(
    "https://api.openai.com/v1/audio/speech"
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
            json_encode($payload)

    ]
);

$audio = curl_exec($ch);


if ($audio === false) {

    http_response_code(500);

    echo json_encode([
        "error" => curl_error($ch)
    ]);

    curl_close($ch);

    exit;
}

$status = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);


curl_close($ch);


if (
    $status < 200 ||
    $status >= 300
) {

    http_response_code($status);

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo $audio;

    exit;
}


header(
    "Content-Type: audio/mpeg"
);

header(
    "Content-Disposition: inline; filename=nex.mp3"
);

header(
    "Content-Length: " . strlen($audio)
);


echo $audio;