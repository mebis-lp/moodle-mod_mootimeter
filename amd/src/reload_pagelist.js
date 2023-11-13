import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

export const init = () => {
    var obj = document.getElementById('mootimeterstate');

    if (!obj) {
        return;
    }

    setInterval(() => {
        getPagelist();
    }, 5000);

    /**
     * Store the value.
     */
    function getPagelist() {
        var pageid = document.getElementById('mootimeterstate').dataset.pageid;
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const cmid = urlParams.get('id');
        execReloadPagelist(pageid, cmid);
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {int} cmid
 * @returns {array}
 */
const reloadPagelist = (
    pageid,
    cmid
) => fetchMany([{
    methodname: 'mod_mootimeter_get_pages_list',
    args: {
        pageid,
        cmid
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {int} cmid
 */
const execReloadPagelist = async(pageid, cmid) => {
    const response = await reloadPagelist(pageid, cmid);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        var mtmstate = document.getElementById('mootimeterstate');

        const pagelist = JSON.parse(response.pagelist);

        // If there are no changes in pagelist. We are finished.
        if (mtmstate.dataset.pagelisttime == pagelist.pagelisttime) {
            return;
        }

        // Set new pagelisttime state.
        mtmstate.setAttribute('data-pagelisttime', pagelist.pagelisttime);

        // Replace the pages list.
        Templates.renderForPromise('mod_mootimeter/elements/snippet_page_list', pagelist)
            .then(({html, js}) => {
                Templates.replaceNodeContents('#mootimeter-pages-list', html, js);
                return true;
            })
            .catch((error) => displayException(error));
    }
};