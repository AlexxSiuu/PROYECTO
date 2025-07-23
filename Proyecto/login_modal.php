<!-- login_modal.php -->
<div class="modal" id="loginModal" style="display:none;">
  <div class="modal-content" style="background:white; padding:20px; border-radius:8px; min-width:300px;">
    <span id="closeLogin" style="float:right; cursor:pointer;">&times;</span>

    <h2>Iniciar sesión</h2>

    <?php if (isset($_GET['error'])): ?>
      <p style="color:red;">❌ Correo o contraseña incorrectos</p>
    <?php endif; ?>

    <form method="POST" action="procesar_login.php">
      <input type="email" name="correo" placeholder="Correo" required><br><br>
      <input type="password" name="contrasena" placeholder="Contraseña" required><br><br>
      <input type="submit" value="Iniciar sesión">
    </form>

    <p style="text-align:center;">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
  </div>
</div>

<style>
.modal {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.6);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
</style>
