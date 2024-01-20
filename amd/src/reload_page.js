import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {execReloadPagelist as reloadPagelist} from 'mod_mootimeter/reload_pagelist';
import {getGetParams} from 'mod_mootimeter/utils';
import {setGetParam} from 'mod_mootimeter/utils';

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
        var pageid = 0;
        var dataset = "";
        if (this.dataset) {
            pageid = this.dataset.pageid;
            dataset = this.dataset;
        }

        if (pageid === null || pageid === undefined || pageid == "undefined" || pageid.length == 0) {
            pageid = 0;
        }

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPage(pageid, cmid, dataset);
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

    if (!dataset) {
        dataset = getGetParams();
    } else {
        Object.assign(dataset, getGetParams());
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
                var container = document.querySelector(".mootimetercontainer");
                for (const [key, value] of Object.entries(pageparmas.contentmenu.sp)) {
                    setGetParam(key, value);
                    setFullscreenClass(container, key, value);
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
 * Set the fullscreen class to the mootimetercontainr
 * @param {mixed} container
 * @param {string} key
 * @param {int} value
 */
function setFullscreenClass(container, key, value) {
    if (key == 'f' && value == 1) {
        container.classList.add("fullscreen");
        document.getElementById("page-wrapper").classList.add("fullscreen");
        openBrowserFullscreen(document.getElementById("page-wrapper"));
    } else if (key == 'f' && value == 0 && document.fullscreenElement !== null) {
        container.classList.remove("fullscreen");
        document.getElementById("page-wrapper").classList.remove("fullscreen");
        closeBrowswerFullscreen();
    }
}

/**
 * Trigger browsers fullscreen mode.
 * @param {mixed} elem
 */
function openBrowserFullscreen(elem) {
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) { /* Safari */
        elem.webkitRequestFullscreen();
    }
}

/**
 * Close browsers fullscreen.
 */
function closeBrowswerFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) { /* Safari */
        document.webkitExitFullscreen();
    }
}