(function($) {
    'use strict';

    // credit: https://davidwalsh.name/javascript-debounce-function
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    function warn(msg) {
        if (!console || !console.warn) {
            return
        }
        console.warn.apply(console, arguments);
    }

    function callback(id, msg) {
        var frame = document.querySelector('iframe.siteloaded-control-panel');
        if (!frame) {
            warn('iframe could not be found, ignoring callback');
            return;
        }
        var message = JSON.stringify({ id_: id, key: 'CALLBACK', value: msg });
        frame.contentWindow.postMessage(message, siteloaded_admin_panel_script.validReferrer);
    }

    function setSubscription(id, subscription) {
        $.post(ajaxurl, { 'action': siteloaded_admin_panel_script.setSubscriptionAction, subscription_id: subscription })
            .always(function(response) {
                var status = typeof response === 'object' ? response.status : Number(response);
                callback(id, status);
            });
    }

    window.addEventListener('message', function(event) {
        if (event.origin !== siteloaded_admin_panel_script.validReferrer) {
            return;
        }

        var msg = JSON.parse(event.data);
        switch (msg.key) {
            case 'SET_SUBSCRIPTION':
                setSubscription(msg.id_, msg.subscription);
                break;
            default:
                warn('unknown message key:', msg.key);
        }
    }, false);

    if (!window.hasOwnProperty('siteloaded_panel_resize_handler')) {
        window.siteloaded_panel_resize_handler = debounce(function() {
            var content = $('#wpbody-content').css({ height: 'auto' });
            var height = $(document).height() - $('#wpadminbar').height();
            content.css({ height: height + 'px' });
        }, 250);

        window.addEventListener('resize', window.siteloaded_panel_resize_handler, false);
    }

    window.siteloaded_panel_resize_handler();
})(jQuery);
