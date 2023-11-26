import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

export const init = (inputid, enterid) => {

    // Register event to input box.
    const ao = document.getElementById(inputid);
    if (ao) {
        ao.addEventListener("keyup", function(event) {
            if (event.code === 'Enter' || event.code === 'NumpadEnter') {
                store(inputid);
            }
        });
    }

    // Register event to submit button.
    const ae = document.getElementById(enterid);
    if (ae) {
        ae.addEventListener("click", function() {
            store(inputid);
        });
    }

    /**
     * Create new page.
     * @param {string} inputid
     */
    function store(inputid) {
        var pageid = document.getElementById(inputid).dataset.pageid;
        var answer = document.getElementById(inputid).value;
        storeAnswer(pageid, answer, inputid);
    }
};

/**
 * Call to store an answer
 * @param {int} pageid
 * @param {string} answer
 * @returns {array}
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
 * @param {string} inputid
 */
const storeAnswer = async(pageid, answer, inputid) => {
    const response = await execStoreAnswer(pageid, answer);

    removeInfoBox();

    if (response.code == 1000 || response.code == 1001 || response.code == 1002) {
        renderInfoBox("warning", response.string);
    }

    if (response.code == 200) {

        const context = {
            'pill': answer,
            'additional_class': ' mootimeter-pill-inline '
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
    document.getElementById(inputid).value = "";
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