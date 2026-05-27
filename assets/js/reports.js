// Reports page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Date range selection handling
    const dateRangeSelect = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    
    dateRangeSelect?.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.classList.remove('d-none');
            // Reset time inputs to full day
            startTime.value = '00:00';
            endTime.value = '23:59';
        } else {
            customDateRange.classList.add('d-none');
        }
    });

    // Populate categories dropdown
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        fetch('../api/categories/list.php')
            .then(response => response.text())
            .then(text => {
                const jsonMatch = text.match(/\{.*\}/s);
                if (jsonMatch) {
                    const data = JSON.parse(jsonMatch[0]);
                    if (data.success) {
                        data.categories.forEach(category => {
                            const option = new Option(category.name, category.id);
                            categorySelect.add(option);
                        });
                    }
                }
            })
            .catch(error => console.error('Error loading categories:', error));
    }

    // Form submit handlers
    const salesReportForm = document.getElementById('salesReportForm');
    salesReportForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport('sales', this);
    });

    const inventoryReportForm = document.getElementById('inventoryReportForm');
    inventoryReportForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport('inventory', this);
    });
});

// Store report data globally for export functionality
let currentReportData = null;
let currentReportType = null;

function generateReport(reportType, form) {
    const resultsDiv = document.getElementById('reportResults');
    resultsDiv.innerHTML = '<div class="alert alert-info">Generating report...</div>';
    
    const formData = new FormData(form);
    formData.append('reportType', reportType);

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Generating...';
    submitButton.disabled = true;

    fetch('../api/reports/generate.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            // Try to parse the response as JSON
            const data = JSON.parse(text);
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to generate report');
            }

            if (data.data) {
                // Store data for export functionality
                currentReportData = data.data;
                currentReportType = reportType;
                displayReport(reportType, data.data);
            } else {
                throw new Error('No report data found in response');
            }
        } catch (e) {
            console.error('Raw response:', text);
            throw new Error('Invalid response format from server');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                ${error.message || 'Error generating report. Please try again.'}
            </div>
        `;
    })
    .finally(() => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    });
}

function formatDateTime(date, time) {
    return new Date(`${date} ${time}`).toLocaleString();
}

function displayReport(reportType, data) {
    const resultsDiv = document.getElementById('reportResults');
    let html = '';

    if (reportType === 'sales') {
        // Display summary
        html += `
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h3 class="card-text">RWF${parseFloat(data.summary.totalSales).toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h3 class="card-text">${data.summary.totalOrders}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Items Sold</h5>
                        <h3 class="card-text">${data.summary.totalItems}</h3>
                    </div>
                </div>
            </div>
        </div>`;

        // Display detailed sales data
        if (data.dailySales && data.dailySales.length > 0) {
            html += `
            <div class="table-responsive">
                <h4>=====Daily Sales Report=====</h4>
                <table class="table table-striped" id="dailySalesTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Orders</th>
                            <th>Items Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            data.dailySales.forEach(sale => {
                html += `
                    <tr>
                        <td>${formatDateTime(sale.date, sale.time)}</td>
                        <td>${sale.total_orders}</td>
                        <td>${sale.items_sold}</td>
                        <td>RWF${parseFloat(sale.total_sales).toFixed(2)}</td>
                    </tr>`;
            });

            html += `
                    </tbody>
                </table>
            </div>`;
        } else {
            html += '<div class="alert alert-info">No sales data found for the selected period.</div>';
        }

        // Show all items sold in the selected range
        if (data.allItems && data.allItems.length > 0) {
            html += `
            <h4 class="mt-4">Items Sold in Selected Range</h4>
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsSoldTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>`;
            data.allItems.forEach(item => {
                html += `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.quantity_sold}</td>
                        <td>RWF${parseFloat(item.total_revenue).toFixed(2)}</td>
                    </tr>`;
            });
            html += `
                    </tbody>
                </table>
            </div>`;
        }
    } else if (reportType === 'inventory') {
        html = generateInventoryReportHTML(data);
    }

    resultsDiv.innerHTML = html;

    // Add export buttons
    const exportDiv = document.createElement('div');
    exportDiv.className = 'mt-3';
    exportDiv.innerHTML = `
        <button class="btn btn-success me-2" onclick="exportToExcel('${reportType}')">
            Export to Excel
        </button>
    `;
    resultsDiv.appendChild(exportDiv);
}

