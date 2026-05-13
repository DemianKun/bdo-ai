<?php
require_once 'assets/templates/includes/config.php';

// Establecer zona horaria de México
date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($accion == 'get_users_by_type') {
        // Nueva acción para obtener lista de usuarios por tipo
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $subtipo = isset($_POST['subtipo']) ? $_POST['subtipo'] : '';

        if (empty($tipo)) {
            echo 'Error: Tipo no especificado';
            exit;
        }

        $usuarios = [];
        
        if ($tipo == 'admin') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM administradores WHERE activo = 1 ORDER BY nombre');
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'permanente') {
            // Filtrar por tipo_usuario si se proporciona subtipo
            if (!empty($subtipo)) {
                $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_permanentes WHERE estado = "activo" AND tipo_usuario = ? ORDER BY nombre');
                $stmt->execute([$subtipo]);
            } else {
                $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_permanentes WHERE estado = "activo" ORDER BY nombre');
                $stmt->execute();
            }
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'servicio_residencias') {
            // Filtrar por tipo_usuario si se proporciona subtipo
            if (!empty($subtipo)) {
                $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_servicio_residencias WHERE estado = "activo" AND tipo_usuario = ? ORDER BY nombre');
                $stmt->execute([$subtipo]);
            } else {
                $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_servicio_residencias WHERE estado = "activo" ORDER BY nombre');
                $stmt->execute();
            }
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'dual') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_dual WHERE estado = "activo" ORDER BY nombre');
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'jovenes_futuro') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_jovenes_futuro WHERE estado = "activo" ORDER BY nombre');
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo 'Error: Tipo de usuario inválido';
            exit;
        }
        
        // Devolver en formato: id|nombre\n para cada usuario
        foreach ($usuarios as $user) {
            echo $user['id'] . '|' . $user['nombre'] . "\n";
        }
        exit;
    }

    if ($accion == 'verify_user_exists') {
        // Nueva acción para verificar si el usuario existe
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

        if (empty($tipo) || empty($nombre)) {
            echo 'Error: Parámetros inválidos';
            exit;
        }

        $row = null;
        if ($tipo == 'admin') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM administradores WHERE nombre LIKE ?');
            $stmt->execute(["%{$nombre}%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'permanente') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_permanentes WHERE nombre LIKE ?');
            $stmt->execute(["%{$nombre}%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'servicio_residencias') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_servicio_residencias WHERE nombre LIKE ?');
            $stmt->execute(["%{$nombre}%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'dual') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_dual WHERE nombre LIKE ?');
            $stmt->execute(["%{$nombre}%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($tipo == 'jovenes_futuro') {
            $stmt = $pdo->prepare('SELECT id, nombre FROM usuarios_jovenes_futuro WHERE nombre LIKE ?');
            $stmt->execute(["%{$nombre}%"]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo 'Error: Tipo de usuario inválido';
            exit;
        }
        
        if ($row) {
            echo "Usuario existe: " . $row['nombre'];
        } else {
            echo "Error: El usuario '{$nombre}' no existe en el tipo seleccionado. Por favor regístralo primero en el sistema web.";
        }
        exit;
    }

    if ($accion == 'get_template') {
        // Nueva acción para obtener el template almacenado
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : ''; // Mantener como string para matrículas

        if (empty($tipo) || empty($id)) {
            echo 'error: Parámetros inválidos';
            exit;
        }

        if ($tipo == 'admin') {
            // Para administradores, buscar por ID numérico
            $stmt = $pdo->prepare('SELECT fingerprint_template, nombre FROM administradores WHERE id = ?');
            $stmt->execute([intval($id)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo 'error: Tipo de usuario inválido';
            exit;
        }
        
        if (!$row) {
            // Usuario no existe
            echo "error: Administrador ({$id}) no existe";
            exit;
        }
        
        if ($row['fingerprint_template']) {
            // El template ya está en base64 si se guardó así, o en binario
            // Verificar si ya es base64 válido
            $template = $row['fingerprint_template'];
            if (base64_decode($template, true) !== false) {
                // Ya está en base64
                echo $template;
            } else {
                // Es binario, convertir a base64
                echo base64_encode($template);
            }
        } else {
            // Usuario existe pero no tiene huella
            echo "error: Administrador ({$row['nombre']}) no tiene huella registrada";
        }
        exit;
    }
    
    if ($accion == 'login_verified') {
        // Crear sesión después de verificación exitosa en el cliente
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : ''; // Mantener como string

        if ($tipo == 'admin') {
            // Para administradores, buscar por ID numérico
            $stmt = $pdo->prepare('SELECT id, nombre FROM administradores WHERE id = ?');
            $stmt->execute([intval($id)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo 'error: Tipo de usuario inválido';
            exit;
        }
        
        if ($row) {
            // Limpiar sesión anterior
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['tipo_usuario'] = $tipo;
            $_SESSION['user_name'] = $row['nombre'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['login_method'] = 'fingerprint';
            
            // Generar token de autologin (válido por 60s)
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 60);

            // Crear tabla si no existe
            $pdo->exec("CREATE TABLE IF NOT EXISTS login_tokens (
                token VARCHAR(64) PRIMARY KEY,
                user_id INT NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                expires_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $insert = $pdo->prepare('INSERT INTO login_tokens (token, user_id, tipo, expires_at) VALUES (?, ?, ?, ?)');
            $insert->execute([$token, $row['id'], $tipo, $expires]);

            $autologinUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/autologin.php?token=' . $token;

            // Respuesta JSON para mejor manejo
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => $tipo == 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php',
                'autologin' => $autologinUrl,
                'user' => $row['nombre'],
                'tipo' => $tipo
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        }
        exit;
    }
    
    // Solo decodificar 'template' si existe (para compatibilidad con código antiguo)
    $template = isset($_POST['template']) ? base64_decode($_POST['template']) : null;

    
    if ($accion == 'enroll_multiple') {
        // Registrar múltiples huellas (5 dedos)
        $tipo = $_POST['tipo'];
        $nombre = trim($_POST['nombre']);
        $template1 = !empty($_POST['template1']) ? base64_decode($_POST['template1']) : null;
        $template2 = !empty($_POST['template2']) ? base64_decode($_POST['template2']) : null;
        $template3 = !empty($_POST['template3']) ? base64_decode($_POST['template3']) : null;
        $template4 = !empty($_POST['template4']) ? base64_decode($_POST['template4']) : null;
        $template5 = !empty($_POST['template5']) ? base64_decode($_POST['template5']) : null;

        $usuario = null;
        $tabla = '';
        $columnas_huella = '';

        if ($tipo == 'admin') {
            $tabla = 'administradores';
            $columnas_huella = 'huella1 = ?, huella2 = ?, huella3 = ?, huella4 = ?, huella5 = ?';
        } elseif ($tipo == 'permanente') {
            $tabla = 'usuarios_permanentes';
            $columnas_huella = 'huella1 = ?, huella2 = ?, huella3 = ?, huella4 = ?, huella5 = ?';
        } elseif ($tipo == 'servicio_residencias') {
            $tabla = 'usuarios_servicio_residencias';
            $columnas_huella = 'huella1 = ?, huella2 = ?, huella3 = ?, huella4 = ?, huella5 = ?';
        } elseif ($tipo == 'dual') {
            $tabla = 'usuarios_dual';
            $columnas_huella = 'huella1 = ?, huella2 = ?, huella3 = ?, huella4 = ?, huella5 = ?';
        } elseif ($tipo == 'jovenes_futuro') {
            $tabla = 'usuarios_jovenes_futuro';
            $columnas_huella = 'huella1 = ?, huella2 = ?, huella3 = ?, huella4 = ?, huella5 = ?';
        } else {
            echo 'Tipo de usuario inválido.';
            exit;
        }

        // Buscar usuario
        $check = $pdo->prepare("SELECT id, nombre FROM {$tabla} WHERE nombre LIKE ?");
        $check->execute(["%{$nombre}%"]);
        $usuario = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            echo "Usuario no encontrado en {$tabla}.";
            exit;
        }

        // Actualizar huellas
        $stmt = $pdo->prepare("UPDATE {$tabla} SET {$columnas_huella} WHERE id = ?");
        $stmt->execute([$template1, $template2, $template3, $template4, $template5, $usuario['id']]);
        echo 'Huellas registradas exitosamente para ' . $usuario['nombre'];
    }
    
    if ($accion == 'enroll') {
        $tipo = $_POST['tipo'];
        $id = $_POST['id']; // Mantener como string

        if ($tipo == 'admin') {
            // Verificar existencia del admin y obtener nombre
            $check = $pdo->prepare('SELECT id, nombre FROM administradores WHERE id = ?');
            $check->execute([intval($id)]);
            $usuario = $check->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                echo 'Usuario (admin) no encontrado.';
                exit;
            }
            // Para administradores, actualizar por ID numérico (solo huella1)
            $stmt = $pdo->prepare('UPDATE administradores SET huella1 = ? WHERE id = ?');
            $stmt->execute([$template, intval($id)]);
            echo 'Huella registrada exitosamente para ' . $usuario['nombre'];
        } else {
            echo 'Tipo de usuario inválido.';
            exit;
        }
    } elseif ($accion == 'verify') {
        // Verificación: primero administradores, luego usuarios del resto de tablas
        $matched = false;
        
        // Buscar en administradores (huella1)
        $stmt = $pdo->query('SELECT id, nombre, huella1 FROM administradores WHERE huella1 IS NOT NULL');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['huella1'] === $template) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['tipo_usuario'] = 'admin';
                $_SESSION['user_name'] = $row['nombre'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['login_method'] = 'fingerprint';
                echo 'redirect_admin';
                $matched = true;
                exit;
            }
        }
        
        if (!$matched) {
            echo 'Huella no reconocida.';
        }
    }
}
?>