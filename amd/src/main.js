import { call as fetchMany } from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import { get_string as getString } from 'core/str';

export const init = async () => {
    // Eventlistener to change page.
    var elements = document.getElementsByClassName("mootimeter_pages_li");
    if (elements) {
        Array.from(elements).forEach(function (element) {
            element.addEventListener('click', function () {
                var pageid = this.dataset.pageid;
                var cmid = this.dataset.cmid;
                location.href = 'view.php?id=' + cmid + '&pageid=' + pageid;
            });
        });
    }

    var addnewpagebtn = document.getElementById("mootimeter_addpage");
    if (addnewpagebtn) {
        addnewpagebtn.addEventListener('click', function () {
            var cmid = this.dataset.cmid;
            location.href = 'view.php?id=' + cmid;
        });
    }

    const modal = await ModalFactory.create({
        type: ModalFactory.types.DELETE_CANCEL,
        title: getString('delete', 'core'),
        body: getString('areyousure'),
        pageid: 5,
    });

    modal.getRoot().on(ModalEvents.delete, function () {
        var pageid = document.getElementById("btn-delete_page").dataset.pageid;
        execDeletePage(pageid);
    });

    var deletebtns = document.getElementsByClassName("mootimeter-delete-page-btn");
    if (deletebtns) {
        Array.from(deletebtns).forEach(function (element) {
            element.addEventListener('click', function () {
                modal.show();
            });
        });
    }

    /**
    * Call to store input value
    * @param {int} pageid
    * @returns
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
    const execDeletePage = async (pageid) => {
        const response = await deletePageCall(pageid);
        if (response.code != 200) {
            window.console.log(response.string);
            return;
        }

        window.location.href = window.location.origin
            + window.location.pathname + "?id=" + response.cmid;
    };
};
