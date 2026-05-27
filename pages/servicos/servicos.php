<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';

$podeGerenciar = usuarioPode(['admin']);
$stmt = $conexao->query("SELECT id, nome, preco, duracao_minutos FROM servicos ORDER BY nome");
$servicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Servicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Servicos</h1>
            <p class="text-muted mb-0">Servicos disponiveis para agendamento</p>
        </div>
        <?php if ($podeGerenciar): ?>
            <a href="servico_cadastrar.php" class="btn btn-primary">Cadastrar servico</a>
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
                            <th>Preco</th>
                            <th>Duracao</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($servicos) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhum servico cadastrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                                <td>R$ <?php echo htmlspecialchars(number_format((float) $servico['preco'], 2, ',', '.')); ?></td>
                                <td>
                                    <?php echo $servico['duracao_minutos'] !== null ? htmlspecialchars($servico['duracao_minutos']) . ' min' : ''; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($podeGerenciar): ?>
                                        <a href="servico_editar.php?id=<?php echo $servico['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <a href="servico_excluir.php?id=<?php echo $servico['id']; ?>" class="btn btn-sm btn-outline-danger">Excluir</a>
                                    <?php else: ?>
                                        <a href="servico_visualizar.php?id=<?php echo $servico['id']; ?>" class="btn btn-sm btn-outline-secondary">Visualizar</a>
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
