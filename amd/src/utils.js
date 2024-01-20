import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import {call as fetchMany} from 'core/ajax';

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

/**
 * Remove a defined parameter from url, without reload the page.
 * @param {string} parameter
 * @returns {string}
 */
export const removeGetParam = (parameter) => {
    var url = document.location.href;
    var urlparts = url.split('?');

    if (urlparts.length >= 2) {
        var urlBase = urlparts.shift();
        var queryString = urlparts.join("?");

        var prefix = encodeURIComponent(parameter) + '=';
        var pars = queryString.split(/[&;]/g);
        for (var i = pars.length; i-- > 0;) {
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }
        url = urlBase + '?' + pars.join('&');

        // Push the new url directly to url bar .
        window.history.pushState('', document.title, url);

    }
    return url;
};

/**
 * Call to store input value
 * @param {string} ajaxmethode
 * @param {int} pageid
 * @param {string} inputname
 * @param {string} inputvalue
 * @param {string} thisDataset
 * @returns {mixed}
 */
export const ajaxRequestInput = (
    ajaxmethode,
    pageid,
    inputname,
    inputvalue,
    thisDataset
) => fetchMany([{
    methodname: ajaxmethode,
    args: {
        pageid,
        inputname,
        inputvalue,
        thisDataset
    },
}])[0];

/**
 * Sets a timeout to delay the next steps.
 *
 * @param {int} ms
 * @returns {mixed}
 */
export const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
