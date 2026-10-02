<?php
session_start();
include("conexao.php");
include("local_contexto.php");
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo json_encode(["sucesso"=>false,"mensagem"=>"Sessão expirada."]);
    exit;
}

$usuarioId=(int)$_SESSION["usuario_id"];
$local=obterLocalAtual($conexao,$usuarioId);
if(!$local){http_response_code(400);echo json_encode(["sucesso"=>false,"mensagem"=>"Nenhum local selecionado."]);exit;}

$dados=json_decode(file_get_contents("php://input"),true) ?: [];
$ambiente=trim($dados["ambiente"]??"");
$ponto=trim($dados["ponto"]??"");
$vazao=(float)($dados["vazao"]??0);
if($ambiente===""||$ponto===""||$vazao<=0){http_response_code(400);echo json_encode(["sucesso"=>false,"mensagem"=>"Dados inválidos."]);exit;}

$stmt=$conexao->prepare("SELECT id FROM aquaflow_vazamentos WHERE usuario_id=? AND local_id=? AND ambiente=? AND ponto=? AND ativo=1 LIMIT 1");
$stmt->bind_param("iiss",$usuarioId,$local["id"],$ambiente,$ponto);$stmt->execute();$res=$stmt->get_result();$existente=$res->fetch_assoc();$stmt->close();

if($existente){$stmt=$conexao->prepare("UPDATE aquaflow_vazamentos SET vazao=?, atualizado_em=NOW() WHERE id=? AND usuario_id=?");$stmt->bind_param("dii",$vazao,$existente["id"],$usuarioId);$stmt->execute();$stmt->close();}
else{$stmt=$conexao->prepare("INSERT INTO aquaflow_vazamentos (usuario_id,local_id,ambiente,ponto,vazao,inicio,ativo,atualizado_em) VALUES (?,?,?,?,?,NOW(),1,NOW())");$stmt->bind_param("iissd",$usuarioId,$local["id"],$ambiente,$ponto,$vazao);$stmt->execute();$stmt->close();}

echo json_encode(["sucesso"=>true,"local_id"=>(int)$local["id"]]);
