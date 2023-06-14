import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    var elements = document.getElementsByClassName("mtmt_answeroption");
    var pageid = document.getElementById('mtmt_question_section').dataset.pageid;

    Array.from(elements).forEach(function (element) {
        element.addEventListener('click', function () {
            var aoid = this.dataset.aoid;

            let promise = Ajax.call([{
                methodname: 'mootimetertool_quiz_store_answer',
                args: {
                    pageid: pageid,
                    aoid: aoid,
                },
                fail: notification.exception,
            }]);

            promise[0].then(function (results) {
                if (results.finished) {
                    window.location.href = results.redirecturl;
                }
                return;
            }).fail();
        });
    });
};