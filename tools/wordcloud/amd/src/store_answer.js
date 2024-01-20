import {call as fetchMany} from 'core/ajax';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {renderInfoBox} from 'mod_mootimeter/utils';
import {removeInfoBox} from 'mod_mootimeter/utils';
import {delay} from 'mod_mootimeter/utils';

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

    // Disable the input field until the page is refreshed.
    document.getElementById(inputid).disabled = true;

    const response = await execStoreAnswer(pageid, answer);

    const infoboxid = "mtmt_answer_warning";

    removeInfoBox(infoboxid);

    if (response.code == 1000 || response.code == 1001 || response.code == 1002) {
        renderInfoBox("mtmt_tool-colct-header", infoboxid, "warning", response.string);
        document.getElementById(inputid).disabled = false;
        document.getElementById(inputid).focus();
    }

    if (response.code == 200) {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        await reloadPage(urlParams.get('pageid'), urlParams.get('id'));

        // Now set the focus to the input field.

        // Yes, it is not nice, but the dom needs some time to be updated.
        await delay(100);

        var elements = document.getElementsByClassName("mtmt-wc-answerinput");
        for (var i = 0; i < elements.length; i++) {
            if (elements.item(i).autofocus == true) {
                elements.item(i).focus();
            }
        }
    }
};
