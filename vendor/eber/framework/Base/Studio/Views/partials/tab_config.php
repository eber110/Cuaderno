<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<!-- TAB: CONFIGURACIÓN (.env, config.php y Vault) -->
<div id="tab-config" class="ts-tab-content">
  <div class="ts-section">
    <div class="ts-section-title">
      <span>Perfil de Configuración</span>
      <span id="cfgProfileBadge" style="font-size:0.72rem; color:var(--ts-primary);">Local (.env)</span>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:12px;">
      <button type="button" class="ts-btn ts-btn-primary" id="btnCfgProfileLocal" onclick="setConfigProfile('local')" style="justify-content:center; padding:7px;">💻 Local (.env)</button>
      <button type="button" class="ts-btn ts-btn-secondary" id="btnCfgProfileProd" onclick="setConfigProfile('production')" style="justify-content:center; padding:7px;">🛡️ Producción</button>
    </div>

    <div style="display:flex; flex-direction:column; gap:8px;">
      <button type="button" class="ts-btn ts-btn-success" style="width:100%; justify-content:center;" onclick="saveConfigProfile(false)">
        💾 Guardar Perfil Activo
      </button>
      <button type="button" class="ts-btn ts-btn-primary" id="btnCfgCompileVault" style="width:100%; justify-content:center;" onclick="compileProductionVault()">
        🔐 Compilar Producción + Vault
      </button>
      <button type="button" class="ts-btn ts-btn-secondary" id="btnCfgCloneLocal" style="width:100%; justify-content:center; display:none;" onclick="cloneNonSecretsFromLocal()">
        📋 Copiar no-secretos desde Local
      </button>
    </div>
  </div>

  <div class="ts-section">
    <div class="ts-section-title"><span>Secciones</span></div>
    <div id="cfgSectionsNav" style="display:flex; flex-direction:column; gap:4px;"></div>
  </div>

  <div class="ts-section">
    <div class="ts-section-title"><span>Estado del Vault en Producción</span></div>
    <div id="cfgVaultStatusBox" style="background:var(--ts-bg); border:1px solid var(--ts-border); border-radius:8px; padding:10px; font-size:0.76rem; color:var(--ts-muted); line-height:1.5;">
      Cargando estado...
    </div>
  </div>
</div>
