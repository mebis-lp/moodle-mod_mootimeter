import {call as fetchMany} from 'core/ajax';
import Log from 'core/log';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {removeGetParam} from 'mod_mootimeter/utils';

export const init = async(id) => {

    const modal = await ModalFactory.create({
        type: ModalFactory.types.DELETE_CANCEL,
        title: getString('delete', 'core'),
        body: getString('areyousure'),
    });

    modal.getRoot().on(ModalEvents.delete, function() {
        var pageid = document.getElementById(id).dataset.pageid;
        execDeletePage(pageid);
    });

    document.getElementById(id).addEventListener('click', () => {
        modal.show();
    });

    /**
     * Call to store input value
     * @param {int} pageid
     * @returns {mixed}
     */
    const deletePageCall = (
        pageid,
    ) => fetchMany([{
        methodname: 'mod_mootimeter_delete_page',
        args: {
            pageid
        },
    }])[0];

    /**
     * Executes the call to store input value.
     * @param {int} pageid
     */
    const execDeletePage = async(pageid) => {
        const response = await deletePageCall(pageid);
        if (response.code != 200) {
            Log.error(response.string);
            return;
        }
        removeGetParam('pageid', window.location.href);
        reloadPage(0, response.cmid);
    };
};
