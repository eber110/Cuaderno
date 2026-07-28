/**
 * Campos de formulario repetibles.
 * 
 * @function repeat_camp
 * @description Permite agregar dinámicamente campos adicionales
 *              a un formulario (ej: encuestas, opciones múltiples).
 * 
 * @param {number|string} [limit=10] - Número máximo de campos permitidos
 * 
 * @example
 * // HTML
 * <div id="create-repeat">
 *   <input class="repeat-camp" name="option[]" placeholder="Opción 1">
 * </div>
 * 
 * @returns {void}
 */
export function repeat_camp(limit = 10) {
  const createSurveyActive = document.querySelectorAll('#create-repeat');
  const campRepeat = document.querySelectorAll('.repeat-camp');

  if (typeof limit === 'string') limit = parseInt(limit) || 10;

  for (let i = 0; i < createSurveyActive.length; i++) {
    const containCamp = document.createElement('div');
    containCamp.className = 'p10 back1 m0 gap5 br15 flex column-direction center-end';
    containCamp.id = "contain-camp";

    if (!campRepeat[i]) return;

    campRepeat[i].insertAdjacentElement('afterend', containCamp);

    const newCampRepeat = campRepeat[i].cloneNode(true);
    newCampRepeat.id = 'repeat-camp-add';
    newCampRepeat.className = 'br10';
    campRepeat[i].remove();

    const btnAdd = document.createElement('div');
    btnAdd.className = 'color7 pointer text-protected x16 m0 p0 mb5 w-auto';
    btnAdd.innerHTML = `+ Agrega otra opción (${limit - 1})`;
    btnAdd.id = 'btn-add';
    btnAdd.dataset.clicks = '1';

    containCamp.appendChild(btnAdd);
    containCamp.appendChild(newCampRepeat);
  }

  document.addEventListener("click", (e) => {
    const event = e.target;
    const activeBtn = event.closest("#btn-add");
    const contCamp = event.closest("#contain-camp");

    if (activeBtn && contCamp) {
      let clicks = parseInt(activeBtn.dataset.clicks || '1') + 1;
      activeBtn.dataset.clicks = clicks.toString();

      const campNew = contCamp.querySelector('#repeat-camp-add');
      const countCamp = contCamp.querySelectorAll('#repeat-camp-add');
      const campCount = countCamp.length;

      if (limit > campCount) {
        activeBtn.innerHTML = `+ Agrega otra opción (${limit - clicks})`;
        const campNewEnd = campNew.cloneNode(true);
        campNewEnd.removeAttribute("required");
        campNewEnd.value = "";
        campNewEnd.placeholder = 'Opción ' + (campCount + 1);
        contCamp.appendChild(campNewEnd);
      } else {
        activeBtn.dataset.clicks = '1';
      }
    }
  });
}
