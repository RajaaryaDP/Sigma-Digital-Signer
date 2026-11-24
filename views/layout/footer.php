<?php
$currentUser = $_SESSION['user'] ?? null;
?>
<?php if ($currentUser): ?>
            </main>

            <footer class="sigma-workspace-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>&copy; <strong>SIGMA</strong> — Sistem Tanda Tangan Digital</span>
                
            </footer>
        </div>
    </div>

    <script>
        (function () {
            var toggleBtn = document.getElementById('sigmaSidebarToggle');
            var sidebar = document.getElementById('sigmaSidebar');
            var backdrop = document.getElementById('sigmaSidebarBackdrop');
            if (!toggleBtn || !sidebar) return;

            function closeMobileDrawer() {
                sidebar.classList.remove('open');
                if (backdrop) backdrop.classList.remove('show');
            }

            toggleBtn.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    var willOpen = !sidebar.classList.contains('open');
                    sidebar.classList.toggle('open', willOpen);
                    if (backdrop) backdrop.classList.toggle('show', willOpen);
                } else if (document.body.classList.contains('sigma-focus-mode')) {
                    document.body.classList.toggle('sigma-sidebar-expanded');
                }
            });

            if (backdrop) {
                backdrop.addEventListener('click', closeMobileDrawer);
            }
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992) closeMobileDrawer();
            });
        })();
    </script>
<?php else: ?>
    </main>

    <?php require __DIR__ . '/sigma-footer.php'; ?>
<?php endif; ?>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
