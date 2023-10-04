export const init = () => {

    // Get all clickable button elements.
    var redirects = document.getElementsByClassName('mtm_redirect_selector');

    for (let i = 0; i < redirects.length; i++) {
        // Remove old listener if exists.
        redirects[i].removeEventListener("click", redirect);
        // Finally add the new listener.
        redirects[i].addEventListener("click", redirect);
    }

    /**
     * Redirect to new url.
     */
    function redirect() {
        var href = this.dataset.href;
        window.location.href = href;
    }
};