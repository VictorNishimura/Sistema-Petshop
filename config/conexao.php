<?php
// Configurações do Banco de Dados
$host = 'localhost';
$db_name = 'DB_Petlife';
$username = 'root'; // Usuário padrão do XAMPP/Laragon
$password = '';     // Senha padrão do XAMPP (vazia) ou 'root' no Laragon

try {
    // Cria a conexão utilizando o PDO
    $conexao = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    
    // Configura o PDO para lançar exceções em caso de erros
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configura o retorno padrão dos dados como array associativo
    $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Se houver erro na conexão, exibe a mensagem e para a execução do sistema
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>