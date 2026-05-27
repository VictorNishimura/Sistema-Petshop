<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function caminhoApp(string $caminho): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $posicaoPages = strpos($script, '/pages/');

    if ($posicaoPages !== false) {
        $base = substr($script, 0, $posicaoPages);
    } else {
        $base = rtrim(dirname($script), '/');
    }

    return $base . '/' . ltrim($caminho, '/');
}

function exigirLogin(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . caminhoApp('index.php'));
        exit;
    }
}

function usuarioNivel(): string
{
    return $_SESSION['usuario_nivel'] ?? '';
}

function usuarioPode(array $niveisPermitidos): bool
{
    return in_array(usuarioNivel(), $niveisPermitidos, true);
}

function exigirPermissao(array $niveisPermitidos): void
{
    exigirLogin();

    if (!usuarioPode($niveisPermitidos)) {
        header("Location: " . caminhoApp('dashboard.php?erro=permissao'));
        exit;
    }
}
