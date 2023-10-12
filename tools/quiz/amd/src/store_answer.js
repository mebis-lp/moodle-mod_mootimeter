import { call as fetchMany } from 'core/ajax';

export const init = () => {

    // Get all up elements.
    var submitbtn = document.getElementById('mtmt_store_answer');

    if (!submitbtn) {
        return;
    }

    submitbtn.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        const selectedanswerids = [];
        var pageid = this.dataset.pageid;
        var checkboxes = document.getElementsByName('multipleanswers[]');
        window.console.log(pageid);
        window.console.log(checkboxes);
        for (var checkbox of checkboxes) {
            if (checkbox.checked) {
                selectedanswerids.push(checkbox.value);
            }
        }
        storeAnswer(pageid, selectedanswerids);
    }

};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @param {string} selectedanswerids
 * @returns
 */
const execStoreAnswer = (
    pageid,
    aoids
) => fetchMany([{
    methodname: 'mootimetertool_quiz_store_answer',
    args: {
        pageid,
        aoids
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {int} pageid
 * @param {array} selectedanswerids
 */
const storeAnswer = async (pageid, selectedanswerids) => {
    selectedanswerids = JSON.stringify(selectedanswerids);
    window.console.log(selectedanswerids);
    const response = await execStoreAnswer(pageid, selectedanswerids);
    if (response.code != 200) {
        window.console.log(response.string);
    }
    if (response.code == 200) {
        window.console.log(response.string);
    }
};
