<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoFuncionarioAgendamento($conexao);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: agendamentos.php");
    exit;
}

$stmt = $conexao->prepare(
    "SELECT a.*, p.nome AS pet_nome, s.nome AS servico_nome
     FROM agendamentos a
     INNER JOIN pets p ON p.id = a.id_pet
     INNER JOIN servicos s ON s.id = a.id_servico
     WHERE a.id = :id"
);
$stmt->execute(['id' => $id]);
$agendamento = $stmt->fetch();

if (!$agendamento || $agendamento['status'] !== 'Agendado') {
    header("Location: agendamentos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'Cancelado' WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: agendamentos.php?data=" . date('Y-m-d', strtotime($agendamento['data_hora'])) . "&sucesso=cancelar");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Cancelar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="mb-3">Cancelar agendamento</h1>
            <p>Tem certeza que deseja cancelar o agendamento de <strong><?php echo htmlspecialchars($agendamento['pet_nome']); ?></strong>?</p>
            <p class="text-muted"><?php echo htmlspecialchars($agendamento['servico_nome']); ?> em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($agendamento['data_hora']))); ?></p>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $agendamento['id']; ?>">
                <button type="submit" class="btn btn-danger">Confirmar cancelamento</button>
                <a href="agendamentos.php" class="btn btn-outline-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
