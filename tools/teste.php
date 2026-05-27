<?php
// Inclui o arquivo de conexão
require_once 'config/conexao.php';

if ($conexao) {
    echo "<h1>Sucesso! O sistema PetLife está conectado ao MySQL.</h1>";
}
?>
