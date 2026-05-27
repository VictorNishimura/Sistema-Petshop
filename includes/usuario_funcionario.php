<?php
function garantirCampoUsuarioFuncionario(PDO $conexao): void
{
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'id_funcionario'";
    $stmt = $conexao->query($sql);

    if ((int) $stmt->fetchColumn() === 0) {
        $conexao->exec("ALTER TABLE usuarios ADD id_funcionario INT NULL AFTER id");
    }
}

function nivelUsuarioPorCargo(string $cargo): string
{
    if ($cargo === 'Veterinario') {
        return 'veterinario';
    }

    if ($cargo === 'Gerente') {
        return 'admin';
    }

    return 'funcionario';
}

function buscarUsuarioFuncionario(PDO $conexao, int $idFuncionario): ?array
{
    $stmt = $conexao->prepare("SELECT id, nome, email, nivel, pergunta_secreta, resposta_secreta FROM usuarios WHERE id_funcionario = :id_funcionario LIMIT 1");
    $stmt->execute(['id_funcionario' => $idFuncionario]);
    $usuario = $stmt->fetch();

    return $usuario ?: null;
}

function validarDadosAcesso(array $dados, bool $senhaObrigatoria): string
{
    if (trim($dados['email'] ?? '') === '') {
        return 'Informe o e-mail de acesso.';
    }

    if ($senhaObrigatoria && trim($dados['senha'] ?? '') === '') {
        return 'Informe a senha de acesso.';
    }

    if (trim($dados['pergunta_secreta'] ?? '') === '' || trim($dados['resposta_secreta'] ?? '') === '') {
        return 'Informe a pergunta e a resposta secreta.';
    }

    return '';
}

function criarUsuarioFuncionario(PDO $conexao, int $idFuncionario, string $nome, string $cargo, array $dados): void
{
    $erro = validarDadosAcesso($dados, true);

    if ($erro !== '') {
        throw new RuntimeException($erro);
    }

    $stmt = $conexao->prepare(
        "INSERT INTO usuarios (id_funcionario, nome, email, senha, pergunta_secreta, resposta_secreta, nivel)
         VALUES (:id_funcionario, :nome, :email, :senha, :pergunta_secreta, :resposta_secreta, :nivel)"
    );
    $stmt->execute([
        'id_funcionario' => $idFuncionario,
        'nome' => $nome,
        'email' => trim($dados['email']),
        'senha' => trim($dados['senha']),
        'pergunta_secreta' => trim($dados['pergunta_secreta']),
        'resposta_secreta' => trim($dados['resposta_secreta']),
        'nivel' => nivelUsuarioPorCargo($cargo),
    ]);
}

function atualizarUsuarioFuncionario(PDO $conexao, int $idUsuario, string $nome, string $cargo, array $dados): void
{
    $erro = validarDadosAcesso($dados, false);

    if ($erro !== '') {
        throw new RuntimeException($erro);
    }

    $params = [
        'id' => $idUsuario,
        'nome' => $nome,
        'email' => trim($dados['email']),
        'pergunta_secreta' => trim($dados['pergunta_secreta']),
        'resposta_secreta' => trim($dados['resposta_secreta']),
        'nivel' => nivelUsuarioPorCargo($cargo),
    ];

    $sqlSenha = '';
    if (trim($dados['senha'] ?? '') !== '') {
        $sqlSenha = ', senha = :senha';
        $params['senha'] = trim($dados['senha']);
    }

    $stmt = $conexao->prepare(
        "UPDATE usuarios
         SET nome = :nome, email = :email, pergunta_secreta = :pergunta_secreta,
             resposta_secreta = :resposta_secreta, nivel = :nivel{$sqlSenha}
         WHERE id = :id"
    );
    $stmt->execute($params);
}

function excluirUsuarioFuncionario(PDO $conexao, int $idFuncionario): void
{
    $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id_funcionario = :id_funcionario");
    $stmt->execute(['id_funcionario' => $idFuncionario]);
}
