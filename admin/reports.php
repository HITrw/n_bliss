<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$pageTitle = 'Reports';
$currentPage = 'reports';

// Get database instance
$db = Database::getInstance();

// Include header
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reports</h1>
    </div>

    <div class="row">
        <!-- Sales Report Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sales Report</h5>
                </div>
                <div class="card-body">
                    <form id="salesReportForm">
                        <div class="mb-3">
                            <label for="dateRange" class="form-label">Date Range</label>
                            <select class="form-select" id="dateRange" name="dateRange">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="thisWeek">This Week</option>
                                <option value="thisMonth">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div id="customDateRange" class="d-none">
                            <div class="mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <div class="d-flex gap-2">
                                    <input type="date" class="form-control" id="startDate" name="startDate">
                                    <input type="time" class="form-control" id="startTime" name="startTime" value="00:00">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <div class="d-flex gap-2">
                                    <input type="date" class="form-control" id="endDate" name="endDate">
                                    <input type="time" class="form-control" id="endTime" name="endTime" value="23:59">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Inventory Report Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">                <div class="card-header">
                    <h5 class="card-title mb-0">Drinks Inventory Report</h5>
                </div>
                <div class="card-body">
                    <form id="inventoryReportForm">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="all">All Categories</option>
                                <!-- Categories will be populated dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stockStatus" class="form-label">Stock Status</label>
                            <select class="form-select" id="stockStatus" name="stockStatus">
                                <option value="all">All</option>
                                <option value="inStock">In Stock</option>
                                <option value="lowStock">Low Stock</option>
                                <option value="outOfStock">Out of Stock</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Results</h5>
                </div>
                <div class="card-body">
                    <div id="reportResults">
                        <!-- Report results will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add required libraries for export functionality -->
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="../assets/js/reports.js"></script>

<?php require_once 'footer.php'; ?>
