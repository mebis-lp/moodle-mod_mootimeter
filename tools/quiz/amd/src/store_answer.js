import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';
import {get_string as getString} from 'core/str';
import Templates from 'core/templates';
import Log from 'core/log';

export const init = () => {

    // Get all up elements.
    const submitbtn = document.getElementById('mtmt_store_answer');

    if (!submitbtn) {
        return;
    }

    submitbtn.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        const selectedanswerids = [];
        const pageid = this.dataset.pageid;
        const checkboxes = document.getElementsByName('multipleanswers[]');
        for (const checkbox of checkboxes) {
            if (checkbox.checked) {
                selectedanswerids.push(checkbox.value);
            }
        }
        return storeAnswer(pageid, selectedanswerids);
    }

};

/**
 * Call to create a new instance
 *
 * @param {int} pageid
 * @param {[]} aoids
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
const storeAnswer = async(pageid, selectedanswerids) => {
    selectedanswerids = JSON.stringify(selectedanswerids);

    const successString = await getString('notification_success_store_answer', 'mod_mootimeter');
    const response = await execStoreAnswer(pageid, selectedanswerids);
    if (response.code != 200) {
        Log.error(response.string);
    }
    if (response.code == 200) {
        renderInfoBox('success', successString, '');
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
        .then(({html, js}) => {
            Templates.appendNodeContents('#mtmt_tool-colct-header', html, js);
            return true;
        })
        .catch((error) => displayException(error));
}
