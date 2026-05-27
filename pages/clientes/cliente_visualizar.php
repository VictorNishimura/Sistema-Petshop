<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/cliente.php';
garantirCampoFotoCliente($conexao);
garantirCamposEnderecoCliente($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: clientes.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, cpf, telefone, email, endereco, rua, numero, bairro, cidade, uf, cep, foto_perfil, data_cadastro FROM clientes WHERE id = :id");
$stmt->execute(['id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: clientes.php");
    exit;
}

$stmt = $conexao->prepare("SELECT nome, especie, raca, status_adocao FROM pets WHERE id_cliente = :id ORDER BY nome");
$stmt->execute(['id' => $id]);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Visualizar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h1>
            <p class="text-muted mb-0">Dados do tutor</p>
        </div>
        <a href="clientes.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <img src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($cliente['foto_perfil'] ?? null))); ?>" class="rounded-circle object-fit-cover mb-3" width="120" height="120" alt="Foto de <?php echo htmlspecialchars($cliente['nome']); ?>">
                    <p class="mb-2"><strong>CPF:</strong> <?php echo htmlspecialchars($cliente['cpf']); ?></p>
                    <p class="mb-2"><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?></p>
                    <p class="mb-2"><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Rua:</strong> <?php echo htmlspecialchars($cliente['rua'] ?? ''); ?>, <?php echo htmlspecialchars($cliente['numero'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Bairro:</strong> <?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?></p>
                    <p class="mb-2"><strong>Cidade/UF:</strong> <?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?> - <?php echo htmlspecialchars($cliente['uf'] ?? ''); ?></p>
                    <p class="mb-0"><strong>CEP:</strong> <?php echo htmlspecialchars($cliente['cep'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Pets vinculados</h2>
                    <?php if (count($pets) === 0): ?>
                        <p class="text-muted mb-0">Nenhum pet cadastrado para este tutor.</p>
                    <?php endif; ?>
                    <?php foreach ($pets as $pet): ?>
                        <div class="border-bottom py-2">
                            <strong><?php echo htmlspecialchars($pet['nome']); ?></strong>
                            <span class="text-muted"><?php echo htmlspecialchars($pet['especie']); ?> <?php echo htmlspecialchars($pet['raca'] ?? ''); ?></span>
                            <?php if ((int) $pet['status_adocao'] === 1): ?>
                                <span class="badge text-bg-success">Disponivel para adocao</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
