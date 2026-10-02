<?php

function tipoImovelLegado($tipoUsuario, $tipoEstabelecimento = "") {
    if ($tipoUsuario === "residencial") {
        return "casa";
    }

    $tipo = strtolower(trim($tipoEstabelecimento));

    if ($tipo === "industria" || $tipo === "indústria") return "industria";
    if ($tipo === "escola") return "escola";
    if ($tipo === "fazenda" || $tipo === "agro" || $tipo === "agronegócio") return "agro";

    return "empresa";
}

function obterLocalAtual($conexao, $usuarioId) {
    $localIdSessao = (int)($_SESSION["local_id"] ?? 0);

    if ($localIdSessao > 0) {
        $stmt = $conexao->prepare("SELECT * FROM locais WHERE id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ii", $localIdSessao, $usuarioId);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $local = $resultado->fetch_assoc();
            $stmt->close();
            if ($local) {
                return $local;
            }
        }
    }

    $stmt = $conexao->prepare("SELECT * FROM locais WHERE usuario_id = ? AND ativo = 1 ORDER BY id ASC LIMIT 1");
    if (!$stmt) return null;

    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $local = $resultado->fetch_assoc();
    $stmt->close();

    if ($local) {
        $_SESSION["local_id"] = (int)$local["id"];
        $_SESSION["local_nome"] = $local["nome"];
        $_SESSION["local_tipo_imovel"] = $local["tipo_imovel"];
        return $local;
    }

    $tipoImovel = $_SESSION["usuario_tipo_imovel"] ?? "";
    if ($tipoImovel === "") {
        $tipoImovel = tipoImovelLegado(
            $_SESSION["usuario_tipo"] ?? "",
            $_SESSION["usuario_tipo_estabelecimento"] ?? ""
        );
    }

    $nomeLocal = trim($_SESSION["usuario_empresa"] ?? "");
    if ($nomeLocal === "") {
        $nomeLocal = ($tipoImovel === "casa" || $tipoImovel === "predio_residencial")
            ? "Minha residência"
            : "Meu estabelecimento";
    }

    $empresa = $_SESSION["usuario_empresa"] ?? null;
    $cep = $_SESSION["usuario_cep"] ?? null;
    $endereco = $_SESSION["usuario_endereco"] ?? null;
    $numero = $_SESSION["usuario_numero"] ?? null;
    $bairro = $_SESSION["usuario_bairro"] ?? null;
    $cidade = $_SESSION["usuario_cidade"] ?? null;

    $stmt = $conexao->prepare("INSERT INTO locais (usuario_id,nome,tipo_imovel,empresa,cep,endereco,numero,bairro,cidade) VALUES (?,?,?,?,?,?,?,?,?)");
    if (!$stmt) return null;

    $stmt->bind_param(
        "issssssss",
        $usuarioId,
        $nomeLocal,
        $tipoImovel,
        $empresa,
        $cep,
        $endereco,
        $numero,
        $bairro,
        $cidade
    );

    $stmt->execute();
    $novoId = $stmt->insert_id;
    $stmt->close();

    if (!$novoId) return null;

    $_SESSION["local_id"] = (int)$novoId;
    $_SESSION["local_nome"] = $nomeLocal;
    $_SESSION["local_tipo_imovel"] = $tipoImovel;

    return [
        "id" => $novoId,
        "usuario_id" => $usuarioId,
        "nome" => $nomeLocal,
        "tipo_imovel" => $tipoImovel,
        "empresa" => $empresa,
        "cep" => $cep,
        "endereco" => $endereco,
        "numero" => $numero,
        "bairro" => $bairro,
        "cidade" => $cidade,
        "ativo" => 1
    ];
}
