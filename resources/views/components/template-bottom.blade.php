<!--   Core JS Files   -->
<script src="{{ url('/') }}/assets/js/core/jquery-3.7.1.min.js"></script>
<script src="{{ url('/') }}/assets/js/core/popper.min.js"></script>
<script src="{{ url('/') }}/assets/js/core/bootstrap.min.js"></script>
<script src="{{ url('/') }}/assets/js/custom.js"></script>

<!-- jQuery Scrollbar -->
<script src="{{ url('/') }}/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

<!-- Chart JS -->
<script src="{{ url('/') }}/assets/js/plugin/chart.js/chart.min.js"></script>

<!-- jQuery Sparkline -->
<script src="{{ url('/') }}/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

<!-- Chart Circle -->
<script src="{{ url('/') }}/assets/js/plugin/chart-circle/circles.min.js"></script>

<!-- Datatables -->
<script src="{{ url('/') }}/assets/js/plugin/datatables/datatables.min.js"></script>

<!-- Bootstrap Notify -->
<script src="{{ url('/') }}/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

<!-- jQuery Vector Maps -->
<script src="{{ url('/') }}/assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
<script src="{{ url('/') }}/assets/js/plugin/jsvectormap/world.js"></script>

<!-- Sweet Alert -->
<script src="{{ url('/') }}/assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<!-- Kaiadmin JS -->
<script src="{{ url('/') }}/assets/js/kaiadmin.min.js"></script>

<!-- Kaiadmin DEMO methods -->
<script src="{{ url('/') }}/assets/js/setting-demo.js"></script>
{{-- <script src="{{ url('/') }}/assets/js/demo.js"></script> --}}
<script>
    /**
     * Global Helper: Mobile Segmented Tab Switcher dengan Sinkronisasi URL (?tab=...)
     */
    window.switchMobileTab = function (groupId, targetTabId, updateUrl = true) {
        const root = groupId ? document.getElementById(groupId) : document;
        if (!root) return;

        root.querySelectorAll('[data-tab-target]').forEach(function (btn) {
            const isMatch = btn.getAttribute('data-tab-target') === targetTabId;
            btn.classList.toggle('active', isMatch);
            btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
        });

        root.querySelectorAll('[data-tab-pane]').forEach(function (pane) {
            const isMatch = pane.getAttribute('data-tab-pane') === targetTabId;
            pane.classList.toggle('d-none', !isMatch);
        });

        if (updateUrl && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', targetTabId);
            window.history.replaceState({}, '', url.toString());
        }

        document.dispatchEvent(new CustomEvent('mobileTabChanged', {
            detail: { groupId: groupId, tabId: targetTabId }
        }));
    };

    // Pulihkan tab aktif dari query parameter ?tab=... saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const requestedTab = params.get('tab');
        if (requestedTab) {
            document.querySelectorAll('[data-tab-group]').forEach(function (groupEl) {
                const gId = groupEl.getAttribute('id');
                if (groupEl.querySelector('[data-tab-pane="' + requestedTab + '"]')) {
                    window.switchMobileTab(gId, requestedTab, false);
                }
            });
        }

        updatePwaConnectionStatus();
    });

    // Deteksi Status Koneksi Online/Offline untuk Proteksi Transaksi PWA
    function updatePwaConnectionStatus() {
        const isOnline = navigator.onLine;
        const offlineBanner = document.getElementById('pwa-offline-banner');
        const netBadge = document.getElementById('pwa-network-badge');

        if (offlineBanner) {
            offlineBanner.classList.toggle('d-none', isOnline);
        }
        if (netBadge) {
            netBadge.className = isOnline
                ? 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1'
                : 'badge bg-danger text-white px-2 py-1';
            netBadge.innerHTML = isOnline
                ? '<i class="fas fa-wifi me-1"></i>Online'
                : '<i class="fas fa-exclamation-triangle me-1"></i>Offline';
        }

        document.querySelectorAll('[data-requires-online="true"]').forEach(function (btn) {
            if (!isOnline) {
                btn.setAttribute('data-was-disabled', btn.disabled ? '1' : '0');
                btn.disabled = true;
            } else if (btn.getAttribute('data-was-disabled') === '0') {
                btn.disabled = false;
            }
        });
    }

    window.addEventListener('online', updatePwaConnectionStatus);
    window.addEventListener('offline', updatePwaConnectionStatus);

    // PWA Install Prompt (Add to Home Screen)
    let deferredPwaPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPwaPrompt = e;
        document.querySelectorAll('.pwa-install-trigger').forEach(function (el) {
            el.classList.remove('d-none');
        });
    });

    window.triggerPwaInstall = async function () {
        if (!deferredPwaPrompt) return;
        deferredPwaPrompt.prompt();
        await deferredPwaPrompt.userChoice;
        deferredPwaPrompt = null;
        document.querySelectorAll('.pwa-install-trigger').forEach(function (el) {
            el.classList.add('d-none');
        });
    };

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ url('/') }}/sw.js').catch(function () {});
        });
    }
</script>
@stack('scripts')
</body>

</html>
