<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'veterinario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoQueixaAgendamento($conexao);

$idPet = (int) ($_GET['id_pet'] ?? 0);
$busca = trim($_GET['busca'] ?? '');
$resultados = [];
$consultas = [];
$petSelecionado = null;

if ($busca !== '') {
    $termo = '%' . $busca . '%';
    $stmt = $conexao->prepare(
        "SELECT p.id, p.nome, p.especie, p.raca, p.sexo, c.nome AS cliente_nome, c.cpf,
                COUNT(co.id) AS total_consultas,
                MAX(co.data_consulta) AS ultima_consulta
         FROM pets p
         INNER JOIN clientes c ON c.id = p.id_cliente
         LEFT JOIN consultas co ON co.id_pet = p.id
         WHERE p.nome LIKE :termo
            OR p.especie LIKE :termo
            OR p.raca LIKE :termo
            OR c.nome LIKE :termo
            OR c.cpf LIKE :termo
         GROUP BY p.id, p.nome, p.especie, p.raca, p.sexo, c.nome, c.cpf
         ORDER BY p.nome
         LIMIT 30"
    );
    $stmt->execute(['termo' => $termo]);
    $resultados = $stmt->fetchAll();
}

if ($idPet > 0) {
    $stmt = $conexao->prepare(
        "SELECT p.nome AS pet_nome, p.especie, p.raca, p.sexo, c.nome AS cliente_nome, c.cpf AS cliente_cpf
         FROM pets p
         INNER JOIN clientes c ON c.id = p.id_cliente
         WHERE p.id = :id"
    );
    $stmt->execute(['id' => $idPet]);
    $petSelecionado = $stmt->fetch();

    $stmt = $conexao->prepare(
        "SELECT co.id, co.data_consulta, co.queixa_principal, co.diagnostico, co.prescricao, f.nome AS veterinario_nome
         FROM consultas co
         INNER JOIN funcionarios f ON f.id = co.id_funcionario
         WHERE co.id_pet = :id_pet
         ORDER BY co.data_consulta DESC"
    );
    $stmt->execute(['id_pet' => $idPet]);
    $consultas = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Prontuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Prontuario</h1>
            <p class="text-muted mb-0">Historico medico dos pets</p>
        </div>
        <?php if (usuarioPode(['veterinario'])): ?>
            <a href="prontuario_cadastrar.php" class="btn btn-primary">Registrar atendimento</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">Atendimento registrado com sucesso.</div>
    <?php endif; ?>

    <form method="GET" class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <label for="busca" class="form-label">Buscar prontuario</label>
            <div class="row g-2">
                <div class="col-md-9">
                    <input type="search" name="busca" id="busca" class="form-control" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome do pet, tutor, CPF, especie ou raca">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary w-100">Buscar</button>
                </div>
            </div>
        </div>
    </form>

    <?php if ($busca !== ''): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Resultados da busca</h2>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Especie/Raca</th>
                                <th>Sexo</th>
                                <th>Tutor</th>
                                <th>CPF</th>
                                <th>Consultas</th>
                                <th>Ultima consulta</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($resultados) === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Nenhum pet encontrado.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($resultados as $pet): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pet['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['especie'] . ' ' . ($pet['raca'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($pet['sexo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($pet['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['cpf']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['total_consultas']); ?></td>
                                    <td><?php echo $pet['ultima_consulta'] ? htmlspecialchars(date('d/m/Y', strtotime($pet['ultima_consulta']))) : ''; ?></td>
                                    <td class="text-end">
                                        <a href="prontuario.php?id_pet=<?php echo $pet['id']; ?>&busca=<?php echo urlencode($busca); ?>" class="btn btn-sm btn-outline-primary">Abrir prontuario</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($petSelecionado): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5 mb-1"><?php echo htmlspecialchars($petSelecionado['pet_nome']); ?></h2>
                <p class="text-muted mb-4">
                    <?php echo htmlspecialchars($petSelecionado['especie'] . ' ' . ($petSelecionado['raca'] ?? '')); ?>
                    | Sexo: <?php echo htmlspecialchars($petSelecionado['sexo'] ?? ''); ?>
                    | Tutor: <?php echo htmlspecialchars($petSelecionado['cliente_nome']); ?>
                    | CPF: <?php echo htmlspecialchars($petSelecionado['cliente_cpf']); ?>
                </p>

                <?php if (count($consultas) === 0): ?>
                    <p class="text-muted mb-0">Nenhum atendimento registrado para este pet.</p>
                <?php endif; ?>

                <?php foreach ($consultas as $consulta): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($consulta['data_consulta']))); ?></strong>
                            <span class="text-muted">Veterinario: <?php echo htmlspecialchars($consulta['veterinario_nome']); ?></span>
                        </div>
                        <?php if (!empty($consulta['queixa_principal'])): ?>
                            <p class="mb-2"><strong>Queixa principal:</strong> <?php echo nl2br(htmlspecialchars($consulta['queixa_principal'])); ?></p>
                        <?php endif; ?>
                        <p class="mb-2"><strong>Diagnostico:</strong> <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?? '')); ?></p>
                        <p class="mb-0"><strong>Prescricao:</strong> <?php echo nl2br(htmlspecialchars($consulta['prescricao'] ?? '')); ?></p>
                        <div class="mt-3">
                            <a href="receita_imprimir.php?id=<?php echo $consulta['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Receita/PDF</a>
                            <a href="atestado_imprimir.php?id=<?php echo $consulta['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Atestado</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
