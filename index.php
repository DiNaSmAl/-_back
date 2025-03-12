<?php
session_start();

// Если пользователь уже авторизован, перенаправляем в каталог
if (isset($_SESSION['user'])) {
    header("Location: catalog.php");
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function validate_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Авторизация
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'])) {
    $login = validate_input($_POST['login']);
    $password = $_POST['password'];
    
    // Проверяем, существует ли пользователь с таким логином
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Проверяем пароль
        if (password_verify($password, $user['password_hash'])) {
            // Успешная авторизация
            $_SESSION['user'] = $user['id'];
            header("Location: catalog.php");
            exit();
        } else {
            $error = "Ошибка авторизации! Неверный пароль.";
        }
    } else {
        $error = "Ошибка авторизации! Пользователь с таким логином не найден.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Авторизация</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="login_user">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
    </div>
</body>
</html>