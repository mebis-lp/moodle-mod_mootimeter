import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {execReloadPagelist as reloadPagelist} from 'mod_mootimeter/reload_pagelist';
import {setGetParam} from 'mod_mootimeter/utils';

export const init = (uniqueID) => {

    const obj = document.getElementById(uniqueID);
    if (!obj) {
        return;
    }
    obj.addEventListener("change", changePage);

    /**
     * Store the value.
     */
    function changePage() {
        var pageid = this.dataset.pageid;
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPage(pageid, cmid, this.dataset);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {int} cmid
 * @param {string} dataset
 * @returns {array}
 */
const reloadPage = (
    pageid,
    cmid,
    dataset
) => fetchMany([{
    methodname: 'mod_mootimeter_get_pagecontentparams',
    args: {
        pageid,
        cmid,
        dataset
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {int} cmid
 * @param {array} dataset
 */
const execReloadPage = async(pageid, cmid, dataset) => {

    if (!dataset) {
        dataset = JSON.stringify([]);
    } else {
        dataset = JSON.stringify(dataset);
    }

    const response = await reloadPage(pageid, cmid, dataset);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        var mtmstate = document.getElementById('mootimeterstate');

        const pageparmas = JSON.parse(response.pageparams);

        // Set new pageid.
        mtmstate.setAttribute('data-pageid', pageparmas.pageid);

        // Replace the pagecontent.
        Templates.renderForPromise(pageparmas.pagecontent.template, pageparmas.pagecontent)
            .then(({html, js}) => {
                Templates.replaceNodeContents('#mootimeter-pagecontent', html, js);
                return true;
            })
            .catch((error) => displayException(error));

        // Set URL parameter.
        setGetParam('pageid', pageparmas.pageid);

        // Set active page marked in pageslist.
        reloadPagelist(pageid, cmid, true);

        // Remove all tooltips of pageslist that are still present.
        document.querySelectorAll('.tooltip').forEach(e => e.remove());
    }
};