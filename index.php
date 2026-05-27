<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetLife - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">🐾 PetLife Login</h3>
            
            <?php if(isset($_GET['erro'])): ?>
                <div class="alert alert-danger text-center" role="alert">
                    E-mail ou senha incorretos!
                </div>
            <?php endif; ?>

            <form action="login_action.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="email" id="email" class="form-all form-control" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" name="senha" id="senha" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="recuperar.php" class="text-decoration-none">Esqueceu a senha?</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>