import {call as fetchMany} from 'core/ajax';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (uniqId) => {

    // Get all up elements.
    var ao = document.getElementById(uniqId);

    if (!ao) {
        return;
    }

    ao.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        const dataset = document.getElementById('mootimeterstate').dataset;
        storeNewAnswerOption(dataset);
    }
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @returns {mixed}
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
 * @param {array} dataset
 */
const storeNewAnswerOption = async(dataset) => {
    await execStoreNewAnswerOption(dataset.pageid);

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const cmid = urlParams.get('id');

    reloadPage(dataset.pageid, cmid, dataset);
};
