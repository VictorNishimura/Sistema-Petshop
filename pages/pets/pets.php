<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';

$podeEditar = usuarioPode(['admin', 'funcionario']);
$podeExcluir = usuarioPode(['admin']);

$sql = "SELECT p.id, p.nome, p.especie, p.raca, p.idade, p.peso, p.status_adocao, c.nome AS cliente_nome
        FROM pets p
        INNER JOIN clientes c ON c.id = p.id_cliente
        ORDER BY p.nome";
$stmt = $conexao->query($sql);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Pets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Pets</h1>
            <p class="text-muted mb-0">Animais cadastrados e pets disponiveis para adocao</p>
        </div>
        <?php if ($podeEditar): ?>
            <a href="pet_cadastrar.php" class="btn btn-primary">Cadastrar pet</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">Operacao realizada com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'permissao'): ?>
        <div class="alert alert-danger">Voce nao tem permissao para acessar essa tela.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Especie</th>
                            <th>Raca</th>
                            <th>Idade</th>
                            <th>Peso</th>
                            <th>Tutor</th>
                            <th>Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pets) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum pet cadastrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($pets as $pet): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pet['nome']); ?></td>
                                <td><?php echo htmlspecialchars($pet['especie']); ?></td>
                                <td><?php echo htmlspecialchars($pet['raca'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($pet['idade'] ?? ''); ?></td>
                                <td><?php echo $pet['peso'] !== null ? htmlspecialchars(number_format((float) $pet['peso'], 2, ',', '.')) . ' kg' : ''; ?></td>
                                <td><?php echo htmlspecialchars($pet['cliente_nome']); ?></td>
                                <td>
                                    <?php if ((int) $pet['status_adocao'] === 1): ?>
                                        <span class="badge text-bg-success">Disponivel para adocao</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Com tutor</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="pet_perfil.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-secondary">Perfil</a>

                                    <?php if ($podeEditar): ?>
                                        <a href="pet_editar.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <a href="pet_excluir.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-danger">Excluir</a>
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
