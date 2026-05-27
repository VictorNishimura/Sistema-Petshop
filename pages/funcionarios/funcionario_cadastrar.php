<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
garantirCampoFotoTabela($conexao, 'funcionarios');

$erro = '';
$funcionario = [
    'nome' => '',
    'cargo' => '',
    'crmv' => '',
    'telefone' => '',
    'data_admissao' => '',
    'foto_perfil' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionario['nome'] = trim($_POST['nome'] ?? '');
    $funcionario['cargo'] = trim($_POST['cargo'] ?? '');
    $funcionario['crmv'] = trim($_POST['crmv'] ?? '');
    $funcionario['telefone'] = trim($_POST['telefone'] ?? '');
    $funcionario['data_admissao'] = trim($_POST['data_admissao'] ?? '');

    if ($funcionario['cargo'] !== 'Veterinario') {
        $funcionario['crmv'] = '';
    }

    if ($funcionario['nome'] === '' || $funcionario['cargo'] === '') {
        $erro = 'Nome e cargo sao obrigatorios.';
    } else {
        try {
            $funcionario['foto_perfil'] = salvarFotoPerfilFormulario($_FILES['foto_perfil'] ?? [], $_POST['foto_camera'] ?? null, 'funcionarios');

            $sql = "INSERT INTO funcionarios (nome, cargo, crmv, telefone, data_admissao, foto_perfil)
                    VALUES (:nome, :cargo, :crmv, :telefone, :data_admissao, :foto_perfil)";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'nome' => $funcionario['nome'],
                'cargo' => $funcionario['cargo'],
                'crmv' => $funcionario['crmv'] !== '' ? $funcionario['crmv'] : null,
                'telefone' => $funcionario['telefone'] !== '' ? $funcionario['telefone'] : null,
                'data_admissao' => $funcionario['data_admissao'] !== '' ? $funcionario['data_admissao'] : null,
                'foto_perfil' => $funcionario['foto_perfil'],
            ]);

            header("Location: funcionarios.php?sucesso=cadastrar");
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel cadastrar o funcionario.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Cadastrar Funcionario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Cadastrar funcionario</h1>
            <p class="text-muted mb-0">Cadastro da equipe, separado dos usuarios de login</p>
        </div>
        <a href="funcionarios.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="foto_perfil" class="form-label">Foto do funcionario</label>
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
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cargo" class="form-label">Cargo</label>
                        <select name="cargo" id="cargo" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach (['Veterinario', 'Tosador', 'Banhista', 'Atendente', 'Gerente'] as $cargo): ?>
                                <option value="<?php echo $cargo; ?>" <?php echo $funcionario['cargo'] === $cargo ? 'selected' : ''; ?>>
                                    <?php echo $cargo; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-none" id="campo_crmv">
                        <label for="crmv" class="form-label">CRMV</label>
                        <input type="text" name="crmv" id="crmv" class="form-control" value="<?php echo htmlspecialchars($funcionario['crmv']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($funcionario['telefone']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="data_admissao" class="form-label">Data de admissao</label>
                        <input type="date" name="data_admissao" id="data_admissao" class="form-control" value="<?php echo htmlspecialchars($funcionario['data_admissao']); ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar funcionario</button>
                    <a href="funcionarios.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo caminhoApp('assets/js/foto-camera.js'); ?>"></script>
<script>
const selectCargo = document.getElementById('cargo');
const campoCrmv = document.getElementById('campo_crmv');
const inputCrmv = document.getElementById('crmv');

function atualizarCampoCrmv() {
    const veterinario = selectCargo.value === 'Veterinario';
    campoCrmv.classList.toggle('d-none', !veterinario);

    if (!veterinario) {
        inputCrmv.value = '';
    }
}

selectCargo.addEventListener('change', atualizarCampoCrmv);
atualizarCampoCrmv();
</script>
</body>
</html>
