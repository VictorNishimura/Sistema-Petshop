<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoFuncionarioAgendamento($conexao);

$podeGerenciar = usuarioPode(['admin', 'funcionario']);
$idFuncionarioLogado = (int) ($_SESSION['usuario_id_funcionario'] ?? 0);
$data = $_GET['data'] ?? date('Y-m-d');

$stmt = $conexao->prepare(
    "SELECT a.id, a.id_pet, a.id_funcionario, a.data_hora, a.status, a.observacoes,
            p.nome AS pet_nome,
            c.nome AS cliente_nome,
            s.nome AS servico_nome,
            s.duracao_minutos,
            f.nome AS funcionario_nome
     FROM agendamentos a
     INNER JOIN pets p ON p.id = a.id_pet
     INNER JOIN clientes c ON c.id = p.id_cliente
     INNER JOIN servicos s ON s.id = a.id_servico
     LEFT JOIN funcionarios f ON f.id = a.id_funcionario
     WHERE DATE(a.data_hora) = :data
     ORDER BY a.data_hora"
);
$stmt->execute(['data' => $data]);
$agendamentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Agendamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Agendamentos</h1>
            <p class="text-muted mb-0">Agenda diaria de servicos e consultas</p>
        </div>
        <?php if ($podeGerenciar): ?>
            <a href="agendamento_cadastrar.php" class="btn btn-primary">Criar agendamento</a>
        <?php endif; ?>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-auto">
            <input type="date" name="data" class="form-control" value="<?php echo htmlspecialchars($data); ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Ver agenda</button>
        </div>
    </form>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">Operacao realizada com sucesso.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Horario</th>
                            <th>Pet</th>
                            <th>Tutor</th>
                            <th>Servico</th>
                            <th>Prestador</th>
                            <th>Status</th>
                            <th>Observacoes</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($agendamentos) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum agendamento neste dia.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($agendamentos as $agendamento): ?>
                            <?php
                            $ehConsulta = servicoEhConsulta($agendamento['servico_nome']);
                            $veterinarioPodeConcluir = usuarioPode(['veterinario'])
                                && $ehConsulta
                                && (int) $agendamento['id_funcionario'] === $idFuncionarioLogado;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('H:i', strtotime($agendamento['data_hora']))); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['pet_nome']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($agendamento['servico_nome']); ?>
                                    <?php if ($agendamento['duracao_minutos']): ?>
                                        <span class="text-muted">(<?php echo htmlspecialchars($agendamento['duracao_minutos']); ?> min)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($agendamento['funcionario_nome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['status']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['observacoes'] ?? ''); ?></td>
                                <td class="text-end">
                                    <?php if ($podeGerenciar && $agendamento['status'] === 'Agendado'): ?>
                                        <a href="agendamento_editar.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <a href="agendamento_concluir.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-sm btn-outline-success">Concluir</a>
                                        <a href="agendamento_cancelar.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-sm btn-outline-danger">Cancelar</a>
                                    <?php elseif ($veterinarioPodeConcluir && $agendamento['status'] === 'Agendado'): ?>
                                        <a href="agendamento_concluir.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-sm btn-outline-success">Diagnosticar</a>
                                    <?php else: ?>
                                        <a href="<?php echo caminhoApp('pages/pets/pet_perfil.php?id=' . $agendamento['id_pet']); ?>" class="btn btn-sm btn-outline-secondary">Visualizar</a>
                                        <?php if (usuarioPode(['admin']) && $agendamento['status'] === 'Cancelado'): ?>
                                            <a href="agendamento_excluir.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-sm btn-outline-danger">Excluir</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
