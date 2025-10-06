// Fixed JavaScript for Member Dashboard Enhanced Live
// Eliminates template literals and fixes syntax errors

// Dashboard refresh function
function refreshDashboard() {
    try {
        const btn = event.target.closest('button');
        if (!btn) {
            console.error('Refresh button not found');
            return;
        }
        
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        btn.disabled = true;
        
        setTimeout(function() {
            location.reload();
        }, 1000);
    } catch (error) {
        console.error('Error in refreshDashboard:', error);
        showNotification('Error refreshing dashboard', 'danger');
    }
}

// Show notification function
function showNotification(message, type) {
    try {
        // Default type to 'info' if not provided
        if (!type) {
            type = 'info';
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        // Build HTML content without template literals
        alertDiv.innerHTML = 
            '<div class="d-flex align-items-center">' +
                '<i class="fas fa-' + getIconForType(type) + ' me-2"></i>' +
                '<span>' + escapeHtml(message) + '</span>' +
                '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            if (alertDiv && alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    } catch (error) {
        console.error('Error in showNotification:', error);
    }
}

// Helper function to get icon for notification type
function getIconForType(type) {
    switch (type) {
        case 'success':
            return 'check-circle';
        case 'danger':
        case 'error':
            return 'exclamation-triangle';
        case 'warning':
            return 'exclamation-circle';
        case 'info':
        default:
            return 'info-circle';
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Additional dashboard functions
function updateDashboardStats() {
    try {
        // Fetch updated stats via AJAX
        fetch('ajax/dashboard_stats.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                updateStatsDisplay(data.stats);
                showNotification('Dashboard updated successfully', 'success');
            } else {
                throw new Error(data.message || 'Failed to update stats');
            }
        })
        .catch(function(error) {
            console.error('Error updating dashboard stats:', error);
            showNotification('Failed to update dashboard stats', 'danger');
        });
    } catch (error) {
        console.error('Error in updateDashboardStats:', error);
        showNotification('Error updating dashboard', 'danger');
    }
}

// Update stats display
function updateStatsDisplay(stats) {
    try {
        // Update commission balance
        const commissionElement = document.getElementById('commission-balance');
        if (commissionElement && stats.commission_balance !== undefined) {
            commissionElement.textContent = '₱' + formatNumber(stats.commission_balance);
        }
        
        // Update total earnings
        const earningsElement = document.getElementById('total-earnings');
        if (earningsElement && stats.total_earnings !== undefined) {
            earningsElement.textContent = '₱' + formatNumber(stats.total_earnings);
        }
        
        // Update team size
        const teamSizeElement = document.getElementById('team-size');
        if (teamSizeElement && stats.team_size !== undefined) {
            teamSizeElement.textContent = stats.team_size;
        }
        
        // Update active referrals
        const referralsElement = document.getElementById('active-referrals');
        if (referralsElement && stats.active_referrals !== undefined) {
            referralsElement.textContent = stats.active_referrals;
        }
    } catch (error) {
        console.error('Error updating stats display:', error);
    }
}

// Format number with commas
function formatNumber(num) {
    if (isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            updateDashboardStats();
        }, 300000); // 5 minutes
        
        // Add click handlers for refresh buttons
        const refreshButtons = document.querySelectorAll('[data-action="refresh"]');
        refreshButtons.forEach(function(button) {
            button.addEventListener('click', refreshDashboard);
        });
        
        // Add click handlers for notification close buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-close')) {
                const alert = e.target.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            }
        });
        
        console.log('Dashboard JavaScript initialized successfully');
    } catch (error) {
        console.error('Error initializing dashboard:', error);
    }
});

// Export functions for global access (if needed)
window.refreshDashboard = refreshDashboard;
window.showNotification = showNotification;
window.updateDashboardStats = updateDashboardStats;
