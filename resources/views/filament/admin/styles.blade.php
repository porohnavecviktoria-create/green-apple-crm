<style>
/* Green Apple Premium Theme - Navigation Styles */

/* Remove ALL bullets, lines, dots, and connecting elements from navigation */
.fi-sidebar-nav,
.fi-sidebar-nav ul,
.fi-sidebar-nav ul li,
.fi-sidebar-nav-item,
.fi-sidebar-nav-item ul,
.fi-sidebar-nav-item ul li,
.fi-sidebar-group,
.fi-sidebar-group-items,
.fi-sidebar-group-items li,
[class*="fi-sidebar"] ul,
[class*="fi-sidebar"] ul li {
    list-style: none !important;
    padding-left: 0 !important;
    margin-left: 0 !important;
}

/* Remove ALL pseudo-elements that create lines and dots */
.fi-sidebar-nav::before,
.fi-sidebar-nav::after,
.fi-sidebar-nav-item::before,
.fi-sidebar-nav-item::after,
.fi-sidebar-nav-item ul::before,
.fi-sidebar-nav-item ul::after,
.fi-sidebar-nav-item ul li::before,
.fi-sidebar-nav-item ul li::after,
.fi-sidebar-group::before,
.fi-sidebar-group::after,
.fi-sidebar-group-items::before,
.fi-sidebar-group-items::after,
.fi-sidebar-group-items li::before,
.fi-sidebar-group-items li::after,
.fi-sidebar-group-items > li::before,
.fi-sidebar-group-items > li::after,
[class*="fi-sidebar"] ul li::before,
[class*="fi-sidebar"] ul li::after {
    display: none !important;
    content: none !important;
}

/* Remove wrapper elements that create lines */
.fi-sidebar-group-items > li > *::before,
.fi-sidebar-group-items > li > *::after,
.fi-sidebar-group-items > li > div::before,
.fi-sidebar-group-items > li > div::after {
    display: none !important;
    content: none !important;
}

/* Remove any connecting lines, borders, and background elements */
.fi-sidebar-group-items {
    position: relative;
    border: none !important;
    background: none !important;
}

