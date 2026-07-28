/**
 * Módulo de gestión de fuentes
 * Maneja la detección, carga y estados de las fuentes web
 */
export function initFontManager() {

  const FontManager = {
    // Configuración de las fuentes principales
    fonts: {
      'AlumniSansSC': {
        weights: ['100', '200', '300', '400', '500', '600', '700', '800', '900'],
        hasItalic: true
      },
      'RobotoCondensed': {
        weights: ['100', '200', '300', '400', '500', '600', '700', '800', '900'],
        hasItalic: true
      }
    },
  
    /**
     * Inicializa el sistema de detección de fuentes
     */
    init() {
      // Verificar si el navegador soporta la API de fuentes
      if (!('fonts' in document)) {
        console.warn('La API de fuentes no está soportada en este navegador');
        this.markAsFallback();
        return;
      }
  
      // Iniciar la detección de fuentes
      this.detectFonts();
    },
  
    /**
     * Detecta el estado de carga de las fuentes
     */
    async detectFonts() {
      try {
        // Esperar a que todas las fuentes estén cargadas
        await document.fonts.ready;
  
        // Verificar cada fuente configurada
        const loadedFonts = await this.checkConfiguredFonts();
        
        if (loadedFonts.length > 0) {
          this.markAsFontsLoaded(loadedFonts);
        } else {
          console.warn('No se detectaron fuentes cargadas');
          this.markAsFallback();
        }
  
      } catch (error) {
        console.error('Error al cargar las fuentes:', error);
        this.markAsFallback();
      }
    },
  
    /**
     * Verifica el estado de carga de las fuentes configuradas
     */
    async checkConfiguredFonts() {
      const loadedFonts = [];
  
      for (const [familyName, config] of Object.entries(this.fonts)) {
        // Verificar la fuente regular
        const isRegularLoaded = await document.fonts.check(`400 normal 16px "${familyName}"`);
        
        if (isRegularLoaded) {
          loadedFonts.push(familyName);
          
          // Verificar variantes
          await this.checkFontVariants(familyName, config);
        }
      }
  
      return loadedFonts;
    },
  
    /**
     * Verifica las variantes de una familia de fuentes
     */
    async checkFontVariants(familyName, config) {
      for (const weight of config.weights) {
        // Verificar peso normal
        const isWeightLoaded = await document.fonts.check(`${weight} normal 16px "${familyName}"`);
        if (isWeightLoaded) {
          document.documentElement.classList.add(`${familyName}-${weight}`);
        }
  
        // Verificar itálica si está configurada
        if (config.hasItalic) {
          const isItalicLoaded = await document.fonts.check(`${weight} italic 16px "${familyName}"`);
          if (isItalicLoaded) {
            document.documentElement.classList.add(`${familyName}-${weight}-italic`);
          }
        }
      }
    },
  
    /**
     * Marca el documento cuando las fuentes están cargadas
     */
    markAsFontsLoaded(loadedFonts) {
      document.documentElement.classList.add('fonts-loaded');
      loadedFonts.forEach(font => {
        document.documentElement.classList.add(`${font}-loaded`);
      });
    },
  
    /**
     * Marca el documento para usar fuentes de fallback
     */
    markAsFallback() {
      document.documentElement.classList.add('fonts-fallback');
    }
  };
  
  // Inicializar el gestor de fuentes cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', () => FontManager.init());
  
}
