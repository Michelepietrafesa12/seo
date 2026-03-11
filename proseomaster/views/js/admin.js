/**
 * ProSEO Master - Admin Panel JavaScript
 * Handles tab navigation, collapsibles, and form interactions
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        ProSEOAdmin.init();
    });

    window.ProSEOAdmin = {
        init: function() {
            this.initNavigation();
            this.initCollapsibles();
            this.initTabs();
            this.initTooltips();
            this.initConfirmDialogs();
            this.initCodeCopy();
            this.restoreActiveSection();
        },

        /**
         * Main sidebar navigation
         */
        initNavigation: function() {
            var navItems = document.querySelectorAll('.proseo-nav-item[data-section]');
            var sections = document.querySelectorAll('.proseo-section');

            navItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sectionId = this.getAttribute('data-section');

                    // Update nav active state
                    navItems.forEach(function(nav) {
                        nav.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Show corresponding section
                    sections.forEach(function(section) {
                        section.classList.remove('active');
                    });

                    var targetSection = document.getElementById('section-' + sectionId);
                    if (targetSection) {
                        targetSection.classList.add('active');
                    }

                    // Store in localStorage
                    localStorage.setItem('proseo_active_section', sectionId);

                    // Update URL hash
                    history.replaceState(null, null, '#' + sectionId);
                });
            });
        },

        /**
         * Restore last active section from localStorage or URL hash
         */
        restoreActiveSection: function() {
            var hash = window.location.hash.substring(1);
            var stored = localStorage.getItem('proseo_active_section');
            var sectionId = hash || stored || 'dashboard';

            var navItem = document.querySelector('.proseo-nav-item[data-section="' + sectionId + '"]');
            if (navItem) {
                navItem.click();
            }
        },

        /**
         * Collapsible sections
         */
        initCollapsibles: function() {
            var collapsibles = document.querySelectorAll('.proseo-collapsible-header');

            collapsibles.forEach(function(header) {
                header.addEventListener('click', function() {
                    var parent = this.closest('.proseo-collapsible');
                    parent.classList.toggle('open');
                });
            });
        },

        /**
         * Inner tabs (within cards)
         */
        initTabs: function() {
            var tabContainers = document.querySelectorAll('.proseo-tabs');

            tabContainers.forEach(function(container) {
                var tabs = container.querySelectorAll('.proseo-tab');
                var parent = container.closest('.proseo-card');
                var contents = parent ? parent.querySelectorAll('.proseo-tab-content') : [];

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function() {
                        var tabId = this.getAttribute('data-tab');

                        // Update tab active state
                        tabs.forEach(function(t) {
                            t.classList.remove('active');
                        });
                        this.classList.add('active');

                        // Show corresponding content
                        contents.forEach(function(content) {
                            content.classList.remove('active');
                        });

                        var targetContent = parent.querySelector('.proseo-tab-content[data-tab="' + tabId + '"]');
                        if (targetContent) {
                            targetContent.classList.add('active');
                        }
                    });
                });
            });
        },

        /**
         * Tooltip initialization (if needed beyond CSS)
         */
        initTooltips: function() {
            // CSS handles basic tooltips, this is for advanced cases
        },

        /**
         * Confirm dialogs for dangerous actions
         */
        initConfirmDialogs: function() {
            var confirmButtons = document.querySelectorAll('[data-confirm]');

            confirmButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    var message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        },

        /**
         * Copy code to clipboard
         */
        initCodeCopy: function() {
            var copyButtons = document.querySelectorAll('.proseo-code-copy');

            copyButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var codeBlock = this.closest('.proseo-code-preview');
                    var code = codeBlock.querySelector('code');
                    var text = code ? code.textContent : codeBlock.textContent;

                    navigator.clipboard.writeText(text).then(function() {
                        btn.textContent = 'Copied!';
                        setTimeout(function() {
                            btn.textContent = 'Copy';
                        }, 2000);
                    });
                });
            });
        },

        /**
         * Show loading state
         */
        showLoading: function(container) {
            var loader = document.createElement('div');
            loader.className = 'proseo-loading';
            loader.innerHTML = '<div class="proseo-spinner"></div><span>Loading...</span>';
            container.appendChild(loader);
        },

        /**
         * Hide loading state
         */
        hideLoading: function(container) {
            var loader = container.querySelector('.proseo-loading');
            if (loader) {
                loader.remove();
            }
        },

        /**
         * Show notification
         */
        notify: function(message, type) {
            type = type || 'info';
            var alert = document.createElement('div');
            alert.className = 'proseo-alert proseo-alert-' + type;
            alert.innerHTML = '<i class="material-icons">' + this.getAlertIcon(type) + '</i><span>' + message + '</span>';

            var content = document.querySelector('.proseo-content');
            if (content) {
                content.insertBefore(alert, content.firstChild);

                // Auto remove after 5 seconds
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            }
        },

        getAlertIcon: function(type) {
            var icons = {
                success: 'check_circle',
                warning: 'warning',
                danger: 'error',
                info: 'info'
            };
            return icons[type] || 'info';
        },

        /**
         * Update SEO score display
         */
        updateScoreCircle: function(score) {
            var circle = document.querySelector('.proseo-score-circle');
            if (circle) {
                circle.style.setProperty('--score-percent', score + '%');
                var valueEl = circle.querySelector('.proseo-score-value');
                if (valueEl) {
                    valueEl.textContent = score + '%';
                }
            }
        },

        /**
         * Toggle all switches in a group
         */
        toggleAllSwitches: function(groupSelector, state) {
            var switches = document.querySelectorAll(groupSelector + ' input[type="checkbox"]');
            switches.forEach(function(sw) {
                sw.checked = state;
            });
        },

        /**
         * Form validation helper
         */
        validateForm: function(form) {
            var valid = true;
            var requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--proseo-danger)';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            return valid;
        }
    };

})();
