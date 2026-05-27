<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/usuario_funcionario.php';
garantirCampoFotoTabela($conexao, 'funcionarios');
garantirCampoUsuarioFuncionario($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: funcionarios.php");
    exit;
}

$stmt = $conexao->prepare("SELECT id, nome, cargo, crmv, telefone, data_admissao, foto_perfil FROM funcionarios WHERE id = :id");
$stmt->execute(['id' => $id]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    header("Location: funcionarios.php");
    exit;
}

$erro = '';
$usuarioFuncionario = buscarUsuarioFuncionario($conexao, $id);
$acesso = [
    'manter_usuario' => $usuarioFuncionario !== null,
    'email' => $usuarioFuncionario['email'] ?? '',
    'senha' => '',
    'pergunta_secreta' => $usuarioFuncionario['pergunta_secreta'] ?? '',
    'resposta_secreta' => $usuarioFuncionario['resposta_secreta'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionario['nome'] = trim($_POST['nome'] ?? '');
    $funcionario['cargo'] = trim($_POST['cargo'] ?? '');
    $funcionario['crmv'] = trim($_POST['crmv'] ?? '');
    $funcionario['telefone'] = trim($_POST['telefone'] ?? '');
    $funcionario['data_admissao'] = trim($_POST['data_admissao'] ?? '');
    $acesso['manter_usuario'] = isset($_POST['manter_usuario']);
    $acesso['email'] = trim($_POST['email_usuario'] ?? '');
    $acesso['senha'] = trim($_POST['senha_usuario'] ?? '');
    $acesso['pergunta_secreta'] = trim($_POST['pergunta_secreta'] ?? '');
    $acesso['resposta_secreta'] = trim($_POST['resposta_secreta'] ?? '');

    if ($funcionario['cargo'] !== 'Veterinario') {
        $funcionario['crmv'] = '';
    }

    if ($funcionario['nome'] === '' || $funcionario['cargo'] === '') {
        $erro = 'Nome e cargo sao obrigatorios.';
    } else {
        try {
            $conexao->beginTransaction();
            $funcionario['foto_perfil'] = salvarFotoPerfilFormulario($_FILES['foto_perfil'] ?? [], $_POST['foto_camera'] ?? null, 'funcionarios', $funcionario['foto_perfil'] ?? null);

            $sql = "UPDATE funcionarios
                    SET nome = :nome, cargo = :cargo, crmv = :crmv, telefone = :telefone, data_admissao = :data_admissao, foto_perfil = :foto_perfil
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'nome' => $funcionario['nome'],
                'cargo' => $funcionario['cargo'],
                'crmv' => $funcionario['crmv'] !== '' ? $funcionario['crmv'] : null,
                'telefone' => $funcionario['telefone'] !== '' ? $funcionario['telefone'] : null,
                'data_admissao' => $funcionario['data_admissao'] !== '' ? $funcionario['data_admissao'] : null,
                'foto_perfil' => $funcionario['foto_perfil'],
                'id' => $id,
            ]);

            if ($acesso['manter_usuario']) {
                if ($usuarioFuncionario) {
                    atualizarUsuarioFuncionario($conexao, (int) $usuarioFuncionario['id'], $funcionario['nome'], $funcionario['cargo'], $acesso);
                } else {
                    criarUsuarioFuncionario($conexao, $id, $funcionario['nome'], $funcionario['cargo'], $acesso);
                }
            } elseif ($usuarioFuncionario) {
                excluirUsuarioFuncionario($conexao, $id);
            }

            $conexao->commit();

            header("Location: funcionarios.php?sucesso=editar");
            exit;
        } catch (RuntimeException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            $erro = 'Nao foi possivel atualizar o funcionario.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Editar Funcionario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Editar funcionario</h1>
            <p class="text-muted mb-0">Atualize os dados da equipe</p>
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
                        <label class="form-label d-block">Foto atual</label>
                        <img
                            src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($funcionario['foto_perfil'] ?? null))); ?>"
                            alt="Foto de <?php echo htmlspecialchars($funcionario['nome']); ?>"
                            class="rounded-circle object-fit-cover mb-3"
                            width="96"
                            height="96"
                        >
                        <label for="foto_perfil" class="form-label">Trocar foto do funcionario</label>
                        <input type="file" name="foto_perfil" id="foto_perfil" class="form-control" accept="image/jpeg,image/png,image/webp,image/*">
                        <input type="hidden" name="foto_camera" id="foto_camera">
                        <div class="form-text">Selecione uma imagem dos arquivos/galeria ou use a camera. Deixe em branco para manter a foto atual.</div>

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
                        <input type="text" name="crmv" id="crmv" class="form-control" value="<?php echo htmlspecialchars($funcionario['crmv'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($funcionario['telefone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="data_admissao" class="form-label">Data de admissao</label>
                        <input type="date" name="data_admissao" id="data_admissao" class="form-control" value="<?php echo htmlspecialchars($funcionario['data_admissao'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <hr>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="manter_usuario" id="manter_usuario" class="form-check-input" value="1" <?php echo $acesso['manter_usuario'] ? 'checked' : ''; ?>>
                            <label for="manter_usuario" class="form-check-label">
                                <?php echo $usuarioFuncionario ? 'Manter acesso ao sistema deste funcionario' : 'Criar acesso ao sistema para este funcionario'; ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-12 d-none" id="campos_acesso">
                        <div class="alert alert-info mb-3">
                            Nivel automatico: <strong id="nivel_acesso_texto">funcionario</strong>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email_usuario" class="form-label">E-mail de acesso</label>
                                <input type="email" name="email_usuario" id="email_usuario" class="form-control" value="<?php echo htmlspecialchars($acesso['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="senha_usuario" class="form-label">Nova senha</label>
                                <input type="password" name="senha_usuario" id="senha_usuario" class="form-control">
                                <div class="form-text"><?php echo $usuarioFuncionario ? 'Deixe em branco para manter a senha atual.' : 'Obrigatoria para criar o acesso.'; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="pergunta_secreta" class="form-label">Pergunta secreta</label>
                                <input type="text" name="pergunta_secreta" id="pergunta_secreta" class="form-control" value="<?php echo htmlspecialchars($acesso['pergunta_secreta']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="resposta_secreta" class="form-label">Resposta secreta</label>
                                <input type="text" name="resposta_secreta" id="resposta_secreta" class="form-control" value="<?php echo htmlspecialchars($acesso['resposta_secreta']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
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
const checkboxManterUsuario = document.getElementById('manter_usuario');
const camposAcesso = document.getElementById('campos_acesso');
const nivelAcessoTexto = document.getElementById('nivel_acesso_texto');

function atualizarCampoCrmv() {
    const veterinario = selectCargo.value === 'Veterinario';
    campoCrmv.classList.toggle('d-none', !veterinario);

    if (!veterinario) {
        inputCrmv.value = '';
    }
}

function nivelPorCargo(cargo) {
    if (cargo === 'Veterinario') {
        return 'veterinario';
    }

    if (cargo === 'Gerente') {
        return 'admin';
    }

    return 'funcionario';
}

function atualizarAcesso() {
    camposAcesso.classList.toggle('d-none', !checkboxManterUsuario.checked);
    nivelAcessoTexto.textContent = nivelPorCargo(selectCargo.value);
}

selectCargo.addEventListener('change', atualizarCampoCrmv);
selectCargo.addEventListener('change', atualizarAcesso);
checkboxManterUsuario.addEventListener('change', atualizarAcesso);
atualizarCampoCrmv();
atualizarAcesso();
</script>
</body>
</html>