.fi-sidebar-group-items > li {
    position: relative;
    border: none !important;
    background: none !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* Remove any SVG, path, or line elements that create visual connectors */
.fi-sidebar-group-items svg[class*="line"],
.fi-sidebar-group-items path[class*="line"],
.fi-sidebar-group-items line {
    display: none !important;
}

/* Ensure icons are visible and properly styled */
.fi-sidebar-item-icon {
    width: 1.25rem !important;
    height: 1.25rem !important;
    flex-shrink: 0 !important;
    display: block !important;
    opacity: 1 !important;
}

/* Clean button styling with icons */
.fi-sidebar-item-button {
    border-radius: 8px !important;
    padding: 0.75rem 1rem !important;
    margin: 0.25rem 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    font-weight: 500 !important;
    color: rgb(55 65 81) !important;
    transition: all 0.2s ease !important;
    border-left: 2px solid transparent !important;
}

.fi-sidebar-item-button:hover {
    background-color: rgb(240 253 244) !important;
    color: rgb(22 163 74) !important;
    transform: translateX(4px);
    border-left-color: rgb(220 252 231) !important;
}

.fi-sidebar-item-active .fi-sidebar-item-button {
    background: linear-gradient(to right, rgb(220 252 231), transparent) !important;
    color: rgb(22 163 74) !important;
    font-weight: 600 !important;
    border-left: 3px solid rgb(34 197 94) !important;
    padding-left: calc(1rem - 1px) !important;
}

/* Enhanced table design */
.fi-ta-table {
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.fi-ta-header-cell {
    background: linear-gradient(to bottom, rgb(249 250 251), rgb(243 244 246)) !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    font-size: 0.75rem !important;
    letter-spacing: 0.05em;
    color: rgb(75 85 99) !important;
}

.fi-ta-row:hover {
    background-color: rgb(240 253 244) !important;
    transition: background-color 0.2s ease;
}

/* Enhanced buttons */
.fi-btn-primary {
    background: linear-gradient(to bottom right, rgb(34 197 94), rgb(22 163 74)) !important;
    border: none !important;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    font-weight: 600;
    transition: all 0.3s ease;
}

.fi-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

/* Enhanced sidebar */
.fi-sidebar {
    border-right: 1px solid rgb(229 231 235);
    background: white;
}

/* Enhanced cards */
.fi-section {
    border-radius: 12px !important;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05) !important;
}

.fi-section-header {
    background: linear-gradient(to bottom, rgb(249 250 251), white) !important;
    border-bottom: 2px solid rgb(34 197 94) !important;
}

/* Enhanced badges */
.fi-badge {
    font-weight: 600;
    border-radius: 9999px;
    padding: 0.25rem 0.75rem;
}

/* Page header */
.fi-header {
    background: linear-gradient(to right, rgb(240 253 244), white);
    border-bottom: 1px solid rgb(229 231 235);
}

/* Dashboard full width */
.fi-main-content {
    max-width: 100% !important;
    width: 100% !important;
}

.fi-page-content {
    max-width: 100% !important;
    width: 100% !important;
}

.fi-page {
    max-width: 100% !important;
    width: 100% !important;
}

.fi-page-header {
    max-width: 100% !important;
    width: 100% !important;
}

.fi-widget {
    max-width: 100% !important;
    width: 100% !important;
}

/* Widget containers full width */
.fi-widgets-grid {
    max-width: 100% !important;
    width: 100% !important;
}

.fi-widgets-container {
    max-width: 100% !important;
    width: 100% !important;
}

/* Remove padding from dashboard container */
.fi-main-ctn {
    max-width: 100% !important;
    width: 100% !important;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}

.fi-body {
    max-width: 100% !important;
    width: 100% !important;
}

/* Dashboard container full width */
.fi-main {
    max-width: 100% !important;
    width: 100% !important;
}

/* Remove max-width constraints */
[class*="max-w"]:has(.fi-widget) {
    max-width: 100% !important;
}

.fi-container {
    max-width: 100% !important;
    width: 100% !important;
}

/* Force full width for all dashboard elements */
.fi-body-content {
    max-width: 100% !important;
    width: 100% !important;
}

/* Remove all container constraints */
.fi-main-content > * {
    max-width: 100% !important;
    width: 100% !important;
}

/* Widgets grid full width */
[class*="fi-widgets"] {
    max-width: 100% !important;
    width: 100% !important;
}

/* All grid containers */
[class*="grid"]:has([class*="fi-widget"]) {
    max-width: 100% !important;
    width: 100% !important;
}

/* Remove padding/margin constraints */
.fi-main-content {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}

/* Section full width */
.fi-section {
    width: 100% !important;
    max-width: 100% !important;
}

/* Custom widgets full width */
.x-filament-widgets-widget,
[x-filament-widgets-widget] {
    width: 100% !important;
    max-width: 100% !important;
}

/* Chart containers */
.fi-widget-chart {
    width: 100% !important;
    max-width: 100% !important;
}

/* Remove all max-width on dashboard page */
body:has([class*="fi-page"]):has([class*="fi-widget"]) [class*="max-w"] {
    max-width: 100% !important;
}

/* Force all divs inside dashboard to full width */
.fi-page [class*="max-w"],
.fi-page > div[class*="max-w"],
.fi-main > div[class*="max-w"] {
    max-width: 100% !important;
    width: 100% !important;
}

/* Nuclear option - remove ALL max-width constraints */
.fi-main [class*="max-w"],
.fi-main > [class*="max-w"],
.fi-body [class*="max-w"],
.fi-body > [class*="max-w"],
.fi-page [class*="max-w"],
.fi-page > [class*="max-w"],
.fi-main-content [class*="max-w"],
.fi-main-content > [class*="max-w"] {
    max-width: 100% !important;
}

/* Force width 100% on main containers */
.fi-main,
.fi-body,
.fi-page,
.fi-main-content,
.fi-page-content,
.fi-body-content {
    max-width: 100vw !important;
    width: 100% !important;
}

/* Remove any inline max-width */
[style*="max-width"] {
    max-width: 100% !important;
}

/* Universal selector for any max-width inside dashboard */
.fi-main *,
.fi-body *,
.fi-page * {
    max-width: 100% !important;
}

/* Remove container padding constraints */
.fi-main,
.fi-body,
.fi-page {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.fi-main-content {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}

/* Override Filament grid CSS variables for full width */
.fi-wi,
[class*="fi-wi"],
[class*="fi-widget"] {
    --cols-default: repeat(1, 1fr) !important;
    --cols-lg: repeat(1, 1fr) !important;
    grid-template-columns: repeat(1, 1fr) !important;
    max-width: 100% !important;
    width: 100% !important;
}

/* Force full width for grid containers */
.grid.grid-cols-\[--cols-default\],
.grid.lg\:grid-cols-\[--cols-lg\] {
    --cols-default: repeat(1, 1fr) !important;
    --cols-lg: repeat(1, 1fr) !important;
    grid-template-columns: repeat(1, 1fr) !important;
}

/* Override grid column spans */
[class*="col-span"] {
    grid-column: span 1 / span 1 !important;
}

/* Force full width for widget wrapper */
.grid.flex-1.auto-cols-fr {
    max-width: 100% !important;
    width: 100% !important;
}

/* Dashboard page specific */
.fi-page-dashboard,
[data-page="dashboard"] {
    max-width: 100% !important;
    width: 100% !important;
}

/* Override section constraints */
section.flex.flex-col.gap-y-8 {
    max-width: 100% !important;
    width: 100% !important;
}
</style>
