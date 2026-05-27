<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/usuario_funcionario.php';
garantirCampoFotoTabela($conexao, 'funcionarios');
garantirCampoUsuarioFuncionario($conexao);

$stmt = $conexao->query(
    "SELECT f.id, f.nome, f.cargo, f.crmv, f.telefone, f.data_admissao, f.foto_perfil, u.nivel AS usuario_nivel
     FROM funcionarios f
     LEFT JOIN usuarios u ON u.id_funcionario = f.id
     ORDER BY f.nome"
);
$funcionarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Funcionarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Funcionarios</h1>
            <p class="text-muted mb-0">Equipe cadastrada no petshop</p>
        </div>
        <a href="funcionario_cadastrar.php" class="btn btn-primary">Cadastrar funcionario</a>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">Operacao realizada com sucesso.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Foto</th>
                            <th>Cargo</th>
                            <th>CRMV</th>
                            <th>Telefone</th>
                            <th>Admissao</th>
                            <th>Acesso</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($funcionarios) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum funcionario cadastrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($funcionarios as $funcionario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($funcionario['nome']); ?></td>
                                <td>
                                    <img
                                        src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($funcionario['foto_perfil'] ?? null))); ?>"
                                        alt="Foto de <?php echo htmlspecialchars($funcionario['nome']); ?>"
                                        class="rounded-circle object-fit-cover"
                                        width="48"
                                        height="48"
                                    >
                                </td>
                                <td><?php echo htmlspecialchars($funcionario['cargo']); ?></td>
                                <td><?php echo htmlspecialchars($funcionario['crmv'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($funcionario['telefone'] ?? ''); ?></td>
                                <td>
                                    <?php echo $funcionario['data_admissao'] ? htmlspecialchars(date('d/m/Y', strtotime($funcionario['data_admissao']))) : ''; ?>
                                </td>
                                <td>
                                    <?php if ($funcionario['usuario_nivel']): ?>
                                        <span class="badge text-bg-success"><?php echo htmlspecialchars($funcionario['usuario_nivel']); ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Sem acesso</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="funcionario_editar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="funcionario_excluir.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-sm btn-outline-danger">Excluir</a>
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
