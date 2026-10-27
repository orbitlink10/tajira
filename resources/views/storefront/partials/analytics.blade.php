@php
    $gaId = setting('seo.ga4_id');
    $requiresConsent = setting('analytics.require_consent', true);
    $pixelId = config('tajira.analytics.meta_pixel_id');
@endphp

@if($gaId)
    <script>
        (function () {
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
            var measurementId = @json($gaId);
            var requiresConsent = @json((bool) $requiresConsent);
            var loaded = false;

            window.tajiraLoadAnalytics = function () {
                if (loaded) return;
                loaded = true;
                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
                document.head.appendChild(script);
                window.gtag('js', new Date());
                window.gtag('config', measurementId, { anonymize_ip: true });
            };

            if (!requiresConsent || localStorage.getItem('tajira_analytics_consent') === 'granted') {
                window.tajiraLoadAnalytics();
            }

            window.addEventListener('tajira-consent-granted', window.tajiraLoadAnalytics);
        })();
    </script>
@endif

@if($pixelId)
    <script>
        (function () {
            var pixelId = @json($pixelId);
            var requiresConsent = @json((bool) $requiresConsent);
            var loaded = false;

            window.tajiraLoadPixel = function () {
                if (loaded) return;
                loaded = true;
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
                window.fbq('init', pixelId);
                window.fbq('track', 'PageView');
            };

            if (!requiresConsent || localStorage.getItem('tajira_analytics_consent') === 'granted') {
                window.tajiraLoadPixel();
            }

            window.addEventListener('tajira-consent-granted', window.tajiraLoadPixel);
        })();
    </script>
@endif
