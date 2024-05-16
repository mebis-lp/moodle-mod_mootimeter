import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';

export const init = () => {

    // Get all up elements.
    const ao = document.getElementById('add_answer_option');

    if (!ao) {
        return;
    }

    ao.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        const pageid = this.dataset.pageid;
        return storeNewAnswerOption(pageid);
    }
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @returns
 */
const execStoreNewAnswerOption = (
    pageid,
) => fetchMany([{
    methodname: 'mootimetertool_quiz_new_answeroption',
    args: {
        pageid,
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {int} pageid
 */
const storeNewAnswerOption = async(pageid) => {
    const response = await execStoreNewAnswerOption(pageid);
    if (response.code != 200) {
        Log.error(response.string);
    }
};