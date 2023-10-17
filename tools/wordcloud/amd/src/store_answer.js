import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

export const init = () => {

    // Register event to input box.
    var ao = document.getElementById('mootimeter_type_answer');
    if (ao) {
        ao.addEventListener("keyup", function (event) {
            if (event.code === 'Enter' || event.code === 'NumpadEnter') {
                store();
            }
        });
    }

    // Register event to submit button.
    var ae = document.getElementById('mootimeter_enter_answer');
    if (ae) {
        ae.addEventListener("click", function () {
            store();
        });
    }

    /**
     * Create new page.
     */
    function store() {
        var pageid = document.getElementById('mootimeter_type_answer').dataset.pageid;
        var answer = document.getElementById('mootimeter_type_answer').value;
        storeAnswer(pageid, answer);
    }
};

/**
 * Call to store an answer
 * @param {int} pageid
 * @param {string} answer
 * @returns
 */
const execStoreAnswer = (
    pageid,
    answer,
) => fetchMany([{
    methodname: 'mootimetertool_wordcloud_store_answer',
    args: {
        pageid,
        answer
    },
}])[0];

/**
 * Executes the call to store an answer.
 * @param {int} pageid
 * @param {string} answer
 */
const storeAnswer = async (pageid, answer) => {
    const response = await execStoreAnswer(pageid, answer);

    removeInfoBox();

    if (response.code == 1000 || response.code == 1001 || response.code == 1002) {
        renderInfoBox("warning", response.string);
    }

    if (response.code == 200) {

        const context = {
            pill: answer,
            additional_class: ' mootimeter-pill-inline '
        };

        // Add the answer to the Badges list.
        Templates.renderForPromise('mod_mootimeter/elements/snippet_pill', context)
            .then(({html, js}) => {
                Templates.appendNodeContents('#mtmt_wordcloud_pills', html, js);
                return true;
            })
            .catch((error) => displayException(error));
    }

    // In any case: Empty the input field after post.
    document.getElementById('mootimeter_type_answer').value = "";
};

/**
 * Generate an info box.
 * @param {string} notificationType
 * @param {string} notificationString
 */
function renderInfoBox(notificationType, notificationString) {

    const context = {
        "notification_id": "mtmt_answer_warning",
        "notification_type": notificationType,
        "notification_icon": "fa-exclamation",
        "notification_text": notificationString
    };

    Templates.renderForPromise('mod_mootimeter/elements/snippet_notification', context)
        .then(({html, js}) => {
            Templates.appendNodeContents('#mtmt_tool-colct-header', html, js);
            return true;
        })
        .catch((error) => displayException(error));
}

/**
 * Remove the info box.
 */
function removeInfoBox() {
    var infobox = document.getElementById("mtmt_answer_warning");
    if (infobox) {
        infobox.remove();
    }

}