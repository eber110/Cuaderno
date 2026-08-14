/**
 * Componentes de Formulario HTML.
 * Agrupa comportamientos especiales para inputs, selects y otros controles de formulario.
 * 
 * @function formComponents
 * @description Inicializa comportamientos especiales de los componentes de formulario,
 *              como la personalización y posicionamiento dinámico de selectores de color.
 */
export function formComponents() {
  
  // 1. Inyectar estilos CSS para normalizar input[type="color"] y para el Custom Color Picker
  if (!document.getElementById('default-color-picker-styles')) {
    const style = document.createElement('style');
    style.id = 'default-color-picker-styles';
    style.textContent = `
      input[type="color"].color-picker {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        outline: none;
      }
      input[type="color"].color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
      }
      input[type="color"].color-picker::-webkit-color-swatch {
        border: none;
        border-radius: inherit;
        box-shadow: inherit;
      }
      input[type="color"].color-picker::-moz-color-swatch {
        border: none;
        border-radius: inherit;
        box-shadow: inherit;
      }

      /* Estilos para el Selector de Color Personalizado (Popover) */
      .custom-color-picker-popover {
        position: fixed;
        z-index: 1000300;
        display: flex;
        flex-direction: column;
        gap: 12px;
        animation: pickerFadeIn 0.2s ease-out;
        font-family: inherit;
      }
      
      /* Estilos base con especificidad 0 para facilitar la sobreescritura */
      :where(.custom-color-picker-popover) {
        width: 240px;
        background: #ffffff;
        border: 1px solid #dcdcdc;
        padding: 15px;
        border-radius: 15px;
      }
      :where(.dark-mode) :where(.custom-color-picker-popover) {
        background: #1e1e1e;
        border-color: #333333;
      }
      @keyframes pickerFadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
      }
      .custom-picker-swatch {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.1s ease, border-color 0.1s ease;
      }
      .custom-picker-swatch:hover {
        transform: scale(1.1);
      }
      .custom-picker-swatch.active {
        border-color: #007bff;
        transform: scale(1.05);
      }
      .custom-hue-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(to right, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000);
        cursor: pointer;
        outline: none;
        border: none;
      }
      .custom-hue-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #555;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }
      .custom-lightness-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(to right, #000000, #808080, #ffffff);
        cursor: pointer;
        outline: none;
        border: none;
      }
      .custom-lightness-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #555;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }
      .custom-saturation-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(to right, #808080, #ff0000);
        cursor: pointer;
        outline: none;
        border: none;
      }
      .custom-saturation-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #555;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }

      /* Estilos para el Checkbox Switch */
      .checkbox-switch-container {
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        vertical-align: middle;
      }
      .checkbox-switch-track {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        background-color: #e0e0e0;
        border-radius: 12px;
        transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #dcdcdc;
      }
      .checkbox-switch-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
      }
      input[type="checkbox"].checkbox-switch:checked + .checkbox-switch-track {
        background-color: #4cd964;
        border-color: #4cd964;
      }
      input[type="checkbox"].checkbox-switch:checked + .checkbox-switch-track .checkbox-switch-thumb {
        transform: translateX(20px);
      }
      .dark-mode .checkbox-switch-track {
        background-color: #333333;
        border-color: #444444;
      }
      .dark-mode input[type="checkbox"].checkbox-switch:checked + .checkbox-switch-track {
        background-color: #30d158;
        border-color: #30d158;
      }
    `;
    document.head.appendChild(style);
  }

  // 2. Procesar atributos style-color en inputs de tipo color
  function styleColorPickers() {
    const colorPickers = document.querySelectorAll('input[type="color"].color-picker');
    colorPickers.forEach(input => {
      // Clases para el propio input (círculo de color)
      const styleClasses = input.getAttribute('style-color') || input.dataset.styleColor || '';
      if (styleClasses) {
        styleClasses.split(' ').forEach(cls => {
          if (cls.trim() && !input.classList.contains(cls.trim())) {
            input.classList.add(cls.trim());
          }
        });
      }
    });
  }

  // 2b. Inicializar switches de tipo checkbox
  function initCheckboxSwitches() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"].checkbox-switch:not([data-switch-initialized])');
    checkboxes.forEach(input => {
      input.setAttribute('data-switch-initialized', 'true');
      
      // Ocultar el checkbox original
      input.style.display = 'none';
      
      // Parsear opciones (data-option="true,false")
      const optionsAttr = input.getAttribute('data-option') || 'true,false';
      const options = optionsAttr.split(',').map(opt => opt.trim());
      const valActive = options[0] || 'true';    // ON (Checked, active="1")
      const valInactive = options[1] || 'false'; // OFF (Unchecked, active="2")
      
      // Leer active actual
      let active = input.getAttribute('active') || '1';
      
      // Configurar estado inicial
      if (active === '1') {
        input.checked = true;
        input.value = valActive;
      } else {
        input.checked = false;
        input.value = valActive; // El valor del input cuando está marcado
      }
      
      // Crear input oculto previo para enviar el valor inactivo si está unchecked
      let hiddenInput = null;
      if (input.name) {
        // Buscar si ya existe el input oculto (para evitar duplicados en actualizaciones)
        let existingHidden = input.previousElementSibling;
        if (existingHidden && existingHidden.tagName === 'INPUT' && existingHidden.type === 'hidden' && existingHidden.name === input.name) {
          hiddenInput = existingHidden;
        } else {
          hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = input.name;
          input.parentNode.insertBefore(hiddenInput, input);
        }
        hiddenInput.value = valInactive;
        hiddenInput.disabled = input.checked;
      }
      
      // Crear la estructura visual del switch
      const wrapper = document.createElement('label');
      wrapper.className = 'checkbox-switch-container';
      
      const track = document.createElement('span');
      track.className = 'checkbox-switch-track';
      
      const thumb = document.createElement('span');
      thumb.className = 'checkbox-switch-thumb';
      
      track.appendChild(thumb);
      
      // Insertar wrapper en lugar del input, e introducir el input y track dentro del wrapper
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);
      wrapper.appendChild(track);
      
      // Evento de cambio
      input.addEventListener('change', () => {
        if (input.checked) {
          input.setAttribute('active', '1');
          if (hiddenInput) {
            hiddenInput.disabled = true;
          }
        } else {
          input.setAttribute('active', '2');
          if (hiddenInput) {
            hiddenInput.disabled = false;
          }
        }
      });
    });
  }

  // Inicializar componentes existentes
  styleColorPickers();
  initCheckboxSwitches();

  document.addEventListener('previewUpdated', () => {
    styleColorPickers();
    initCheckboxSwitches();
  });

  // 3. Funciones Helper para conversión HSL y HEX
  function hslToHex(h, s, l) {
    l /= 100;
    const a = s * Math.min(l, 1 - l) / 100;
    const f = n => {
      const k = (n + h / 30) % 12;
      const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
      return Math.round(255 * color).toString(16).padStart(2, '0');
    };
    return `#${f(0)}${f(8)}${f(4)}`;
  }

  function hexToHsl(hex) {
    let r = parseInt(hex.slice(1, 3), 16) / 255;
    let g = parseInt(hex.slice(3, 5), 16) / 255;
    let b = parseInt(hex.slice(5, 7), 16) / 255;
    let max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    if (max === min) {
      h = s = 0;
    } else {
      let d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h /= 6;
    }
    return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
  }

  // Actualizar la vista previa en vivo en el cliente
  function updateLivePreviewColor(name, hex) {
    if (!name || !hex) return;
    const preview = document.querySelector('.user-profile-preview');
    if (!preview) return;

    if (name === 'back_perfil') {
      preview.querySelectorAll('.back-card').forEach(el => {
        el.style.backgroundColor = hex;
      });
      preview.querySelectorAll('.back-card-container').forEach(el => {
        el.style.backgroundColor = hex;
      });
    } else if (name === 'colorText') {
      preview.querySelectorAll('.desc-hero-regular, .desc-hero-big, .desc-hero-mini, p, .color-text').forEach(el => {
        if (!el.closest('.theme-button')) {
          el.style.color = hex;
        }
      });
    } else if (name === 'titleColor') {
      preview.querySelectorAll('.title-hero-regular, .title-hero-big, .title-hero-mini, h1, h2, h3').forEach(el => {
        el.style.color = hex;
      });
    } else if (name === 'back') {
      preview.querySelectorAll('.theme-button').forEach(el => {
        el.style.backgroundColor = hex;
      });
    } else if (name === 'color') {
      preview.querySelectorAll('.theme-button').forEach(el => {
        el.style.color = hex;
      });
    }
  }

  // Paleta de colores predefinida premium (pasteles, primarios y neutros)
  const presetColors = [
    '#FF4B4B', '#FF8E53', '#FFD369', '#4E9F3D', '#17B978',
    '#3F72AF', '#3D84B8', '#3F51B5', '#8A39E1', '#D1345B',
    '#111111', '#555555', '#888888', '#CCCCCC', '#FFFFFF'
  ];

  let activePopover = null;
  let activeInput = null;

  // Cerrar selector de color y confirmar cambios
  function closeActivePopover(confirm = false) {
    if (!activePopover) return;
    
    const inputRef = activeInput;
    const popoverRef = activePopover;

    activePopover = null;
    activeInput = null;

    if (popoverRef && popoverRef.parentNode) {
      popoverRef.parentNode.removeChild(popoverRef);
    }

    if (confirm && inputRef) {
      const currentInput = inputRef.id ? (document.getElementById(inputRef.id) || inputRef) : inputRef;
      const labelText = currentInput.closest('label')?.querySelector('p, span');
      if (labelText && currentInput.value) {
        labelText.textContent = currentInput.value;
      }
      // Disparar evento de cambio definitivo (indispensable para auto-submit)
      const changeEvent = new Event('change', { bubbles: true });
      currentInput.dispatchEvent(changeEvent);
    }
  }

  // Abrir selector de color personalizado
  function openCustomColorPicker(input, trigger) {
    if (activePopover && activeInput === input) {
      closeActivePopover(true);
      return;
    }

    closeActivePopover(true);

    activeInput = input;

    // Crear contenedor del popover
    const popover = document.createElement('div');
    popover.className = 'custom-color-picker-popover';
    
    // Obtener clases personalizadas de style-box y aplicarlas al popover
    const boxClasses = input.getAttribute('style-box') || input.dataset.styleBox || '';
    if (boxClasses) {
      boxClasses.split(' ').forEach(cls => {
        if (cls.trim()) {
          popover.classList.add(cls.trim());
        }
      });
    }
    
    // Evitar burbujeo al hacer clic dentro del popover
    popover.addEventListener('click', (e) => e.stopPropagation());

    // 1. Grid de colores predefinidos (swatches)
    const grid = document.createElement('div');
    grid.style.cssText = 'display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 5px;';
    
    let currentValue = (input.value || '#000000').toUpperCase();
    if (!currentValue.startsWith('#') || currentValue.length !== 7) {
      currentValue = '#000000';
    }

    presetColors.forEach(color => {
      const swatch = document.createElement('div');
      swatch.className = 'custom-picker-swatch';
      swatch.style.backgroundColor = color;
      if (color === currentValue) {
        swatch.classList.add('active');
      }

      swatch.addEventListener('click', () => {
        popover.querySelectorAll('.custom-picker-swatch').forEach(s => s.classList.remove('active'));
        swatch.classList.add('active');
        
        // Actualizar inputs y sliders
        input.value = color;
        hexInput.value = color;
        preview.style.backgroundColor = color;

        const labelText = input.closest('label')?.querySelector('p, span');
        if (labelText) {
          labelText.textContent = color;
        }

        const hsl = hexToHsl(color);
        hueSlider.value = hsl.h;
        satSlider.value = hsl.s;
        lightSlider.value = hsl.l;
        satSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hsl.h, 0, hsl.l)}, ${hslToHex(hsl.h, 100, hsl.l)})`;
        lightSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hsl.h, hsl.s, 0)}, ${hslToHex(hsl.h, hsl.s, 50)}, ${hslToHex(hsl.h, hsl.s, 100)})`;

        updateLivePreviewColor(input.name, color);

        // Disparar evento input para cambios en tiempo real
        const inputEvent = new Event('input', { bubbles: true });
        input.dispatchEvent(inputEvent);
      });

      grid.appendChild(swatch);
    });

    popover.appendChild(grid);

    // 2. Sliders de espectro cromático HSL
    const slidersContainer = document.createElement('div');
    slidersContainer.style.cssText = 'display: flex; flex-direction: column; gap: 8px;';

    const hslStart = hexToHsl(currentValue);

    // Tono (Hue)
    const hueLabel = document.createElement('div');
    hueLabel.className = 'x12 bold500 textb';
    hueLabel.textContent = 'Tono';
    slidersContainer.appendChild(hueLabel);

    const hueSlider = document.createElement('input');
    hueSlider.type = 'range';
    hueSlider.className = 'custom-hue-slider';
    hueSlider.min = '0';
    hueSlider.max = '360';
    hueSlider.value = hslStart.h;
    slidersContainer.appendChild(hueSlider);

    // Saturación (Saturation)
    const satLabel = document.createElement('div');
    satLabel.className = 'x12 bold500 textb';
    satLabel.textContent = 'Saturación';
    slidersContainer.appendChild(satLabel);

    const satSlider = document.createElement('input');
    satSlider.type = 'range';
    satSlider.className = 'custom-saturation-slider';
    satSlider.min = '0';
    satSlider.max = '100';
    satSlider.value = hslStart.s;
    satSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hslStart.h, 0, hslStart.l)}, ${hslToHex(hslStart.h, 100, hslStart.l)})`;
    slidersContainer.appendChild(satSlider);

    // Luminosidad (Lightness)
    const lightLabel = document.createElement('div');
    lightLabel.className = 'x12 bold500 textb';
    lightLabel.textContent = 'Brillo';
    slidersContainer.appendChild(lightLabel);
    
    const lightSlider = document.createElement('input');
    lightSlider.type = 'range';
    lightSlider.className = 'custom-lightness-slider';
    lightSlider.min = '5';
    lightSlider.max = '95';
    lightSlider.value = hslStart.l;
    lightSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hslStart.h, hslStart.s, 0)}, ${hslToHex(hslStart.h, hslStart.s, 50)}, ${hslToHex(hslStart.h, hslStart.s, 100)})`;
    slidersContainer.appendChild(lightSlider);

    // 3. Footer con Preview y Campo Hexadecimal
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 8px; align-items: center; justify-content: space-between; margin-top: 8px;';

    const preview = document.createElement('div');
    preview.style.cssText = 'width: 32px; height: 32px; border-radius: 50%; border: 1px solid #ccc; flex-shrink: 0;';
    preview.style.backgroundColor = currentValue;

    const hexInput = document.createElement('input');
    hexInput.type = 'text';
    hexInput.className = 'textb p5 br5 border text-center x14 w100';
    hexInput.style.cssText = 'text-transform: uppercase; max-width: 90px;';
    hexInput.value = currentValue;

    function updateColorFromSliders() {
      const h = parseInt(hueSlider.value);
      const s = parseInt(satSlider.value);
      const l = parseInt(lightSlider.value);

      // Actualizar degradados de saturación y brillo basados en la selección
      satSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(h, 0, l)}, ${hslToHex(h, 100, l)})`;
      lightSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(h, s, 0)}, ${hslToHex(h, s, 50)}, ${hslToHex(h, s, 100)})`;

      const hex = hslToHex(h, s, l).toUpperCase();
      input.value = hex;
      hexInput.value = hex;
      preview.style.backgroundColor = hex;

      const labelText = input.closest('label')?.querySelector('p, span');
      if (labelText) {
        labelText.textContent = hex;
      }

      // Quitar marcador activo de la paleta
      popover.querySelectorAll('.custom-picker-swatch').forEach(s => s.classList.remove('active'));

      updateLivePreviewColor(input.name, hex);

      // Disparar evento input para cambios en tiempo real
      const inputEvent = new Event('input', { bubbles: true });
      input.dispatchEvent(inputEvent);
    }

    hueSlider.addEventListener('input', updateColorFromSliders);
    satSlider.addEventListener('input', updateColorFromSliders);
    lightSlider.addEventListener('input', updateColorFromSliders);

    // Input manual por texto
    hexInput.addEventListener('input', () => {
      let hex = hexInput.value;
      if (!hex.startsWith('#')) hex = '#' + hex;
      if (/^#[0-9A-F]{6}$/i.test(hex)) {
        input.value = hex;
        preview.style.backgroundColor = hex;

        const labelText = input.closest('label')?.querySelector('p, span');
        if (labelText) {
          labelText.textContent = hex;
        }

        const hsl = hexToHsl(hex);
        hueSlider.value = hsl.h;
        satSlider.value = hsl.s;
        lightSlider.value = hsl.l;
        satSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hsl.h, 0, hsl.l)}, ${hslToHex(hsl.h, 100, hsl.l)})`;
        lightSlider.style.backgroundImage = `linear-gradient(to right, ${hslToHex(hsl.h, hsl.s, 0)}, ${hslToHex(hsl.h, hsl.s, 50)}, ${hslToHex(hsl.h, hsl.s, 100)})`;

        updateLivePreviewColor(input.name, hex);

        const inputEvent = new Event('input', { bubbles: true });
        input.dispatchEvent(inputEvent);
      }
    });

    popover.appendChild(slidersContainer);
    footer.appendChild(preview);
    footer.appendChild(hexInput);

    // Botón Aceptar
    const acceptBtn = document.createElement('button');
    acceptBtn.className = 'btn-success p5 br5 x14 textw pointer';
    acceptBtn.style.cssText = 'padding: 6px 12px; border: none; font-weight: 500;';
    acceptBtn.textContent = 'Aceptar';
    acceptBtn.addEventListener('click', () => closeActivePopover(true));
    footer.appendChild(acceptBtn);

    popover.appendChild(footer);

    // Añadir al DOM
    document.body.appendChild(popover);

    // Posicionar el popover relativo al trigger
    const rect = trigger.getBoundingClientRect();
    let top = rect.bottom + window.scrollY + 8;
    let left = rect.left + window.scrollX;

    // Ajustes por si se desborda de la pantalla
    if (left + 240 > window.innerWidth) {
      left = window.innerWidth - 250;
    }
    if (top + 340 > window.innerHeight + window.scrollY) {
      top = rect.top + window.scrollY - 360; // Colocar arriba si no cabe abajo
    }

    popover.style.top = `${top}px`;
    popover.style.left = `${left}px`;

    activePopover = popover;
  }

  // 4. Interceptar clics globales en disparadores y labels
  document.addEventListener('click', (e) => {
    // Si hace clic dentro del popover activo, ignorar
    if (e.target.closest('.custom-color-picker-popover')) {
      return;
    }

    // Si hace clic en un label o disparador de color picker
    const trigger = e.target.closest('label, [data-trigger-color], input[type="color"].color-picker');
    
    // Si no hizo clic en el picker o en su disparador, y hay un popover abierto, cerrarlo
    if (!trigger) {
      if (activePopover) {
        closeActivePopover(true);
      }
      return;
    }

    let colorInput = null;
    let targetElement = trigger;

    if (trigger.tagName === 'INPUT' && trigger.type === 'color') {
      colorInput = trigger;
    } else if (trigger.tagName === 'LABEL') {
      const forId = trigger.getAttribute('for');
      colorInput = forId ? document.getElementById(forId) : trigger.querySelector('input[type="color"]');
    } else {
      const inputId = trigger.getAttribute('data-trigger-color');
      colorInput = document.getElementById(inputId);
    }

    // Si encontramos un input de color elegible que use nuestra clase color-picker
    if (colorInput && colorInput.classList.contains('color-picker')) {
      e.preventDefault(); // Cancelar el selector nativo del navegador
      openCustomColorPicker(colorInput, targetElement);
    } else {
      if (activePopover) {
        closeActivePopover(true);
      }
    }
  });
}
