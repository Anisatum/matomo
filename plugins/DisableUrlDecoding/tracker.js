/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

(function () {

    var documentAlias = document,
        windowAlias = window;

    // Private function copied from piwik.js
    function getReferrer()
    {
        var referrer = '';

        try {
            referrer = windowAlias.top.document.referrer;
        } catch (e) {
            if (windowAlias.parent) {
                try {
                    referrer = windowAlias.parent.document.referrer;
                } catch (e2) {
                    referrer = '';
                }
            }
        }

        if (referrer === '') {
            referrer = documentAlias.referrer;
        }

        return referrer;
    }

    // Private function copied from piwik.js
    function safeDecodeWrapper(url)
    {
        try {
            return decodeWrapper(url);
        } catch (e) {
            return unescape(url);
        }
    }

    // Private function copied from piwik.js
    function urlFixup(hostName, href, referrer)
    {
        if (!hostName) {
            hostName = '';
        }

        if (!href) {
            href = '';
        }

        if (hostName === 'translate.googleusercontent.com') {       // Google
            if (referrer === '') {
                referrer = href;
            }

            href = getUrlParameter(href, 'u');
            hostName = getHostName(href);
        } else if (hostName === 'cc.bingj.com' ||                   // Bing
        hostName === 'webcache.googleusercontent.com' ||    // Google
        hostName.slice(0, 5) === '74.6.') {                 // Yahoo (via Inktomi 74.6.0.0/16)
            href = documentAlias.links[0].href;
            hostName = getHostName(href);
        }

        return [hostName, href, referrer];
    }

    function init()
    {
        if ('object' === typeof windowAlias && 'object' === typeof windowAlias.Matomo && 'object' === typeof windowAlias.Matomo.DisableUrlDecoding) {
            // do not initialize twice
            return;
        }

        if ('object' === typeof windowAlias && !windowAlias.Matomo) {
            // matomo is not defined yet
            return;
        }

        // Will be overwritten on SystemSettings update
        Matomo.DisableUrlDecoding = {};

        Matomo.on('TrackerSetup', function (tracker) {
            tracker.DisableUrlDecoding = {
                doNotDecode: function () {
                    let locationArray = urlFixup(documentAlias.domain, windowAlias.location.href, getReferrer());
                    if (!tracker.DisableUrlDecoding.currentUrlBackup)
                        tracker.DisableUrlDecoding.currentUrlBackup = tracker.getCurrentUrl();
                    if (!tracker.DisableUrlDecoding.referrerUrlBackup)
                        tracker.DisableUrlDecoding.referrerUrlBackup = safeDecodeWrapper(locationArray[2]);
                    tracker.setCustomUrl(locationArray[1]);
                    tracker.setReferrerUrl(locationArray[2]);
                },
                doDecode: function () {
                    if (tracker.DisableUrlDecoding.currentUrlBackup)
                        tracker.setCustomUrl(tracker.DisableUrlDecoding.currentUrlBackup);
                    if (tracker.DisableUrlDecoding.referrerUrlBackup)
                        tracker.setReferrerUrl(tracker.DisableUrlDecoding.referrerUrlBackup);
                }
            }

            if (Matomo.DisableUrlDecoding.forceDoNotDecode) {
                tracker.DisableUrlDecoding.doNotDecode();
            }
        });
    }

    if ('object' === typeof windowAlias.Matomo) {
        init();
    } else {
        // tracker is loaded separately for sure
        if ('object' !== typeof windowAlias.matomoPluginAsyncInit) {
            windowAlias.matomoPluginAsyncInit = [];
        }

        windowAlias.matomoPluginAsyncInit.push(init);
    }

})();
