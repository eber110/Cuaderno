<?php
  /** @var string $variantId ID opcional de variante */
  /** @var string $userEmail Email del usuario si está logueado */
  /** @var string $username Nombre de usuario si está logueado */
?>

<div class="pricing-card-wrapper flex-column gap20 align-center justify-center w100 my30">
  
  <style>
    .pricing-card {
      background: var(--back-color-card, rgba(255, 255, 255, 0.05));
      border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
      border-radius: 20px;
      padding: 35px 30px;
      max-width: 480px;
      width: 100%;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .pricing-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
      border-color: var(--primary-color, #6366f1);
    }

    .pricing-badge {
      background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
      color: #ffffff;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 6px 16px;
      border-radius: 50px;
      display: inline-block;
      margin-bottom: 15px;
    }

    .pricing-amount {
      font-size: 3.2rem;
      font-weight: 800;
      color: var(--text-primary, #ffffff);
      line-height: 1;
    }

    .pricing-currency {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-secondary, #9ca3af);
    }

    .pricing-period {
      font-size: 1rem;
      color: var(--text-secondary, #9ca3af);
      font-weight: 500;
    }

    .pricing-clp-approx {
      font-size: 0.95rem;
      color: #10b981;
      font-weight: 600;
      margin-top: 5px;
    }

    .feature-list {
      list-style: none;
      padding: 0;
      margin: 25px 0;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.98rem;
      color: var(--text-primary, #e5e7eb);
    }

    .feature-icon {
      width: 22px;
      height: 22px;
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      flex-shrink: 0;
    }

    .btn-checkout {
      background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
      color: #ffffff;
      font-size: 1.1rem;
      font-weight: 700;
      padding: 16px 28px;
      border-radius: 14px;
      border: none;
      cursor: pointer;
      width: 100%;
      text-align: center;
      transition: all 0.25s ease;
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35);
      text-decoration: none;
      display: inline-block;
    }

    .btn-checkout:hover {
      background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
      transform: scale(1.02);
      box-shadow: 0 14px 30px rgba(99, 102, 241, 0.45);
    }

    .security-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 0.85rem;
      color: var(--text-secondary, #9ca3af);
      margin-top: 18px;
    }
  </style>

  <div class="pricing-card flex-column">

    <div class="text-center mb10">
      <span class="pricing-badge">Plan Mensual Pro</span>
      <h2 class="bold700 font24 color-text mb5">Suscripción Premium</h2>
      <p class="color-secondary font14">Acceso ilimitado a todas las herramientas avanzadas</p>
    </div>

    <div class="text-center my15">
      <div class="flex-row justify-center align-baseline gap5">
        <span class="pricing-currency">$</span>
        <span class="pricing-amount">4</span>
        <span class="pricing-period">USD / mes</span>
      </div>
      <div class="pricing-clp-approx">
        (~ $3.500 CLP mensuales aprox.)
      </div>
    </div>

    <ul class="feature-list">
      <li class="feature-item">
        <span class="feature-icon">✓</span>
        <span>Acceso total sin restricciones</span>
      </li>
      <li class="feature-item">
        <span class="feature-icon">✓</span>
        <span>Soporte prioritario y atención personalizada</span>
      </li>
      <li class="feature-item">
        <span class="feature-icon">✓</span>
        <span>Actualizaciones continuas incluidas</span>
      </li>
      <li class="feature-item">
        <span class="feature-icon">✓</span>
        <span>Cancela tu suscripción en cualquier momento</span>
      </li>
    </ul>

    <form action="/lemon-squeezy/checkout" method="POST" class="flex-column gap15 w100">
      <input type="hidden" name="variant_id" value="<?= e(!empty($variantId) ? $variantId : '2004539'); ?>">

      <?php if (!empty($username)): ?>
        <input type="hidden" name="user_id" value="<?= e($username); ?>">
      <?php endif; ?>


      <div class="flex-column gap5">
        <label for="email_checkout" class="font13 bold600 color-secondary">Tu correo electrónico:</label>
        <input 
          type="email" 
          id="email_checkout" 
          name="email" 
          value="<?= e($userEmail ?? ''); ?>" 
          placeholder="ejemplo@correo.com" 
          required 
          class="input-custom w100 padding12 br10 border1 color-text back-card"
          style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.15); color: inherit;"
        >
      </div>

      <button type="submit" class="btn-checkout mt10">
        Suscribirme con Lemon Squeezy
      </button>
    </form>

    <div class="security-note">
      <span>🔒 Pago 100% seguro encriptado procesado por Lemon Squeezy</span>
    </div>

  </div>

</div>
