<?php
require_once 'config.php';
require_once 'auth.php';

if (checkAuth()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_POST) {
    $password = $_POST['password'] ?? '';
    if (login($password)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Contraseña incorrecta';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Organizador de Objetos</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-warehouse"></i>
                <h1>Organizador de Objetos</h1>
                <p>Inicia sesión para continuar</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label" for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <p><small>Contraseña por defecto: <strong>admin123</strong></small></p>
            </div>
        </div>
    </div>
</body>
</html>
