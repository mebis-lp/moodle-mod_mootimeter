import Ajax from 'core/ajax';
import { exception as displayException } from 'core/notification';
import Templates from 'core/templates';
import notification from 'core/notification';

export const init = () => {

    // Register event to input box.
    var pageid = document.getElementById('mtmt_question_section').dataset.pageid;

    var element = document.getElementById('new_ao_label');

    element.addEventListener('click', function () {

        const promise = Ajax.call([{
            methodname: 'mootimetertool_quiz_new_answeroption',
            args: {
                pageid: pageid,
            },
            fail: notification.exception,
        }]);

        promise[0].then(function (results) {
            const context = {
                aoid: results.aoid,
                isediting: true,
                [results.quiztype]: true
            };
            window.console.log(context);

            // Add the answer to the Badges list.
            Templates.renderForPromise('mootimetertool_quiz/answer_option', context)
                .then(({ html, js }) => {
                    Templates.appendNodeContents('#mtmt_question_section', html, js);

                    // Now add an event listener.
                    document.getElementById('ao_text_' + results.aoid).addEventListener('keyup', delay(function () {
                        var aoid = this.parentElement.dataset.aoid;

                        Ajax.call([{
                            methodname: 'mootimetertool_quiz_store_answeroption',
                            args: {
                                pageid: pageid,
                                aoid: aoid,
                                value: this.value,
                                id: this.id
                            },
                            fail: notification.exception,
                        }]);

                    }, 1000));
                    return true;
                })
                .catch((error) => displayException(error));

            return;

        }).fail();
    });

    /**
    * Delay a callback for ms.
    * @param callback {{string}}
    * @param ms
    * @returns {object}
    */
    function delay(callback, ms) {
        var timer = 0;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }
};
