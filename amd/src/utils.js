import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

/**
 * Generate an info box.
 * @param {string} apendtoelementid
 * @param {string} infoboxid
 * @param {string} notificationType
 * @param {string} notificationString
 */
export const renderInfoBox = async(apendtoelementid, infoboxid, notificationType, notificationString) => {
    const context = {
        "notification_id": infoboxid,
        "notification_type": notificationType,
        "notification_icon": "fa-exclamation",
        "notification_text": notificationString
    };

    Templates.renderForPromise('mod_mootimeter/elements/snippet_notification', context)
        .then(({html, js}) => {
            Templates.appendNodeContents('#' + apendtoelementid, html, js);
            return true;
        })
        .catch((error) => displayException(error));
};

/**
 * Remove the info box.
 * @param {string} infoboxid
 */
export const removeInfoBox = async(infoboxid) => {
    var infobox = document.getElementById(infoboxid);
    if (infobox) {
        infobox.remove();
    }
};

/**
 * Set the Query Parameter.
 * @param {string} key
 * @param {string} value
 */
export const setGetParam = (key, value) => {
    if (history.pushState) {
        var params = new URLSearchParams(window.location.search);
        params.set(key, value);
        var newUrl = window.location.origin
            + window.location.pathname
            + '?' + params.toString();
        window.history.pushState({path: newUrl}, '', newUrl);
    }
};

/**
 * Get an array of all url search params.
 * @param {string} url
 * @returns {array}
 */
export const getGetParams = (url = window.location) => {
    // Create a params object
    let params = {};
    new URL(url).searchParams.forEach(function(val, key) {
        params[key] = val;
    });
    return params;
};