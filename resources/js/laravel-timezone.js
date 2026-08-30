/**
 * LaravelTimezone Client - Lightweight, Zero-Dependency Browser Timezone Sync & Hydration
 * https://github.com/mixudev/laravel-timezone
 */
(function (window, document) {
    'use strict';

    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    const HEADER_NAME = 'X-Timezone';
    const COOKIE_NAME = 'timezone';
    const STORAGE_KEY = 'laravel_timezone';

    let liveIntervalId = null;
    const liveElements = [];

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
         * Sync timezone to cookie and localStorage without disturbing other cookies.
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
         * @param {string|Date} isoOrDate
         * @param {string} format
         * @returns {string}
         */
        formatDate: function (isoOrDate, format) {
            if (!isoOrDate) return '';
            const date = (isoOrDate instanceof Date) ? isoOrDate : new Date(isoOrDate);
            if (isNaN(date.getTime())) return '';

            const fmt = (format || 'datetime').toLowerCase();

            if (fmt === 'relative') {
                return this.formatRelative(date);
            }

            if (fmt === 'date' || fmt === 'y-m-d') {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            if (fmt === 'time' || fmt === 'h:i:s') {
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${hours}:${minutes}:${seconds}`;
            }

            if (fmt === 'datetime' || fmt === 'y-m-d h:i:s') {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }

            if (fmt === 'human') {
                return new Intl.DateTimeFormat(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                }).format(date);
            }

            // General PHP date format tokens fallback
            return this.formatCustomPhp(date, format || 'datetime');
        },

        /**
         * Format date according to standard PHP date() format characters.
         * @param {Date} date
         * @param {string} pattern
         * @returns {string}
         */
        formatCustomPhp: function (date, pattern) {
            const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthsLong = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const daysShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const daysLong = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            const Y = String(date.getFullYear());
            const y = Y.slice(-2);
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const n = String(date.getMonth() + 1);
            const M = monthsShort[date.getMonth()];
            const F = monthsLong[date.getMonth()];
            const d = String(date.getDate()).padStart(2, '0');
            const j = String(date.getDate());
            const D = daysShort[date.getDay()];
            const l = daysLong[date.getDay()];
            const H = String(date.getHours()).padStart(2, '0');
            const G = String(date.getHours());
            const h = String((date.getHours() % 12) || 12).padStart(2, '0');
            const g = String((date.getHours() % 12) || 12);
            const i = String(date.getMinutes()).padStart(2, '0');
            const s = String(date.getSeconds()).padStart(2, '0');
            const a = date.getHours() >= 12 ? 'pm' : 'am';
            const A = date.getHours() >= 12 ? 'PM' : 'AM';

            const map = { Y, y, m, n, M, F, d, j, D, l, H, G, h, g, i, s, a, A };

            return pattern.replace(/[YymnMDFdjDlhGgisaA]/g, function (match) {
                return map[match] !== undefined ? map[match] : match;
            });
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
                const isLive = el.getAttribute('data-live') === 'true';
                const isNow = el.getAttribute('data-now') === 'true';

                // Initial render
                const iso = isNow ? new Date().toISOString() : el.getAttribute('datetime');
                const format = el.getAttribute('data-format') || 'datetime';

                if (iso) {
                    const formatted = this.formatDate(iso, format);
                    if (formatted && el.textContent !== formatted) {
                        el.textContent = formatted;
                    }
                }

                if (isLive && !el.hasAttribute('data-live-registered')) {
                    el.setAttribute('data-live-registered', 'true');
                    liveElements.push(el);
                }

                el.setAttribute('data-local-time-hydrated', 'true');
            }

            this.startLiveTicker();
        },

        /**
         * Start ticker for live elements.
         */
        startLiveTicker: function () {
            if (liveElements.length === 0 || liveIntervalId !== null) {
                return;
            }

            const self = this;
            liveIntervalId = setInterval(function () {
                if (liveElements.length === 0) {
                    clearInterval(liveIntervalId);
                    liveIntervalId = null;
                    return;
                }

                const now = new Date();
                const nowIso = now.toISOString();

                for (let i = liveElements.length - 1; i >= 0; i--) {
                    const el = liveElements[i];
                    if (!document.body || !document.body.contains(el)) {
                        liveElements.splice(i, 1);
                        continue;
                    }

                    const isNow = el.getAttribute('data-now') === 'true';
                    const format = el.getAttribute('data-format') || 'datetime';
                    const iso = isNow ? nowIso : el.getAttribute('datetime');

                    if (iso) {
                        const formatted = self.formatDate(iso, format);
                        if (formatted && el.textContent !== formatted) {
                            el.textContent = formatted;
                        }
                    }
                }
            }, 1000);
        },

        /**
         * Safe HTTP Client Interceptors (Non-destructive to Request objects / CSRF / Auth Cookies).
         */
        attachInterceptors: function () {
            const tz = this.get();

            // 1. Fetch API Interceptor (Safe, non-mutating)
            if (typeof window.fetch === 'function') {
                const originalFetch = window.fetch;
                window.fetch = function (input, init) {
                    try {
                        if (typeof input === 'string' || (typeof URL !== 'undefined' && input instanceof URL)) {
                            init = init ? Object.assign({}, init) : {};
                            if (typeof Headers !== 'undefined' && init.headers instanceof Headers) {
                                if (!init.headers.has(HEADER_NAME)) {
                                    init.headers.set(HEADER_NAME, tz);
                                }
                            } else if (Array.isArray(init.headers)) {
                                init.headers.push([HEADER_NAME, tz]);
                            } else {
                                init.headers = Object.assign({}, init.headers);
                                if (!init.headers[HEADER_NAME] && !init.headers[HEADER_NAME.toLowerCase()]) {
                                    init.headers[HEADER_NAME] = tz;
                                }
                            }
                            return originalFetch.call(this, input, init);
                        } else if (input && typeof input === 'object' && input.headers && typeof input.headers.set === 'function') {
                            if (!input.headers.has(HEADER_NAME)) {
                                input.headers.set(HEADER_NAME, tz);
                            }
                            return originalFetch.call(this, input, init);
                        }
                    } catch (e) {}

                    return originalFetch.call(this, input, init);
                };
            }

            // 2. Axios Interceptor
            if (typeof window.axios !== 'undefined' && window.axios.defaults && window.axios.defaults.headers) {
                if (window.axios.defaults.headers.common) {
                    window.axios.defaults.headers.common[HEADER_NAME] = tz;
                }
            }

            // 3. Inertia Hook
            if (typeof document !== 'undefined' && document.addEventListener) {
                document.addEventListener('inertia:start', function (event) {
                    try {
                        if (event && event.detail && event.detail.visit && event.detail.visit.headers) {
                            event.detail.visit.headers[HEADER_NAME] = tz;
                        }
                    } catch (e) {}
                });

                document.addEventListener('inertia:finish', function () {
                    LaravelTimezone.hydrate();
                });

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

            // High-performance MutationObserver: strictly ignore text/content mutations to prevent CPU loops
            if (typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver(function (mutations) {
                    let hasNewTimeElements = false;

                    for (let i = 0; i < mutations.length; i++) {
                        const added = mutations[i].addedNodes;
                        if (!added || added.length === 0) continue;

                        for (let j = 0; j < added.length; j++) {
                            const node = added[j];
                            // Only check Element nodes (nodeType === 1), ignore text node ticks completely
                            if (node.nodeType === 1) {
                                if (node.tagName === 'TIME' && node.hasAttribute('data-local-time') && !node.hasAttribute('data-local-time-hydrated')) {
                                    hasNewTimeElements = true;
                                    break;
                                }
                                if (node.querySelector && node.querySelector('time[data-local-time]:not([data-local-time-hydrated])')) {
                                    hasNewTimeElements = true;
                                    break;
                                }
                            }
                        }
                        if (hasNewTimeElements) break;
                    }

                    if (hasNewTimeElements) {
                        LaravelTimezone.hydrate();
                    }
                });

                const observeRoot = document.body || document.documentElement;
                if (observeRoot) {
                    observer.observe(observeRoot, { childList: true, subtree: true });
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
