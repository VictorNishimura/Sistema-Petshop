<?php
function garantirCamposDetalhesPet(PDO $conexao): void
{
    $campos = [
        'sexo' => "VARCHAR(10) NULL",
        'data_nascimento' => "DATE NULL",
        'pelagem' => "VARCHAR(100) NULL",
        'vacinacao_atualizada' => "BOOLEAN DEFAULT FALSE",
        'ultima_aplicacao_parasitas' => "DATE NULL",
        'alergias_restricoes' => "TEXT NULL",
        'condicoes_especiais' => "TEXT NULL",
        'temperamento' => "VARCHAR(100) NULL",
        'reacao_animais' => "VARCHAR(100) NULL",
        'observacoes_gerais' => "TEXT NULL",
    ];

    foreach ($campos as $campo => $definicao) {
        $stmt = $conexao->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'pets'
               AND COLUMN_NAME = :campo"
        );
        $stmt->execute(['campo' => $campo]);

        if ((int) $stmt->fetchColumn() === 0) {
            $conexao->exec("ALTER TABLE pets ADD {$campo} {$definicao}");
        }
    }
}

function valorOuNulo(string $valor): ?string
{
    $valor = trim($valor);
    return $valor !== '' ? $valor : null;
}
