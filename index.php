<?php
require_once 'assets/templates/includes/config.php';
require_once 'assets/templates/includes/auth.php';

// Si hay usuario logueado como 'usuario', redirigir a su dashboard
if (isLoggedIn() && isUsuario()) {
    header('Location: user/dashboard.php');
    exit();
}

// Recoger y limpiar mensaje de error de login de usuario
$login_error = '';
if (!empty($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, Arial, sans-serif; background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; }
        .main-wrapper { width: 100%; min-height: 100vh; display: flex; align-items: stretch; }
        .left-panel { background: linear-gradient(160deg,#1a3a5c 0%,#0d5c8a 60%,#1279b5 100%); color:#fff; flex:1; display:flex; flex-direction:column; justify-content:center; padding:60px 50px; position:relative; overflow:hidden; }
        .left-panel::before { content:""; position:absolute; top:-80px; right:-80px; width:300px; height:300px; background:rgba(255,255,255,0.05); border-radius:50%; }
        .left-panel::after  { content:""; position:absolute; bottom:-60px; left:-60px; width:250px; height:250px; background:rgba(255,255,255,0.04); border-radius:50%; }
        .brand-logo { font-size:2.8rem; font-weight:700; letter-spacing:-1px; margin-bottom:8px; }
        .brand-logo span { color:#5bc8f5; }
        .brand-tagline { font-size:0.9rem; color:rgba(255,255,255,0.65); margin-bottom:40px; letter-spacing:1px; text-transform:uppercase; }
        .left-panel h2 { font-size:2rem; font-weight:700; line-height:1.3; margin-bottom:16px; }
        .desc { font-size:1rem; color:rgba(255,255,255,0.8); line-height:1.7; max-width:380px; }
        .features-list { margin-top:36px; list-style:none; padding:0; }
        .features-list li { display:flex; align-items:center; gap:12px; margin-bottom:14px; font-size:0.9rem; color:rgba(255,255,255,0.85); }
        .features-list .icon { width:32px; height:32px; background:rgba(255,255,255,0.12); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
        .right-panel { width:480px; background:#fff; display:flex; flex-direction:column; justify-content:center; padding:60px 50px; box-shadow:-10px 0 40px rgba(0,0,0,0.08); }
        .right-panel h3 { font-size:1.5rem; font-weight:700; color:#1a3a5c; margin-bottom:6px; }
        .subtitle { font-size:0.875rem; color:#6b7280; margin-bottom:32px; }
        .tab-selector { display:flex; background:#f0f4f8; border-radius:10px; padding:4px; margin-bottom:28px; gap:4px; }
        .tab-selector button { flex:1; border:none; background:transparent; padding:9px 12px; border-radius:7px; font-size:0.85rem; font-weight:500; color:#6b7280; cursor:pointer; font-family:inherit; transition:all 0.2s; }
        .tab-selector button.active { background:#fff; color:#1a3a5c; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:0.78rem; font-weight:600; color:#374151; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
        .form-group input { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb; border-radius:8px; font-size:0.9rem; color:#111827; outline:none; transition:border-color 0.2s; font-family:inherit; }
        .form-group input:focus { border-color:#0d5c8a; box-shadow:0 0 0 3px rgba(13,92,138,0.08); }
        .btn-login { width:100%; padding:12px; border:none; border-radius:8px; font-size:0.92rem; font-weight:600; cursor:pointer; transition:all 0.2s; margin-top:6px; font-family:inherit; }
        .btn-alumno { background:#1279b5; color:#fff; }
        .btn-alumno:hover { background:#0d5c8a; box-shadow:0 4px 12px rgba(18,121,181,0.3); }
        .btn-admin { background:#1a3a5c; color:#fff; }
        .btn-admin:hover { background:#0f2640; box-shadow:0 4px 12px rgba(26,58,92,0.3); }
        .tab-pane { display:none; }
        .tab-pane.active { display:block; }
        .footer-note { margin-top:28px; text-align:center; font-size:0.75rem; color:#9ca3af; }
        @media (max-width:900px) { .left-panel{display:none;} .right-panel{width:100%;padding:40px 30px;box-shadow:none;} }
    </style>
</head>
<body>
<div class="main-wrapper">
    <div class="left-panel">
        <div class="brand-logo">BDO <span>Ixtapaluca</span></div>
        <div class="brand-tagline">Soluciones efectivas en comunicacion</div>
        <h2>Control de Servicio Social y Residencias</h2>
        <p class="desc">Plataforma integral para el seguimiento de horas, generacion de cartas y administracion de alumnos.</p>
        <ul class="features-list">
            <li><span class="icon">&#128203;</span> Registro y control de horas</li>
            <li><span class="icon">&#128196;</span> Generacion de cartas oficiales</li>
            <li><span class="icon">&#128101;</span> Gestion de usuarios por modalidad</li>
            <li><span class="icon">&#128202;</span> Dashboard en tiempo real</li>
        </ul>
    </div>
    <div class="right-panel">
        <h3>Bienvenido</h3>
        <p class="subtitle">Ingresa tus credenciales para acceder al sistema</p>
        <?php if ($login_error): ?>
        <div style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:.83rem;margin-bottom:16px">
            <?php echo htmlspecialchars($login_error); ?>
        </div>
        <?php endif; ?>
        <div class="tab-selector">
            <button class="active" onclick="switchTab('student',this)">Usuario</button>
            <button onclick="switchTab('admin',this)">Administrador</button>
        </div>
        <div class="tab-pane active" id="tab-student">
            <form action="user/login.php" method="POST">
                <div class="form-group">
                    <label>Nombre, Matrícula o ID</label>
                    <input type="text" name="nombre" placeholder="Tu nombre, matrícula o ID" required autocomplete="name">
                </div>
                <button type="submit" class="btn-login btn-alumno">Ingresar como Usuario</button>
            </form>
        </div>
        <div class="tab-pane" id="tab-admin">
            <form action="admin/login.php" method="POST">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" placeholder="Usuario administrador" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Contrasena</label>
                    <input type="password" name="password" placeholder="Contrasena" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login btn-admin">Ingresar como Administrador</button>
            </form>
        </div>
        <p class="footer-note">&copy; <?php echo date('Y'); ?> BDO Ixtapaluca &middot; Sistema de Gestion Academica</p>
    </div>
</div>
<script>
function switchTab(tab,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-selector button').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    btn.classList.add('active');
}
<?php if ($login_error): ?>
// Abrir el tab de usuario automáticamente si hubo error
document.addEventListener('DOMContentLoaded',function(){
    const btn = document.querySelector('.tab-selector button');
    if (btn) switchTab('student', btn);
});
<?php endif; ?>
</script>
</body>
</html>