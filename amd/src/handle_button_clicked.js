import { call as fetchMany } from 'core/ajax';
import Log from 'core/log';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import { get_string as getString } from 'core/str';

export const init = async (uniqueID) => {
    var obj = document.getElementById(uniqueID);

    if (!document.getElementById(uniqueID)) {
        return;
    }

    obj.addEventListener("click", buttonClicked);

    const modal = await ModalFactory.create({
        type: ModalFactory.types.DELETE_CANCEL,
        title: getString('delete', 'core'),
        body: getString('areyousure'),
        pageid: 5,
    });

    modal.getRoot().on(ModalEvents.delete, function () {
        var pageid = obj.dataset.pageid;
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
const buttonClickedHandle = async (pageid, uniqueID, ajaxmethode) => {
    var dataset = JSON.stringify(document.getElementById(uniqueID).dataset);
    const response = await execButtonClicked(pageid, dataset, ajaxmethode);

    if (response.code != 200) {
        Log.error(response.string);
    }

    if (response.code == 200) {
    }
};