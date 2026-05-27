<?php
function garantirCamposEnderecoCliente(PDO $conexao): void
{
    $campos = [
        'rua' => 'VARCHAR(150) NULL',
        'numero' => 'VARCHAR(20) NULL',
        'bairro' => 'VARCHAR(100) NULL',
        'cidade' => 'VARCHAR(100) NULL',
        'uf' => 'CHAR(2) NULL',
        'cep' => 'VARCHAR(10) NULL',
    ];

    foreach ($campos as $campo => $definicao) {
        $stmt = $conexao->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'clientes'
               AND COLUMN_NAME = :campo"
        );
        $stmt->execute(['campo' => $campo]);

        if ((int) $stmt->fetchColumn() === 0) {
            $conexao->exec("ALTER TABLE clientes ADD {$campo} {$definicao}");
        }
    }
}

function montarEnderecoCliente(array $cliente): string
{
    $linha1 = trim(($cliente['rua'] ?? '') . ', ' . ($cliente['numero'] ?? ''), ' ,');
    $linha2 = trim($cliente['bairro'] ?? '');
    $linha3 = trim(($cliente['cidade'] ?? '') . ' - ' . ($cliente['uf'] ?? ''), ' -');
    $linha4 = trim($cliente['cep'] ?? '');

    return implode(' | ', array_filter([$linha1, $linha2, $linha3, $linha4]));
}
