<?php
// Inicia a sessão para podermos usar $_SESSION
session_start();

// Inclui a nossa ponte com o banco de dados
require_once 'config/conexao.php';

// Verifica se os dados vieram via método POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Busca o usuário no banco de dados
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $usuario = $stmt->fetch();

    // Se o usuário existir, vamos verificar a senha
    // Nota: Em produção usaríamos password_verify, para o trabalho acadêmico direto pode ser comparação simples se as senhas forem salvas em texto limpo para testes.
    if ($usuario && $senha === $usuario['senha']) {
        // LOGIN COM SUCESSO: Salva os dados na Sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel'];

        // Redireciona para o Painel Principal
        header("Location: dashboard.php");
        exit;
    } else {
        // FALHA NO LOGIN: Redireciona de volta com um aviso de erro
        header("Location: index.php?erro=1");
        exit;
    }
}