import {call as fetchMany} from 'core/ajax';

export const init = (id) => {

    // Get all up elements.
    var ao = document.getElementById(id);

    if (!ao) {
        return;
    }

    ao.addEventListener("click", remove);

    /**
     * Create new page.
     */
    function remove() {
        var pageid = this.dataset.pageid;
        var aoid = this.dataset.aoid;
        removeAnswerOption(pageid, aoid);
    }
};

/**
 * Call to remove an answer option
 * @param {int} pageid
 * @param {int} aoid
 * @returns
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
const removeAnswerOption = async (pageid, aoid) => {
    const response = await execRemoveAnswerOption(pageid, aoid);
    if (response.code != 200) {
        window.console.log(response.string);
    }

    if (response.code == 200) {
        document.getElementById('ao_wrapper_' + aoid).remove();
        document.getElementById('wrapper_ao_' + aoid).remove();
    }
};
