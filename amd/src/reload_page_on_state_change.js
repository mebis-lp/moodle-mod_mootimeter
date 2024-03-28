import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = (id) => {
    if (!document.getElementById('mootimeterstate').dataset.pageid) {
        return;
    }

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            reloadPageOnStateChange();
        }, intervalms);
    }, 2500);
};

/**
 * Refresh the page on a state change.
 * @returns {mixed}
 */
const reloadPageOnStateChange = () => {
    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.lastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const cmid = urlParams.get('id');

    reloadPage(mtmstate.dataset.pageid, cmid, mtmstate.dataset);

    // Set lastupdated.
    let nodelastupdated = document.getElementById('mootimeterstate');
    nodelastupdated.setAttribute('data-lastupdated', mtmstate.dataset.contentchangedat);
};
