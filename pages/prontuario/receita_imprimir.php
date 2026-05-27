<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'veterinario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/pet.php';
garantirCamposDetalhesPet($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: prontuario.php");
    exit;
}

$stmt = $conexao->prepare(
    "SELECT co.id, co.data_consulta, co.diagnostico, co.prescricao,
            p.id AS pet_id, p.nome AS pet_nome, p.especie, p.raca, p.sexo, p.data_nascimento,
            p.idade, p.pelagem, p.peso, p.vacinacao_atualizada, p.ultima_aplicacao_parasitas,
            p.alergias_restricoes, p.condicoes_especiais, p.temperamento, p.reacao_animais,
            p.observacoes_gerais, p.status_adocao,
            c.nome AS cliente_nome, c.cpf AS cliente_cpf, c.telefone AS cliente_telefone,
            c.email AS cliente_email, c.endereco AS cliente_endereco,
            f.nome AS veterinario_nome, f.crmv AS veterinario_crmv, f.telefone AS veterinario_telefone
     FROM consultas co
     INNER JOIN pets p ON p.id = co.id_pet
     INNER JOIN clientes c ON c.id = p.id_cliente
     INNER JOIN funcionarios f ON f.id = co.id_funcionario
     WHERE co.id = :id"
);
$stmt->execute(['id' => $id]);
$consulta = $stmt->fetch();

