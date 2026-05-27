<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirPermissao(['admin', 'veterinario']);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/agendamento.php';
garantirCampoQueixaAgendamento($conexao);

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: prontuario.php");
    exit;
}

$stmt = $conexao->prepare(
    "SELECT co.id, co.data_consulta, co.queixa_principal, co.diagnostico, co.prescricao,
            p.id AS pet_id, p.nome AS pet_nome, p.especie, p.raca,
            c.nome AS cliente_nome, c.cpf AS cliente_cpf, c.telefone AS cliente_telefone,
            f.nome AS veterinario_nome, f.crmv AS veterinario_crmv
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
    <title>Atestado - <?php echo htmlspecialchars($consulta['pet_nome']); ?></title>
    <style>
        body { margin: 0; background: #eef2f5; font-family: Arial, Helvetica, sans-serif; color: #17202a; }
        .toolbar { max-width: 820px; margin: 20px auto; text-align: right; }
        .toolbar button, .toolbar a { border: 1px solid #0b3a63; background: #0b3a63; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; }
        .page { max-width: 820px; margin: 0 auto 30px; background: #fff; padding: 38px 48px; box-shadow: 0 8px 30px rgba(0,0,0,.12); }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0b3a63; padding-bottom: 14px; }
        .logo { width: 160px; }
        h1 { color: #0b3a63; text-align: right; margin: 0; font-size: 26px; }
        .content { margin-top: 34px; font-size: 16px; line-height: 1.65; }
        .box { border: 1px solid #d9e2e8; border-radius: 8px; padding: 14px; margin-top: 16px; }
        .signature { margin-top: 80px; text-align: center; }
        .line { width: 340px; border-top: 1px solid #17202a; margin: 0 auto 10px; }
        .footer { margin-top: 40px; font-size: 12px; color: #6c757d; text-align: center; border-top: 1px solid #d9e2e8; padding-top: 10px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            @page { size: A4; margin: 12mm; }
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
            <h1>Atestado de Atendimento Veterinário</h1>
        </header>

        <section class="content">
            <p>
                Atesto, para os devidos fins, que o pet <strong><?php echo htmlspecialchars($consulta['pet_nome']); ?></strong>,
                espécie <strong><?php echo htmlspecialchars($consulta['especie']); ?></strong>
                <?php if (!empty($consulta['raca'])): ?>, raça <strong><?php echo htmlspecialchars($consulta['raca']); ?></strong><?php endif; ?>,
                pertencente ao tutor <strong><?php echo htmlspecialchars($consulta['cliente_nome']); ?></strong>,
                CPF <strong><?php echo htmlspecialchars($consulta['cliente_cpf']); ?></strong>,
                foi atendido nesta clínica em
                <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($consulta['data_consulta']))); ?></strong>
                às <strong><?php echo htmlspecialchars(date('H:i', strtotime($consulta['data_consulta']))); ?></strong>.
            </p>

            <div class="box">
                <p><strong>Queixa principal:</strong> <?php echo nl2br(htmlspecialchars($consulta['queixa_principal'] ?? '')); ?></p>
                <p><strong>Diagnóstico:</strong> <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?? '')); ?></p>
                <p><strong>Prescrição/orientações:</strong> <?php echo nl2br(htmlspecialchars($consulta['prescricao'] ?? '')); ?></p>
            </div>
        </section>

        <div class="signature">
            <div class="line"></div>
            <strong><?php echo htmlspecialchars($consulta['veterinario_nome']); ?></strong>
            <div>CRMV: <?php echo htmlspecialchars($consulta['veterinario_crmv'] ?? ''); ?></div>
        </div>

        <footer class="footer">
            PetLife - Sistema para Pet Shop | Documento gerado em <?php echo date('d/m/Y H:i'); ?>
        </footer>
    </main>
</body>
</html>
