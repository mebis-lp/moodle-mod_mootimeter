import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {
    setInterval(function () {

        var pageid = document.getElementById('mootimeter_type_answer').dataset.pageid;
        var lastposttimestamp = document.getElementById('mootimeter_lastupdated').value;
        const promise = Ajax.call([{
            methodname: 'mootimetertool_quiz_get_answers',
            args: {
                pageid: pageid,
                lastupdated: lastposttimestamp
            },
            fail: notification.exception,
        }]);

        promise[0].then(function (results) {
            if (results.lastupdated == lastposttimestamp) {
                return;
            }

            // Set lastupdated.
            let nodelastupdated = document.getElementById('mootimeter_lastupdated');
            nodelastupdated.value = results.lastupdated;

            // Redraw wordcloud.
            let mtmtcanvas = document.getElementById('quizcanvas');
            mtmtcanvas.setAttribute('data-count', JSON.stringify(results.answerlist));
            document.getElementById('quizcanvas').dispatchEvent(new Event("redrawwordcloud"));

            return;
        }).fail();

    }, 1000);

};
