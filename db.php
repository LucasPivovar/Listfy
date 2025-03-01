<?php
$host = 'sql200.infinityfree.com';
$dbname = 'if0_38253128_listify';
$username = 'if0_38253128'; // Altere se necessário
$password = '25FG04yt08'; // Altere se necessário

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>
