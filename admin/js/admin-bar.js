(function($) {
    'use strict';

    function onPurgeAllClick(event) {
        event.preventDefault();

        $.featherlight(siteloaded_admin_bar_script.emptyingMessage, { type: 'text' });
        var start = new Date().valueOf();
        var close = function () {
            var opened = $.featherlight.current();
            opened && opened.close();
        };

        $.post(siteloaded_admin_bar_script.ajaxUrl, { 'action': siteloaded_admin_bar_script.purgeAllAction })
            .always(function(res) {
                if (res.code !== 200) {
                    close();
                    $.featherlight(siteloaded_admin_bar_script.failedMessage, { type: 'text' });
                    return;
                }

                setTimeout(close, Math.max(1500 - (new Date().valueOf() - start), 0));
            });
    }

    document.addEventListener("DOMContentLoaded", function() {
        var n = document.querySelector('.siteloaded-admin-bar-purge-all a');
        if (!n) {
            return;
        }
        n.addEventListener('click', onPurgeAllClick, false);
    }, false);
})(jQuery);
