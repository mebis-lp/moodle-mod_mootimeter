import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {
    setInterval(function () {

        var pageid = document.getElementById('wordcloudcanvas').dataset.pageid;
        var lastposttimestamp = document.getElementById('wordcloudcanvas').dataset.lastupdated;
        const promise = Ajax.call([{
            methodname: 'mootimetertool_wordcloud_get_answers',
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
            let nodelastupdated = document.getElementById('wordcloudcanvas');
            nodelastupdated.setAttribute('data-lastupdated', results.lastupdated);

            // Redraw wordcloud.
            let mtmtcanvas = document.getElementById('wordcloudcanvas');
            mtmtcanvas.setAttribute('data-answers', JSON.stringify(results.answerlist));
            document.getElementById('wordcloudcanvas').dispatchEvent(new Event("redrawwordcloud"));

            return;
        }).fail();

    }, 1000);

};
