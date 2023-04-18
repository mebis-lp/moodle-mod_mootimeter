import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    document.getElementById('toggleteacherpermission').addEventListener("click", function () {

        var pageid = document.getElementById('mootimeter_type_answer').dataset.pageid;

        // Send the answer to server.
        let promise = Ajax.call([{
            methodname: 'mootimetertool_wordcloud_set_show_results_state',
            args: { pageid: pageid },
            fail: notification.exception,
        }]);

        promise[0].then(function (results) {
            document.getElementById("toggleteacherpermission").childNodes[0].nodeValue = results.buttontext;
            return;
        }).fail();
    });
};
