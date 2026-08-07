document.addEventListener("DOMContentLoaded", () => {
  // Contenedores de pasos
  const stepUsernameContainer = document.getElementById("step-username-container");
  const stepEmailContainer = document.getElementById("step-email-container");
  const stepPasswordContainer = document.getElementById("step-password-container");

  // Formularios
  const formUsername = document.getElementById("form-step-username");
  const formEmail = document.getElementById("form-step-email");
  const formPassword = document.getElementById("form-step-password");

  // Inputs
  const inputUsername = document.getElementById("input-username");
  const inputEmail = document.getElementById("input-email");
  const inputPassword = document.getElementById("input-password");
  const inputRepassword = document.getElementById("input-repassword");

  const hiddenUsername = document.getElementById("hidden-username");
  const hiddenEmail = document.getElementById("hidden-email");

  // Mensajes de error
  const errorUsername = document.getElementById("error-username");
  const errorEmail = document.getElementById("error-email");
  const errorPassword = document.getElementById("error-password");

  // Botones de retroceso
  const btnBackToUsername = document.getElementById("btn-back-to-username");
  const btnBackToEmail = document.getElementById("btn-back-to-email");

  let countdownInterval = null;

  // Helper para mostrar errores
  function showError(element, message) {
    element.innerHTML = message;
    element.style.display = "block";
  }

  // Helper para ocultar errores
  function hideError(element) {
    element.innerHTML = "";
    element.style.display = "none";
  }

  // Helper para peticiones POST AJAX
  async function apiPost(url, data) {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(data)
    });
    return response;
  }

  // Helper para dar formato de minutos y segundos a un número de segundos
  function formatRemainingTime(totalSeconds) {
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;
    if (mins > 0) {
      return `${mins} min y ${secs} seg`;
    }
    return `${secs} seg`;
  }

  // --- TRANSICIONES ---
  function showStep(containerToShow) {
    // Ocultar todos
    stepUsernameContainer.style.display = "none";
    stepEmailContainer.style.display = "none";
    stepPasswordContainer.style.display = "none";

    // Mostrar el seleccionado
    containerToShow.style.display = "flex";
  }

  // Retroceder de Email a Username
  if (btnBackToUsername) {
    btnBackToUsername.addEventListener("click", () => {
      showStep(stepUsernameContainer);
    });
  }

  // Retroceder de Password a Email
  if (btnBackToEmail) {
    btnBackToEmail.addEventListener("click", () => {
      showStep(stepEmailContainer);
    });
  }

  // --- PASO 1: USERNAME ---
  if (formUsername) {
    formUsername.addEventListener("submit", async (e) => {
      e.preventDefault();
      hideError(errorUsername);

      const username = inputUsername.value.trim();

      // Validaciones del cliente
      if (username.length < 4) {
        showError(errorUsername, "El nombre de usuario debe tener al menos 4 letras.");
        return;
      }

      if (/\s/.test(username)) {
        showError(errorUsername, "El nombre de usuario no debe contener espacios.");
        return;
      }

      const submitBtn = formUsername.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Verificando...";

      try {
        const response = await apiPost("/registrar/check-username", { username });
        const result = await response.json();

        submitBtn.disabled = false;
        submitBtn.textContent = "Continuar";

        if (response.status === 429) {
          // Límite de tasa (Rate limit)
          const seconds = result.seconds || 300;
          submitBtn.disabled = true;
          
          let remaining = seconds;
          showError(errorUsername, `Has sobrepasado los intentos para verificar el usuario.<br>Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`);
          
          if (countdownInterval) clearInterval(countdownInterval);
          countdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
              clearInterval(countdownInterval);
              submitBtn.disabled = false;
              hideError(errorUsername);
            } else {
              errorUsername.innerHTML = `Has sobrepasado los intentos para verificar el usuario.<br>Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`;
            }
          }, 1000);
          return;
        }

        if (!response.ok || result.status === "error") {
          showError(errorUsername, result.message || "Error al verificar el usuario.");
          return;
        }

        if (result.status === "taken") {
          showError(errorUsername, "El nombre de usuario ya está en uso.");
          return;
        }

        if (result.status === "available") {
          // Guardar valor validado en el formulario final
          hiddenUsername.value = username;
          // Avanzar al paso del correo
          showStep(stepEmailContainer);
        }

      } catch (err) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Continuar";
        showError(errorUsername, "No se pudo conectar con el servidor. Inténtalo más tarde.");
        console.error(err);
      }
    });
  }

  // --- PASO 2: EMAIL ---
  if (formEmail) {
    formEmail.addEventListener("submit", async (e) => {
      e.preventDefault();
      hideError(errorEmail);

      const email = inputEmail.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Validaciones del cliente
      if (!emailRegex.test(email)) {
        showError(errorEmail, "El formato de correo electrónico no es válido.");
        return;
      }

      const submitBtn = formEmail.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.textContent = "Verificando...";

      try {
        const response = await apiPost("/registrar/check-email", { email });
        const result = await response.json();

        submitBtn.disabled = false;
        submitBtn.textContent = "Continuar";

        if (response.status === 429) {
          // Límite de tasa (Rate limit)
          const seconds = result.seconds || 300;
          submitBtn.disabled = true;
          
          let remaining = seconds;
          showError(errorEmail, `Has sobrepasado los intentos para verificar el correo.<br>Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`);
          
          if (countdownInterval) clearInterval(countdownInterval);
          countdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
              clearInterval(countdownInterval);
              submitBtn.disabled = false;
              hideError(errorEmail);
            } else {
              errorEmail.innerHTML = `Has sobrepasado los intentos para verificar el correo.<br>Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`;
            }
          }, 1000);
          return;
        }

        if (!response.ok || result.status === "error") {
          showError(errorEmail, result.message || "Error al verificar el correo.");
          return;
        }

        if (result.status === "taken") {
          showError(errorEmail, "El correo electrónico ya está registrado con otra cuenta.");
          return;
        }

        if (result.status === "available") {
          // Guardar valor en formulario final
          hiddenEmail.value = email;
          // Avanzar al paso de contraseña
          showStep(stepPasswordContainer);
        }

      } catch (err) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Continuar";
        showError(errorEmail, "No se pudo conectar con el servidor. Inténtalo más tarde.");
        console.error(err);
      }
    });
  }

  // --- PASO 3: PASSWORD (SUBMIT FINAL) ---
  if (formPassword) {
    formPassword.addEventListener("submit", (e) => {
      hideError(errorPassword);

      const pass = inputPassword.value;
      const repass = inputRepassword.value;

      // Validaciones cliente
      if (pass.length < 8) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe tener al menos 8 caracteres.");
        return;
      }

      if (!/[A-Z]/.test(pass)) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe contener al menos una letra mayúscula.");
        return;
      }

      if (!/[0-9]/.test(pass)) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe contener al menos un número.");
        return;
      }

      if (pass !== repass) {
        e.preventDefault();
        showError(errorPassword, "Las contraseñas ingresadas no coinciden.");
        return;
      }

      // Asegurar que los datos ocultos sigan cargados
      if (!hiddenUsername.value || !hiddenEmail.value) {
        e.preventDefault();
        showError(errorPassword, "Faltan datos de los pasos anteriores. Por favor, reinicia el registro.");
        return;
      }

      // Se deja que el envío del formulario ocurra de forma nativa a /registrar
    });
  }
});
