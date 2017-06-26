(function($) {
    'use strict';

    function onPurgeAllClick(event) {
        event.preventDefault();

        $.featherlight(siteloaded_admin_bar_script.emptying_message, { type: 'text' });
        var start = new Date().valueOf();
        var close = function() {
            var opened = $.featherlight.current();
            opened && opened.close();
        };
        var safeClose = setTimeout(close, 6000);

        $.post(siteloaded_admin_bar_script.ajax_url, {
            action: siteloaded_admin_bar_script.purge_all_action,
            nonce: siteloaded_admin_bar_script.ajax_nonce
        }).always(function(res) {
            clearTimeout(safeClose);

            if (!res || res.code !== 200) {
                close();
                $.featherlight(siteloaded_admin_bar_script.failed_message, { type: 'text' });
                return;
            }

            setTimeout(close, Math.max(1500 - (new Date().valueOf() - start), 0));
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var n = document.querySelector('.siteloaded-admin-bar-purge-all a');
        if (!n) {
            return;
        }
        n.addEventListener('click', onPurgeAllClick, false);
    }, false);
})(jQuery);
