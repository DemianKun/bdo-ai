<?php
require_once 'assets/templates/includes/config.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    die('Token inválido');
}

// Buscar token
$stmt = $pdo->prepare('SELECT token, user_id, tipo, expires_at FROM login_tokens WHERE token = ?');
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Token no encontrado o expirado');
}

if (strtotime($row['expires_at']) < time()) {
    // Borrar token expirado
    $del = $pdo->prepare('DELETE FROM login_tokens WHERE token = ?');
    $del->execute([$token]);
    die('Token expirado');
}

// Crear sesión y redirigir
session_regenerate_id(true);
$_SESSION['user_id'] = $row['user_id'];
$_SESSION['tipo_usuario'] = $row['tipo'];
// Obtener nombre
if ($row['tipo'] === 'admin') {
    $s = $pdo->prepare('SELECT nombre FROM administradores WHERE id = ?');
    $s->execute([$row['user_id']]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_name'] = $r ? $r['nombre'] : '';
    $redirect = 'admin/dashboard.php';
} else {
    $s = $pdo->prepare('SELECT nombre, matricula FROM alumnos WHERE id = ?');
    $s->execute([$row['user_id']]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_name'] = $r ? $r['nombre'] : '';
    $_SESSION['matricula'] = $r ? $r['matricula'] : '';
    $redirect = 'student/dashboard.php';
}

// Borrar token de un solo uso
$del = $pdo->prepare('DELETE FROM login_tokens WHERE token = ?');
$del->execute([$token]);

header('Location: ' . $redirect);
exit;
?>