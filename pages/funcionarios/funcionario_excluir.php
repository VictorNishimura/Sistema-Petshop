<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/usuario_funcionario.php';
garantirCampoFotoTabela($conexao, 'funcionarios');
garantirCampoUsuarioFuncionario($conexao);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: funcionarios.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, cargo, foto_perfil FROM funcionarios WHERE id = :id");
$stmt->execute(['id' => $id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    header("Location: funcionarios.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        excluirUsuarioFuncionario($conexao, $id);
        $stmt = $conexao->prepare("DELETE FROM funcionarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        excluirFotoPerfil($funcionario['foto_perfil'] ?? null);

        header("Location: funcionarios.php?sucesso=excluir");
        exit;
    } catch (PDOException $e) {
        $erro = 'Nao foi possivel excluir este funcionario. Verifique se existem consultas vinculadas a ele.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Excluir Funcionario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="mb-3">Excluir funcionario</h1>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <p>Tem certeza que deseja excluir <strong><?php echo htmlspecialchars($funcionario['nome']); ?></strong>?</p>
            <p class="text-muted">Cargo: <?php echo htmlspecialchars($funcionario['cargo']); ?></p>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $funcionario['id']; ?>">
                <button type="submit" class="btn btn-danger">Confirmar exclusao</button>
                <a href="funcionarios.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
