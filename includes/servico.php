<?php
function categoriasServico(): array
{
    return [
        'Petshop / Banho e Tosa',
        'Clinica Veterinaria',
        'Centro de Adocao',
    ];
}

function garantirCampoCategoriaServico(PDO $conexao): void
{
    $stmt = $conexao->query(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'servicos'
           AND COLUMN_NAME = 'categoria'"
    );

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE servicos ADD categoria VARCHAR(50) NULL AFTER nome");
    }
}
