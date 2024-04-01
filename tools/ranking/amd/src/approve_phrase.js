import {call as fetchMany} from 'core/ajax';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {renderInfoBox} from 'mod_mootimeter/utils';
import {removeInfoBox} from 'mod_mootimeter/utils';

export const init = (yesid, noid, radioname) => {

    // var yesObj = document.getElementById(yesid);
    // var noObj = document.getElementById(noid);

    // window.console.log(yesObj);
    // window.console.log(noObj);
    // if (!document.getElementById(yesObj) || !document.getElementById(noObj)) {
    //     return;
    // }

    window.console.log('input[type="radio"][name="' + radioname + '"]');
    for (let elem of document.querySelectorAll('input[type="radio"][name="' + radioname +'"]')) {
        elem.addEventListener("input", (event) => {
            approve(event);
        });
    }

    // yesObj.addEventListener("change", approve);
    // noObj.addEventListener("change", approve);

    /**
     * Create new page.
     * @param {object} event
     */
    function approve(event) {
        const id = event.target.id;
        const pageid = document.getElementById(id).dataset.pageid;
        const phraseid = document.getElementById(id).dataset.phraseid;
        const togglevalue = document.getElementById(id).dataset.togglevalue;
        window.console.log([id, pageid, phraseid, togglevalue]);
        approvePhrase(id, pageid, phraseid, togglevalue);
    }
};

/**
 * Call to approve a phrase
 * @param {int} pageid
 * @param {int} phraseid
 * @param {int} value
 */
const execApprovePhrase = (
    pageid,
    phraseid,
    value
) => fetchMany([{
    methodname: 'mootimetertool_ranking_approve_phrase',
    args: {
        pageid,
        phraseid,
        value
    },
}])[0];

/**
 * Executes the call to approve a phrase.
 * @param {string} inputid
 * @param {int} pageid
 * @param {int} phraseid
 * @param {int} togglevalue
 */
const approvePhrase = async (inputid, pageid, phraseid, togglevalue) => {

    // Disable the input field until the page is refreshed.
    document.getElementById(inputid).disabled = true;

    const response = await execApprovePhrase(pageid, phraseid, togglevalue);

    const infoboxid = "mtmt_phrase_warning";

    removeInfoBox(infoboxid);

    if (response.code != 200) {
        renderInfoBox("mtmt_tool-colct-header", infoboxid, "warning", response.string);
        document.getElementById(inputid).disabled = false;
        document.getElementById(inputid).focus();
    }

    if (response.code == 200) {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        await reloadPage(urlParams.get('pageid'), urlParams.get('id'));
    }
};
