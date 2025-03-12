<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Подключение к базе данных
$pdo = new PDO('mysql:host=localhost;dbname=fooddelivery', 'root', 'a25d0c');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получение параметров поиска и сортировки
$search = $_GET['search'] ?? '';
$sortOptions = ['name', 'price']; // Разрешенные поля для сортировки
$sort = in_array($_GET['sort'] ?? '', $sortOptions) ? $_GET['sort'] : 'name';

// Получение данных о товарах из таблицы menu
$query = "SELECT * FROM menu WHERE name LIKE :search ORDER BY $sort ASC";
$stmt = $pdo->prepare($query);
$stmt->execute(['search' => "%$search%"]);
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров</title>
    <link rel="stylesheet" href="css/catalog.css">
</head>
<body>
    <div class="container">
        <h2>Каталог товаров</h2>
        <form method="GET">
            <input type="text" name="search" placeholder="Поиск" value="<?= htmlspecialchars($search) ?>">
            <select name="sort">
                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>По названию</option>
                <option value="price" <?= $sort == 'price' ? 'selected' : '' ?>>По цене</option>
            </select>
            <button type="submit">Применить</button>
        </form>
        <div class="product-list">
            <?php foreach ($menuItems as $item): ?>
                <div class="product">
                    <!-- Отображение изображения товара -->
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-image">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p><?= htmlspecialchars($item['description']) ?></p>
                    <p>Цена: <?= htmlspecialchars($item['price']) ?> руб.</p>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <button type="submit">Добавить в корзину</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <p>
            <a href="cart.php" class="cart-button">🛒 Корзина (<?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>)</a>
            | <a href="logout.php">Выйти</a>
        </p>
    </div>
</body>
</html>