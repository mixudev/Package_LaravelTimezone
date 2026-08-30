/**
 * LaravelTimezone Client - Lightweight, Zero-Dependency Browser Timezone Sync & Hydration
 * https://github.com/mixudev/laravel-timezone
 */
(function (window, document) {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }

    const HEADER_NAME = 'X-Timezone';
    const COOKIE_NAME = 'timezone';
    const STORAGE_KEY = 'laravel_timezone';

    const LaravelTimezone = {
        /**
         * Get the user's browser timezone identifier.
         * @returns {string}
         */
        get: function () {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
            } catch (e) {
                return 'UTC';
            }
        },

        /**
         * Sync timezone to cookie and localStorage.
         */
        sync: function () {
            const tz = this.get();

            // Store in localStorage
            try {
                if (window.localStorage && window.localStorage.getItem(STORAGE_KEY) !== tz) {
                    window.localStorage.setItem(STORAGE_KEY, tz);
                }
            } catch (e) {}

            // Set cookie for server request fallback (valid for 1 year)
            try {
                const cookiePattern = new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)');
                const match = document.cookie.match(cookiePattern);
                const currentCookie = match ? decodeURIComponent(match[1]) : null;

                if (currentCookie !== tz) {
                    document.cookie = COOKIE_NAME + '=' + encodeURIComponent(tz) +
                        '; path=/; max-age=31536000; SameSite=Lax';
                }
            } catch (e) {}
        },

        /**
         * Format ISO date string into browser local time string.
         * @param {string} isoString
         * @param {string} format
         * @returns {string}
         */
        formatDate: function (isoString, format) {
            if (!isoString) return '';
            const date = new Date(isoString);
            if (isNaN(date.getTime())) return '';

            const fmt = (format || 'datetime').toLowerCase();

            if (fmt === 'relative') {
                return this.formatRelative(date);
            }

            if (fmt === 'date') {
                return new Intl.DateTimeFormat(undefined, {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                }).format(date);
            }

            if (fmt === 'time') {
                return new Intl.DateTimeFormat(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).format(date);
            }

            if (fmt === 'human') {
                return new Intl.DateTimeFormat(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }).format(date);
            }

            // Default: 'datetime'
            return new Intl.DateTimeFormat(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).format(date);
        },

        /**
         * Format date into relative human readable time (e.g. 5 minutes ago).
         * @param {Date} date
         * @returns {string}
         */
        formatRelative: function (date) {
            const now = new Date();
            const diffSeconds = Math.round((date.getTime() - now.getTime()) / 1000);
            const absDiff = Math.abs(diffSeconds);

            if (typeof Intl !== 'undefined' && Intl.RelativeTimeFormat) {
                const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
                if (absDiff < 60) return rtf.format(diffSeconds, 'second');
                if (absDiff < 3600) return rtf.format(Math.round(diffSeconds / 60), 'minute');
                if (absDiff < 86400) return rtf.format(Math.round(diffSeconds / 3600), 'hour');
                if (absDiff < 2592000) return rtf.format(Math.round(diffSeconds / 86400), 'day');
                if (absDiff < 31536000) return rtf.format(Math.round(diffSeconds / 2592000), 'month');
                return rtf.format(Math.round(diffSeconds / 31536000), 'year');
            }

            // Fallback if RelativeTimeFormat is unavailable
            if (absDiff < 60) return 'just now';
            if (absDiff < 3600) return Math.round(absDiff / 60) + ' min ago';
            if (absDiff < 86400) return Math.round(absDiff / 3600) + ' hours ago';
            return Math.round(absDiff / 86400) + ' days ago';
        },

        /**
         * Hydrate elements matching [data-local-time].
         * @param {Element|Document} root
         */
        hydrate: function (root) {
            const container = root || document;
            const elements = container.querySelectorAll('time[data-local-time]');

            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                if (el.getAttribute('data-local-time-hydrated')) {
                    continue;
                }

                const iso = el.getAttribute('datetime');
                const format = el.getAttribute('data-format') || 'datetime';

                if (iso) {
                    const formatted = this.formatDate(iso, format);
                    if (formatted) {
                        el.textContent = formatted;
                    }
                }

                el.setAttribute('data-local-time-hydrated', 'true');
            }
        },

        /**
         * Hook into HTTP clients: Fetch, Axios, Inertia, Livewire.
         */
        attachInterceptors: function () {
            const tz = this.get();

            // 1. Fetch API Interceptor
            if (typeof window.fetch === 'function') {
                const originalFetch = window.fetch;
                window.fetch = function (resource, init) {
                    init = init || {};
                    let headers = new Headers(init.headers || {});
                    if (!headers.has(HEADER_NAME)) {
                        headers.set(HEADER_NAME, tz);
                    }
                    init.headers = headers;
                    return originalFetch.call(this, resource, init);
                };
            }

            // 2. Axios Interceptor
            if (typeof window.axios !== 'undefined' && window.axios.defaults) {
                window.axios.defaults.headers.common[HEADER_NAME] = tz;
            }

            // 3. Inertia Hook
            if (document.addEventListener) {
                document.addEventListener('inertia:start', function (event) {
                    if (event && event.detail && event.detail.visit && event.detail.visit.headers) {
                        event.detail.visit.headers[HEADER_NAME] = tz;
                    }
                });

                // Auto-hydrate on Inertia page visits
                document.addEventListener('inertia:finish', function () {
                    LaravelTimezone.hydrate();
                });

                // Auto-hydrate on Livewire navigations
                document.addEventListener('livewire:navigated', function () {
                    LaravelTimezone.hydrate();
                });
            }
        },

        /**
         * Initialize observer and auto-execution.
         */
        init: function () {
            this.sync();
            this.attachInterceptors();

            // Run initial hydration when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    LaravelTimezone.hydrate();
                });
            } else {
                this.hydrate();
            }

            // Set up MutationObserver for dynamically added elements
            if (typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver(function (mutations) {
                    for (let i = 0; i < mutations.length; i++) {
                        if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                            LaravelTimezone.hydrate();
                            break;
                        }
                    }
                });

                if (document.body) {
                    observer.observe(document.body, { childList: true, subtree: true });
                } else {
                    document.addEventListener('DOMContentLoaded', function () {
                        if (document.body) {
                            observer.observe(document.body, { childList: true, subtree: true });
                        }
                    });
                }
            }
        }
    };

    // Expose to window
    window.LaravelTimezone = LaravelTimezone;

    // Auto-initialize
    LaravelTimezone.init();
})(window, document);
