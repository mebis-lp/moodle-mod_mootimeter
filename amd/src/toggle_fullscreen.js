import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {setGetParam} from 'mod_mootimeter/utils';

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
