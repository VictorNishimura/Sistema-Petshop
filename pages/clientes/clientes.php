<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
garantirCampoFotoCliente($conexao);

$podeEditar = usuarioPode(['admin', 'funcionario']);
$podeExcluir = usuarioPode(['admin']);

$stmt = $conexao->query("SELECT id, nome, cpf, telefone, email, endereco, foto_perfil, data_cadastro FROM clientes ORDER BY nome");
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Clientes</h1>
            <p class="text-muted mb-0">Tutores cadastrados no sistema</p>
        </div>
        <?php if ($podeEditar): ?>
            <a href="cliente_cadastrar.php" class="btn btn-primary">Cadastrar cliente</a>
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
                            <th>Foto</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Endereco</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhum cliente cadastrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                <td>
                                    <img
                                        src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($cliente['foto_perfil'] ?? null))); ?>"
                                        alt="Foto de <?php echo htmlspecialchars($cliente['nome']); ?>"
                                        class="rounded-circle object-fit-cover"
                                        width="48"
                                        height="48"
                                    >
                                </td>
                                <td><?php echo htmlspecialchars($cliente['cpf']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?></td>
                                <td class="text-end">
                                    <?php if ($podeEditar): ?>
                                        <a href="cliente_editar.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <?php endif; ?>

                                    <?php if ($podeExcluir): ?>
                                        <a href="cliente_excluir.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-outline-danger">Excluir</a>
                                    <?php endif; ?>

                                    <?php if (!$podeEditar && !$podeExcluir): ?>
                                        <span class="text-muted">Visualizar</span>
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
