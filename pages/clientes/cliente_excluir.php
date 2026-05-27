<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
garantirCampoFotoCliente($conexao);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: clientes.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, cpf, foto_perfil FROM clientes WHERE id = :id");
$stmt->execute(['id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: clientes.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conexao->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        excluirFotoCliente($cliente['foto_perfil'] ?? null);

        header("Location: clientes.php?sucesso=excluir");
        exit;
    } catch (PDOException $e) {
        $erro = 'Nao foi possivel excluir este cliente. Verifique se existem registros vinculados a ele.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Excluir Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="mb-3">Excluir cliente</h1>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <p>Tem certeza que deseja excluir o cliente <strong><?php echo htmlspecialchars($cliente['nome']); ?></strong>?</p>
            <p class="text-muted">CPF: <?php echo htmlspecialchars($cliente['cpf']); ?></p>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                <button type="submit" class="btn btn-danger">Confirmar exclusao</button>
                <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
