<?php
function caminhoFotoClientePadrao(): string
{
    return 'uploads/clientes/sem-foto.svg';
}

function garantirCampoFotoCliente(PDO $conexao): void
{
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'foto_perfil'";
    $stmt = $conexao->query($sql);

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE clientes ADD foto_perfil VARCHAR(255) NULL AFTER endereco");
    }
}

function prepararPastaFotosClientes(): string
{
    $pasta = __DIR__ . '/../uploads/clientes';

    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    return $pasta;
}

function salvarFotoCliente(array $arquivo, ?string $fotoAtual = null): ?string
{
    if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return $fotoAtual;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nao foi possivel enviar a foto.');
    }

    if ($arquivo['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('A foto deve ter no maximo 2 MB.');
    }

    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo = $finfo->file($arquivo['tmp_name']);

    if (!isset($tiposPermitidos[$tipo])) {
        throw new RuntimeException('Envie uma foto nos formatos JPG, PNG ou WEBP.');
    }

    $pasta = prepararPastaFotosClientes();
    $nomeArquivo = uniqid('cliente_', true) . '.' . $tiposPermitidos[$tipo];
    $destino = $pasta . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Nao foi possivel salvar a foto.');
    }

    excluirFotoCliente($fotoAtual);

    return 'uploads/clientes/' . $nomeArquivo;
}

function excluirFotoCliente(?string $foto): void
{
    if (!$foto || $foto === caminhoFotoClientePadrao()) {
        return;
    }

    $caminho = realpath(__DIR__ . '/../' . $foto);
    $pastaUploads = realpath(__DIR__ . '/../uploads/clientes');

    if ($caminho && $pastaUploads && strpos($caminho, $pastaUploads) === 0 && is_file($caminho)) {
        unlink($caminho);
    }
}

function fotoCliente(?string $foto): string
{
    return $foto ?: caminhoFotoClientePadrao();
}
