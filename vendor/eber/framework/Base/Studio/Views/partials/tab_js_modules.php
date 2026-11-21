<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<!-- TAB: MÓDULOS JS Y LIBRERÍAS -->
<div id="tab-jsmodules" class="ts-tab-content">
  <div class="ts-section">
    <div class="ts-section-title">
      <span>Gestor de Módulos JS y Librerías</span>
      <span id="jsModulesCountBadge" style="font-size:0.75rem; color:var(--ts-muted);">—</span>
    </div>

    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
      <button type="button" class="ts-btn ts-btn-primary" style="width:100%; justify-content:center;" onclick="detectJsUsage(true)">
        🔍 Auto-detectar Usados en el Proyecto
      </button>
      <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; justify-content:center;" onclick="applyRecommendedJsModules()">
        ⚡ Aplicar Recomendación Óptima
      </button>
      <button type="button" class="ts-btn ts-btn-success" style="width:100%; justify-content:center;" onclick="saveJsModulesConfig()">
        💾 Guardar y Minificar js.min.js
      </button>
    </div>

    <div id="jsModulesSummaryBox" style="background:var(--ts-bg); border:1px solid var(--ts-border); border-radius:8px; padding:10px; font-size:0.78rem; color:var(--ts-muted); line-height:1.5;">
      Cargando catálogo de módulos...
    </div>
  </div>

  <div class="ts-section">
    <div class="ts-section-title"><span>Filtros de Vista</span></div>
    <div class="ts-control-group" style="margin-bottom:8px;">
      <label class="ts-label">Buscar componente o archivo</label>
      <input type="text" id="jsModSearchInput" class="ts-input-text" placeholder="ej. modal, gsap, chart..." oninput="renderJsModulesStage()">
    </div>
    <div class="ts-control-group">
      <label class="ts-label">Estado</label>
      <select id="jsModFilterSelect" class="ts-input-text" onchange="renderJsModulesStage()">
        <option value="all">Todos los módulos</option>
        <option value="active">Solo activos</option>
        <option value="used">Detectados en uso</option>
        <option value="unused">Sin uso detectado</option>
      </select>
    </div>
  </div>

  <div class="ts-section">
    <div class="ts-section-title"><span>Orden Priority (Separado por coma)</span></div>
    <input type="text" id="jsModPriorityInput" class="ts-input-text" style="font-family:monospace; font-size:0.76rem;" placeholder="aaSvgStore.js, querie.js, lang.js, uiFormUtils.js">
    <div class="ts-section-title" style="margin-top:12px;"><span>Excluidos explícitos (exclude)</span></div>
    <input type="text" id="jsModExcludeInput" class="ts-input-text" style="font-family:monospace; font-size:0.76rem;" placeholder="archivo1.js, archivo2.js">
  </div>
</div>
