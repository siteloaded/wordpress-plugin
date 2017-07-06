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

        $.ajax({
            method: 'POST',
            url: siteloaded_admin_bar_script.ajax_url,
            timeout: 6000,
            data: {
                action: siteloaded_admin_bar_script.purge_all_action
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            if (textStatus === 'timeout') {
                close();
                return;
            }
            $.featherlight(siteloaded_admin_bar_script.failed_message, { type: 'text' });
        }).done(function(res) {
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
