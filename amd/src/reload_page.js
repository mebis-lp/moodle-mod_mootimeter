import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {execReloadPagelist as reloadPagelist} from 'mod_mootimeter/reload_pagelist';

export const init = (uniqueID) => {

    const obj = document.getElementById(uniqueID);
    if (!obj) {
        return;
    }
    obj.addEventListener("click", changePage);

    /**
     * Store the value.
     */
    function changePage() {
        var pageid = this.dataset.pageid;
        if (pageid === null || pageid === undefined || pageid == "undefined" || pageid.length == 0) {
            pageid = 0;
        }
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
export const execReloadPage = async(pageid, cmid, dataset) => {

    // Add all url parameters to dataset.
    if (dataset.useUrlParams) {
        const searchParams = new URL(window.location.href).searchParams;
        searchParams.forEach((value, name) => {
            dataset[name] = value;
        });
    }

    dataset = JSON.stringify(dataset);
    const response = await reloadPage(pageid, cmid, dataset);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        var mtmstate = document.getElementById('mootimeterstate');

        const pageparmas = JSON.parse(response.pageparams);

        // Replace the pagecontent.
        Templates.renderForPromise(pageparmas.pagecontent.template, pageparmas.pagecontent)
            .then(({html, js}) => {
                Templates.replaceNodeContents('#mootimeter-pagecontent', html, js);
                return true;
            })
            .catch((error) => displayException(error));

        // Replace the pagecontent menu.
        if (pageparmas.contentmenu) {
            Templates.renderForPromise(pageparmas.contentmenu.template, pageparmas.contentmenu)
                .then(({html, js}) => {
                    Templates.replaceNode('#mootimeter-pagecontentmenu', html, js);
                    return true;
                })
                .catch((error) => displayException(error));

            // Set subpage URL parameters.
            if (pageparmas.contentmenu.sp) {
                for (const [key, value] of Object.entries(pageparmas.contentmenu.sp)) {
                    setGetParam(key, value);
                }
            }
        }

        // Replace the settings col if necessary.
        if (pageparmas.colsettings) {
            Templates.renderForPromise(pageparmas.colsettings.template, pageparmas.colsettings)
                .then(({html, js}) => {
                    Templates.replaceNodeContents('#mootimeter-col-settings', html, js);
                    return true;
                })
                .catch((error) => displayException(error));
        }

        if (pageparmas.pageid) {

            // Set new pageid.
            mtmstate.setAttribute('data-pageid', pageparmas.pageid);

            // Set URL parameter - pageid.
            setGetParam('pageid', pageparmas.pageid);

        }

        // Set active page marked in pageslist.
        reloadPagelist(pageid, cmid, true);


        // Remove all tooltips of pageslist that are still present.
        document.querySelectorAll('.tooltip').forEach(e => e.remove());
    }
};

/**
 * Set the Query Parameter.
 * @param {string} key
 * @param {string} value
 */
function setGetParam(key, value) {
    if (history.pushState) {
        var params = new URLSearchParams(window.location.search);
        params.set(key, value);
        var newUrl = window.location.origin
            + window.location.pathname
            + '?' + params.toString();
        window.history.pushState({path: newUrl}, '', newUrl);
    }
}