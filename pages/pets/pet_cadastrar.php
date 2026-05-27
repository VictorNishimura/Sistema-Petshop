<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
garantirCampoFotoTabela($conexao, 'pets');

$clientes = $conexao->query("SELECT id, nome, cpf FROM clientes ORDER BY nome")->fetchAll();
$erro = '';
$pet = [
    'id_cliente' => '',
    'nome' => '',
    'especie' => '',
    'raca' => '',
    'idade' => '',
    'peso' => '',
    'status_adocao' => 0,
    'foto_perfil' => null,
];

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
            $pet['foto_perfil'] = salvarFotoPerfilFormulario($_FILES['foto_perfil'] ?? [], $_POST['foto_camera'] ?? null, 'pets');

            $sql = "INSERT INTO pets (id_cliente, nome, especie, raca, idade, peso, status_adocao, foto_perfil)
                    VALUES (:id_cliente, :nome, :especie, :raca, :idade, :peso, :status_adocao, :foto_perfil)";
            $stmt = $conexao->prepare($sql);
            $stmt->execute($pet);

            header("Location: pets.php?sucesso=cadastrar");
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel cadastrar o pet.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Cadastrar Pet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Cadastrar pet</h1>
            <p class="text-muted mb-0">Vincule o pet a um tutor cadastrado</p>
        </div>
        <a href="pets.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if (count($clientes) === 0): ?>
        <div class="alert alert-warning">Cadastre um cliente antes de cadastrar pets.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="foto_perfil" class="form-label">Foto do pet</label>
                        <input type="file" name="foto_perfil" id="foto_perfil" class="form-control" accept="image/jpeg,image/png,image/webp,image/*">
                        <input type="hidden" name="foto_camera" id="foto_camera">
                        <div class="form-text">Selecione uma imagem dos arquivos/galeria ou use a camera. Tamanho maximo: 2 MB.</div>

                        <div class="border rounded p-3 mt-3">
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="abrir_camera">Abrir camera</button>
                                <button type="button" class="btn btn-outline-success btn-sm d-none" id="capturar_foto">Tirar foto</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="fechar_camera">Fechar camera</button>
                            </div>
                            <video id="camera_video" class="w-100 rounded d-none bg-dark" autoplay playsinline style="max-width: 420px;"></video>
                            <canvas id="camera_canvas" class="d-none"></canvas>
                            <img id="camera_preview" class="rounded-circle object-fit-cover d-none mt-3" width="120" height="120" alt="Previa da foto capturada">
                            <div id="camera_aviso" class="form-text text-danger d-none mt-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="buscar_tutor" class="form-label">Buscar tutor</label>
                        <input type="search" id="buscar_tutor" class="form-control mb-2" placeholder="Digite nome ou CPF do tutor">
                        <label for="id_cliente" class="form-label">Tutor</label>
                        <select name="id_cliente" id="id_cliente" class="form-select" required>
                            <option value="">Selecione</option>
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
                        <input type="text" name="raca" id="raca" class="form-control" value="<?php echo htmlspecialchars($pet['raca']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="idade" class="form-label">Idade</label>
                        <input type="number" name="idade" id="idade" class="form-control" value="<?php echo htmlspecialchars((string) $pet['idade']); ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="peso" class="form-label">Peso</label>
                        <input type="number" name="peso" id="peso" class="form-control" value="<?php echo htmlspecialchars((string) $pet['peso']); ?>" min="0" step="0.01">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="status_adocao" id="status_adocao" class="form-check-input" value="1" <?php echo (int) $pet['status_adocao'] === 1 ? 'checked' : ''; ?>>
                            <label for="status_adocao" class="form-check-label">Disponivel para adocao</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" <?php echo count($clientes) === 0 ? 'disabled' : ''; ?>>Salvar pet</button>
                    <a href="pets.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo caminhoApp('assets/js/foto-camera.js'); ?>"></script>
<script>
const campoBuscaTutor = document.getElementById('buscar_tutor');
const selectTutor = document.getElementById('id_cliente');
const mensagemSemResultado = document.getElementById('sem_resultado_tutor');

campoBuscaTutor.addEventListener('input', () => {
    const termo = campoBuscaTutor.value.trim().toLowerCase();
    let encontrou = false;

    Array.from(selectTutor.options).forEach((option) => {
        if (option.value === '') {
            option.hidden = false;
            return;
        }

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
