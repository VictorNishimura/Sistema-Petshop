<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'funcionario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
require_once __DIR__ . '/../../includes/servico.php';
garantirCampoFuncionarioAgendamento($conexao);
garantirCampoQueixaAgendamento($conexao);
garantirCampoCategoriaServico($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: agendamentos.php");
    exit;
}

$stmt = $conexao->prepare("SELECT * FROM agendamentos WHERE id = :id");
$stmt->execute(['id' => $id]);
$agendamento = $stmt->fetch();

if (!$agendamento || $agendamento['status'] !== 'Agendado') {
    header("Location: agendamentos.php");
    exit;
}

$pets = $conexao->query(
    "SELECT p.id, p.nome, c.nome AS cliente_nome
     FROM pets p
     INNER JOIN clientes c ON c.id = p.id_cliente
     ORDER BY p.nome"
)->fetchAll();
$servicos = $conexao->query("SELECT id, nome, categoria, preco, duracao_minutos FROM servicos ORDER BY categoria, nome")->fetchAll();
$funcionarios = $conexao->query("SELECT id, nome, cargo FROM funcionarios ORDER BY nome")->fetchAll();

$erro = '';
$agendamento['data_hora'] = dataHoraFormulario($agendamento['data_hora']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento['id_pet'] = (int) ($_POST['id_pet'] ?? 0);
    $agendamento['id_servico'] = (int) ($_POST['id_servico'] ?? 0);
    $agendamento['id_funcionario'] = (int) ($_POST['id_funcionario'] ?? 0);
    $agendamento['data_hora'] = trim($_POST['data_hora'] ?? '');
    $agendamento['queixa_principal'] = trim($_POST['queixa_principal'] ?? '');
    $agendamento['observacoes'] = trim($_POST['observacoes'] ?? '');
    $dataBanco = $agendamento['data_hora'] !== '' ? dataHoraBanco($agendamento['data_hora']) : '';

    if ($agendamento['id_pet'] <= 0 || $agendamento['id_servico'] <= 0 || $agendamento['id_funcionario'] <= 0 || $dataBanco === '') {
        $erro = 'Pet, servico, prestador e data/hora sao obrigatorios.';
    } elseif (existeConflitoAgenda($conexao, $agendamento['id_funcionario'], $agendamento['id_servico'], $dataBanco, $id)) {
        $erro = 'Este prestador ja possui outro agendamento neste horario.';
    } else {
        try {
            $stmt = $conexao->prepare(
                "UPDATE agendamentos
                 SET id_pet = :id_pet, id_servico = :id_servico, id_funcionario = :id_funcionario,
                     data_hora = :data_hora, queixa_principal = :queixa_principal, observacoes = :observacoes
                 WHERE id = :id"
            );
            $stmt->execute([
                'id_pet' => $agendamento['id_pet'],
                'id_servico' => $agendamento['id_servico'],
                'id_funcionario' => $agendamento['id_funcionario'],
                'data_hora' => $dataBanco,
                'queixa_principal' => $agendamento['queixa_principal'] !== '' ? $agendamento['queixa_principal'] : null,
                'observacoes' => $agendamento['observacoes'] !== '' ? $agendamento['observacoes'] : null,
                'id' => $id,
            ]);

            header("Location: agendamentos.php?data=" . date('Y-m-d', strtotime($dataBanco)) . "&sucesso=editar");
            exit;
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel atualizar o agendamento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Editar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Editar agendamento</h1>
            <p class="text-muted mb-0">Ajuste o horario, servico ou prestador</p>
        </div>
        <a href="agendamentos.php" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="id_pet" class="form-label">Pet</label>
                        <select name="id_pet" id="id_pet" class="form-select" required>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?php echo $pet['id']; ?>" <?php echo (int) $agendamento['id_pet'] === (int) $pet['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pet['nome'] . ' - ' . $pet['cliente_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="id_servico" class="form-label">Servico</label>
                        <select name="id_servico" id="id_servico" class="form-select" required>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id']; ?>" <?php echo (int) $agendamento['id_servico'] === (int) $servico['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($servico['categoria'] ? $servico['categoria'] . ' - ' : '') . $servico['nome'] . ' - R$ ' . number_format((float) $servico['preco'], 2, ',', '.')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="id_funcionario" class="form-label">Prestador</label>
                        <select name="id_funcionario" id="id_funcionario" class="form-select" required>
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <option value="<?php echo $funcionario['id']; ?>" <?php echo (int) $agendamento['id_funcionario'] === (int) $funcionario['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($funcionario['nome'] . ' - ' . $funcionario['cargo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_hora" class="form-label">Data e hora</label>
                        <input type="datetime-local" name="data_hora" id="data_hora" class="form-control" value="<?php echo htmlspecialchars($agendamento['data_hora']); ?>" required>
                    </div>
                    <div class="col-12 d-none" id="campo_queixa">
                        <label for="queixa_principal" class="form-label">O que esta acontecendo?</label>
                        <textarea name="queixa_principal" id="queixa_principal" class="form-control" rows="4" placeholder="Descreva os sintomas, comportamento diferente, quando comecou, frequencia e detalhes importantes."><?php echo htmlspecialchars($agendamento['queixa_principal'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="observacoes" class="form-label">Observacoes</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($agendamento['observacoes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                    <a href="agendamentos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const selectServico = document.getElementById('id_servico');
const campoQueixa = document.getElementById('campo_queixa');

function atualizarQueixa() {
    const texto = selectServico.options[selectServico.selectedIndex]?.textContent.toLowerCase() || '';
    campoQueixa.classList.toggle('d-none', !(texto.includes('consulta') || texto.includes('veterin')));
}

selectServico.addEventListener('change', atualizarQueixa);
atualizarQueixa();
</script>
</body>
</html>
