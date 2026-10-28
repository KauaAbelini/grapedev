
<?php
session_start();

require_once __DIR__ . '/../database/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuarioId = (int) $_SESSION['usuario_id'];
$nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$email = $_SESSION['usuario_email'] ?? '';
$cidade = $_SESSION['usuario_cidade'] ?? '';

$mensagem = '';
$tipoMensagem = 'sucesso';

if (empty($_SESSION['csrf_configuracoes'])) {
    $_SESSION['csrf_configuracoes'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_configuracoes'], $token)) {
        $mensagem = 'Sua sessão expirou. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'perfil') {
            $novoNome = trim($_POST['nome'] ?? '');
            $novoEmail = trim($_POST['email'] ?? '');
            $novaCidade = trim($_POST['cidade'] ?? '');

            if ($novoNome === '' || $novoEmail === '') {
                $mensagem = 'Preencha o nome e o e-mail.';
                $tipoMensagem = 'erro';
            } elseif (!filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {
                $mensagem = 'Digite um endereço de e-mail válido.';
                $tipoMensagem = 'erro';
            } else {
                try {
                    // A tabela usuarios deve possuir id, nome, email e cidade.
                    $stmt = $conexao->prepare(
                        'SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1'
                    );
                    $stmt->bind_param('si', $novoEmail, $usuarioId);
                    $stmt->execute();
                    $emailExiste = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if ($emailExiste) {
                        $mensagem = 'Esse e-mail já está sendo utilizado.';
                        $tipoMensagem = 'erro';
                    } else {
                        $stmt = $conexao->prepare(
                            'UPDATE usuarios SET nome = ?, email = ?, cidade = ? WHERE id = ?'
                        );
                        $stmt->bind_param(
                            'sssi',
                            $novoNome,
                            $novoEmail,
                            $novaCidade,
                            $usuarioId
                        );
                        $stmt->execute();
                        $stmt->close();

                        $_SESSION['usuario_nome'] = $novoNome;
                        $_SESSION['usuario_email'] = $novoEmail;
                        $_SESSION['usuario_cidade'] = $novaCidade;

                        $nome = $novoNome;
                        $email = $novoEmail;
                        $cidade = $novaCidade;

                        $mensagem = 'Seus dados foram atualizados.';
                    }
                } catch (Throwable $e) {
                    error_log('Erro ao atualizar perfil AquaFlow: ' . $e->getMessage());
                    $mensagem = 'Não foi possível atualizar os dados. Confira a estrutura da tabela usuarios.';
                    $tipoMensagem = 'erro';
                }
            }
        } elseif ($acao === 'senha') {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $senhaNova = $_POST['senha_nova'] ?? '';
            $confirmacao = $_POST['confirmar_senha'] ?? '';

            if ($senhaAtual === '' || $senhaNova === '' || $confirmacao === '') {
                $mensagem = 'Preencha todos os campos de senha.';
                $tipoMensagem = 'erro';
            } elseif (strlen($senhaNova) < 8) {
                $mensagem = 'A nova senha deve ter pelo menos 8 caracteres.';
                $tipoMensagem = 'erro';
            } elseif ($senhaNova !== $confirmacao) {
                $mensagem = 'A confirmação não corresponde à nova senha.';
                $tipoMensagem = 'erro';
            } else {
                try {
                    // A coluna senha deve armazenar senhas com password_hash().
                    $stmt = $conexao->prepare(
                        'SELECT senha FROM usuarios WHERE id = ? LIMIT 1'
                    );
                    $stmt->bind_param('i', $usuarioId);
                    $stmt->execute();
                    $resultado = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$resultado || !password_verify($senhaAtual, $resultado['senha'])) {
                        $mensagem = 'A senha atual está incorreta.';
                        $tipoMensagem = 'erro';
                    } else {
                        $hash = password_hash($senhaNova, PASSWORD_DEFAULT);

                        $stmt = $conexao->prepare(
                            'UPDATE usuarios SET senha = ? WHERE id = ?'
                        );
                        $stmt->bind_param('si', $hash, $usuarioId);
                        $stmt->execute();
                        $stmt->close();

                        $mensagem = 'Sua senha foi alterada com sucesso.';
                    }
                } catch (Throwable $e) {
                    error_log('Erro ao alterar senha AquaFlow: ' . $e->getMessage());
                    $mensagem = 'Não foi possível alterar a senha. Confira a coluna senha da tabela usuarios.';
                    $tipoMensagem = 'erro';
                }
            }
        }
    }
}

