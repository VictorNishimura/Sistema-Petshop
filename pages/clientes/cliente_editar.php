<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

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

$stmt = $conexao->prepare("SELECT id, nome, cpf, telefone, email, endereco, rua, numero, bairro, cidade, uf, cep, foto_perfil FROM clientes WHERE id = :id");
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
    $cliente['rua'] = trim($_POST['rua'] ?? '');
    $cliente['numero'] = trim($_POST['numero'] ?? '');
    $cliente['bairro'] = trim($_POST['bairro'] ?? '');
    $cliente['cidade'] = trim($_POST['cidade'] ?? '');
    $cliente['uf'] = strtoupper(trim($_POST['uf'] ?? ''));
    $cliente['cep'] = trim($_POST['cep'] ?? '');
    $cliente['endereco'] = montarEnderecoCliente($cliente);

    if ($cliente['nome'] === '' || $cliente['cpf'] === '') {
        $erro = 'Nome e CPF sao obrigatorios.';
    } else {
        try {
            $cliente['foto_perfil'] = salvarFotoClienteFormulario($_FILES['foto_perfil'] ?? [], $_POST['foto_camera'] ?? null, $cliente['foto_perfil'] ?? null);

            $sql = "UPDATE clientes
                    SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email,
                        endereco = :endereco, rua = :rua, numero = :numero, bairro = :bairro,
                        cidade = :cidade, uf = :uf, cep = :cep, foto_perfil = :foto_perfil
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'nome' => $cliente['nome'],
                'cpf' => $cliente['cpf'],
                'telefone' => $cliente['telefone'],
                'email' => $cliente['email'],
                'endereco' => $cliente['endereco'],
                'rua' => $cliente['rua'],
                'numero' => $cliente['numero'],
                'bairro' => $cliente['bairro'],
                'cidade' => $cliente['cidade'],
                'uf' => $cliente['uf'],
                'cep' => $cliente['cep'],
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
                        <h2 class="h5 mt-3 mb-0">Endereco</h2>
                    </div>
                    <div class="col-md-6">
                        <label for="rua" class="form-label">Rua</label>
                        <input type="text" name="rua" id="rua" class="form-control" value="<?php echo htmlspecialchars($cliente['rua'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero" class="form-label">Numero</label>
                        <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($cliente['numero'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="uf" class="form-label">UF</label>
                        <input type="text" name="uf" id="uf" class="form-control" value="<?php echo htmlspecialchars($cliente['uf'] ?? ''); ?>" maxlength="2">
                    </div>
                    <div class="col-md-5">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" name="cep" id="cep" class="form-control" value="<?php echo htmlspecialchars($cliente['cep'] ?? ''); ?>" maxlength="10">
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
<script>
const botaoAbrirCamera = document.getElementById('abrir_camera');
const botaoCapturarFoto = document.getElementById('capturar_foto');
const botaoFecharCamera = document.getElementById('fechar_camera');
const videoCamera = document.getElementById('camera_video');
const canvasCamera = document.getElementById('camera_canvas');
const previewCamera = document.getElementById('camera_preview');
const inputFotoCamera = document.getElementById('foto_camera');
const inputFotoPerfil = document.getElementById('foto_perfil');
const avisoCamera = document.getElementById('camera_aviso');
let streamCamera = null;

function mostrarAvisoCamera(mensagem) {
    avisoCamera.textContent = mensagem;
    avisoCamera.classList.toggle('d-none', mensagem === '');
}

function pararCamera() {
    if (streamCamera) {
        streamCamera.getTracks().forEach((track) => track.stop());
        streamCamera = null;
    }

    videoCamera.classList.add('d-none');
    botaoCapturarFoto.classList.add('d-none');
    botaoFecharCamera.classList.add('d-none');
}

botaoAbrirCamera.addEventListener('click', async () => {
    mostrarAvisoCamera('');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarAvisoCamera('Este navegador nao permite abrir a camera.');
        return;
    }

    try {
        streamCamera = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        videoCamera.srcObject = streamCamera;
        videoCamera.classList.remove('d-none');
        botaoCapturarFoto.classList.remove('d-none');
        botaoFecharCamera.classList.remove('d-none');
    } catch (error) {
        mostrarAvisoCamera('Nao foi possivel acessar a camera. Verifique a permissao do navegador.');
    }
});

botaoCapturarFoto.addEventListener('click', () => {
    canvasCamera.width = videoCamera.videoWidth;
    canvasCamera.height = videoCamera.videoHeight;
    canvasCamera.getContext('2d').drawImage(videoCamera, 0, 0);

    const foto = canvasCamera.toDataURL('image/jpeg', 0.9);
    inputFotoCamera.value = foto;
    inputFotoPerfil.value = '';
    previewCamera.src = foto;
    previewCamera.classList.remove('d-none');
    pararCamera();
});

botaoFecharCamera.addEventListener('click', pararCamera);

inputFotoPerfil.addEventListener('change', () => {
    if (inputFotoPerfil.files.length > 0) {
        inputFotoCamera.value = '';
        previewCamera.classList.add('d-none');
    }
});
</script>
</body>
</html>
