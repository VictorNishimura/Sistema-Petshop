<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/servico.php';
garantirCampoCategoriaServico($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: servicos.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, categoria, preco, duracao_minutos FROM servicos WHERE id = :id");
$stmt->execute(['id' => $id]);
$servico = $stmt->fetch();

if (!$servico) {
    header("Location: servicos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Visualizar Servico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?php echo htmlspecialchars($servico['nome']); ?></h1>
            <p class="text-muted mb-0">Dados do servico</p>
        </div>
        <a href="servicos.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <p class="mb-2"><strong>Preco:</strong> R$ <?php echo htmlspecialchars(number_format((float) $servico['preco'], 2, ',', '.')); ?></p>
            <p class="mb-2"><strong>Sessao:</strong> <?php echo htmlspecialchars($servico['categoria'] ?? ''); ?></p>
            <p class="mb-0"><strong>Duracao:</strong> <?php echo $servico['duracao_minutos'] !== null ? htmlspecialchars($servico['duracao_minutos']) . ' min' : 'Nao informada'; ?></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
