<?php
function garantirCampoFuncionarioAgendamento(PDO $conexao): void
{
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'agendamentos'
              AND COLUMN_NAME = 'id_funcionario'";
    $stmt = $conexao->query($sql);

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE agendamentos ADD id_funcionario INT NULL AFTER id_servico");
    }
}

function garantirCampoQueixaAgendamento(PDO $conexao): void
{
    garantirCampoTexto($conexao, 'agendamentos', 'queixa_principal');
    garantirCampoTexto($conexao, 'consultas', 'queixa_principal');
}

function garantirCampoTexto(PDO $conexao, string $tabela, string $campo): void
{
    $stmt = $conexao->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :campo"
    );
    $stmt->execute(['tabela' => $tabela, 'campo' => $campo]);

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE {$tabela} ADD {$campo} TEXT NULL");
    }
}

function servicoEhConsulta(string $nomeServico): bool
{
    $nome = strtolower($nomeServico);
    return strpos($nome, 'consulta') !== false || strpos($nome, 'veterin') !== false;
}

function existeConflitoAgenda(PDO $conexao, int $idFuncionario, int $idServico, string $dataHora, ?int $idIgnorar = null): bool
{
    $stmt = $conexao->prepare("SELECT COALESCE(duracao_minutos, 60) FROM servicos WHERE id = :id");
    $stmt->execute(['id' => $idServico]);
    $duracao = (int) ($stmt->fetchColumn() ?: 60);

    $sql = "SELECT COUNT(*)
            FROM agendamentos a
            INNER JOIN servicos s ON s.id = a.id_servico
            WHERE a.id_funcionario = :id_funcionario
              AND a.status = 'Agendado'
              AND (:data_hora_inicio < DATE_ADD(a.data_hora, INTERVAL COALESCE(s.duracao_minutos, 60) MINUTE))
              AND (a.data_hora < DATE_ADD(:data_hora_fim, INTERVAL :duracao MINUTE))";
    $params = [
        'id_funcionario' => $idFuncionario,
        'data_hora_inicio' => $dataHora,
        'data_hora_fim' => $dataHora,
        'duracao' => $duracao,
    ];

    if ($idIgnorar !== null) {
        $sql .= " AND a.id <> :id_ignorar";
        $params['id_ignorar'] = $idIgnorar;
    }

    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function dataHoraFormulario(string $dataHora): string
{
    return date('Y-m-d\TH:i', strtotime($dataHora));
}

function dataHoraBanco(string $dataHora): string
{
    return date('Y-m-d H:i:s', strtotime($dataHora));
}
