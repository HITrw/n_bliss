</div><!-- /.admin-content -->

<!-- Scripts -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script>
    // Sidebar toggle for mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.toggle('show');
        });
    }

    // Initialize Select2
    $(document).ready(function () {
        $('.select2').select2();

        // DataTables initialization
        $('.datatable').DataTable({
            pageLength: 25,
            responsive: true,
            language: {
                search: "",
                searchPlaceholder: "Search..."
            }
        });


        // Update pending orders count
        function updatePendingOrders() {
            $.get('ajax/get-pending-orders.php', function (data) {
                $('#pending-orders-count').text(data.count);
            });
        }

        // Periodically update notifications and pending orders
        setInterval(loadNotifications, 30000); // Every 30 seconds
        setInterval(updatePendingOrders, 30000);

        // Initial load
        loadNotifications();
        updatePendingOrders();
    });

    // Show loading spinner
    function showLoading() {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = `
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(spinner);
    }

    // Hide loading spinner
    function hideLoading() {
        const spinner = document.querySelector('.spinner-overlay');
        if (spinner) {
            spinner.remove();
        }
    }

    // Handle form submissions with loading spinner
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            showLoading();
        });
    });

    // Format currency
    function formatCurrency(amount) {
        const formatted = new Intl.NumberFormat('rw-RW', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
        return `RWF ${formatted}`;
    }
</script>
</body>
</html>
