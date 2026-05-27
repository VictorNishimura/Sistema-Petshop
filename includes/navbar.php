<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo caminhoApp('dashboard.php'); ?>">PetLife Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $paginaAtual === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo caminhoApp('dashboard.php'); ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($paginaAtual, ['clientes.php', 'cliente_cadastrar.php', 'cliente_editar.php', 'cliente_excluir.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/clientes/clientes.php'); ?>">Clientes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($paginaAtual, ['pets.php', 'pet_cadastrar.php', 'pet_editar.php', 'pet_excluir.php', 'pet_perfil.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/pets/pets.php'); ?>">Pets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($paginaAtual, ['servicos.php', 'servico_cadastrar.php', 'servico_editar.php', 'servico_excluir.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/servicos/servicos.php'); ?>">Servicos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($paginaAtual, ['agendamentos.php', 'agendamento_cadastrar.php', 'agendamento_editar.php', 'agendamento_cancelar.php', 'agendamento_concluir.php', 'agendamento_excluir.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/agendamentos/agendamentos.php'); ?>">Agendamentos</a>
                </li>
                <?php if (usuarioPode(['admin', 'veterinario'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($paginaAtual, ['prontuario.php', 'prontuario_cadastrar.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/prontuario/prontuario.php'); ?>">Prontuario</a>
                    </li>
                <?php endif; ?>
                <?php if (usuarioPode(['admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($paginaAtual, ['funcionarios.php', 'funcionario_cadastrar.php', 'funcionario_editar.php', 'funcionario_excluir.php'], true) ? 'active' : ''; ?>" href="<?php echo caminhoApp('pages/funcionarios/funcionarios.php'); ?>">Funcionarios</a>
                    </li>
                <?php endif; ?>
            </ul>

            <span class="navbar-text text-white me-3">
                Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuario'); ?>!
            </span>
            <a href="<?php echo caminhoApp('logout.php'); ?>" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>
