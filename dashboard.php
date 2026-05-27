<?php
session_start();

// TRAVA DE SEGURANÇA: Se não existir a sessão do usuário, expulsa ele
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PetLife - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">🐾 PetLife Admin</span>
        <span class="text-white">Bem-vindo(a), <?php echo $_SESSION['usuario_nome']; ?>!</span>
    </div>
</nav>

<div class="container mt-5">
    <h1>Painel de Controle</h1>
    <p>Aqui ficarão os botões de atalho para os módulos de Veterinária, Banho e Tosa, Hospedagem e Vendas.</p>
    
    <a href="logout.php" class="btn btn-danger">Sair do Sistema</a>
</div>

</body>
</html>