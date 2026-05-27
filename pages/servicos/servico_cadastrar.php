<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';

$erro = '';
$servico = [
    'nome' => '',
    'preco' => '',
    'duracao_minutos' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servico['nome'] = trim($_POST['nome'] ?? '');
    $servico['preco'] = trim($_POST['preco'] ?? '');
    $servico['duracao_minutos'] = trim($_POST['duracao_minutos'] ?? '');
    $preco = (float) str_replace(',', '.', $servico['preco']);
    $duracao = $servico['duracao_minutos'] !== '' ? (int) $servico['duracao_minutos'] : null;

    if ($servico['nome'] === '' || $preco <= 0) {
        $erro = 'Nome e preco sao obrigatorios.';
    } else {
        try {
            $stmt = $conexao->prepare(
                "INSERT INTO servicos (nome, preco, duracao_minutos)
                 VALUES (:nome, :preco, :duracao_minutos)"
            );
            $stmt->execute([
                'nome' => $servico['nome'],
                'preco' => $preco,
                'duracao_minutos' => $duracao,
            ]);

            header("Location: servicos.php?sucesso=cadastrar");
            exit;
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel cadastrar o servico.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Cadastrar Servico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Cadastrar servico</h1>
            <p class="text-muted mb-0">Novo servico para agendamentos</p>
        </div>
        <a href="servicos.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="preco" class="form-label">Preco</label>
                        <input type="number" name="preco" id="preco" class="form-control" value="<?php echo htmlspecialchars($servico['preco']); ?>" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label for="duracao_minutos" class="form-label">Duracao em minutos</label>
                        <input type="number" name="duracao_minutos" id="duracao_minutos" class="form-control" value="<?php echo htmlspecialchars($servico['duracao_minutos']); ?>" min="0">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar servico</button>
                    <a href="servicos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
