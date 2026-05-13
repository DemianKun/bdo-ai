<?php
require_once 'assets/templates/includes/config.php';

date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    // Log para debugging
    error_log("procesar_asistencia.php - accion: " . $accion . " - timestamp: " . date('Y-m-d H:i:s'));

    if ($accion == 'get_all_templates') {
        header('Content-Type: text/plain');
        
        // Administradores
        $stmt = $pdo->query('SELECT id, nombre, huella1, huella2, huella3, huella4, huella5 FROM administradores WHERE activo = 1');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "admin|{$row['id']}|{$row['nombre']}|";
            for ($i = 1; $i <= 5; $i++) {
                $template = $row["huella$i"];
                echo ($template ? base64_encode($template) : 'NULL');
                if ($i < 5) echo '|';
            }
            echo "\n";
        }
        
        // Usuarios Permanentes (Agente, Paquetería)
        $stmt = $pdo->query('SELECT id, nombre, huella1, huella2, huella3, huella4, huella5 FROM usuarios_permanentes WHERE estado = "activo"');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "permanente|{$row['id']}|{$row['nombre']}|";
            for ($i = 1; $i <= 5; $i++) {
                $template = $row["huella$i"];
                echo ($template ? base64_encode($template) : 'NULL');
                if ($i < 5) echo '|';
            }
            echo "\n";
        }
        
        // Servicio Social y Residencias
        $stmt = $pdo->query('SELECT id, nombre, huella1, huella2, huella3, huella4, huella5 FROM usuarios_servicio_residencias WHERE estado = "activo"');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "servicio_residencias|{$row['id']}|{$row['nombre']}|";
            for ($i = 1; $i <= 5; $i++) {
                $template = $row["huella$i"];
                echo ($template ? base64_encode($template) : 'NULL');
                if ($i < 5) echo '|';
            }
            echo "\n";
        }
        
        // Dual
        $stmt = $pdo->query('SELECT id, nombre, huella1, huella2, huella3, huella4, huella5 FROM usuarios_dual WHERE estado = "activo"');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "dual|{$row['id']}|{$row['nombre']}|";
            for ($i = 1; $i <= 5; $i++) {
                $template = $row["huella$i"];
                echo ($template ? base64_encode($template) : 'NULL');
                if ($i < 5) echo '|';
            }
            echo "\n";
        }
        
        // Jóvenes Construyendo Futuro
        $stmt = $pdo->query('SELECT id, nombre, huella1, huella2, huella3, huella4, huella5 FROM usuarios_jovenes_futuro WHERE estado = "activo"');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "jovenes_futuro|{$row['id']}|{$row['nombre']}|";
            for ($i = 1; $i <= 5; $i++) {
                $template = $row["huella$i"];
                echo ($template ? base64_encode($template) : 'NULL');
                if ($i < 5) echo '|';
            }
            echo "\n";
        }
        exit;
    }

    if ($accion == 'register_attendance') {
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (empty($tipo) || $userId <= 0) {
            echo "Error: Parámetros inválidos (tipo: '$tipo', user_id: '$userId')";
            error_log("procesar_asistencia.php - Error: Parámetros inválidos. tipo: $tipo, user_id: $userId");
            exit;
        }
        
        $hoy = date('Y-m-d');
        $ahora = date('Y-m-d H:i:s');
        $userName = '';
        $tabla = '';
        
        try {
            if ($tipo == 'admin') {
                $stmt = $pdo->prepare('SELECT nombre FROM administradores WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    echo "Error: Administrador no encontrado (ID: $userId)";
                    error_log("procesar_asistencia.php - Admin not found: $userId");
                    exit;
                }
                $userName = $user['nombre'];
                $tabla = 'asistencias_administradores';
            } elseif ($tipo == 'permanente') {
                $stmt = $pdo->prepare('SELECT nombre FROM usuarios_permanentes WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    echo "Error: Usuario permanente no encontrado (ID: $userId)";
                    error_log("procesar_asistencia.php - Permanent user not found: $userId");
                    exit;
                }
                $userName = $user['nombre'];
                $tabla = 'asistencias_permanentes';
            } elseif (in_array($tipo, ['servicio_residencias', 'dual', 'jovenes_futuro'])) {
                $tabla_origen = [
                    'servicio_residencias' => 'usuarios_servicio_residencias',
                    'dual' => 'usuarios_dual',
                    'jovenes_futuro' => 'usuarios_jovenes_futuro'
                ][$tipo];
                
                $stmt = $pdo->prepare("SELECT nombre FROM {$tabla_origen} WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    echo "Error: Usuario $tipo no encontrado (ID: $userId)";
                    error_log("procesar_asistencia.php - $tipo user not found: $userId");
                    exit;
                }
                $userName = $user['nombre'];
                $tabla = 'asistencias_temporales';
            } else {
                echo "Error: Tipo de usuario no válido ($tipo)";
                error_log("procesar_asistencia.php - Invalid user type: $tipo");
                exit;
            }
        } catch (Exception $e) {
            echo "Error en base de datos: " . $e->getMessage();
            error_log("procesar_asistencia.php - Database error: " . $e->getMessage());
            exit;
        }
        
        // Registrar la asistencia
        try {
            if ($tabla == 'asistencias_administradores') {
                $check = $pdo->prepare('SELECT id, hora_entrada, hora_salida FROM asistencias_administradores WHERE usuario_id = ? AND fecha = ?');
                $check->execute([$userId, $hoy]);
                $registro = $check->fetch(PDO::FETCH_ASSOC);
                
                if (!$registro) {
                    $insert = $pdo->prepare('INSERT INTO asistencias_administradores (usuario_id, fecha, hora_entrada) VALUES (?, ?, ?)');
                    $insert->execute([$userId, $hoy, date('H:i:s')]);
                    $mensaje = "ENTRADA registrada - $userName - " . date('H:i:s');
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Attendance recorded: $mensaje");
                } elseif ($registro['hora_salida'] == null) {
                    $horaEntrada = new DateTime($hoy . ' ' . $registro['hora_entrada']);
                    $horaSalida = new DateTime($ahora);
                    $intervalo = $horaEntrada->diff($horaSalida);
                    $horasTrabajadas = $intervalo->h + ($intervalo->i / 60) + ($intervalo->days * 24);
                    
                    $update = $pdo->prepare('UPDATE asistencias_administradores SET hora_salida = ?, horas = ? WHERE id = ?');
                    $update->execute([date('H:i:s'), $horasTrabajadas, $registro['id']]);
                    $mensaje = "SALIDA registrada - $userName - " . date('H:i:s') . " | Horas: " . number_format($horasTrabajadas, 2) . "h";
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Exit recorded: $mensaje");
                } else {
                    echo "Ya registraste entrada y salida hoy - $userName";
                }
            } elseif ($tabla == 'asistencias_permanentes') {
                $check = $pdo->prepare('SELECT id, hora_entrada, hora_salida FROM asistencias_permanentes WHERE usuario_id = ? AND fecha = ?');
                $check->execute([$userId, $hoy]);
                $registro = $check->fetch(PDO::FETCH_ASSOC);
                
                if (!$registro) {
                    $insert = $pdo->prepare('INSERT INTO asistencias_permanentes (usuario_id, fecha, hora_entrada) VALUES (?, ?, ?)');
                    $insert->execute([$userId, $hoy, date('H:i:s')]);
                    $mensaje = "ENTRADA registrada - $userName - " . date('H:i:s');
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Attendance recorded: $mensaje");
                } elseif ($registro['hora_salida'] == null) {
                    $horaEntrada = new DateTime($hoy . ' ' . $registro['hora_entrada']);
                    $horaSalida = new DateTime($ahora);
                    $intervalo = $horaEntrada->diff($horaSalida);
                    $horasTrabajadas = $intervalo->h + ($intervalo->i / 60) + ($intervalo->days * 24);
                    
                    $update = $pdo->prepare('UPDATE asistencias_permanentes SET hora_salida = ?, horas = ? WHERE id = ?');
                    $update->execute([date('H:i:s'), $horasTrabajadas, $registro['id']]);
                    $mensaje = "SALIDA registrada - $userName - " . date('H:i:s') . " | Horas: " . number_format($horasTrabajadas, 2) . "h";
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Exit recorded: $mensaje");
                } else {
                    echo "Ya registraste entrada y salida hoy - $userName";
                }
            } elseif ($tabla == 'asistencias_temporales') {
                $check = $pdo->prepare('SELECT id, hora_entrada, hora_salida FROM asistencias_temporales WHERE usuario_id = ? AND tipo_usuario = ? AND fecha = ?');
                $check->execute([$userId, $tipo, $hoy]);
                $registro = $check->fetch(PDO::FETCH_ASSOC);
                
                if (!$registro) {
                    $insert = $pdo->prepare('INSERT INTO asistencias_temporales (usuario_id, tipo_usuario, fecha, hora_entrada) VALUES (?, ?, ?, ?)');
                    $insert->execute([$userId, $tipo, $hoy, date('H:i:s')]);
                    $mensaje = "ENTRADA registrada - $userName - " . date('H:i:s');
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Attendance recorded: $mensaje");
                } elseif ($registro['hora_salida'] == null) {
                    $horaEntrada = new DateTime($hoy . ' ' . $registro['hora_entrada']);
                    $horaSalida = new DateTime($ahora);
                    $intervalo = $horaEntrada->diff($horaSalida);
                    $horasTrabajadas = $intervalo->h + ($intervalo->i / 60) + ($intervalo->days * 24);
                    
                    $update = $pdo->prepare('UPDATE asistencias_temporales SET hora_salida = ?, horas = ? WHERE id = ?');
                    $update->execute([date('H:i:s'), $horasTrabajadas, $registro['id']]);
                    $mensaje = "SALIDA registrada - $userName - " . date('H:i:s') . " | Horas: " . number_format($horasTrabajadas, 2) . "h";
                    echo $mensaje;
                    error_log("procesar_asistencia.php - Exit recorded: $mensaje");
                } else {
                    echo "Ya registraste entrada y salida hoy - $userName";
                }
            }
        } catch (Exception $e) {
            echo "Error registrando asistencia: " . $e->getMessage();
            error_log("procesar_asistencia.php - Error recording attendance: " . $e->getMessage());
            exit;
        }
        exit;
    }
}
?>
