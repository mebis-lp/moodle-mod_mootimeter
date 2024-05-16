import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {setGetParam} from 'mod_mootimeter/utils';

let fullscreenButton = null;
let fullscreenEnabled = false;

export const init = (ids) => {
    if (!document.getElementById(ids[0])) {
        return;
    }
    fullscreenButton = document.getElementById(ids[0]);
    fullscreenButton.addEventListener('click', toggleFullscreenMode);
};

/**
 * Function to toggle the fullscreen mode.
 *
 * Note that this will NOT trigger browser fullscreen mode.
 */
const toggleFullscreenMode = () => {
    fullscreenEnabled = !fullscreenEnabled;
    setGetParam('f', fullscreenEnabled ? 1 : 0);
    const mootimetercontainer = document.querySelector('.mootimetercontainer');
    if (fullscreenEnabled) {
        mootimetercontainer.classList.add('fullscreen');
    } else {
        mootimetercontainer.classList.remove('fullscreen');
    }
    reloadPage(fullscreenButton.dataset.pageid, fullscreenButton.dataset.cmid, fullscreenButton.dataset);
};
