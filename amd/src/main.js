export const init = () => {
    // Eventlistener to change page.
    var elements = document.getElementsByClassName("mootimeter_pages_li");
    if (elements) {
        Array.from(elements).forEach(function (element) {
            element.addEventListener('click', function() {
                var pageid = this.dataset.pageid;
                var cmid = this.dataset.cmid;
                location.href = 'view.php?id=' + cmid + '&pageid=' + pageid;
            });
        });
    }
};
