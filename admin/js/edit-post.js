(function($) {
    'use strict';

    function onPurgeClick(event) {
        event.preventDefault();

        var postId = Number($(event.target).data('post-id'));
        if (postId < 1) {
            return;
        }

        $.featherlight(siteloaded_editpost_script.emptying_message, { type: 'text' });
        var start = new Date().valueOf();
        var close = function() {
            var opened = $.featherlight.current();
            opened && opened.close();
        };
        var safeClose = setTimeout(close, 6000);

        $.post(ajaxurl, {
            action: siteloaded_editpost_script.purge_post_cache_action,
            post_id: postId
        }).always(function(res) {
            clearTimeout(safeClose);

            if (!res || res.code !== 200) {
                close();
                $.featherlight(siteloaded_editpost_script.failed_message, { type: 'text' });
                return;
            }

            setTimeout(close, Math.max(1500 - (new Date().valueOf() - start), 0));
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var n = document.querySelector('.siteloaded-editpost-submitbox a.purge');
        if (!n) {
            return;
        }
        n.addEventListener('click', onPurgeClick, false);
    }, false);

})(jQuery);
