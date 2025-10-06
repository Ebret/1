// ExtremeLife MLM Enhanced Dashboard JavaScript
// Implements all interactive features without template literals

(function() {
    'use strict';

    // Global variables
    let dashboardData = {};
    let charts = {};
    let searchTimeout = null;
    let notificationPermission = false;

    // Initialize dashboard when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializeDashboard();
    });

    // Main initialization function
    function initializeDashboard() {
        try {
            console.log('Initializing ExtremeLife Enhanced Dashboard...');
            
            // Initialize core features
            initializeSearch();
            initializeNotifications();
            initializeRealTimeUpdates();
            initializePWAFeatures();
            initializeInteractiveElements();
            initializeLazyLoading();
            
            // Load initial data
            loadDashboardData();
            
            console.log('Dashboard initialized successfully');
            showNotification('Dashboard loaded successfully', 'success');
            
        } catch (error) {
            console.error('Error initializing dashboard:', error);
            showNotification('Error loading dashboard', 'danger');
        }
    }

    // Global search functionality
    function initializeSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                hideSearchResults();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });

        // Handle search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = e.target.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            }
        });
    }

    // Perform search across dashboard
    function performSearch(query) {
        try {
            showLoading();
            
            fetch('ajax/dashboard_search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ query: query })
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }
                return response.json();
            })
            .then(function(data) {
                hideLoading();
                if (data.success) {
                    displaySearchResults(data.results);
                } else {
                    showNotification('Search failed: ' + (data.message || 'Unknown error'), 'warning');
                }
            })
            .catch(function(error) {
                hideLoading();
                console.error('Search error:', error);
                showNotification('Search error occurred', 'danger');
            });
            
        } catch (error) {
            hideLoading();
            console.error('Search error:', error);
        }
    }

    // Display search results
    function displaySearchResults(results) {
        // Implementation for search results display
        console.log('Search results:', results);
        // This would show a dropdown with search results
    }

    // Hide search results
    function hideSearchResults() {
        const resultsContainer = document.getElementById('searchResults');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }

    // Initialize push notifications
    function initializeNotifications() {
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                notificationPermission = true;
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(function(permission) {
                    notificationPermission = (permission === 'granted');
                });
            }
        }
    }

    // Show notification function (enhanced)
    function showNotification(message, type, options) {
        try {
            type = type || 'info';
            options = options || {};
            
            // Create notification element
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed notification-toast';
            alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
            
            // Build notification content
            const iconClass = getNotificationIcon(type);
            alertDiv.innerHTML = 
                '<div class="d-flex align-items-center">' +
                    '<i class="fas fa-' + iconClass + ' me-2"></i>' +
                    '<div class="flex-grow-1">' +
                        '<div class="notification-message">' + escapeHtml(message) + '</div>' +
                        (options.subtitle ? '<small class="notification-subtitle">' + escapeHtml(options.subtitle) + '</small>' : '') +
                    '</div>' +
                    '<button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
            
            document.body.appendChild(alertDiv);
            
            // Auto-remove notification
            setTimeout(function() {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, options.duration || 5000);
            
            // Show browser notification if enabled
            if (notificationPermission && options.browser) {
                new Notification('ExtremeLife MLM', {
                    body: message,
                    icon: 'images/extremelife-icon.png'
                });
            }
            
        } catch (error) {
            console.error('Error showing notification:', error);
        }
    }

    // Get notification icon based on type
    function getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle',
            'primary': 'bell'
        };
        return icons[type] || 'info-circle';
    }

    // Real-time updates
    function initializeRealTimeUpdates() {
        // Update dashboard every 2 minutes
        setInterval(function() {
            updateDashboardStats();
        }, 120000);
        
        // Check for new notifications every 30 seconds
        setInterval(function() {
            checkNewNotifications();
        }, 30000);
    }

    // Update dashboard statistics
    function updateDashboardStats() {
        try {
            fetch('ajax/dashboard_stats.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Stats update failed');
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                    dashboardData = data.stats;
                } else {
                    console.warn('Stats update failed:', data.message);
                }
            })
            .catch(function(error) {
                console.error('Error updating stats:', error);
            });
            
        } catch (error) {
            console.error('Stats update error:', error);
        }
    }

    // Update stats display
    function updateStatsDisplay(stats) {
        try {
            const elements = {
                'commission-balance': stats.commission_balance,
                'monthly-sales': stats.monthly_sales,
                'team-size': stats.team_size,
                'pending-orders': stats.pending_orders
            };
            
            Object.keys(elements).forEach(function(id) {
                const element = document.getElementById(id);
                if (element && elements[id] !== undefined) {
                    const value = elements[id];
                    if (id.includes('balance') || id.includes('sales')) {
                        element.textContent = '₱' + formatNumber(value);
                    } else {
                        element.textContent = value;
                    }
                    
                    // Add update animation
                    element.classList.add('stats-updated');
                    setTimeout(function() {
                        element.classList.remove('stats-updated');
                    }, 1000);
                }
            });
            
        } catch (error) {
            console.error('Error updating stats display:', error);
        }
    }

    // Check for new notifications
    function checkNewNotifications() {
        try {
            fetch('ajax/check_notifications.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.notifications.length > 0) {
                    updateNotificationBadge(data.count);
                    
                    // Show new notifications
                    data.notifications.forEach(function(notification) {
                        if (notification.is_new) {
                            showNotification(notification.message, notification.type, {
                                browser: true,
                                subtitle: notification.created_at
                            });
                        }
                    });
                }
            })
            .catch(function(error) {
                console.error('Error checking notifications:', error);
            });
            
        } catch (error) {
            console.error('Notification check error:', error);
        }
    }

    // Update notification badge
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // Initialize PWA features
    function initializePWAFeatures() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registered successfully');
                })
                .catch(function(error) {
                    console.log('ServiceWorker registration failed:', error);
                });
        }
        
        // Handle install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            showInstallPrompt();
        });
        
        // Handle app installed
        window.addEventListener('appinstalled', function(e) {
            console.log('PWA was installed');
            showNotification('App installed successfully!', 'success');
        });
    }

    // Show PWA install prompt
    function showInstallPrompt() {
        const installButton = document.createElement('button');
        installButton.className = 'btn btn-primary btn-sm position-fixed';
        installButton.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000;';
        installButton.innerHTML = '<i class="fas fa-download me-1"></i>Install App';
        
        installButton.addEventListener('click', function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                    installButton.remove();
                });
            }
        });
        
        document.body.appendChild(installButton);
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            if (installButton.parentNode) {
                installButton.remove();
            }
        }, 10000);
    }

    // Initialize interactive elements
    function initializeInteractiveElements() {
        // Tab switching with animation
        const tabButtons = document.querySelectorAll('[data-bs-toggle="pill"]');
        tabButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = button.getAttribute('data-bs-target');
                const targetPane = document.querySelector(targetId);
                
                if (targetPane) {
                    // Add loading animation
                    targetPane.style.opacity = '0.5';
                    setTimeout(function() {
                        targetPane.style.opacity = '1';
                        loadTabContent(targetId.replace('#', ''));
                    }, 200);
                }
            });
        });
        
        // Collapsible sections
        initializeCollapsibleSections();
        
        // Quick action buttons
        initializeQuickActions();
    }

    // Initialize collapsible sections
    function initializeCollapsibleSections() {
        const collapsibleElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
        collapsibleElements.forEach(function(element) {
            element.addEventListener('click', function() {
                const icon = element.querySelector('i');
                if (icon) {
                    setTimeout(function() {
                        if (element.getAttribute('aria-expanded') === 'true') {
                            icon.className = icon.className.replace('fa-plus', 'fa-minus');
                        } else {
                            icon.className = icon.className.replace('fa-minus', 'fa-plus');
                        }
                    }, 100);
                }
            });
        });
    }

    // Initialize quick actions
    function initializeQuickActions() {
        // These functions will be called by onclick handlers in HTML
        window.openProductCatalog = function() {
            showLoading();
            setTimeout(function() {
                hideLoading();
                document.querySelector('[data-bs-target="#sales"]').click();
                showNotification('Product catalog loaded', 'info');
            }, 500);
        };
        
        window.shareReferralLink = function() {
            if (navigator.share) {
                navigator.share({
                    title: 'Join ExtremeLife MLM',
                    text: 'Join my ExtremeLife MLM team and start earning!',
                    url: window.location.origin + '/register?ref=' + (dashboardData.member_id || '123')
                });
            } else {
                // Fallback to clipboard
                const referralLink = window.location.origin + '/register?ref=' + (dashboardData.member_id || '123');
                navigator.clipboard.writeText(referralLink).then(function() {
                    showNotification('Referral link copied to clipboard!', 'success');
                });
            }
        };
        
        window.viewGenealogy = function() {
            showLoading();
            setTimeout(function() {
                hideLoading();
                document.querySelector('[data-bs-target="#team"]').click();
                showNotification('Genealogy tree loaded', 'info');
            }, 500);
        };
        
        window.contactSupport = function() {
            showNotification('Opening support chat...', 'info');
            // This would open a support chat widget
        };
    }

    // Initialize lazy loading
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const lazyImages = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    // Load dashboard data
    function loadDashboardData() {
        updateDashboardStats();
    }

    // Load tab content dynamically
    function loadTabContent(tabId) {
        console.log('Loading content for tab:', tabId);
        // This would load specific content for each tab
    }

    // Utility functions
    function showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    }

    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function formatNumber(num) {
        if (isNaN(num)) return '0.00';
        return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

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

    // Activity toggle function
    window.toggleActivityDetails = function() {
        const timeline = document.getElementById('activityTimeline');
        if (timeline) {
            timeline.classList.toggle('expanded');
            const button = event.target.closest('button');
            const icon = button.querySelector('i');
            if (timeline.classList.contains('expanded')) {
                timeline.style.maxHeight = 'none';
                icon.className = 'fas fa-compress-alt';
            } else {
                timeline.style.maxHeight = '400px';
                icon.className = 'fas fa-expand-alt';
            }
        }
    };

    // Export functions for global access
    window.ExtremeLifeDashboard = {
        showNotification: showNotification,
        updateDashboardStats: updateDashboardStats,
        showLoading: showLoading,
        hideLoading: hideLoading
    };

})();
