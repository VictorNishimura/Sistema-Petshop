<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
garantirCampoFotoCliente($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: clientes.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, cpf, telefone, email, endereco, foto_perfil FROM clientes WHERE id = :id");
$stmt->execute(['id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header("Location: clientes.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente['nome'] = trim($_POST['nome'] ?? '');
    $cliente['cpf'] = trim($_POST['cpf'] ?? '');
    $cliente['telefone'] = trim($_POST['telefone'] ?? '');
    $cliente['email'] = trim($_POST['email'] ?? '');
    $cliente['endereco'] = trim($_POST['endereco'] ?? '');

    if ($cliente['nome'] === '' || $cliente['cpf'] === '') {
        $erro = 'Nome e CPF sao obrigatorios.';
    } else {
        try {
            $cliente['foto_perfil'] = salvarFotoCliente($_FILES['foto_perfil'] ?? [], $cliente['foto_perfil'] ?? null);

            $sql = "UPDATE clientes
                    SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email, endereco = :endereco, foto_perfil = :foto_perfil
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'nome' => $cliente['nome'],
                'cpf' => $cliente['cpf'],
                'telefone' => $cliente['telefone'],
                'email' => $cliente['email'],
                'endereco' => $cliente['endereco'],
                'foto_perfil' => $cliente['foto_perfil'],
                'id' => $id,
            ]);

            header("Location: clientes.php?sucesso=editar");
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel atualizar o cliente. Verifique se o CPF ja esta cadastrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Editar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Editar cliente</h1>
            <p class="text-muted mb-0">Atualize os dados do tutor</p>
        </div>
        <a href="clientes.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label d-block">Foto atual</label>
                        <img
                            src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($cliente['foto_perfil'] ?? null))); ?>"
                            alt="Foto de <?php echo htmlspecialchars($cliente['nome']); ?>"
                            class="rounded-circle object-fit-cover mb-3"
                            width="96"
                            height="96"
                        >
                        <label for="foto_perfil" class="form-label">Trocar foto de perfil</label>
                        <input type="file" name="foto_perfil" id="foto_perfil" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Deixe em branco para manter a foto atual. Formatos aceitos: JPG, PNG ou WEBP. Tamanho maximo: 2 MB.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" name="cpf" id="cpf" class="form-control" value="<?php echo htmlspecialchars($cliente['cpf']); ?>" maxlength="14" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="endereco" class="form-label">Endereco</label>
                        <textarea name="endereco" id="endereco" class="form-control" rows="3"><?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                    <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
