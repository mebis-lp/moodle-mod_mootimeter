import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';


export const init = (ids) => {

    if (!document.getElementById(ids[0])) {
        return;
    }
    const button = document.getElementById(ids[0]);

    button.addEventListener("click", function() {

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const fullscreenstate = urlParams.get('f');

        if (fullscreenstate == 0) {
            setGetParam('f', 1);
        } else {
            setGetParam('f', 0);
        }

        var datasetobj = this.dataset;
        reloadPage(this.dataset.pageid, this.dataset.cmid, datasetobj);

    });
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

// /**
//  * Get an array of all url search params.
//  * @param {string} url
//  * @returns {array}
//  */
// function getParams(url = window.location) {
//     // Create a params object
//     let params = {};
//     new URL(url).searchParams.forEach(function(val, key) {
//         params[key] = val;
//     });
//     return params;
// }
