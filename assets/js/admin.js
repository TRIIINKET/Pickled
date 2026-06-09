// Admin Panel JavaScript Utilities

// Initialize dropdowns
function initializeDropdowns() {
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                menu.classList.toggle('active');
            }
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
}

// Form validation
function initializeFormValidation() {
    document.querySelectorAll('.form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill in all required fields', 'error');
            }
        });
    });
}

// Toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${getToastColor(type)};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        animation: slideRight 0.45s cubic-bezier(.22,.68,0,1.1) both;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function getToastColor(type) {
    const colors = {
        'success': '#DDE255',
        'error': '#F85696',
        'warning': '#F68806',
        'info': '#264414'
    };
    return colors[type] || colors['info'];
}

// Format currency
function formatCurrency(value, currency = '₱') {
    return currency + Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Format date
function formatDate(date, format = 'MMM DD, YYYY') {
    if (typeof date === 'string') {
        date = new Date(date);
    }
    
    const options = {
        'MMM DD, YYYY': { month: 'short', day: 'numeric', year: 'numeric' },
        'DD/MM/YYYY': { day: '2-digit', month: '2-digit', year: 'numeric' },
        'YYYY-MM-DD': { year: 'numeric', month: '2-digit', day: '2-digit' }
    };
    
    return new Intl.DateTimeFormat('en-US', options[format] || options['MMM DD, YYYY']).format(date);
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td, th')).map(cell => {
            let text = cell.textContent.trim();
            text = text.replace(/"/g, '""');
            return `"${text}"`;
        });
        csv.push(cells.join(','));
    });
    
    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', filename);
    link.click();
}

// Print table
function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const printWindow = window.open('', '', 'height=500,width=800');
    printWindow.document.write('<html><head><title>Print</title>');
    const appBase = window.location.pathname.includes('/admin/')
        ? window.location.pathname.split('/admin/')[0]
        : '';
    printWindow.document.write('<link rel="stylesheet" href="' + appBase + '/assets/css/admin-style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(table.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Search table
function searchTable(searchId, tableId) {
    const searchInput = document.getElementById(searchId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Sort table
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        return aVal.localeCompare(bVal);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Delete confirmation
function confirmDelete(message = 'Are you sure?') {
    return confirm(message);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeDropdowns();
    initializeFormValidation();
    
    // Make currency and date formatters available globally
    window.formatCurrency = formatCurrency;
    window.formatDate = formatDate;
    window.showToast = showToast;
    window.exportTableToCSV = exportTableToCSV;
    window.printTable = printTable;
    window.searchTable = searchTable;
    window.sortTable = sortTable;
    window.confirmDelete = confirmDelete;
});
