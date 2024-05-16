import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

export const init = (id) => {

    // Get all up elements.
    const ao = document.getElementById(id);

    if (!ao) {
        return;
    }

    ao.addEventListener("click", remove);

    /**
     * Create new page.
     */
    function remove() {
        const pageid = this.dataset.pageid;
        const aoid = this.dataset.aoid;
        removeAnswerOption(pageid, aoid);
    }
};

/**
 * Call to remove an answer option
 * @param {int} pageid
 * @param {int} aoid
 * @returns {mixed}
 */
const execRemoveAnswerOption = (
    pageid,
    aoid,
) => fetchMany([{
    methodname: 'mootimetertool_quiz_remove_answeroption',
    args: {
        pageid,
        aoid
    },
}])[0];

/**
 * Executes the call to remove an answer option.
 * @param {int} pageid
 * @param {int} aoid
 */
const removeAnswerOption = async(pageid, aoid) => {
    const response = await execRemoveAnswerOption(pageid, aoid);
    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
        document.getElementById('ao_wrapper_' + aoid).remove();
        document.getElementById('wrapper_ao_' + aoid).remove();
    }
};
