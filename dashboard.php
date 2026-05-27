<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/agendamento.php';
garantirCampoFuncionarioAgendamento($conexao);
garantirCampoQueixaAgendamento($conexao);

function contarRegistros(PDO $conexao, string $sql): int
{
    $stmt = $conexao->query($sql);
    return (int) $stmt->fetchColumn();
}

$totalClientes = contarRegistros($conexao, "SELECT COUNT(*) FROM clientes");
$totalPets = contarRegistros($conexao, "SELECT COUNT(*) FROM pets");
$agendamentosDia = contarRegistros($conexao, "SELECT COUNT(*) FROM agendamentos WHERE DATE(data_hora) = CURDATE()");
$consultasMarcadas = contarRegistros($conexao, "SELECT COUNT(*) FROM consultas");
$banhosTosasAgendados = contarRegistros(
    $conexao,
    "SELECT COUNT(*)
     FROM agendamentos a
     INNER JOIN servicos s ON s.id = a.id_servico
     WHERE a.status = 'Agendado'
       AND (LOWER(s.nome) LIKE '%banho%' OR LOWER(s.nome) LIKE '%tosa%')"
);
$funcionariosCadastrados = contarRegistros($conexao, "SELECT COUNT(*) FROM funcionarios");

$cardsDashboard = [
    ['icone' => 'Clientes', 'titulo' => 'Total de clientes', 'valor' => $totalClientes, 'cor' => 'primary'],
    ['icone' => 'Pets', 'titulo' => 'Total de pets cadastrados', 'valor' => $totalPets, 'cor' => 'success'],
    ['icone' => 'Hoje', 'titulo' => 'Agendamentos do dia', 'valor' => $agendamentosDia, 'cor' => 'warning'],
    ['icone' => 'Vet', 'titulo' => 'Consultas marcadas', 'valor' => $consultasMarcadas, 'cor' => 'danger'],
    ['icone' => 'Tosa', 'titulo' => 'Banhos/Tosas agendados', 'valor' => $banhosTosasAgendados, 'cor' => 'info'],
    ['icone' => 'Equipe', 'titulo' => 'Funcionarios cadastrados', 'valor' => $funcionariosCadastrados, 'cor' => 'secondary'],
];

$proximasConsultasVeterinario = [];
$veterinarioVinculado = true;

if (usuarioPode(['veterinario'])) {
    $idFuncionarioLogado = (int) ($_SESSION['usuario_id_funcionario'] ?? 0);
    $veterinarioVinculado = $idFuncionarioLogado > 0;

    if ($idFuncionarioLogado > 0) {
        $stmt = $conexao->prepare(
            "SELECT a.id, a.data_hora, a.queixa_principal,
                    p.nome AS pet_nome,
                    COALESCE(c.nome, 'Sem tutor vinculado') AS cliente_nome,
                    s.nome AS servico_nome
             FROM agendamentos a
             INNER JOIN pets p ON p.id = a.id_pet
             LEFT JOIN clientes c ON c.id = p.id_cliente
             INNER JOIN servicos s ON s.id = a.id_servico
             WHERE a.id_funcionario = :id_funcionario
               AND a.status = 'Agendado'
               AND a.data_hora >= NOW()
               AND (LOWER(s.nome) LIKE '%consulta%' OR LOWER(s.nome) LIKE '%veterin%')
             ORDER BY a.data_hora
             LIMIT 8"
        );
        $stmt->execute(['id_funcionario' => $idFuncionarioLogado]);
        $proximasConsultasVeterinario = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Painel de Controle</h1>
            <p class="text-muted mb-0">Resumo geral do PetLife</p>
        </div>
        <a href="<?php echo caminhoApp('pages/clientes/clientes.php'); ?>" class="btn btn-primary">Ver clientes</a>
    </div>

    <div class="row g-4 align-items-start">
        <div class="<?php echo usuarioPode(['veterinario']) ? 'col-12 col-xl-8' : 'col-12'; ?>">
            <div class="row g-4">
                <?php foreach ($cardsDashboard as $card): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($card['titulo']); ?></p>
                                        <h2 class="fw-bold mb-0"><?php echo $card['valor']; ?></h2>
                                    </div>
                                    <span class="badge text-bg-<?php echo $card['cor']; ?>">
                                        <?php echo htmlspecialchars($card['icone']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (usuarioPode(['veterinario'])): ?>
            <div class="col-12 col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                            <h2 class="h5 mb-1">Proximas consultas</h2>
                            <p class="text-muted mb-0">Sua agenda veterinaria</p>
                            </div>
                            <a href="<?php echo caminhoApp('pages/agendamentos/agendamentos.php'); ?>" class="btn btn-sm btn-outline-primary">Agenda</a>
                        </div>

                        <?php if (!$veterinarioVinculado): ?>
                            <p class="text-muted mb-0">Sua conta de veterinario ainda nao esta vinculada ao cadastro de funcionario.</p>
                        <?php elseif (count($proximasConsultasVeterinario) === 0): ?>
                            <p class="text-muted mb-0">Nenhuma consulta futura encontrada.</p>
                        <?php endif; ?>

                        <?php foreach ($proximasConsultasVeterinario as $consulta): ?>
                            <div class="border rounded p-3 mb-2">
                                <strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($consulta['data_hora']))); ?></strong>
                                <div><?php echo htmlspecialchars($consulta['pet_nome']); ?></div>
                                <div class="text-muted small">Tutor: <?php echo htmlspecialchars($consulta['cliente_nome']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($consulta['servico_nome']); ?></div>
                                <?php if (!empty($consulta['queixa_principal'])): ?>
                                    <div class="small mt-2">
                                        <span class="text-muted">Queixa:</span>
                                        <?php echo htmlspecialchars($consulta['queixa_principal']); ?>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo caminhoApp('pages/agendamentos/agendamento_concluir.php?id=' . $consulta['id']); ?>" class="btn btn-sm btn-success mt-2">Diagnosticar</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
