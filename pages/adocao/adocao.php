<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/cliente_foto.php';
require_once __DIR__ . '/../../includes/pet.php';
garantirCampoFotoTabela($conexao, 'pets');
garantirCamposDetalhesPet($conexao);

$stmt = $conexao->query(
    "SELECT p.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
     FROM pets p
     INNER JOIN clientes c ON c.id = p.id_cliente
     WHERE p.status_adocao = 1
     ORDER BY p.nome"
);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Adocao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Adocao</h1>
            <p class="text-muted mb-0">Pets disponiveis para encontrar um novo lar</p>
        </div>
        <a href="<?php echo caminhoApp('pages/pets/pets.php'); ?>" class="btn btn-outline-primary">Ver todos os pets</a>
    </div>

    <?php if (count($pets) === 0): ?>
        <div class="alert alert-info">Nenhum pet disponivel para adocao no momento.</div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($pets as $pet): ?>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-start">
                            <img
                                src="<?php echo htmlspecialchars(caminhoApp(fotoCliente($pet['foto_perfil'] ?? null))); ?>"
                                alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                                class="rounded-circle object-fit-cover"
                                width="96"
                                height="96"
                            >
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-2 align-items-start">
                                    <div>
                                        <h2 class="h4 mb-1"><?php echo htmlspecialchars($pet['nome']); ?></h2>
                                        <p class="text-muted mb-2">
                                            <?php echo htmlspecialchars($pet['especie']); ?>
                                            <?php echo !empty($pet['raca']) ? ' - ' . htmlspecialchars($pet['raca']) : ''; ?>
                                        </p>
                                    </div>
                                    <span class="badge text-bg-success">Disponivel</span>
                                </div>

                                <div class="row g-2 small">
                                    <div class="col-md-6"><strong>Sexo:</strong> <?php echo htmlspecialchars($pet['sexo'] ?? ''); ?></div>
                                    <div class="col-md-6"><strong>Idade:</strong> <?php echo htmlspecialchars((string) ($pet['idade'] ?? '')); ?></div>
                                    <div class="col-md-6"><strong>Peso:</strong> <?php echo $pet['peso'] !== null ? htmlspecialchars(number_format((float) $pet['peso'], 2, ',', '.')) . ' kg' : ''; ?></div>
                                    <div class="col-md-6"><strong>Pelagem:</strong> <?php echo htmlspecialchars($pet['pelagem'] ?? ''); ?></div>
                                    <div class="col-md-6"><strong>Temperamento:</strong> <?php echo htmlspecialchars($pet['temperamento'] ?? ''); ?></div>
                                    <div class="col-md-6"><strong>Outros animais:</strong> <?php echo htmlspecialchars($pet['reacao_animais'] ?? ''); ?></div>
                                </div>

                                <?php if (!empty($pet['alergias_restricoes']) || !empty($pet['condicoes_especiais'])): ?>
                                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                                        <?php if (!empty($pet['alergias_restricoes'])): ?>
                                            <div><strong>Alergias/restricoes:</strong> <?php echo htmlspecialchars($pet['alergias_restricoes']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($pet['condicoes_especiais'])): ?>
                                            <div><strong>Condicoes especiais:</strong> <?php echo htmlspecialchars($pet['condicoes_especiais']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($pet['observacoes_gerais'])): ?>
                                    <p class="mt-3 mb-0 small"><strong>Observacoes:</strong> <?php echo nl2br(htmlspecialchars($pet['observacoes_gerais'])); ?></p>
                                <?php endif; ?>

                                <hr>
                                <p class="mb-2 small">
                                    <strong>Tutor/responsavel atual:</strong> <?php echo htmlspecialchars($pet['cliente_nome']); ?>
                                    <?php if (!empty($pet['cliente_telefone'])): ?>
                                        | <?php echo htmlspecialchars($pet['cliente_telefone']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($pet['cliente_email'])): ?>
                                        | <?php echo htmlspecialchars($pet['cliente_email']); ?>
                                    <?php endif; ?>
                                </p>
                                <a href="<?php echo caminhoApp('pages/pets/pet_perfil.php?id=' . $pet['id']); ?>" class="btn btn-sm btn-outline-primary">Ver perfil completo</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
