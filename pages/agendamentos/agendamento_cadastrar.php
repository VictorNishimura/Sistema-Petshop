<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoFuncionarioAgendamento($conexao);

$pets = $conexao->query(
    "SELECT p.id, p.nome, c.nome AS cliente_nome
     FROM pets p
     INNER JOIN clientes c ON c.id = p.id_cliente
     ORDER BY p.nome"
)->fetchAll();
$servicos = $conexao->query("SELECT id, nome, preco, duracao_minutos FROM servicos ORDER BY nome")->fetchAll();
$funcionarios = $conexao->query("SELECT id, nome, cargo FROM funcionarios ORDER BY nome")->fetchAll();

$erro = '';
$agendamento = [
    'id_pet' => '',
    'id_servico' => '',
    'id_funcionario' => '',
    'data_hora' => date('Y-m-d\TH:i'),
    'observacoes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento['id_pet'] = (int) ($_POST['id_pet'] ?? 0);
    $agendamento['id_servico'] = (int) ($_POST['id_servico'] ?? 0);
    $agendamento['id_funcionario'] = (int) ($_POST['id_funcionario'] ?? 0);
    $agendamento['data_hora'] = trim($_POST['data_hora'] ?? '');
    $agendamento['observacoes'] = trim($_POST['observacoes'] ?? '');
    $dataBanco = $agendamento['data_hora'] !== '' ? dataHoraBanco($agendamento['data_hora']) : '';

    if ($agendamento['id_pet'] <= 0 || $agendamento['id_servico'] <= 0 || $agendamento['id_funcionario'] <= 0 || $dataBanco === '') {
        $erro = 'Pet, servico, prestador e data/hora sao obrigatorios.';
    } elseif (existeConflitoAgenda($conexao, $agendamento['id_funcionario'], $agendamento['id_servico'], $dataBanco)) {
        $erro = 'Este prestador ja possui outro agendamento neste horario.';
    } else {
        try {
            $stmt = $conexao->prepare(
                "INSERT INTO agendamentos (id_pet, id_servico, id_funcionario, data_hora, status, observacoes)
                 VALUES (:id_pet, :id_servico, :id_funcionario, :data_hora, 'Agendado', :observacoes)"
            );
            $stmt->execute([
                'id_pet' => $agendamento['id_pet'],
                'id_servico' => $agendamento['id_servico'],
                'id_funcionario' => $agendamento['id_funcionario'],
                'data_hora' => $dataBanco,
                'observacoes' => $agendamento['observacoes'] !== '' ? $agendamento['observacoes'] : null,
            ]);

            header("Location: agendamentos.php?data=" . date('Y-m-d', strtotime($dataBanco)) . "&sucesso=cadastrar");
            exit;
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel criar o agendamento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Criar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Criar agendamento</h1>
            <p class="text-muted mb-0">Selecione pet, servico, prestador e horario</p>
        </div>
        <a href="agendamentos.php" class="btn btn-outline-secondary">Voltar</a>
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
                                <option value="<?php echo $pet['id']; ?>" <?php echo (int) $agendamento['id_pet'] === (int) $pet['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pet['nome'] . ' - ' . $pet['cliente_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="id_servico" class="form-label">Servico</label>
                        <select name="id_servico" id="id_servico" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id']; ?>" <?php echo (int) $agendamento['id_servico'] === (int) $servico['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($servico['nome'] . ' - R$ ' . number_format((float) $servico['preco'], 2, ',', '.')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="id_funcionario" class="form-label">Prestador</label>
                        <select name="id_funcionario" id="id_funcionario" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <option value="<?php echo $funcionario['id']; ?>" <?php echo (int) $agendamento['id_funcionario'] === (int) $funcionario['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($funcionario['nome'] . ' - ' . $funcionario['cargo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_hora" class="form-label">Data e hora</label>
                        <input type="datetime-local" name="data_hora" id="data_hora" class="form-control" value="<?php echo htmlspecialchars($agendamento['data_hora']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="observacoes" class="form-label">Observacoes</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($agendamento['observacoes']); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar agendamento</button>
                    <a href="agendamentos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
