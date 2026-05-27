<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario', 'veterinario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoFuncionarioAgendamento($conexao);
garantirCampoQueixaAgendamento($conexao);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: agendamentos.php");
    exit;
}

$stmt = $conexao->prepare(
    "SELECT a.*, p.nome AS pet_nome, s.nome AS servico_nome, f.nome AS funcionario_nome
     FROM agendamentos a
     INNER JOIN pets p ON p.id = a.id_pet
     INNER JOIN servicos s ON s.id = a.id_servico
     LEFT JOIN funcionarios f ON f.id = a.id_funcionario
     WHERE a.id = :id"
);
$stmt->execute(['id' => $id]);
$agendamento = $stmt->fetch();

if (!$agendamento || $agendamento['status'] !== 'Agendado') {
    header("Location: agendamentos.php");
    exit;
}

$ehConsulta = servicoEhConsulta($agendamento['servico_nome']);
$idFuncionarioLogado = (int) ($_SESSION['usuario_id_funcionario'] ?? 0);
$veterinarioPodeConcluir = usuarioPode(['veterinario'])
    && $ehConsulta
    && (int) $agendamento['id_funcionario'] === $idFuncionarioLogado;

if (usuarioPode(['veterinario']) && !$veterinarioPodeConcluir) {
    header("Location: agendamentos.php");
    exit;
}

$erro = '';
$diagnostico = '';
$prescricao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $prescricao = trim($_POST['prescricao'] ?? '');

    if ($ehConsulta && $diagnostico === '') {
        $erro = 'Informe o diagnostico para registrar a consulta.';
    } else {
        try {
            $conexao->beginTransaction();

            if ($ehConsulta) {
                $stmt = $conexao->prepare(
                    "INSERT INTO consultas (id_pet, id_funcionario, data_consulta, diagnostico, prescricao)
                     VALUES (:id_pet, :id_funcionario, :data_consulta, :diagnostico, :prescricao)"
                );
                $stmt->execute([
                    'id_pet' => $agendamento['id_pet'],
                    'id_funcionario' => $agendamento['id_funcionario'],
                    'data_consulta' => $agendamento['data_hora'],
                    'diagnostico' => $diagnostico,
                    'prescricao' => $prescricao !== '' ? $prescricao : null,
                ]);
                $idConsulta = (int) $conexao->lastInsertId();

                if (!empty($agendamento['queixa_principal'])) {
                    $stmtQueixa = $conexao->prepare("UPDATE consultas SET queixa_principal = :queixa_principal WHERE id = :id");
                    $stmtQueixa->execute([
                        'queixa_principal' => $agendamento['queixa_principal'],
                        'id' => $idConsulta,
                    ]);
                }
            }

            $stmt = $conexao->prepare("UPDATE agendamentos SET status = 'Concluído' WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $conexao->commit();

            header("Location: agendamentos.php?data=" . date('Y-m-d', strtotime($agendamento['data_hora'])) . "&sucesso=concluir");
            exit;
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            $erro = 'Nao foi possivel concluir o agendamento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Concluir Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="mb-3">Concluir agendamento</h1>
            <p class="mb-1"><strong>Pet:</strong> <?php echo htmlspecialchars($agendamento['pet_nome']); ?></p>
            <p class="mb-1"><strong>Servico:</strong> <?php echo htmlspecialchars($agendamento['servico_nome']); ?></p>
            <p class="text-muted"><strong>Prestador:</strong> <?php echo htmlspecialchars($agendamento['funcionario_nome'] ?? ''); ?></p>
            <?php if (!empty($agendamento['queixa_principal'])): ?>
                <div class="alert alert-warning">
                    <strong>Queixa principal:</strong><br>
                    <?php echo nl2br(htmlspecialchars($agendamento['queixa_principal'])); ?>
                </div>
            <?php endif; ?>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $agendamento['id']; ?>">

                <?php if ($ehConsulta): ?>
                    <div class="alert alert-info">Este servico sera registrado no historico de consultas do pet.</div>
                    <div class="mb-3">
                        <label for="diagnostico" class="form-label">Diagnostico</label>
                        <textarea name="diagnostico" id="diagnostico" class="form-control" rows="4" required><?php echo htmlspecialchars($diagnostico); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="prescricao" class="form-label">Prescricao</label>
                        <textarea name="prescricao" id="prescricao" class="form-control" rows="4"><?php echo htmlspecialchars($prescricao); ?></textarea>
                    </div>
                <?php else: ?>
                    <p>Confirme a conclusao do servico realizado.</p>
                <?php endif; ?>

                <button type="submit" class="btn btn-success">Marcar como concluido</button>
                <a href="agendamentos.php" class="btn btn-outline-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
