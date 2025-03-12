<?php
session_start();

$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function validate_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Регистрация пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    $login = validate_input($_POST['login']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Пароли не совпадают!";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        try {
            $stmt->execute([$login, $password_hash]);
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Ошибка регистрации! Логин уже используется.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Регистрация</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
            <button type="submit" name="register_user">Зарегистрироваться</button>
        </form>
        <p>Уже есть аккаунт? <a href="index.php">Войти</a></p>
    </div>
</body>
</html>