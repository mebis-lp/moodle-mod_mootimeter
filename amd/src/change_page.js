export const init = (uniqueID) => {
    const obj = document.getElementById(uniqueID);
    if (!obj) {
        return;
    }
    obj.addEventListener("click", changePage);

    /**
     * Create new page.
     */
    function changePage() {
        var pageid = this.dataset.pageid;
        var cmid = this.dataset.cmid;
        location.href = 'view.php?id=' + cmid + '&pageid=' + pageid;
    }
};