import { call as fetchMany } from 'core/ajax';

export const init = () => {

    // Get all up elements.
    var ao = document.getElementById('add_answer_option');

    if (!ao) {
        return;
    }

    ao.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        var pageid = this.dataset.pageid;
        storeNewAnswerOption(pageid);
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
const storeNewAnswerOption = async (pageid) => {
    const response = await execStoreNewAnswerOption(pageid);
    if (response.code != 200) {
        window.console.log(response.string);
    }
};