$primeiraLetra = strtoupper(substr($nome, 0, 1));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurações | AquaFlow</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@300;400;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
:root {
    --fundo: #080914;
    --painel: #111322;
    --painel2: #17192b;
    --roxo: #963cff;
    --roxo-claro: #b779ff;
    --texto: #f5f1fa;
    --suave: #999bb4;
    --borda: rgba(167, 123, 255, .16);
    --verde: #35d6a0;
    --vermelho: #ff6578;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    background: var(--fundo);
    color: var(--texto);
    font-family: 'Nunito', sans-serif;
}
a { color: inherit; }
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 245px;
    padding: 28px 17px;
    background: #0c0d1a;
    border-right: 1px solid var(--borda);
    z-index: 5;
}
.logo {
    font: 36px 'Bebas Neue', sans-serif;
    letter-spacing: 1px;
    padding: 0 13px 32px;
}
.logo span { color: var(--roxo-claro); }
.menu { display: flex; flex-direction: column; gap: 7px; }
.menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 14px;
    color: #a9abc1;
    text-decoration: none;
    border-radius: 11px;
    font-size: 14px;
    font-weight: 800;
    transition: .2s;
}
.menu a i { font-size: 18px; }
.menu a:hover, .menu a.active {
    color: white;
    background: linear-gradient(100deg, rgba(150,60,255,.23), rgba(150,60,255,.06));
}
.menu a.active { box-shadow: inset 3px 0 var(--roxo); }
.menu .logout { margin-top: 25px; color: #ff8190; }
.main {
    margin-left: 245px;
    padding: 30px 35px 55px;
    max-width: 1600px;
}
.top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}
.page-label, .eyebrow {
    color: var(--roxo-claro);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.7px;
}
h1, h2, h3, p { margin-top: 0; }
h1 { font-size: clamp(26px, 3vw, 36px); margin: 8px 0; }
.subtitle { color: var(--suave); font-size: 14px; margin: 0; }
.profile { display: flex; align-items: center; gap: 12px; }
.avatar {
    display: grid;
    place-items: center;
    width: 43px;
    height: 43px;
    border-radius: 14px;
    background: linear-gradient(135deg, #963cff, #5125a8);
    font-weight: 900;
}
.profile-info strong, .profile-info span { display: block; }
.profile-info strong { font-size: 13px; }
.profile-info span { font-size: 12px; color: var(--suave); margin-top: 3px; }
.settings-layout {
    display: grid;
    grid-template-columns: 225px minmax(0, 1fr);
    gap: 22px;
    align-items: start;
}
.settings-nav, .panel {
    border: 1px solid var(--borda);
    border-radius: 18px;
    background: var(--painel);
}
.settings-nav { padding: 12px; }
.settings-nav a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 12px;
    margin: 3px 0;
    border-radius: 10px;
    color: #b0b1c7;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
}
.settings-nav a:hover, .settings-nav a.selected {
    background: rgba(150,60,255,.13);
    color: white;
}
.settings-nav a.selected { box-shadow: inset 3px 0 var(--roxo); }
.settings-nav i { font-size: 17px; }
.settings-content { display: grid; gap: 20px; min-width: 0; }
.panel { padding: 25px; scroll-margin-top: 20px; }
.panel-heading {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 23px;
}
.panel-icon {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 13px;
    color: var(--roxo-claro);
    background: rgba(150,60,255,.13);
    font-size: 20px;
}
.panel-heading h2 { font-size: 18px; margin: 1px 0 5px; }
.panel-heading p { color: var(--suave); font-size: 12px; margin: 0; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 18px; }
.field { display: flex; flex-direction: column; gap: 8px; }
.field.full { grid-column: 1 / -1; }
.field label { font-size: 12px; font-weight: 800; color: #d9d7e8; }
.field input {
    width: 100%;
    min-width: 0;
    border: 1px solid #2b2d45;
    border-radius: 10px;
    background: #0b0d19;
    padding: 13px 14px;
    color: white;
    font: 14px 'Nunito', sans-serif;
    outline: none;
}
.field input:focus { border-color: var(--roxo); box-shadow: 0 0 0 3px #963cff20; }
.field input::placeholder { color: #666981; }
.form-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--borda);
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 0;
    border-radius: 10px;
    padding: 12px 17px;
    background: linear-gradient(110deg, #963cff, #742be0);
    color: white;
    text-decoration: none;
    font: 800 13px 'Nunito', sans-serif;
    cursor: pointer;
    transition: .2s;
}
.btn:hover { transform: translateY(-1px); filter: brightness(1.1); }
.notice {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 13px;
    font-weight: 800;
}
.notice.sucesso { color: var(--verde); background: #35d6a012; border: 1px solid #35d6a035; }
.notice.erro { color: #ff8291; background: #ff657812; border: 1px solid #ff657835; }
.preference {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 17px 0;
    border-bottom: 1px solid var(--borda);
}
.preference:last-child { border-bottom: 0; padding-bottom: 0; }
.preference strong { display: block; font-size: 13px; margin-bottom: 4px; }
.preference p { color: var(--suave); font-size: 12px; margin: 0; line-height: 1.6; }
.switch { position: relative; width: 44px; height: 25px; flex-shrink: 0; }
.switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.switch span {
    position: absolute; inset: 0; cursor: pointer;
    border-radius: 20px; background: #393b51; transition: .2s;
}
.switch span:before {
    content: ""; position: absolute; width: 19px; height: 19px;
    left: 3px; top: 3px; border-radius: 50%; background: white; transition: .2s;
}
.switch input:checked + span { background: var(--roxo); }
.switch input:checked + span:before { transform: translateX(19px); }
.info-note { color: var(--suave); font-size: 12px; line-height: 1.7; margin: 15px 0 0; }
.footer { text-align: center; padding-top: 28px; color: #686b85; font-size: 11px; }
@media (max-width: 1000px) {
    .sidebar { width: 205px; }
    .main { margin-left: 205px; padding: 25px 20px; }
    .settings-layout { grid-template-columns: 1fr; }
    .settings-nav { display: flex; flex-wrap: wrap; gap: 4px; }
    .settings-nav a { padding: 10px; }
}
@media (max-width: 650px) {
    .sidebar { position: static; width: 100%; padding: 12px; }
    .logo { padding: 0 10px 12px; font-size: 30px; }
    .menu { flex-direction: row; overflow-x: auto; padding-bottom: 5px; }
    .menu a { white-space: nowrap; padding: 10px; font-size: 12px; }
    .menu .logout { margin-top: 0; }
    .main { margin-left: 0; padding: 22px 14px; }
    .top { align-items: flex-start; }
    .profile-info { display: none; }
    .form-grid { grid-template-columns: 1fr; }
    .field.full { grid-column: auto; }
    .panel { padding: 18px; }
    .settings-nav { flex-wrap: nowrap; overflow-x: auto; }
    .settings-nav a { white-space: nowrap; }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">Aqua<span>Flow</span></div>

    <nav class="menu">
        <a href="dashboard.php"><i class="bi bi-grid"></i>Dashboard</a>
        <a href="consumo.php"><i class="bi bi-droplet"></i>Consumo</a>
        <a href="gastos.php"><i class="bi bi-currency-dollar"></i>Gastos</a>
        <a href="dispositivos.php"><i class="bi bi-cpu"></i>Dispositivos</a>
        <a href="alertas.php"><i class="bi bi-exclamation-triangle"></i>Alertas</a>
        <a href="historico.php"><i class="bi bi-clock-history"></i>Histórico</a>
        <a href="configuracoes.php" class="active"><i class="bi bi-gear"></i>Configurações</a>
        <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i>Sair</a>
    </nav>
</aside>

<main class="main">
    <header class="top">
        <div>
            <span class="page-label">AQUAFLOW / CONFIGURAÇÕES</span>
            <h1>Configurações</h1>
            <p class="subtitle">Gerencie sua conta e personalize sua experiência.</p>
        </div>

        <div class="profile">
            <div class="avatar"><?= htmlspecialchars($primeiraLetra) ?></div>
            <div class="profile-info">
                <strong><?= htmlspecialchars($nome) ?></strong>
                <span><?= htmlspecialchars($cidade ?: 'Cidade não informada') ?></span>
            </div>
        </div>
    </header>

    <?php if ($mensagem !== ''): ?>
        <div class="notice <?= $tipoMensagem === 'erro' ? 'erro' : 'sucesso' ?>">
            <i class="bi <?= $tipoMensagem === 'erro' ? 'bi-exclamation-circle' : 'bi-check-circle' ?>"></i>
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="settings-layout">
        <nav class="settings-nav">
            <a class="selected" href="#perfil"><i class="bi bi-person"></i>Meu perfil</a>
            <a href="#notificacoes"><i class="bi bi-bell"></i>Notificações</a>
            <a href="#seguranca"><i class="bi bi-shield-lock"></i>Segurança</a>
            <a href="#monitoramento"><i class="bi bi-droplet"></i>Monitoramento</a>
        </nav>

        <div class="settings-content">

            <section class="panel" id="perfil">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="bi bi-person"></i></div>
                    <div>
                        <h2>Dados do perfil</h2>
                        <p>Atualize as informações da sua conta AquaFlow.</p>
                    </div>
                </div>

                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_configuracoes']) ?>">
                    <input type="hidden" name="acao" value="perfil">

                    <div class="form-grid">
                        <div class="field">
                            <label for="nome">Nome completo</label>
                            <input id="nome" name="nome" type="text" maxlength="120" required value="<?= htmlspecialchars($nome) ?>">
                        </div>

                        <div class="field">
                            <label for="email">E-mail</label>
                            <input id="email" name="email" type="email" maxlength="190" required value="<?= htmlspecialchars($email) ?>">
                        </div>

                        <div class="field full">
                            <label for="cidade">Cidade</label>
                            <input id="cidade" name="cidade" type="text" maxlength="120" value="<?= htmlspecialchars($cidade) ?>" placeholder="Digite sua cidade">
                        </div>
                    </div>

                    <div class="form-footer">
                        <button class="btn" type="submit"><i class="bi bi-check2"></i>Salvar alterações</button>
                    </div>
                </form>
            </section>

            <section class="panel" id="notificacoes">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="bi bi-bell"></i></div>
                    <div>
                        <h2>Preferências de notificações</h2>
                        <p>Escolha quais avisos deseja visualizar nesta página.</p>
                    </div>
                </div>

                <div class="preference">
                    <div>
                        <strong>Alertas de vazamento</strong>
                        <p>Mostrar preferência para alertas de possíveis vazamentos.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-pref="alertas" checked>
                        <span></span>
                    </label>
                </div>

                <div class="preference">
                    <div>
                        <strong>Avisos de consumo</strong>
                        <p>Exibir lembretes e informações sobre o consumo de água.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-pref="consumo" checked>
                        <span></span>
                    </label>
                </div>

                <div class="preference">
                    <div>
                        <strong>Atualizações do monitoramento 3D</strong>
                        <p>Permitir avisos visuais sobre o estado da sincronização.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" data-pref="monitoramento" checked>
                        <span></span>
                    </label>
                </div>

                <p class="info-note">Essas preferências ficam salvas neste navegador. Elas não alteram o envio de e-mails nem as regras do sistema de alertas.</p>
            </section>

            <section class="panel" id="seguranca">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <h2>Segurança da conta</h2>
                        <p>Altere sua senha para proteger seu acesso.</p>
                    </div>
                </div>

                <form method="POST" action="configuracoes.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_configuracoes']) ?>">
                    <input type="hidden" name="acao" value="senha">

                    <div class="form-grid">
                        <div class="field full">
                            <label for="senha_atual">Senha atual</label>
                            <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required>
                        </div>

                        <div class="field">
                            <label for="senha_nova">Nova senha</label>
                            <input id="senha_nova" name="senha_nova" type="password" minlength="8" autocomplete="new-password" required>
                        </div>

                        <div class="field">
                            <label for="confirmar_senha">Confirmar nova senha</label>
                            <input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button class="btn" type="submit"><i class="bi bi-lock"></i>Alterar senha</button>
                    </div>
                </form>
            </section>

            <section class="panel" id="monitoramento">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="bi bi-droplet"></i></div>
                    <div>
                        <h2>Monitoramento AquaFlow</h2>
                        <p>Acesse rapidamente os recursos de acompanhamento.</p>
                    </div>
                </div>

                <div class="preference">
                    <div>
                        <strong>Consumo de água</strong>
                        <p>Consulte os registros e gráficos de consumo.</p>
                    </div>
                    <a class="btn" href="consumo.php">Acessar <i class="bi bi-arrow-up-right"></i></a>
                </div>

                <div class="preference">
                    <div>
                        <strong>Dispositivos</strong>
                        <p>Consulte os dispositivos vinculados à sua conta.</p>
                    </div>
                    <a class="btn" href="dispositivos.php">Acessar <i class="bi bi-arrow-up-right"></i></a>
                </div>

                <div class="preference">
                    <div>
                        <strong>Monitoramento 3D</strong>
                        <p>Visualize os modelos e o acompanhamento de vazamentos.</p>
                    </div>
                    <a class="btn" href="3d.php">Abrir 3D <i class="bi bi-boxes"></i></a>
                </div>
            </section>

        </div>
    </div>

    <footer class="footer">AquaFlow · Monitoramento inteligente de água</footer>
</main>

<script>
(function () {
    const chaves = ['alertas', 'consumo', 'monitoramento'];

    chaves.forEach(function (chave) {
        const controle = document.querySelector('[data-pref="' + chave + '"]');
        if (!controle) return;

        try {
            const salvo = localStorage.getItem('aquaflow_pref_' + chave);
            if (salvo !== null) controle.checked = salvo === 'true';
        } catch (e) {
            // A página continua funcionando se o navegador bloquear o armazenamento.
        }

        controle.addEventListener('change', function () {
            try {
                localStorage.setItem('aquaflow_pref_' + chave, String(controle.checked));
            } catch (e) {
                // Não impede o uso da página.
            }
        });
    });

    document.querySelectorAll('.settings-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            document.querySelectorAll('.settings-nav a').forEach(function (item) {
                item.classList.remove('selected');
            });
            link.classList.add('selected');
        });
    });
})();
</script>

</body>
</html>
