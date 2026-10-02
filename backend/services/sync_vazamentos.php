<?php
session_start();
include("conexao.php");
include("local_contexto.php");
header("Content-Type: application/json; charset=utf-8");
if(!isset($_SESSION["usuario_id"])) { http_response_code(401); echo json_encode(["sucesso"=>false,"mensagem"=>"Sessão expirada."]); exit; }
$usuarioId=(int)$_SESSION["usuario_id"];$local=obterLocalAtual($conexao,$usuarioId);
if(!$local){echo json_encode(["sucesso"=>true,"vazamentos"=>[],"quantidade"=>0,"litros_totais"=>0,"local_id"=>0]);exit;}
$stmt=$conexao->prepare("SELECT id,ambiente,ponto,vazao,inicio,TIMESTAMPDIFF(SECOND,inicio,NOW()) AS segundos_ativos FROM aquaflow_vazamentos WHERE usuario_id=? AND local_id=? AND ativo=1 ORDER BY inicio ASC");$stmt->bind_param("ii",$usuarioId,$local["id"]);$stmt->execute();$res=$stmt->get_result();$vazamentos=[];$litrosTotais=0;
while($row=$res->fetch_assoc()){$litros=(float)$row["vazao"]*((int)$row["segundos_ativos"]/60);$row["litros_perdidos"]=$litros;$vazamentos[]=$row;$litrosTotais+=$litros;}$stmt->close();
echo json_encode(["sucesso"=>true,"local_id"=>(int)$local["id"],"local_nome"=>$local["nome"],"vazamentos"=>$vazamentos,"quantidade"=>count($vazamentos),"litros_totais"=>$litrosTotais]);
