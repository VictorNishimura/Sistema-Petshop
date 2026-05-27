<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: pets.php");
    exit;
}

$clientes = $conexao->query("SELECT id, nome, cpf FROM clientes ORDER BY nome")->fetchAll();
$stmt = $conexao->prepare("SELECT id, id_cliente, nome, especie, raca, idade, peso, status_adocao FROM pets WHERE id = :id");
$stmt->execute(['id' => $id]);
$pet = $stmt->fetch();

if (!$pet) {
    header("Location: pets.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet['id_cliente'] = (int) ($_POST['id_cliente'] ?? 0);
    $pet['nome'] = trim($_POST['nome'] ?? '');
    $pet['especie'] = trim($_POST['especie'] ?? '');
    $pet['raca'] = trim($_POST['raca'] ?? '');
    $pet['idade'] = $_POST['idade'] !== '' ? (int) $_POST['idade'] : null;
    $pet['peso'] = $_POST['peso'] !== '' ? (float) str_replace(',', '.', $_POST['peso']) : null;
    $pet['status_adocao'] = isset($_POST['status_adocao']) ? 1 : 0;

    if ($pet['id_cliente'] <= 0 || $pet['nome'] === '' || $pet['especie'] === '') {
        $erro = 'Tutor, nome e especie sao obrigatorios.';
    } else {
        try {
            $sql = "UPDATE pets
                    SET id_cliente = :id_cliente, nome = :nome, especie = :especie, raca = :raca,
                        idade = :idade, peso = :peso, status_adocao = :status_adocao
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'id_cliente' => $pet['id_cliente'],
                'nome' => $pet['nome'],
                'especie' => $pet['especie'],
                'raca' => $pet['raca'],
                'idade' => $pet['idade'],
                'peso' => $pet['peso'],
                'status_adocao' => $pet['status_adocao'],
                'id' => $id,
            ]);

            header("Location: pets.php?sucesso=editar");
            exit;
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel atualizar o pet.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Editar Pet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Editar pet</h1>
            <p class="text-muted mb-0">Atualize os dados do animal</p>
        </div>
        <a href="pets.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="buscar_tutor" class="form-label">Buscar tutor</label>
                        <input type="search" id="buscar_tutor" class="form-control mb-2" placeholder="Digite nome ou CPF do tutor">
                        <label for="id_cliente" class="form-label">Tutor</label>
                        <select name="id_cliente" id="id_cliente" class="form-select" required>
                            <?php foreach ($clientes as $cliente): ?>
                                <option
                                    value="<?php echo $cliente['id']; ?>"
                                    data-busca="<?php echo htmlspecialchars(strtolower($cliente['nome'] . ' ' . $cliente['cpf'])); ?>"
                                    <?php echo (int) $pet['id_cliente'] === (int) $cliente['id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($cliente['nome'] . ' - ' . $cliente['cpf']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="sem_resultado_tutor" class="form-text text-danger d-none">Nenhum tutor encontrado.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($pet['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="especie" class="form-label">Especie</label>
                        <input type="text" name="especie" id="especie" class="form-control" value="<?php echo htmlspecialchars($pet['especie']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="raca" class="form-label">Raca</label>
                        <input type="text" name="raca" id="raca" class="form-control" value="<?php echo htmlspecialchars($pet['raca'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="idade" class="form-label">Idade</label>
                        <input type="number" name="idade" id="idade" class="form-control" value="<?php echo htmlspecialchars((string) ($pet['idade'] ?? '')); ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="peso" class="form-label">Peso</label>
                        <input type="number" name="peso" id="peso" class="form-control" value="<?php echo htmlspecialchars((string) ($pet['peso'] ?? '')); ?>" min="0" step="0.01">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="status_adocao" id="status_adocao" class="form-check-input" value="1" <?php echo (int) $pet['status_adocao'] === 1 ? 'checked' : ''; ?>>
                            <label for="status_adocao" class="form-check-label">Disponivel para adocao</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                    <a href="pets.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const campoBuscaTutor = document.getElementById('buscar_tutor');
const selectTutor = document.getElementById('id_cliente');
const mensagemSemResultado = document.getElementById('sem_resultado_tutor');

campoBuscaTutor.addEventListener('input', () => {
    const termo = campoBuscaTutor.value.trim().toLowerCase();
    let encontrou = false;

    Array.from(selectTutor.options).forEach((option) => {
        const combina = option.dataset.busca.includes(termo);
        option.hidden = !combina;
        encontrou = encontrou || combina;
    });

    if (selectTutor.selectedOptions[0] && selectTutor.selectedOptions[0].hidden) {
        selectTutor.value = '';
    }

    mensagemSemResultado.classList.toggle('d-none', encontrou || termo === '');
});
</script>
</body>
</html>
