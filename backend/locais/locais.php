<?php
session_start();
include("conexao.php");
include("local_contexto.php");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int)$_SESSION["usuario_id"];
$localAtual = obterLocalAtual($conexao, $usuarioId);

$stmt = $conexao->prepare("SELECT * FROM locais WHERE usuario_id = ? AND ativo = 1 ORDER BY id ASC");
$locais = [];
if ($stmt) {
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) $locais[] = $row;
    $stmt->close();
}

$tipos = [
    "casa" => "Casa",
    "predio_residencial" => "Prédio residencial",
    "empresa" => "Empresa / Escritório",
    "industria" => "Indústria",
    "predio_comercial" => "Prédio comercial / industrial",
    "escola" => "Escola",
    "agro" => "Agronegócio"
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meus locais | AquaFlow</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}body{min-height:100vh;background:#050811;color:#fff;font-family:Nunito,Arial,sans-serif;padding:40px}.page{max-width:1100px;margin:auto}.top{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:28px}.eyebrow{font-size:11px;letter-spacing:2px;opacity:.55;font-weight:800}.title{font-family:'Bebas Neue';font-size:48px;letter-spacing:1px}.title span{color:#7654ff}.back{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.1);padding:11px 15px;border-radius:12px;background:rgba(255,255,255,.04)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:16px}.card{background:linear-gradient(145deg,rgba(17,25,43,.98),rgba(8,13,25,.98));border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:22px;position:relative;min-height:190px}.card.active{border-color:rgba(118,84,255,.7);box-shadow:0 0 35px rgba(118,84,255,.12)}.icon{width:50px;height:50px;border-radius:15px;display:grid;place-items:center;background:rgba(118,84,255,.12);color:#9a86ff;font-size:22px;margin-bottom:18px}.name{font-size:20px;font-weight:800}.type{font-size:12px;opacity:.6;margin-top:4px}.address{font-size:12px;opacity:.55;margin-top:13px;line-height:1.5}.actions{display:flex;gap:8px;margin-top:18px}.actions a{flex:1;text-align:center;text-decoration:none;padding:10px;border-radius:10px;font-size:12px;font-weight:800}.use{background:#7654ff;color:#fff}.current{background:rgba(255,255,255,.07);color:#fff}.empty{padding:30px;border:1px dashed rgba(255,255,255,.12);border-radius:18px;opacity:.7}@media(max-width:600px){body{padding:22px}.top{align-items:flex-start;flex-direction:column}.title{font-size:40px}}
</style>
</head>
<body>
<div class="page">
<div class="top"><div><div class="eyebrow">AQUAFLOW / LOCAIS MONITORADOS</div><h1 class="title">Meus <span>locais</span></h1></div><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="back" href="adicionar_local.php"><i class="bi bi-plus-lg"></i> Novo local</a><a class="back" href="dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a></div></div>
<div class="grid">
<?php foreach($locais as $local): ?>
<?php $ativo = $localAtual && (int)$localAtual['id'] === (int)$local['id']; ?>
<div class="card <?php echo $ativo ? 'active' : ''; ?>">
<div class="icon"><i class="bi <?php echo in_array($local['tipo_imovel'], ['casa','predio_residencial']) ? 'bi-house' : 'bi-building'; ?>"></i></div>
<div class="name"><?php echo htmlspecialchars($local['nome']); ?></div>
<div class="type"><?php echo htmlspecialchars($tipos[$local['tipo_imovel']] ?? $local['tipo_imovel']); ?></div>
<?php if (!empty($local['empresa'])): ?><div class="type" style="margin-top:6px;opacity:.78;"><i class="bi bi-building"></i> <?php echo htmlspecialchars($local['empresa']); ?></div><?php endif; ?>
<div class="address"><?php echo htmlspecialchars(trim(($local['endereco'] ?? '') . ', ' . ($local['numero'] ?? '') . ' — ' . ($local['cidade'] ?? ''))); ?></div>
<div class="actions">
<?php if($ativo): ?><a class="current" href="dashboard.php">● Local atual</a><?php else: ?><a class="use" href="selecionar_local.php?id=<?php echo (int)$local['id']; ?>&destino=dashboard.php">Usar este local</a><?php endif; ?>
<a class="current" href="selecionar_local.php?id=<?php echo (int)$local['id']; ?>&destino=3d.php">Abrir 3D</a>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</body>
</html>