function generateSalesReportHTML(data) {
    const { summary, dailySales, topItems } = data;
    return `
        <div class="report-summary mb-4">
            <h4>Sales Summary (${summary.startDate} to ${summary.endDate})</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text">${formatCurrency(summary.totalSales)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <p class="card-text">${summary.totalOrders}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Items Sold</h5>
                            <p class="card-text">${summary.totalItems}</p>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mt-4">==Daily Sales==</h4>
            <div class="table-responsive">
                <table class="table table-striped" id="salesReportTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Items Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${dailySales.map(day => `
                            <tr>
                                <td>${day.date}</td>
                                <td>${day.total_orders}</td>
                                <td>${day.items_sold}</td>
                                <td>${formatCurrency(day.total_sales)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            <h4 class="mt-4">==Top Selling Items==</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${topItems.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.quantity_sold}</td>
                                <td>${formatCurrency(item.total_revenue)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function generateInventoryReportHTML(data) {
    const { summary, items } = data;
    return `
        <div class="report-summary mb-4">
            <h4>Drinks Inventory Summary</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Items</h5>
                            <p class="card-text">${summary.totalItems}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">In Stock</h5>
                            <p class="card-text">${summary.inStock}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Low Stock</h5>
                            <p class="card-text">${summary.lowStock}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Out of Stock</h5>
                            <p class="card-text">${summary.outOfStock}</p>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mt-4">Inventory Details</h4>
            <div class="table-responsive">
                <table class="table table-striped" id="inventoryReportTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Stock Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.category}</td>
                                <td>${item.stock_quantity}</td>
                                <td>
                                    <span class="badge bg-${getStatusBadgeClass(item.stock_status)}">
                                        ${item.stock_status}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'In Stock':
            return 'success';
        case 'Low Stock':
            return 'warning';
        case 'Out of Stock':
            return 'danger';
        default:
            return 'secondary';
    }
}

function formatCurrency(amount) {
    const formatted = new Intl.NumberFormat('rw-RW', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return `RWF ${formatted}`;
}

function exportToExcel(reportType) {
    if (!currentReportData || currentReportType !== reportType) {
        alert('No report data available for export. Please generate a report first.');
        return;
    }

    // Debug: Log the current report data to console
    console.log('=== EXPORT DEBUG INFO ===');
    console.log('Current report data:', JSON.stringify(currentReportData, null, 2));
    console.log('Report type:', currentReportType);
    console.log('Data keys:', Object.keys(currentReportData));
    
    if (currentReportData.summary) {
        console.log('Summary keys:', Object.keys(currentReportData.summary));
    }
    
    console.log('=== END DEBUG INFO ===');

    const wb = XLSX.utils.book_new();
    
    if (reportType === 'sales') {
        exportSalesReportToExcel(wb, currentReportData);
    } else if (reportType === 'inventory') {
        exportInventoryReportToExcel(wb, currentReportData);
    }

    // Save the file
    const fileName = `${reportType}_report_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(wb, fileName);
}

function exportSalesReportToExcel(wb, data) {
    console.log('=== SALES EXPORT DEBUG ===');
    console.log('Data received in export function:', data);
    
    const ws_data = [];

    // Add title
    ws_data.push(['=====Sales Report=====']);
    ws_data.push([]);

    // Add summary section
    ws_data.push(['=====Sales Summary=====']);
    ws_data.push(['Metric', 'Value']);
    
    // Handle different possible data structures
    if (data.summary) {
        console.log('Found summary data:', data.summary);
        ws_data.push(['Total Sales', `RWF${parseFloat(data.summary.totalSales || 0).toFixed(2)}`]);
        ws_data.push(['Total Orders', data.summary.totalOrders || 0]);
        ws_data.push(['Items Sold', data.summary.totalItems || 0]);
    } else {
        console.log('No summary data found');
        ws_data.push(['Total Sales', 'RWF0.00']);
        ws_data.push(['Total Orders', 0]);
        ws_data.push(['Items Sold', 0]);
    }
    ws_data.push([]);

    // Add daily sales section if available
    if (data.dailySales && Array.isArray(data.dailySales) && data.dailySales.length > 0) {
        console.log('Found dailySales data:', data.dailySales.length, 'entries');
        ws_data.push(['=====Daily Sales Report=====']);
        ws_data.push(['Date & Time', 'Orders', 'Items Sold', 'Total Sales']);
        
        data.dailySales.forEach(sale => {
            ws_data.push([
                formatDateTime(sale.date, sale.time),
                sale.total_orders,
                sale.items_sold,
                `RWF${parseFloat(sale.total_sales).toFixed(2)}`
            ]);
        });
        
        ws_data.push([]);
    } else {
        console.log('No dailySales data found or empty array');
    }

    // Add items sold section if available
    if (data.allItems && Array.isArray(data.allItems) && data.allItems.length > 0) {
        console.log('Found allItems data:', data.allItems.length, 'entries');
        ws_data.push(['===Items Sold in Selected Range===']);
        ws_data.push(['Item Name', 'Quantity Sold', 'Total Revenue']);
        
        data.allItems.forEach(item => {
            ws_data.push([
                item.name,
                item.quantity_sold,
                `RWF${parseFloat(item.total_revenue).toFixed(2)}`
            ]);
        });
        
        ws_data.push([]);
    } else {
        console.log('No allItems data found or empty array');
    }

    // Add top items section if available
    if (data.topItems && Array.isArray(data.topItems) && data.topItems.length > 0) {
        console.log('Found topItems data:', data.topItems.length, 'entries');
        ws_data.push(['==Top Selling Items==']);
        ws_data.push(['Item', 'Quantity Sold', 'Revenue']);
        
        data.topItems.forEach(item => {
            ws_data.push([
                item.name,
                item.quantity_sold,
                `RWF${parseFloat(item.total_revenue).toFixed(2)}`
            ]);
        });
    } else {
        console.log('No topItems data found or empty array');
    }

    // Try to find any other arrays in the data that might contain sales information
    console.log('=== SEARCHING FOR OTHER DATA ARRAYS ===');
    Object.keys(data).forEach(key => {
        if (Array.isArray(data[key]) && data[key].length > 0) {
            console.log(`Found array "${key}" with ${data[key].length} items:`, data[key][0]);
        }
    });

    console.log('Final ws_data array:', ws_data);
    console.log('=== END SALES EXPORT DEBUG ===');

    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    
    // Set column widths
    ws['!cols'] = [
        { width: 25 }, // Column A
        { width: 15 }, // Column B
        { width: 15 }, // Column C
        { width: 20 }  // Column D
    ];

    // Add styling for headers
    const headerStyle = {
        font: { bold: true },
        fill: { fgColor: { rgb: "CCCCCC" } }
    };

    // Apply header styling
    if (ws['A1']) ws['A1'].s = { font: { bold: true, size: 14 } };
    
    // Find and style section headers
    ws_data.forEach((row, index) => {
        if (row[0] === 'Sales Summary' || row[0] === 'Daily Sales Report' || 
            row[0] === 'Items Sold in Selected Range' || row[0] === 'Top Selling Items') {
            const cellRef = XLSX.utils.encode_cell({ r: index, c: 0 });
            if (ws[cellRef]) ws[cellRef].s = headerStyle;
        }
        // Style column headers
        if (row[0] === 'Metric' || row[0] === 'Date & Time' || row[0] === 'Item Name' || row[0] === 'Item') {
            for (let col = 0; col < row.length; col++) {
                const cellRef = XLSX.utils.encode_cell({ r: index, c: col });
                if (ws[cellRef]) ws[cellRef].s = headerStyle;
            }
        }
    });

    XLSX.utils.book_append_sheet(wb, ws, 'Sales Report');
}

function exportInventoryReportToExcel(wb, data) {
    const ws_data = [];
    
    // Add title
    ws_data.push(['Inventory Report']);
    ws_data.push([]);

    // Add summary section
    ws_data.push(['Inventory Summary']);
    ws_data.push(['Total Items', data.summary.totalItems]);
    ws_data.push(['In Stock', data.summary.inStock]);
    ws_data.push(['Low Stock', data.summary.lowStock]);
    ws_data.push(['Out of Stock', data.summary.outOfStock]);
    ws_data.push([]);

    // Add inventory details
    ws_data.push(['Inventory Details']);
    ws_data.push(['Item', 'Category', 'Stock Quantity', 'Status']);
    
    data.items.forEach(item => {
        ws_data.push([
            item.name,
            item.category,
            item.stock_quantity,
            item.stock_status
        ]);
    });

    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    
    // Set column widths
    ws['!cols'] = [
        { width: 30 }, // Column A
        { width: 20 }, // Column B
        { width: 15 }, // Column C
        { width: 15 }  // Column D
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Inventory Report');
}

function exportToPDF(tableId) {
    const element = document.getElementById(tableId);
    html2pdf()
        .from(element)
        .save(`${tableId}_${new Date().toISOString().split('T')[0]}.pdf`);
}