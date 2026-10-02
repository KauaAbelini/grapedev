<?php

const DB_HOST = '127.0.0.1';
const DB_NAME = 'grapedev';
const DB_USER = 'root';
const DB_PASS = '';

$OPENAI_API_KEY = '';


const OPENAI_MODEL = 'gpt-5.6-luna';


function db()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4',

        DB_USER,
        DB_PASS,

        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false
        ]
    );

    return $pdo;
}


function getUserId()
{
    if (
        session_status() !==
        PHP_SESSION_ACTIVE
    ) {
        session_start();
    }

    if (
        isset($_SESSION['usuario_id'])
    ) {
        return (int) $_SESSION['usuario_id'];
    }

    return 1;
}



function jsonResponse(
    $data,
    $status = 200
) {
    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}