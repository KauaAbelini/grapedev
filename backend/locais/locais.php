
<?php
session_start();

require_once __DIR__ . '/../database/conexao.php';
require_once __DIR__ . '/local_contexto.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

/**
 * Escapa os textos exibidos no HTML.
 */
function escapar($valor)
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Monta o endereço sem separadores desnecessários.
 */
function montarEndereco($local)
{
    $partes = [];

    $endereco = trim((string) ($local['endereco'] ?? ''));
    $numero = trim((string) ($local['numero'] ?? ''));
    $cidade = trim((string) ($local['cidade'] ?? ''));

    if ($endereco !== '') {
        $partes[] = $endereco;
    }

    if ($numero !== '') {
        $partes[] = 'nº ' . $numero;
    }

    if ($cidade !== '') {
        $partes[] = $cidade;
    }

    return implode(', ', $partes);
}

/**
 * Gera um token de segurança para os formulários.
 */
if (empty($_SESSION['csrf_locais'])) {
    $_SESSION['csrf_locais'] = bin2hex(random_bytes(32));
}

/**
 * Remove um local do usuário autenticado.
 * O registro é preservado no banco com ativo = 0.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'remover_local'
) {
    $token = $_POST['csrf_token'] ?? '';
    $localId = filter_input(
        INPUT_POST,
        'local_id',
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($token)
        || !hash_equals($_SESSION['csrf_locais'], $token)
        || !$localId
        || $localId <= 0
    ) {
        $_SESSION['mensagem_local'] = [
            'tipo' => 'erro',
            'texto' => 'Não foi possível validar a remoção.'
        ];

        header("Location: locais.php");
        exit;
    }

    $stmtRemover = $conexao->prepare(
        "UPDATE locais
         SET ativo = 0
         WHERE id = ?
           AND usuario_id = ?
           AND ativo = 1"
    );

    if ($stmtRemover) {
        $stmtRemover->bind_param(
            "ii",
            $localId,
            $usuarioId
        );

        if (
            $stmtRemover->execute()
            && $stmtRemover->affected_rows > 0
        ) {
            /*
             * Limpa a seleção da sessão se o local removido
             * era o local atualmente selecionado.
             */
            if (
                (int) ($_SESSION['local_id'] ?? 0)
                === $localId
            ) {
                unset(
                    $_SESSION['local_id'],
                    $_SESSION['local_nome'],
                    $_SESSION['local_tipo_imovel']
                );
            }

            $_SESSION['mensagem_local'] = [
                'tipo' => 'sucesso',
                'texto' => 'Local removido com sucesso.'
            ];
        } else {
            $_SESSION['mensagem_local'] = [
                'tipo' => 'erro',
                'texto' => 'Não foi possível remover esse local.'
            ];
        }

        $stmtRemover->close();
    } else {
        error_log(
            'Erro ao preparar remoção de local: '
            . $conexao->error
        );

        $_SESSION['mensagem_local'] = [
            'tipo' => 'erro',
            'texto' => 'Erro ao preparar a remoção do local.'
        ];
    }

    header("Location: locais.php");
    exit;
}

/**
 * Recupera a mensagem após a remoção.
 */
$mensagemLocal = $_SESSION['mensagem_local'] ?? null;
unset($_SESSION['mensagem_local']);

/**
 * Obtém o local atualmente selecionado.
 */
$localAtual = obterLocalAtual($conexao, $usuarioId);

/**
 * Busca somente os locais ativos do usuário conectado.
 */
$locais = [];

$stmt = $conexao->prepare(
    "SELECT *
     FROM locais
     WHERE usuario_id = ?
       AND ativo = 1
     ORDER BY id ASC"
);

