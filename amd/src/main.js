import { call as fetchMany } from 'core/ajax';

export const init = () => {
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
            location.href = 'view.php?id=' + cmid + "&a=addpage";
        });
    }

    var deletebtns = document.getElementsByClassName("mootimeter-delete-page-btn");
    if (deletebtns) {
        Array.from(deletebtns).forEach(function (element) {
            element.addEventListener('click', function () {
                var pageid = this.dataset.pageid;
                execDeletePage(pageid);
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
     * @param {string} ajaxmethode
     * @param {int} pageid
     * @param {string} inputname
     * @param {string} inputvalue
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
