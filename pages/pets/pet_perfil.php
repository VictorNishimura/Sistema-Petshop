<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/pet.php';
garantirCampoFotoTabela($conexao, 'pets');
garantirCamposDetalhesPet($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: pets.php");
    exit;
}

$sql = "SELECT p.*, c.nome AS cliente_nome, c.cpf AS cliente_cpf, c.telefone AS cliente_telefone, c.email AS cliente_email
        FROM pets p
        INNER JOIN clientes c ON c.id = p.id_cliente
        WHERE p.id = :id";
$stmt = $conexao->prepare($sql);
$stmt->execute(['id' => $id]);
$pet = $stmt->fetch();

if (!$pet) {
    header("Location: pets.php");
    exit;
}

$stmt = $conexao->prepare(
    "SELECT co.data_consulta, co.diagnostico, co.prescricao, f.nome AS funcionario_nome
     FROM consultas co
     INNER JOIN funcionarios f ON f.id = co.id_funcionario
     WHERE co.id_pet = :id
     ORDER BY co.data_consulta DESC"
);
$stmt->execute(['id' => $id]);
$consultas = $stmt->fetchAll();

$stmt = $conexao->prepare(
    "SELECT a.data_hora, a.status, a.observacoes, s.nome AS servico_nome
     FROM agendamentos a
     INNER JOIN servicos s ON s.id = a.id_servico
     WHERE a.id_pet = :id
     ORDER BY a.data_hora DESC"
);
$stmt->execute(['id' => $id]);
$agendamentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Perfil do Pet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?php echo htmlspecialchars($pet['nome']); ?></h1>
            <p class="text-muted mb-0">Historico e dados do pet</p>
        </div>
        <a href="pets.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Dados do pet</h2>
                    <img
                        src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($pet['foto_perfil'] ?? null))); ?>"
                        alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                        class="rounded-circle object-fit-cover mb-3"
                        width="120"
                        height="120"
                    >
                    <p class="mb-2"><strong>Especie:</strong> <?php echo htmlspecialchars($pet['especie']); ?></p>
                    <p class="mb-2"><strong>Raca:</strong> <?php echo htmlspecialchars($pet['raca'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Sexo:</strong> <?php echo htmlspecialchars($pet['sexo'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Nascimento:</strong> <?php echo !empty($pet['data_nascimento']) ? htmlspecialchars(date('d/m/Y', strtotime($pet['data_nascimento']))) : ''; ?></p>
                    <p class="mb-2"><strong>Idade aproximada:</strong> <?php echo htmlspecialchars((string) ($pet['idade'] ?? '')); ?></p>
                    <p class="mb-2"><strong>Pelagem:</strong> <?php echo htmlspecialchars($pet['pelagem'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Peso:</strong> <?php echo $pet['peso'] !== null ? htmlspecialchars(number_format((float) $pet['peso'], 2, ',', '.')) . ' kg' : ''; ?></p>
                    <p class="mb-2"><strong>Vacinacao:</strong> <?php echo (int) ($pet['vacinacao_atualizada'] ?? 0) === 1 ? 'Atualizada' : 'Nao informada/pendente'; ?></p>
                    <p class="mb-2"><strong>Antipulgas/carrapatos:</strong> <?php echo !empty($pet['ultima_aplicacao_parasitas']) ? htmlspecialchars(date('d/m/Y', strtotime($pet['ultima_aplicacao_parasitas']))) : ''; ?></p>
                    <p class="mb-0">
                        <strong>Status:</strong>
                        <?php if ((int) $pet['status_adocao'] === 1): ?>
                            <span class="badge text-bg-success">Disponivel para adocao</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Com tutor</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Tutor vinculado</h2>
                    <p class="mb-2"><strong>Nome:</strong> <?php echo htmlspecialchars($pet['cliente_nome']); ?></p>
                    <p class="mb-2"><strong>CPF:</strong> <?php echo htmlspecialchars($pet['cliente_cpf']); ?></p>
                    <p class="mb-2"><strong>Telefone:</strong> <?php echo htmlspecialchars($pet['cliente_telefone'] ?? ''); ?></p>
                    <p class="mb-0"><strong>E-mail:</strong> <?php echo htmlspecialchars($pet['cliente_email'] ?? ''); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Saude e bem-estar</h2>
                    <p class="mb-2"><strong>Alergias e restricoes:</strong> <?php echo nl2br(htmlspecialchars($pet['alergias_restricoes'] ?? '')); ?></p>
                    <p class="mb-0"><strong>Condicoes especiais:</strong> <?php echo nl2br(htmlspecialchars($pet['condicoes_especiais'] ?? '')); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Comportamento</h2>
                    <p class="mb-2"><strong>Temperamento:</strong> <?php echo htmlspecialchars($pet['temperamento'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Reacao a outros animais:</strong> <?php echo htmlspecialchars($pet['reacao_animais'] ?? ''); ?></p>
                    <p class="mb-0"><strong>Observacoes gerais:</strong> <?php echo nl2br(htmlspecialchars($pet['observacoes_gerais'] ?? '')); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Consultas</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Veterinario</th>
                            <th>Diagnostico</th>
                            <th>Prescricao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($consultas) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhuma consulta registrada.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($consultas as $consulta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($consulta['data_consulta']))); ?></td>
                                <td><?php echo htmlspecialchars($consulta['funcionario_nome']); ?></td>
                                <td><?php echo htmlspecialchars($consulta['diagnostico'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($consulta['prescricao'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4 mb-5">
        <div class="card-body">
            <h2 class="h5 mb-3">Agendamentos</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Servico</th>
                            <th>Status</th>
                            <th>Observacoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($agendamentos) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum agendamento registrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($agendamento['data_hora']))); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['status']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['observacoes'] ?? ''); ?></td>
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
