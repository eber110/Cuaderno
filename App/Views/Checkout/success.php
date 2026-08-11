<?php
  /** @var string $message Mensaje opcional */
?>

<div class="container container-xl-mid container-sml flex-column gap30 w100 padding20 align-center justify-center min-h80vh">

  <style>
    .success-card {
      background: var(--back-color-card, rgba(255, 255, 255, 0.04));
      border: 1px solid rgba(16, 185, 129, 0.3);
      border-radius: 24px;
      padding: 45px 35px;
      max-width: 520px;
      width: 100%;
      box-shadow: 0 20px 45px rgba(16, 185, 129, 0.12);
      backdrop-filter: blur(12px);
      text-align: center;
    }

    .success-icon-wrapper {
      width: 80px;
      height: 80px;
      background: rgba(16, 185, 129, 0.15);
      border: 2px solid #10b981;
      color: #10b981;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px auto;
      box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
    }

    .status-badge-success {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      font-size: 0.85rem;
      font-weight: 700;
      padding: 6px 18px;
      border-radius: 50px;
      display: inline-block;
      margin-bottom: 12px;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .btn-success-action {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #ffffff;
      font-size: 1.05rem;
      font-weight: 700;
      padding: 14px 28px;
      border-radius: 12px;
      text-decoration: none;
      display: inline-block;
      transition: all 0.25s ease;
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-success-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 28px rgba(16, 185, 129, 0.4);
    }

    .btn-secondary-action {
      background: transparent;
      color: var(--text-primary, #ffffff);
      font-size: 0.95rem;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 12px;
      border: 1px solid var(--border-color, rgba(255, 255, 255, 0.2));
      text-decoration: none;
      display: inline-block;
      transition: all 0.2s ease;
    }

    .btn-secondary-action:hover {
      background: rgba(255, 255, 255, 0.08);
    }
  </style>

  <div class="success-card flex-column align-center">
    
    <div class="success-icon-wrapper">
      <div style="font-size: 40px; line-height: 1;">✓</div>
    </div>

    <span class="status-badge-success">Suscripción Activa</span>
    
    <h1 class="bold800 font28 color-text mb10">¡Pago Procesado con Éxito!</h1>
    
    <p class="color-secondary font15 line-height-mid mb25">
      <?= e($message ?? "Gracias por tu compra. Tu suscripción mensual Pro de $4 USD se ha activado correctamente y ya tienes acceso total."); ?>
    </p>

    <div class="flex-column gap12 w100 mt10">
      <a href="/" class="btn-success-action w100">
        Ir al Panel de Control
      </a>
      <a href="/" class="btn-secondary-action w100">
        Volver al Inicio
      </a>
    </div>

  </div>

</div>
