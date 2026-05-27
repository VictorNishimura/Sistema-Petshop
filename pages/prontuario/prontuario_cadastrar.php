<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['veterinario']);

require_once __DIR__ . '/../../config/conexao.php';

$idFuncionario = (int) ($_SESSION['usuario_id_funcionario'] ?? 0);

$pets = $conexao->query(
    "SELECT p.id, p.nome, c.nome AS cliente_nome
     FROM pets p
     INNER JOIN clientes c ON c.id = p.id_cliente
     ORDER BY p.nome"
)->fetchAll();

$erro = '';
$atendimento = [
    'id_pet' => (int) ($_GET['id_pet'] ?? 0),
    'data_consulta' => date('Y-m-d\TH:i'),
    'diagnostico' => '',
    'prescricao' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atendimento['id_pet'] = (int) ($_POST['id_pet'] ?? 0);
    $atendimento['data_consulta'] = trim($_POST['data_consulta'] ?? '');
    $atendimento['diagnostico'] = trim($_POST['diagnostico'] ?? '');
    $atendimento['prescricao'] = trim($_POST['prescricao'] ?? '');

    if ($idFuncionario <= 0) {
        $erro = 'Seu usuario veterinario nao esta vinculado a um funcionario.';
    } elseif ($atendimento['id_pet'] <= 0 || $atendimento['data_consulta'] === '' || $atendimento['diagnostico'] === '') {
        $erro = 'Pet, data e diagnostico sao obrigatorios.';
    } else {
        try {
            $stmt = $conexao->prepare(
                "INSERT INTO consultas (id_pet, id_funcionario, data_consulta, diagnostico, prescricao)
                 VALUES (:id_pet, :id_funcionario, :data_consulta, :diagnostico, :prescricao)"
            );
            $stmt->execute([
                'id_pet' => $atendimento['id_pet'],
                'id_funcionario' => $idFuncionario,
                'data_consulta' => date('Y-m-d H:i:s', strtotime($atendimento['data_consulta'])),
                'diagnostico' => $atendimento['diagnostico'],
                'prescricao' => $atendimento['prescricao'] !== '' ? $atendimento['prescricao'] : null,
            ]);

            header("Location: prontuario.php?id_pet=" . $atendimento['id_pet'] . "&sucesso=cadastrar");
            exit;
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel registrar o atendimento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Registrar Atendimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Registrar atendimento</h1>
            <p class="text-muted mb-0">Novo registro medico no prontuario</p>
        </div>
        <a href="prontuario.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="id_pet" class="form-label">Pet</label>
                        <select name="id_pet" id="id_pet" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?php echo $pet['id']; ?>" <?php echo (int) $atendimento['id_pet'] === (int) $pet['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pet['nome'] . ' - Tutor: ' . $pet['cliente_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_consulta" class="form-label">Data e hora</label>
                        <input type="datetime-local" name="data_consulta" id="data_consulta" class="form-control" value="<?php echo htmlspecialchars($atendimento['data_consulta']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="diagnostico" class="form-label">Diagnostico</label>
                        <textarea name="diagnostico" id="diagnostico" class="form-control" rows="4" required><?php echo htmlspecialchars($atendimento['diagnostico']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="prescricao" class="form-label">Prescricao</label>
                        <textarea name="prescricao" id="prescricao" class="form-control" rows="4"><?php echo htmlspecialchars($atendimento['prescricao']); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar atendimento</button>
                    <a href="prontuario.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
