<?php
session_start();
include("conexao.php");
include("local_contexto.php");
header("Content-Type: application/json; charset=utf-8");
if(!isset($_SESSION["usuario_id"])) { http_response_code(401); echo json_encode(["sucesso"=>false,"mensagem"=>"Sessão expirada."]); exit; }
$usuarioId=(int)$_SESSION["usuario_id"];$local=obterLocalAtual($conexao,$usuarioId);
$dados=json_decode(file_get_contents("php://input"),true) ?: [];$ambiente=trim($dados["ambiente"]??"");$ponto=trim($dados["ponto"]??"");
if(!$local||$ambiente===""||$ponto===""){http_response_code(400);echo json_encode(["sucesso"=>false,"mensagem"=>"Dados inválidos."]);exit;}
$stmt=$conexao->prepare("UPDATE aquaflow_vazamentos SET ativo=0, atualizado_em=NOW() WHERE usuario_id=? AND local_id=? AND ambiente=? AND ponto=? AND ativo=1");$stmt->bind_param("iiss",$usuarioId,$local["id"],$ambiente,$ponto);$stmt->execute();$stmt->close();
echo json_encode(["sucesso"=>true]);
