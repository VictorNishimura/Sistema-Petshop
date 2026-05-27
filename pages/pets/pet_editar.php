<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/pet.php';
garantirCampoFotoTabela($conexao, 'pets');
garantirCamposDetalhesPet($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: pets.php");
    exit;
}

$clientes = $conexao->query("SELECT id, nome, cpf FROM clientes ORDER BY nome")->fetchAll();
$stmt = $conexao->prepare("SELECT * FROM pets WHERE id = :id");
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
    $pet['sexo'] = trim($_POST['sexo'] ?? '');
    $pet['data_nascimento'] = trim($_POST['data_nascimento'] ?? '');
    $pet['idade'] = $_POST['idade'] !== '' ? (int) $_POST['idade'] : null;
    $pet['pelagem'] = trim($_POST['pelagem'] ?? '');
    $pet['peso'] = $_POST['peso'] !== '' ? (float) str_replace(',', '.', $_POST['peso']) : null;
    $pet['vacinacao_atualizada'] = isset($_POST['vacinacao_atualizada']) ? 1 : 0;
    $pet['ultima_aplicacao_parasitas'] = trim($_POST['ultima_aplicacao_parasitas'] ?? '');
    $pet['alergias_restricoes'] = trim($_POST['alergias_restricoes'] ?? '');
    $pet['condicoes_especiais'] = trim($_POST['condicoes_especiais'] ?? '');
    $pet['temperamento'] = trim($_POST['temperamento'] ?? '');
    $pet['reacao_animais'] = trim($_POST['reacao_animais'] ?? '');
    $pet['observacoes_gerais'] = trim($_POST['observacoes_gerais'] ?? '');
    $pet['status_adocao'] = isset($_POST['status_adocao']) ? 1 : 0;

    if ($pet['id_cliente'] <= 0 || $pet['nome'] === '' || $pet['especie'] === '' || $pet['sexo'] === '') {
        $erro = 'Tutor, nome, especie e sexo sao obrigatorios.';
    } else {
        try {
            $pet['foto_perfil'] = salvarFotoPerfilFormulario($_FILES['foto_perfil'] ?? [], $_POST['foto_camera'] ?? null, 'pets', $pet['foto_perfil'] ?? null);

            $sql = "UPDATE pets
                    SET id_cliente = :id_cliente, nome = :nome, especie = :especie, raca = :raca,
                        sexo = :sexo, data_nascimento = :data_nascimento, idade = :idade,
                        pelagem = :pelagem, peso = :peso, vacinacao_atualizada = :vacinacao_atualizada,
                        ultima_aplicacao_parasitas = :ultima_aplicacao_parasitas,
                        alergias_restricoes = :alergias_restricoes,
                        condicoes_especiais = :condicoes_especiais,
                        temperamento = :temperamento, reacao_animais = :reacao_animais,
                        observacoes_gerais = :observacoes_gerais,
                        status_adocao = :status_adocao, foto_perfil = :foto_perfil
                    WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'id_cliente' => $pet['id_cliente'],
                'nome' => $pet['nome'],
                'especie' => $pet['especie'],
                'raca' => valorOuNulo($pet['raca']),
                'sexo' => $pet['sexo'],
                'data_nascimento' => valorOuNulo($pet['data_nascimento']),
                'idade' => $pet['idade'],
                'pelagem' => valorOuNulo($pet['pelagem']),
                'peso' => $pet['peso'],
                'vacinacao_atualizada' => $pet['vacinacao_atualizada'],
                'ultima_aplicacao_parasitas' => valorOuNulo($pet['ultima_aplicacao_parasitas']),
                'alergias_restricoes' => valorOuNulo($pet['alergias_restricoes']),
                'condicoes_especiais' => valorOuNulo($pet['condicoes_especiais']),
                'temperamento' => valorOuNulo($pet['temperamento']),
                'reacao_animais' => valorOuNulo($pet['reacao_animais']),
                'observacoes_gerais' => valorOuNulo($pet['observacoes_gerais']),
                'status_adocao' => $pet['status_adocao'],
                'foto_perfil' => $pet['foto_perfil'],
                'id' => $id,
            ]);

            header("Location: pets.php?sucesso=editar");
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
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
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label d-block">Foto atual</label>
                        <img
                            src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($pet['foto_perfil'] ?? null))); ?>"
                            alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                            class="rounded-circle object-fit-cover mb-3"
                            width="96"
                            height="96"
                        >
                        <label for="foto_perfil" class="form-label">Trocar foto do pet</label>
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
                    <div class="col-12">
                        <h2 class="h5 mt-3 mb-0">Identificacao basica</h2>
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
                        <label for="sexo" class="form-label">Sexo</label>
                        <select name="sexo" id="sexo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="Macho" <?php echo ($pet['sexo'] ?? '') === 'Macho' ? 'selected' : ''; ?>>Macho</option>
                            <option value="Femea" <?php echo ($pet['sexo'] ?? '') === 'Femea' ? 'selected' : ''; ?>>Femea</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de nascimento</label>
                        <input type="date" name="data_nascimento" id="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($pet['data_nascimento'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="idade" class="form-label">Idade aproximada</label>
                        <input type="number" name="idade" id="idade" class="form-control" value="<?php echo htmlspecialchars((string) ($pet['idade'] ?? '')); ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="pelagem" class="form-label">Pelagem</label>
                        <input type="text" name="pelagem" id="pelagem" class="form-control" value="<?php echo htmlspecialchars($pet['pelagem'] ?? ''); ?>" placeholder="Cor e tipo de pelo">
                    </div>
                    <div class="col-12">
                        <h2 class="h5 mt-3 mb-0">Saude e bem-estar</h2>
                    </div>
                    <div class="col-md-6">
                        <label for="peso" class="form-label">Peso atual</label>
                        <input type="number" name="peso" id="peso" class="form-control" value="<?php echo htmlspecialchars((string) ($pet['peso'] ?? '')); ?>" min="0" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label for="ultima_aplicacao_parasitas" class="form-label">Ultima aplicacao de antipulgas/carrapatos</label>
                        <input type="date" name="ultima_aplicacao_parasitas" id="ultima_aplicacao_parasitas" class="form-control" value="<?php echo htmlspecialchars($pet['ultima_aplicacao_parasitas'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="vacinacao_atualizada" id="vacinacao_atualizada" class="form-check-input" value="1" <?php echo (int) ($pet['vacinacao_atualizada'] ?? 0) === 1 ? 'checked' : ''; ?>>
                            <label for="vacinacao_atualizada" class="form-check-label">Carteira de vacinacao atualizada</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="alergias_restricoes" class="form-label">Alergias e restricoes</label>
                        <textarea name="alergias_restricoes" id="alergias_restricoes" class="form-control" rows="3"><?php echo htmlspecialchars($pet['alergias_restricoes'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="condicoes_especiais" class="form-label">Doencas ou condicoes especiais</label>
                        <textarea name="condicoes_especiais" id="condicoes_especiais" class="form-control" rows="3"><?php echo htmlspecialchars($pet['condicoes_especiais'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <h2 class="h5 mt-3 mb-0">Comportamento</h2>
                    </div>
                    <div class="col-md-6">
                        <label for="temperamento" class="form-label">Temperamento</label>
                        <input type="text" name="temperamento" id="temperamento" class="form-control" value="<?php echo htmlspecialchars($pet['temperamento'] ?? ''); ?>" placeholder="Docil, arisco, morde, medo de secador">
                    </div>
                    <div class="col-md-6">
                        <label for="reacao_animais" class="form-label">Reacao a outros animais</label>
                        <input type="text" name="reacao_animais" id="reacao_animais" class="form-control" value="<?php echo htmlspecialchars($pet['reacao_animais'] ?? ''); ?>" placeholder="Sociavel ou reativo">
                    </div>
                    <div class="col-12">
                        <label for="observacoes_gerais" class="form-label">Observacoes gerais</label>
                        <textarea name="observacoes_gerais" id="observacoes_gerais" class="form-control" rows="3"><?php echo htmlspecialchars($pet['observacoes_gerais'] ?? ''); ?></textarea>
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
<script src="<?php echo caminhoApp('assets/js/foto-camera.js'); ?>"></script>
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
