/* Shared telemetry for the historical PAUSATF archive. */
(function () {
    'use strict';

    const owner = document.currentScript;
    if (!owner) {
        return;
    }
    const provider = owner.getAttribute('data-provider');

    function loadLibrary(url, ready) {
        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        if (ready) {
            script.addEventListener('load', ready, { once: true });
        }
        document.head.appendChild(script);
    }

    switch (provider) {
        case 'quantcast':
            loadLibrary('https://edge.quantserve.com/quant.js', function () {
                if (typeof window.quantserve === 'function') {
                    window._qacct = 'p-72cL-7RSrrmhE';
                    window.quantserve();
                }
            });
            break;
        case 'quantcast-options':
            window._qoptions = { qacct: 'p-72cL-7RSrrmhE' };
            loadLibrary('https://edge.quantserve.com/quant.js');
            break;
        case 'google':
        case 'google-pageview':
        case 'google-library': {
            const url = window.location.protocol === 'https:'
                ? 'https://ssl.google-analytics.com/ga.js'
                : 'https://www.google-analytics.com/ga.js';
            loadLibrary(url, function () {
                if (provider === 'google-library' || typeof window._gat?._getTracker !== 'function') {
                    return;
                }
                const tracker = window._gat._getTracker('UA-2651783-1');
                // Preserve the historical tracker global for existing callers.
                window.pageTracker = tracker;
                if (provider === 'google') {
                    tracker._initData();
                }
                tracker._trackPageview();
            });
            break;
        }
        default:
            // Provider identifiers never become executable URLs or code.
            break;
    }
}());
