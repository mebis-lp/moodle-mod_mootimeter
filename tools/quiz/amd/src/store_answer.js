import { call as fetchMany } from 'core/ajax';
import Templates from 'core/templates';
import { get_string as getString } from 'core/str';

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

    const SuccessString = await getString('notification_success_store_answer', 'mod_mootimeter');
    const response = await execStoreAnswer(pageid, selectedanswerids);
    if (response.code != 200) {
        window.console.log(response.string);
    }
    if (response.code == 200) {
        renderInfoBox('success', SuccessString, '');
    }
};

/**
 * Generate an info box.
 * @param {string} notificationType
 * @param {string} notificationString
 * @param {string} icon
 */
function renderInfoBox(notificationType, notificationString, icon) {

    const context = {
        "notification_id": "mtmt_answer_warning",
        "notification_type": notificationType,
        "notification_icon": icon,
        "notification_text": notificationString
    };

    Templates.renderForPromise('mod_mootimeter/elements/snippet_notification', context)
        .then(({ html, js }) => {
            Templates.appendNodeContents('#mtmt_tool-colct-header', html, js);
            return true;
        })
        .catch((error) => displayException(error));
}
