import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';

export const init = async(uniqueID) => {
    var obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("click", buttonClicked);
    const pageid = document.getElementById('mootimeterstate').dataset.pageid;
    var dataset = obj.dataset;
    var confirmationTitleStr;
    if (!obj.getAttribute("data-confirmationtitlestr")) {
        confirmationTitleStr = getString('delete', 'core');
    } else {
        confirmationTitleStr = dataset.confirmationtitlestr;
    }

    var confirmationQuestionStr;
    if (!obj.getAttribute("data-confirmationquestionstr")) {
        confirmationQuestionStr = getString('areyousure');
    } else {
        confirmationQuestionStr = dataset.confirmationquestionstr;
    }

    var modal;
    if (!obj.getAttribute("data-confirmationtype")) {
        window.console.log('No confirmationtype specified! Abort!');
        return;
    } else {
        switch (dataset.confirmationtype) {
            case 'DELETE_CANCEL':
                modal = await ModalDeleteCancel.create({
                    title: confirmationTitleStr,
                    body: confirmationQuestionStr,
                    pageid: pageid,
                });
                break;
            case 'SAVE_CANCEL':
                modal = await ModalSaveCancel.create({
                    title: confirmationTitleStr,
                    body: confirmationQuestionStr,
                    pageid: pageid,
                });
                break;
        }
    }

    modal.getRoot().on(ModalEvents.delete, function() {
        var uniqueID = obj.id;
        var ajaxmethode = obj.dataset.ajaxmethode;

        buttonClickedHandle(pageid, uniqueID, ajaxmethode);
    });

    modal.getRoot().on(ModalEvents.save, function() {
        var uniqueID = obj.id;
        var ajaxmethode = obj.dataset.ajaxmethode;

        buttonClickedHandle(pageid, uniqueID, ajaxmethode);
    });

    /**
     * Store the value.
     */
    function buttonClicked() {
        modal.show();
    }
};

/**
 * Call to store input value
 * @param {int} pageid
 * @param {string} thisDataset
 * @param {string} ajaxmethode
 * @returns {mixed}
 */
const execButtonClicked = (
    pageid,
    thisDataset,
    ajaxmethode
) => fetchMany([{
    methodname: ajaxmethode,
    args: {
        pageid,
        thisDataset
    },
}])[0];

/**
 * Executes the call to store input value.
 * @param {int} pageid
 * @param {string} uniqueID
 * @param {string} ajaxmethode
 */
const buttonClickedHandle = async(pageid, uniqueID, ajaxmethode) => {
    var dataset = JSON.stringify(document.getElementById(uniqueID).dataset);
    const response = await execButtonClicked(pageid, dataset, ajaxmethode);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.reload == true) {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        reloadPage(urlParams.get('pageid'), urlParams.get('id'));
    }
};
