export const init = (id) => {

    // Get all up elements.
    var ao = document.getElementById(id);

    if (!ao) {
        return;
    }

    ao.addEventListener("click", reload);

    /**
     * Create new page.
     */
    function reload() {
        // Not realy nice but it take sure that the reload takes place after the ws communication of other events is finished.
        setTimeout(function () {
            document.location.reload(true);
        }, 200);
    }
};