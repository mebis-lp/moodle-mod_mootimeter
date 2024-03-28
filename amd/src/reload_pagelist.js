import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import SortableList from 'core/sortable_list';
import jQuery from 'jquery';
import {ajaxRequestInput} from 'mod_mootimeter/utils';

export const init = (pagerefreshintervall) => {
    var obj = document.getElementById('mootimeterstate');

    if (!obj) {
        return;
    }

    if (pagerefreshintervall < 500) {
        pagerefreshintervall = 500;
    }

    setInterval(() => {
        getPagelist();
    }, pagerefreshintervall);

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const cmid = urlParams.get('id');
    var pageid = urlParams.get('pageid');

    if (pageid === null || pageid === undefined || pageid.length == 0) {
        pageid = 0;
    }
    reloadPage(pageid, cmid, '');

    /**
     * Store the value.
     */
    function getPagelist() {
        var pageid = 0;

        if (document.getElementById('mootimeterstate').dataset.pageid) {
            pageid = document.getElementById('mootimeterstate').dataset.pageid;
            if (pageid == "undefined" || pageid.length == 0) {
                pageid = 0;
            }
        }

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
 * @param {bool} forcereload
 */
export const execReloadPagelist = async(pageid, cmid, forcereload = false) => {
    const response = await reloadPagelist(pageid, cmid);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {

        var mtmstate = document.getElementById('mootimeterstate');

        const pagelist = JSON.parse(response.pagelist);
        const loadpageid = pagelist.loadpageid;

        // Reload pagecontent if page does not exit any more.
        if (pagelist.loadpageid) {
            reloadPage(loadpageid, cmid, '');
        }

        // Set all datasets to mootimeterstate.
        for (let dataattribute in pagelist.dataset) {
            if (pagelist.dataset.hasOwnProperty(dataattribute)) {
                mtmstate.setAttribute('data-' + dataattribute, pagelist.dataset[dataattribute]);
            }
        }

        // If there are no changes in pagelist. We are finished.
        if (mtmstate.dataset.pagelisttime == pagelist.dataset.pagelisttime && !forcereload) {
            return;
        }

        // Set new pagelisttime state.

        // Replace the pages list.
        Templates.renderForPromise('mod_mootimeter/elements/snippet_page_list', pagelist)
            .then(({html, js}) => {
                if (document.getElementById('mootimeter-addpage-button')) {
                    document.getElementById('mootimeter-addpage-button').remove();
                }
                Templates.replaceNode(document.getElementById('mootimeter-pages-list'), html, js);

                // Finally make pageslist sortable.
                var listelements = document.getElementsByClassName('mootimeter_pages_li');
                if (listelements[0]) {
                    var uniqid = listelements[0].dataset.uniqid;
                    new SortableList('#mootimeter-pages-list', {
                        moveHandlerSelector: '.mootimeter_page_move_sortablehandle_' + uniqid,
                    });
                    jQuery('.mootimeter_pages_li_sortable_' + uniqid).on(SortableList.EVENTS.DROP, function(_, info) {
                        var newIndex = info.targetList.children().index(info.element);
                        storePagePosition(this.dataset.pageid, newIndex);
                    });
                }

                // Remove all tooltips of pageslist that are still present.
                document.querySelectorAll('.tooltip').forEach(e => e.remove());

                return true;
            })
            .catch((error) => displayException(error));
    }
};

/**
 * Store the new page position.
 * @param {int} pageid
 * @param {int} newIndex
 */
const storePagePosition = (pageid, newIndex) => {
    ajaxRequestInput(
        'mod_mootimeter_store_page_details',
        pageid,
        'sortorder',
        newIndex,
        ''
    );
};
