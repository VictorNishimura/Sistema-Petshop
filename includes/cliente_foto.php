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

function garantirCampoFotoTabela(PDO $conexao, string $tabela): void
{
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :tabela
              AND COLUMN_NAME = 'foto_perfil'";
    $stmt = $conexao->prepare($sql);
    $stmt->execute(['tabela' => $tabela]);

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE {$tabela} ADD foto_perfil VARCHAR(255) NULL");
    }
}

function prepararPastaFotosClientes(): string
{
    return prepararPastaFotos('clientes');
}

function prepararPastaFotos(string $pastaRelativa): string
{
    $pasta = __DIR__ . '/../uploads/' . trim($pastaRelativa, '/');

    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    return $pasta;
}

function salvarFotoCliente(array $arquivo, ?string $fotoAtual = null): ?string
{
    return salvarFotoArquivo($arquivo, 'clientes', $fotoAtual);
}

function salvarFotoArquivo(array $arquivo, string $pastaRelativa, ?string $fotoAtual = null): ?string
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

    $pasta = prepararPastaFotos($pastaRelativa);
    $nomeArquivo = uniqid('cliente_', true) . '.' . $tiposPermitidos[$tipo];
    $destino = $pasta . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Nao foi possivel salvar a foto.');
    }

    excluirFotoCliente($fotoAtual);

    return 'uploads/' . trim($pastaRelativa, '/') . '/' . $nomeArquivo;
}

function salvarFotoClienteCamera(?string $fotoCamera, ?string $fotoAtual = null): ?string
{
    return salvarFotoCamera($fotoCamera, 'clientes', $fotoAtual);
}

function salvarFotoCamera(?string $fotoCamera, string $pastaRelativa, ?string $fotoAtual = null): ?string
{
    if (!$fotoCamera) {
        return $fotoAtual;
    }

    if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $fotoCamera, $matches)) {
        throw new RuntimeException('A foto capturada pela camera e invalida.');
    }

    $extensoes = [
        'jpeg' => 'jpg',
        'png' => 'png',
        'webp' => 'webp',
    ];
    $extensao = $extensoes[$matches[1]];
    $conteudoBase64 = substr($fotoCamera, strpos($fotoCamera, ',') + 1);
    $conteudo = base64_decode($conteudoBase64, true);

    if ($conteudo === false) {
        throw new RuntimeException('Nao foi possivel processar a foto da camera.');
    }

    if (strlen($conteudo) > 2 * 1024 * 1024) {
        throw new RuntimeException('A foto deve ter no maximo 2 MB.');
    }

    $pasta = prepararPastaFotos($pastaRelativa);
    $nomeArquivo = uniqid('cliente_', true) . '.' . $extensao;
    $destino = $pasta . '/' . $nomeArquivo;

    if (file_put_contents($destino, $conteudo) === false) {
        throw new RuntimeException('Nao foi possivel salvar a foto da camera.');
    }

    excluirFotoCliente($fotoAtual);

    return 'uploads/' . trim($pastaRelativa, '/') . '/' . $nomeArquivo;
}

function salvarFotoClienteFormulario(array $arquivo, ?string $fotoCamera, ?string $fotoAtual = null): ?string
{
    return salvarFotoPerfilFormulario($arquivo, $fotoCamera, 'clientes', $fotoAtual);
}

function salvarFotoPerfilFormulario(array $arquivo, ?string $fotoCamera, string $pastaRelativa, ?string $fotoAtual = null): ?string
{
    if ($fotoCamera) {
        return salvarFotoCamera($fotoCamera, $pastaRelativa, $fotoAtual);
    }

    return salvarFotoArquivo($arquivo, $pastaRelativa, $fotoAtual);
}

function excluirFotoCliente(?string $foto): void
{
    excluirFotoPerfil($foto);
}

function excluirFotoPerfil(?string $foto): void
{
    if (!$foto || $foto === caminhoFotoClientePadrao()) {
        return;
    }

    $caminho = realpath(__DIR__ . '/../' . $foto);
    $pastaUploads = realpath(__DIR__ . '/../uploads');

    if ($caminho && $pastaUploads && strpos($caminho, $pastaUploads) === 0 && is_file($caminho)) {
        unlink($caminho);
    }
}

function fotoCliente(?string $foto): string
{
    return $foto ?: caminhoFotoClientePadrao();
}
