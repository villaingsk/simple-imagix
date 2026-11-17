jQuery(document).ready(function ($) {
    'use strict';

    function switchTab(tabId) {
        if (!tabId) tabId = 'settings';

        // Update nav tabs
        $('.simgx-nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        const $activeTab = $('.simgx-nav-tab-wrapper .nav-tab[data-tab="' + tabId + '"]');
        if ($activeTab.length) {
            $activeTab.addClass('nav-tab-active');
        } else {
            $('.simgx-nav-tab-wrapper .nav-tab:first').addClass('nav-tab-active');
            tabId = $('.simgx-nav-tab-wrapper .nav-tab:first').data('tab') || 'settings';
        }

        // Update tab contents
        $('.simgx-tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');

        // Update URL hash without page reload
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, '#' + tabId);
        } else {
            window.location.hash = tabId;
        }
    }

    // Handle tab click
    $(document).on('click', '.simgx-nav-tab-wrapper .nav-tab', function (e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        switchTab(tabId);
    });

    // Handle initial tab from hash or default to settings
    const currentHash = window.location.hash ? window.location.hash.substring(1) : '';
    if (currentHash && $('#tab-' + currentHash).length) {
        switchTab(currentHash);
    } else {
        switchTab('settings');
    }
});

