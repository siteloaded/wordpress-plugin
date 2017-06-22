(function($) {
    'use strict';

    function onPurgeAllClick(event) {
        event.preventDefault();

        $.post(siteloaded_admin_bar_script.ajaxUrl, { 'action': siteloaded_admin_bar_script.purgeAllAction })
            .always(function(res) {
                if (res.code !== 200) {
                    $.featherlight(siteloaded_admin_bar_script.failedMessage, { type: 'text' });
                    return;
                }

                $.featherlight(siteloaded_admin_bar_script.succeededMessage, { type: 'text' });
                setTimeout(function() {
                    var opened = $.featherlight.current();
                    opened && opened.close();
                }, 1500);
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
