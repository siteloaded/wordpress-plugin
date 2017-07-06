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

        $.ajax({
            method: 'POST',
            url: ajaxurl,
            timeout: 6000,
            data: {
                action: siteloaded_editpost_script.purge_post_cache_action,
                post_id: postId
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            if (textStatus === 'timeout') {
                close();
                return;
            }
            $.featherlight(siteloaded_editpost_script.failed_message, { type: 'text' });
        }).done(function(res) {
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
