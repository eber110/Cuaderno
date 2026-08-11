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

    <form id="lemon-checkout-form" action="/lemon-squeezy/checkout" method="POST" class="flex-column gap15 w100">
      <input type="hidden" name="variant_id" value="<?= e(!empty($variantId) ? $variantId : '2004539'); ?>">
      <input type="hidden" name="locale" value="<?= e($locale ?? 'es'); ?>">
      <input type="hidden" name="country" id="country_input" value="<?= e($countryCode ?? 'CL'); ?>">

      <?php if (!empty($username)): ?>
        <input type="hidden" name="user_id" id="user_id_input" value="<?= e($username); ?>">
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

      <a 
        id="lemon-overlay-button"
        href="https://clikhub.lemonsqueezy.com/checkout/buy/e2ba4ce6-2307-4d5e-b965-47b519aca9de?embed=1&checkout[locale]=<?= urlencode($locale ?? 'es') ?>&checkout[country]=<?= urlencode($countryCode ?? 'CL') ?>&checkout[billing_address][country]=<?= urlencode($countryCode ?? 'CL') ?><?= !empty($userEmail) ? '&checkout[email]=' . urlencode($userEmail) : '' ?><?= !empty($username) ? '&checkout[custom][user_id]=' . urlencode($username) : '' ?>"
        class="lemonsqueezy-button btn-checkout mt10 text-center flex-row center-center"
        style="text-decoration: none; display: flex; justify-content: center; align-items: center;"
      >
        Suscribirme con Lemon Squeezy
      </a>
    </form>

    <div class="security-note">
      <span>🔒 Pago 100% seguro encriptado procesado por Lemon Squeezy</span>
    </div>

  </div>

</div>

<!-- Carga oficial de Lemon.js para permitir la experiencia de checkout modal u overlay -->
<script src="https://assets.lemonsqueezy.com/lemon.js" defer></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Inicializar LemonSqueezy Overlay con manejador de eventos
    if (typeof window.createLemonSqueezy === 'function') {
      window.createLemonSqueezy();
    }

    if (window.LemonSqueezy && typeof window.LemonSqueezy.Setup === 'function') {
      window.LemonSqueezy.Setup({
        eventHandler: (event) => {
          if (event.event === 'Checkout.Success') {
            window.location.href = '/lemon-squeezy/success';
          }
          if (event.event === 'Checkout.Closed') {
            console.log('Lemon Squeezy modal cerrado por el usuario.');
          }
        }
      });
    }

    const emailInput = document.getElementById('email_checkout');
    const overlayBtn = document.getElementById('lemon-overlay-button');
    const userIdInput = document.getElementById('user_id_input');
    const countryInput = document.getElementById('country_input');
    
    const userLocale = "<?= e($locale ?? 'es'); ?>";
    const userCountry = "<?= e($countryCode ?? 'CL'); ?>";
    
    const baseUrl = "https://clikhub.lemonsqueezy.com/checkout/buy/e2ba4ce6-2307-4d5e-b965-47b519aca9de?embed=1&checkout[locale]=" + encodeURIComponent(userLocale) + "&checkout[country]=" + encodeURIComponent(userCountry) + "&checkout[billing_address][country]=" + encodeURIComponent(userCountry);


    function updateOverlayUrl() {
      if (!overlayBtn) return;
      let url = baseUrl;
      const emailVal = emailInput ? emailInput.value.trim() : "";
      const userIdVal = userIdInput ? userIdInput.value.trim() : "";

      if (emailVal) {
        url += "&checkout[email]=" + encodeURIComponent(emailVal);
      }
      if (userIdVal) {
        url += "&checkout[custom][user_id]=" + encodeURIComponent(userIdVal);
      }
      overlayBtn.setAttribute('href', url);
    }


    if (emailInput) {
      emailInput.addEventListener('input', updateOverlayUrl);
      emailInput.addEventListener('change', updateOverlayUrl);
    }

    if (overlayBtn) {
      overlayBtn.addEventListener('click', function(e) {
        updateOverlayUrl();
        if (window.LemonSqueezy && window.LemonSqueezy.Url && typeof window.LemonSqueezy.Url.Open === 'function') {
          e.preventDefault();
          window.LemonSqueezy.Url.Open(overlayBtn.getAttribute('href'));
        }
      });
    }
  });
</script>