if (!$consulta) {
    header("Location: prontuario.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receita - <?php echo htmlspecialchars($consulta['pet_nome']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f5; color: #17202a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 900px; margin: 20px auto; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a { border: 1px solid #0b3a63; background: #0b3a63; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; }
        .page { max-width: 900px; min-height: 1120px; margin: 0 auto 30px; background: #fff; padding: 28px 38px; box-shadow: 0 8px 30px rgba(0,0,0,.12); }
        .header { display: flex; align-items: center; justify-content: space-between; gap: 18px; border-bottom: 2px solid #0b3a63; padding-bottom: 12px; }
        .logo { width: 150px; height: auto; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; color: #0b3a63; font-size: 23px; }
        .doc-title p { margin: 3px 0 0; color: #5c6b73; font-size: 12px; }
        .section { margin-top: 14px; }
        .section h2 { color: #0b3a63; border-bottom: 1px solid #d9e2e8; font-size: 14px; margin: 0 0 8px; padding-bottom: 5px; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px 18px; }
        .field { font-size: 12px; line-height: 1.28; }
        .field strong { color: #2d3b45; }
        .box { border: 1px solid #d9e2e8; border-radius: 6px; padding: 9px; min-height: 52px; white-space: pre-wrap; font-size: 12px; line-height: 1.3; }
        .signature { margin-top: 34px; text-align: center; font-size: 12px; }
        .signature-line { width: 300px; border-top: 1px solid #17202a; margin: 0 auto 7px; }
        .footer { margin-top: 18px; border-top: 1px solid #d9e2e8; padding-top: 8px; font-size: 10px; color: #6c757d; text-align: center; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { box-shadow: none; margin: 0; max-width: none; min-height: auto; padding: 0; }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="prontuario.php?id_pet=<?php echo $consulta['pet_id']; ?>">Voltar</a>
        <button type="button" onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>

    <main class="page">
        <header class="header">
            <img class="logo" src="<?php echo htmlspecialchars(caminhoApp('assets/img/logo-petlife.svg')); ?>" alt="PetLife">
            <div class="doc-title">
                <h1>Receita / Relatorio de Consulta</h1>
                <p>Consulta #<?php echo htmlspecialchars($consulta['id']); ?></p>
                <p><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($consulta['data_consulta']))); ?></p>
            </div>
        </header>

        <section class="section">
            <h2>Dados do Tutor</h2>
            <div class="grid">
                <div class="field"><strong>Nome:</strong> <?php echo htmlspecialchars($consulta['cliente_nome']); ?></div>
                <div class="field"><strong>CPF:</strong> <?php echo htmlspecialchars($consulta['cliente_cpf']); ?></div>
                <div class="field"><strong>Telefone:</strong> <?php echo htmlspecialchars($consulta['cliente_telefone'] ?? ''); ?></div>
                <div class="field"><strong>E-mail:</strong> <?php echo htmlspecialchars($consulta['cliente_email'] ?? ''); ?></div>
                <div class="field"><strong>Endereco:</strong> <?php echo htmlspecialchars($consulta['cliente_endereco'] ?? ''); ?></div>
            </div>
        </section>

        <section class="section">
            <h2>Dados do Pet</h2>
            <div class="grid">
                <div class="field"><strong>Nome:</strong> <?php echo htmlspecialchars($consulta['pet_nome']); ?></div>
                <div class="field"><strong>Especie:</strong> <?php echo htmlspecialchars($consulta['especie']); ?></div>
                <div class="field"><strong>Raca:</strong> <?php echo htmlspecialchars($consulta['raca'] ?? ''); ?></div>
                <div class="field"><strong>Sexo:</strong> <?php echo htmlspecialchars($consulta['sexo'] ?? ''); ?></div>
                <div class="field"><strong>Nascimento:</strong> <?php echo !empty($consulta['data_nascimento']) ? htmlspecialchars(date('d/m/Y', strtotime($consulta['data_nascimento']))) : ''; ?></div>
                <div class="field"><strong>Idade aproximada:</strong> <?php echo htmlspecialchars((string) ($consulta['idade'] ?? '')); ?></div>
                <div class="field"><strong>Peso atual:</strong> <?php echo $consulta['peso'] !== null ? htmlspecialchars(number_format((float) $consulta['peso'], 2, ',', '.')) . ' kg' : ''; ?></div>
                <div class="field"><strong>Pelagem:</strong> <?php echo htmlspecialchars($consulta['pelagem'] ?? ''); ?></div>
                <div class="field"><strong>Vacinacao:</strong> <?php echo (int) ($consulta['vacinacao_atualizada'] ?? 0) === 1 ? 'Atualizada' : 'Nao informada/pendente'; ?></div>
                <div class="field"><strong>Antipulgas/carrapatos:</strong> <?php echo !empty($consulta['ultima_aplicacao_parasitas']) ? htmlspecialchars(date('d/m/Y', strtotime($consulta['ultima_aplicacao_parasitas']))) : ''; ?></div>
            </div>
        </section>

        <section class="section">
            <h2>Informacoes Clinicas e Comportamentais</h2>
            <div class="grid">
                <div class="field"><strong>Alergias/restricoes:</strong> <?php echo htmlspecialchars($consulta['alergias_restricoes'] ?? ''); ?></div>
                <div class="field"><strong>Condicoes especiais:</strong> <?php echo htmlspecialchars($consulta['condicoes_especiais'] ?? ''); ?></div>
                <div class="field"><strong>Temperamento:</strong> <?php echo htmlspecialchars($consulta['temperamento'] ?? ''); ?></div>
                <div class="field"><strong>Reacao a outros animais:</strong> <?php echo htmlspecialchars($consulta['reacao_animais'] ?? ''); ?></div>
            </div>
            <p class="field"><strong>Observacoes gerais:</strong> <?php echo nl2br(htmlspecialchars($consulta['observacoes_gerais'] ?? '')); ?></p>
        </section>

        <section class="section">
            <h2>Diagnostico</h2>
            <div class="box"><?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?? '')); ?></div>
        </section>

        <section class="section">
            <h2>Prescricao / Receita</h2>
            <div class="box"><?php echo nl2br(htmlspecialchars($consulta['prescricao'] ?? '')); ?></div>
        </section>

        <section class="section">
            <h2>Veterinario Responsavel</h2>
            <div class="grid">
                <div class="field"><strong>Nome:</strong> <?php echo htmlspecialchars($consulta['veterinario_nome']); ?></div>
                <div class="field"><strong>CRMV:</strong> <?php echo htmlspecialchars($consulta['veterinario_crmv'] ?? ''); ?></div>
                <div class="field"><strong>Telefone:</strong> <?php echo htmlspecialchars($consulta['veterinario_telefone'] ?? ''); ?></div>
            </div>
        </section>

        <div class="signature">
            <div class="signature-line"></div>
            <strong><?php echo htmlspecialchars($consulta['veterinario_nome']); ?></strong>
            <div>CRMV: <?php echo htmlspecialchars($consulta['veterinario_crmv'] ?? ''); ?></div>
        </div>

        <footer class="footer">
            PetLife - Sistema para Pet Shop | Documento gerado em <?php echo date('d/m/Y H:i'); ?>
        </footer>
    </main>
</body>
</html>
