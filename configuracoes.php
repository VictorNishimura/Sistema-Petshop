<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';

$idUsuario = (int) $_SESSION['usuario_id'];
$erro = '';
$sucesso = '';

$stmt = $conexao->prepare("SELECT id, nome, email, senha, pergunta_secreta, resposta_secreta, nivel FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $idUsuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: logout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario['nome'] = trim($_POST['nome'] ?? '');
    $usuario['email'] = trim($_POST['email'] ?? '');
    $senhaAtual = trim($_POST['senha_atual'] ?? '');
    $novaSenha = trim($_POST['nova_senha'] ?? '');
    $confirmarSenha = trim($_POST['confirmar_senha'] ?? '');
    $usuario['pergunta_secreta'] = trim($_POST['pergunta_secreta'] ?? '');
    $usuario['resposta_secreta'] = trim($_POST['resposta_secreta'] ?? '');

    if ($usuario['nome'] === '' || $usuario['email'] === '' || $usuario['pergunta_secreta'] === '' || $usuario['resposta_secreta'] === '') {
        $erro = 'Nome, e-mail, pergunta secreta e resposta secreta sao obrigatorios.';
    } elseif ($novaSenha !== '' && $senhaAtual !== $usuario['senha']) {
        $erro = 'Para alterar a senha, informe a senha atual corretamente.';
    } elseif ($novaSenha !== '' && $novaSenha !== $confirmarSenha) {
        $erro = 'A nova senha e a confirmacao nao conferem.';
    } else {
        try {
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id <> :id");
            $stmt->execute(['email' => $usuario['email'], 'id' => $idUsuario]);

            if ((int) $stmt->fetchColumn() > 0) {
                $erro = 'Este e-mail ja esta sendo usado por outro usuario.';
            } else {
                $senhaParaSalvar = $novaSenha !== '' ? $novaSenha : $usuario['senha'];

                $stmt = $conexao->prepare(
                    "UPDATE usuarios
                     SET nome = :nome, email = :email, senha = :senha,
                         pergunta_secreta = :pergunta_secreta, resposta_secreta = :resposta_secreta
                     WHERE id = :id"
                );
                $stmt->execute([
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'senha' => $senhaParaSalvar,
                    'pergunta_secreta' => $usuario['pergunta_secreta'],
                    'resposta_secreta' => $usuario['resposta_secreta'],
                    'id' => $idUsuario,
                ]);

                $_SESSION['usuario_nome'] = $usuario['nome'];
                $usuario['senha'] = $senhaParaSalvar;
                $sucesso = 'Dados atualizados com sucesso.';
            }
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel atualizar os dados da conta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Configuracoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Configuracoes</h1>
            <p class="text-muted mb-0">Altere os dados da sua conta</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nivel de acesso</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['nivel']); ?>" disabled>
                    </div>

                    <div class="col-12">
                        <hr>
                        <h2 class="h5">Alterar senha</h2>
                    </div>
                    <div class="col-md-4">
                        <label for="senha_atual" class="form-label">Senha atual</label>
                        <input type="password" name="senha_atual" id="senha_atual" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="nova_senha" class="form-label">Nova senha</label>
                        <input type="password" name="nova_senha" id="nova_senha" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control">
                    </div>

                    <div class="col-12">
                        <hr>
                        <h2 class="h5">Recuperacao de conta</h2>
                    </div>
                    <div class="col-md-6">
                        <label for="pergunta_secreta" class="form-label">Pergunta secreta</label>
                        <input type="text" name="pergunta_secreta" id="pergunta_secreta" class="form-control" value="<?php echo htmlspecialchars($usuario['pergunta_secreta']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="resposta_secreta" class="form-label">Resposta secreta</label>
                        <input type="text" name="resposta_secreta" id="resposta_secreta" class="form-control" value="<?php echo htmlspecialchars($usuario['resposta_secreta']); ?>" required>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
