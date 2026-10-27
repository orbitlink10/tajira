@php
    $gaId = setting('seo.ga4_id');
    $requiresConsent = setting('analytics.require_consent', true);
@endphp

@if($gaId && $requiresConsent)
    <div
        x-data="consentBanner()"
        x-show="visible"
        x-cloak
        x-transition
        data-required="true"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200 bg-white p-4 shadow-2xl"
        role="dialog"
        aria-label="Analytics consent"
    >
        <div class="container-page flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink-700">
                We use optional analytics to understand how the shop is used. You can decline and still browse and buy normally.
            </p>
            <div class="flex shrink-0 gap-2">
                <button type="button" class="btn btn-secondary" @click="decline()">Decline</button>
                <button type="button" class="btn btn-primary" @click="accept()">Accept analytics</button>
            </div>
        </div>
    </div>
@endif