if ($stmt) {
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($row = $resultado->fetch_assoc()) {
        $locais[] = $row;
    }

    $stmt->close();
} else {
    error_log(
        'Erro ao consultar locais: ' . $conexao->error
    );
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

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800&display=swap"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #05030d;
            --surface: #0d0a16;
            --line: rgba(203, 177, 255, .14);
            --text: #f5f1fa;
            --muted: #aaa2b8;
            --purple: #963cff;
            --purple-light: #b783ff;
            --red: #ff536b;
        }

        body {
            min-height: 100vh;
            padding: 40px;
            background:
                radial-gradient(
                    ellipse at 50% 0%,
                    rgba(100, 35, 180, .12),
                    transparent 55%
                ),
                var(--bg);
            color: var(--text);
            font-family: 'Nunito', Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font-family: inherit;
        }

        .page {
            max-width: 1100px;
            margin: 0 auto;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .eyebrow {
            margin-bottom: 5px;
            color: var(--purple-light);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .title {
            font-family: 'Bebas Neue', Impact, sans-serif;
            font-size: 48px;
            font-weight: 400;
            letter-spacing: .5px;
            line-height: 1.1;
        }

        .title span {
            color: var(--purple-light);
        }

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 15px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: var(--surface);
            color: var(--text);
            font-size: 12px;
            font-weight: 800;
            transition: border-color .2s, background .2s;
        }

        .back:hover {
            border-color: var(--purple);
        }

        .new-local {
            border-color: var(--purple);
            background: var(--purple);
        }

        .new-local:hover {
            background: #7e29df;
        }

        /* Mensagem de resultado */
        .local-message {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
        }

        .local-message.sucesso {
            border-color: rgba(75, 220, 150, .25);
            background: rgba(75, 220, 150, .08);
            color: #8af0ba;
        }

        .local-message.erro {
            border-color: rgba(255, 83, 107, .25);
            background: rgba(255, 83, 107, .08);
            color: #ff9eaa;
        }

        /* Grade dos locais */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .card {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 235px;
            flex-direction: column;
            padding: 21px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: linear-gradient(
                145deg,
                rgba(18, 13, 30, .98),
                rgba(9, 7, 17, .98)
            );
            transition: border-color .2s, transform .2s;
        }

        .card:hover {
            transform: translateY(-3px);
            border-color: rgba(183, 131, 255, .5);
        }

        .card.active {
            border-color: rgba(150, 60, 255, .7);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            padding-right: 32px;
        }

        .icon {
            display: grid;
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            place-items: center;
            border: 1px solid rgba(150, 60, 255, .25);
            border-radius: 8px;
            background: rgba(150, 60, 255, .09);
            color: var(--purple-light);
            font-size: 21px;
        }

        .status {
            padding: 5px 8px;
            border: 1px solid var(--line);
            border-radius: 5px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
        }

        .status.active {
            border-color: rgba(150, 60, 255, .35);
            background: rgba(150, 60, 255, .1);
            color: var(--purple-light);
        }

        /* Botão X para remover o local */
        .remove-form {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
        }

        .remove-local {
            display: flex;
            width: 30px;
            height: 30px;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid rgba(255, 83, 107, .25);
            border-radius: 7px;
            background: rgba(255, 83, 107, .08);
            color: var(--red);
            font-size: 15px;
            cursor: pointer;
            transition:
                background .2s,
                border-color .2s,
                transform .2s;
        }

        .remove-local:hover {
            transform: scale(1.06);
            border-color: var(--red);
            background: rgba(255, 83, 107, .2);
        }

        .remove-local:focus-visible {
            outline: 2px solid var(--red);
            outline-offset: 3px;
        }

        .name {
            overflow-wrap: anywhere;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.35;
        }

        .type {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .address {
            margin-top: 13px;
            color: #aaa2b8;
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .address i {
            margin-right: 5px;
            color: var(--purple-light);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: auto;
            padding-top: 18px;
        }

        .actions a {
            display: inline-flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 0;
            padding: 10px 8px;
            border: 1px solid var(--line);
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-align: center;
            transition: background .2s, border-color .2s;
        }

        .actions .use {
            border-color: var(--purple);
            background: var(--purple);
            color: #fff;
        }

        .actions .use:hover {
            background: #7e29df;
        }

        .actions .current {
            background: rgba(150, 60, 255, .09);
            color: var(--purple-light);
        }

        .actions .secondary:hover {
            border-color: var(--purple);
        }

        /* Estado vazio */
        .empty {
            padding: 35px 24px;
            border: 1px dashed var(--line);
            border-radius: 10px;
            background: var(--surface);
            text-align: center;
        }

        .empty h2 {
            margin-bottom: 8px;
            font-size: 19px;
        }

        .empty p {
            max-width: 440px;
            margin: 0 auto 20px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 22px 16px;
            }

            .top {
                align-items: flex-start;
                flex-direction: column;
            }

            .title {
                font-size: 40px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .top-actions {
                width: 100%;
            }

            .top-actions a {
                flex: 1;
            }
        }
    </style>
</head>

<body>
    <main class="page">

        <?php if ($mensagemLocal !== null): ?>
            <div
                class="local-message <?php echo escapar($mensagemLocal['tipo'] ?? 'erro'); ?>"
                role="status"
            >
                <?php if (($mensagemLocal['tipo'] ?? '') === 'sucesso'): ?>
                    <i class="bi bi-check-circle"></i>
                <?php else: ?>
                    <i class="bi bi-exclamation-circle"></i>
                <?php endif; ?>

                <?php echo escapar($mensagemLocal['texto'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <header class="top">
            <div>
                <div class="eyebrow">
                    AQUAFLOW / LOCAIS MONITORADOS
                </div>

                <h1 class="title">
                    Meus <span>locais</span>
                </h1>
            </div>

            <div class="top-actions">
                <a class="back new-local" href="adicionar_local.php">
                    <i class="bi bi-plus-lg"></i>
                    Novo local
                </a>

                <a class="back" href="../dashboard/dashboard.php">
                    <i class="bi bi-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </header>

        <?php if (empty($locais)): ?>

            <section class="empty">
                <h2>Nenhum local cadastrado</h2>

                <p>
                    Cadastre sua casa, empresa ou outro ambiente para começar
                    a organizar os locais monitorados pelo AquaFlow.
                </p>

                <a class="back new-local" href="adicionar_local.php">
                    <i class="bi bi-plus-lg"></i>
                    Cadastrar primeiro local
                </a>
            </section>

        <?php else: ?>

            <section class="grid" aria-label="Lista de locais">

                <?php foreach ($locais as $local): ?>

                    <?php
                    $ativo = $localAtual
                        && (int) $localAtual['id'] === (int) $local['id'];

                    $tipoImovel = $local['tipo_imovel'] ?? '';

                    $icone = in_array(
                        $tipoImovel,
                        ['casa', 'predio_residencial'],
                        true
                    ) ? 'bi-house' : 'bi-building';

                    $tipoTexto = $tipos[$tipoImovel] ?? $tipoImovel;
                    ?>

                    <article class="card <?php echo $ativo ? 'active' : ''; ?>">

                        <!-- X para remover este local -->
                        <form
                            method="POST"
                            class="remove-form"
                            onsubmit="return confirm('Tem certeza que deseja remover este local? Ele deixará de aparecer na lista.');"
                        >
                            <input
                                type="hidden"
                                name="acao"
                                value="remover_local"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo escapar($_SESSION['csrf_locais']); ?>"
                            >

                            <input
                                type="hidden"
                                name="local_id"
                                value="<?php echo (int) $local['id']; ?>"
                            >

                            <button
                                type="submit"
                                class="remove-local"
                                title="Remover local"
                                aria-label="Remover <?php echo escapar($local['nome'] ?? 'local'); ?>"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>

                        <div class="card-header">
                            <div class="icon">
                                <i class="bi <?php echo escapar($icone); ?>"></i>
                            </div>

                            <?php if ($ativo): ?>
                                <span class="status active">
                                    Local atual
                                </span>
                            <?php else: ?>
                                <span class="status">
                                    Cadastrado
                                </span>
                            <?php endif; ?>
                        </div>

                        <h2 class="name">
                            <?php
                            echo escapar(
                                $local['nome'] ?? 'Local sem nome'
                            );
                            ?>
                        </h2>

                        <p class="type">
                            <?php echo escapar($tipoTexto); ?>
                        </p>

                        <p class="address">
                            <i class="bi bi-geo-alt"></i>

                            <?php
                            $enderecoCompleto = montarEndereco($local);

                            echo $enderecoCompleto !== ''
                                ? escapar($enderecoCompleto)
                                : 'Endereço não informado';
                            ?>
                        </p>

                        <div class="actions">

                            <?php if ($ativo): ?>

                                <a
                                    class="current"
                                    href="../dashboard/dashboard.php"
                                >
                                    <i class="bi bi-check-circle"></i>
                                    Local atual
                                </a>

                            <?php else: ?>

                                <a
                                    class="use"
                                    href="selecionar_local.php?id=<?php echo (int) $local['id']; ?>&destino=<?php echo rawurlencode('../dashboard/dashboard.php'); ?>"
                                >
                                    <i class="bi bi-arrow-left-right"></i>
                                    Usar este local
                                </a>

                            <?php endif; ?>

                            <a
                                class="secondary"
                                href="../../public/3d/3d.php"
                            >
                                <i class="bi bi-box"></i>
                                Abrir 3D
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </main>
</body>

</html>
