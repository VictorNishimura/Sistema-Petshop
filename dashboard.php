<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

require_once __DIR__ . '/config/conexao.php';

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
    ['icone' => '👥', 'titulo' => 'Total de clientes', 'valor' => $totalClientes, 'cor' => 'primary'],
    ['icone' => '🐶', 'titulo' => 'Total de pets cadastrados', 'valor' => $totalPets, 'cor' => 'success'],
    ['icone' => '📅', 'titulo' => 'Agendamentos do dia', 'valor' => $agendamentosDia, 'cor' => 'warning'],
    ['icone' => '🩺', 'titulo' => 'Consultas marcadas', 'valor' => $consultasMarcadas, 'cor' => 'danger'],
    ['icone' => '✂️', 'titulo' => 'Banhos/Tosas agendados', 'valor' => $banhosTosasAgendados, 'cor' => 'info'],
    ['icone' => '👨‍💼', 'titulo' => 'Funcionários cadastrados', 'valor' => $funcionariosCadastrados, 'cor' => 'secondary'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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
                            <span class="badge text-bg-<?php echo $card['cor']; ?> fs-4">
                                <?php echo $card['icone']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
