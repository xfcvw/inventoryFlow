<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>InventoryFlow SaaS</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-logo" href="#" data-section-link="dashboard"><span class="logo-icon">IF</span><span>InventoryFlow</span></a>
        <div class="workspace-box">
            <small data-i18n="workspace">Workspace</small>
            <select id="workspace-select" class="workspace-select"></select>
            <div class="workspace-meta"><span class="badge badge-processing" id="workspace-role">—</span><span class="badge badge-in" id="workspace-plan">—</span></div>
        </div>
        <nav class="sidebar-nav">
            <button class="nav-button active" data-section="dashboard"><span>▦</span><span data-i18n="navDashboard">Dashboard</span></button>
            <button class="nav-button" data-section="products"><span>□</span><span data-i18n="navProducts">Products</span></button>
            <button class="nav-button" data-section="catalog"><span>◫</span><span data-i18n="navCatalog">Catalog</span></button>
            <button class="nav-button" data-section="inventory"><span>↕</span><span data-i18n="navInventory">Inventory</span></button>
            <button class="nav-button" data-section="orders"><span>☰</span><span data-i18n="navOrders">Orders</span></button>
            <button class="nav-button" data-section="customers"><span>◎</span><span data-i18n="navCustomers">Customers</span></button>
            <button class="nav-button" data-section="reports"><span>⌁</span><span data-i18n="navReports">Reports</span></button>
            <button class="nav-button admin-only" data-section="team"><span>♙</span><span data-i18n="navTeam">Team</span></button>
            <button class="nav-button owner-only" data-section="billing"><span>◇</span><span data-i18n="navBilling">Billing</span></button>
            <button class="nav-button admin-only" data-section="audit"><span>≡</span><span data-i18n="navAudit">Audit</span></button>
            <button class="nav-button" data-section="settings"><span>⚙</span><span data-i18n="navSettings">Settings</span></button>
        </nav>
        <div class="sidebar-footer"><span class="user-dot"></span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <button class="mobile-menu" id="mobile-menu">☰</button>
            <div class="topbar-status"><span class="status-dot"></span><span data-i18n="saasConnected">SaaS connected</span></div>
            <div class="topbar-spacer"></div>
            <button class="ghost-button" id="notification-button">🔔 <span id="notification-count">0</span></button>
            <select id="language-select" class="top-language"><option value="en">🇺🇸 EN</option><option value="pt">🇧🇷 PT</option></select>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="ghost-button" data-i18n="logout">Logout</button></form>
        </header>

        <section class="page-content app-section" id="section-dashboard">
            <div class="page-heading"><span class="eyebrow" data-i18n="overview">OVERVIEW</span><h1 data-i18n="dashboardTitle">Dashboard</h1><p data-i18n="dashboardSubtitle">Business overview for the selected workspace.</p></div>
            <section class="stats-grid six-stats">
                <article class="stat-card"><span data-i18n="totalProducts">Products</span><strong id="stat-products">0</strong><small data-i18n="registeredProducts">registered products</small></article>
                <article class="stat-card"><span data-i18n="totalUnits">Units in stock</span><strong id="stat-stock">0</strong><small data-i18n="availableUnits">available units</small></article>
                <article class="stat-card warning"><span data-i18n="lowStock">Low stock</span><strong id="stat-low-stock">0</strong><small data-i18n="needsAttention">needs attention</small></article>
                <article class="stat-card"><span data-i18n="totalOrders">Orders</span><strong id="stat-orders">0</strong><small data-i18n="registeredOrders">registered orders</small></article>
                <article class="stat-card"><span data-i18n="monthOrders">Month orders</span><strong id="stat-month-orders">0</strong><small data-i18n="thisMonth">this month</small></article>
                <article class="stat-card"><span data-i18n="monthRevenue">Month revenue</span><strong id="stat-revenue">—</strong><small data-i18n="thisMonth">this month</small></article>
            </section>
            <section class="content-grid">
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="recentMovements">Recent movements</h2><p data-i18n="recentMovementsSubtitle">Latest stock entries and exits.</p></div></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th data-i18n="type">Type</th><th data-i18n="quantity">Qty</th><th data-i18n="warehouse">Warehouse</th><th data-i18n="actor">Actor</th><th data-i18n="date">Date</th></tr></thead><tbody id="recent-movements"></tbody></table></div></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="lowStockProducts">Low stock</h2><p data-i18n="lowStockProductsSubtitle">Products at or below the minimum level.</p></div></div><div id="low-stock-list" class="stack-list"></div></article>
            </section>
        </section>

        <section class="page-content app-section hidden" id="section-products">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow">CATALOG</span><h1 data-i18n="productsTitle">Products</h1><p data-i18n="productsSubtitle">Create and manage the workspace catalog.</p></div><button class="primary-button manager-only" id="open-product-modal" data-i18n="newProduct">+ New product</button></div>
            <section class="panel"><div class="toolbar"><input id="product-search" class="search-input" type="search" data-i18n-placeholder="searchProducts" placeholder="Search products..."><select id="product-category-filter" class="filter-select"><option value="">All categories</option></select></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th>SKU</th><th data-i18n="category">Category</th><th data-i18n="supplier">Supplier</th><th data-i18n="price">Price</th><th data-i18n="stock">Stock</th><th data-i18n="actions">Actions</th></tr></thead><tbody id="products-table"></tbody></table></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-catalog">
            <div class="page-heading"><span class="eyebrow">MASTER DATA</span><h1 data-i18n="catalogManagement">Catalog management</h1><p data-i18n="catalogManagementSubtitle">Categories, suppliers and warehouses used by the rest of the system.</p></div>
            <section class="three-column-grid">
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="categories">Categories</h2></div><button class="primary-button manager-only" id="add-category">+</button></div><div id="categories-list" class="stack-list"></div></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="suppliers">Suppliers</h2></div><button class="primary-button manager-only" id="add-supplier">+</button></div><div id="suppliers-list" class="stack-list"></div></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="warehouses">Warehouses</h2></div><button class="primary-button admin-only" id="add-warehouse">+</button></div><div id="warehouses-list" class="stack-list"></div></article>
            </section>
        </section>

        <section class="page-content app-section hidden" id="section-inventory">
            <div class="page-heading"><span class="eyebrow">STOCK CONTROL</span><h1 data-i18n="inventoryTitle">Inventory</h1><p data-i18n="inventorySubtitle">Stock is tracked per warehouse and every movement is audited.</p></div>
            <section class="content-grid inventory-layout">
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="newMovement">New movement</h2></div></div><form id="movement-form" class="member-only"><div class="input-group"><label data-i18n="product">Product</label><select id="movement-product" required></select></div><div class="input-group"><label data-i18n="warehouse">Warehouse</label><select id="movement-warehouse" required></select></div><div class="input-group"><label data-i18n="type">Type</label><select id="movement-type"><option value="in" data-i18n="stockIn">Stock in</option><option value="out" data-i18n="stockOut">Stock out</option></select></div><div class="input-group"><label data-i18n="quantity">Quantity</label><input id="movement-quantity" type="number" min="1" required></div><div class="input-group"><label data-i18n="reason">Reason</label><input id="movement-reason" maxlength="160"></div><p id="movement-error" class="form-error"></p><button class="primary-button full-button" data-i18n="registerMovement">Register movement</button></form></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="movementHistory">Movement history</h2></div></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th data-i18n="warehouse">Warehouse</th><th data-i18n="type">Type</th><th data-i18n="quantity">Qty</th><th data-i18n="balance">Balance</th><th data-i18n="actor">Actor</th><th data-i18n="date">Date</th></tr></thead><tbody id="movements-table"></tbody></table></div></article>
            </section>
        </section>

        <section class="page-content app-section hidden" id="section-orders">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow">SALES</span><h1 data-i18n="ordersTitle">Orders</h1><p data-i18n="ordersSubtitle">Orders contain real line items and can automatically move stock.</p></div><button class="primary-button member-only" id="open-order-modal" data-i18n="newOrder">+ New order</button></div>
            <section class="stats-grid compact-stats"><article class="stat-card"><span data-i18n="pending">Pending</span><strong id="orders-pending">0</strong></article><article class="stat-card"><span data-i18n="processing">Processing</span><strong id="orders-processing">0</strong></article><article class="stat-card"><span data-i18n="completed">Completed</span><strong id="orders-completed">0</strong></article><article class="stat-card"><span data-i18n="cancelled">Cancelled</span><strong id="orders-cancelled">0</strong></article></section>
            <section class="panel"><div class="toolbar"><input id="order-search" class="search-input" type="search" data-i18n-placeholder="searchOrders" placeholder="Search orders..."><select id="order-status-filter" class="filter-select"><option value="" data-i18n="allStatuses">All statuses</option><option value="pending" data-i18n="pending">Pending</option><option value="processing" data-i18n="processing">Processing</option><option value="completed" data-i18n="completed">Completed</option><option value="cancelled" data-i18n="cancelled">Cancelled</option></select></div><div class="table-wrapper"><table><thead><tr><th data-i18n="orderId">Order</th><th data-i18n="customer">Customer</th><th data-i18n="items">Items</th><th data-i18n="total">Total</th><th data-i18n="status">Status</th><th data-i18n="date">Date</th><th data-i18n="actions">Actions</th></tr></thead><tbody id="orders-table"></tbody></table></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-customers">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow">CRM LITE</span><h1 data-i18n="customers">Customers</h1><p data-i18n="customersSubtitle">Reusable customer records instead of repeating customer names inside orders.</p></div><button class="primary-button member-only" id="add-customer">+ Customer</button></div>
            <section class="panel"><div class="toolbar"><input id="customer-search" class="search-input" type="search" placeholder="Search customers..."></div><div class="table-wrapper"><table><thead><tr><th data-i18n="customer">Customer</th><th>Email</th><th data-i18n="phone">Phone</th><th data-i18n="ordersTitle">Orders</th><th data-i18n="actions">Actions</th></tr></thead><tbody id="customers-table"></tbody></table></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-reports">
            <div class="page-heading"><span class="eyebrow">ANALYTICS</span><h1 data-i18n="reports">Reports</h1><p data-i18n="reportsSubtitle">Sales and inventory indicators built with aggregate queries.</p></div>
            <section class="panel"><div class="toolbar"><input id="report-from" type="date" class="search-input"><input id="report-to" type="date" class="search-input"><button class="primary-button" id="load-report">Run report</button></div><div id="report-gate" class="empty-state"></div><div id="report-content" class="hidden"><section class="stats-grid compact-stats"><article class="stat-card"><span>Orders</span><strong id="report-orders">0</strong></article><article class="stat-card"><span>Revenue</span><strong id="report-revenue">—</strong></article><article class="stat-card"><span>Average ticket</span><strong id="report-average">—</strong></article><article class="stat-card"><span>Inventory value</span><strong id="report-inventory-value">—</strong></article></section><div class="content-grid"><article class="panel nested-panel"><h2>Top products</h2><div id="report-top-products" class="stack-list"></div></article><article class="panel nested-panel"><h2>Low stock</h2><div id="report-low-stock" class="stack-list"></div></article></div></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-team">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow">RBAC</span><h1 data-i18n="team">Team</h1><p data-i18n="teamSubtitle">Invite members and control permissions with roles.</p></div><button class="primary-button admin-only" id="invite-member">+ Invite</button></div>
            <section class="content-grid"><article class="panel"><h2>Members</h2><div class="table-wrapper"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody id="team-table"></tbody></table></div></article><article class="panel"><h2>Pending invitations</h2><div id="invitations-list" class="stack-list"></div></article></section>
        </section>

        <section class="page-content app-section hidden" id="section-billing">
            <div class="page-heading"><span class="eyebrow">SUBSCRIPTION</span><h1 data-i18n="billing">Billing</h1><p data-i18n="billingSubtitle">Local billing simulator: plan limits are real, payment charging is intentionally simulated.</p></div><div id="billing-mode" class="notice-box"></div><section id="plans-grid" class="three-column-grid"></section>
        </section>

        <section class="page-content app-section hidden" id="section-audit">
            <div class="page-heading"><span class="eyebrow">SECURITY</span><h1 data-i18n="auditLog">Audit log</h1><p data-i18n="auditSubtitle">Who changed what, when and from which request context.</p></div><section class="panel"><div class="table-wrapper"><table><thead><tr><th>Date</th><th>Actor</th><th>Action</th><th>Subject</th><th>Metadata</th></tr></thead><tbody id="audit-table"></tbody></table></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-settings">
            <div class="page-heading"><span class="eyebrow">SAAS SETTINGS</span><h1 data-i18n="workspaceSettings">Workspace Settings</h1><p data-i18n="workspaceSettingsSubtitle">Tenant identity and localization settings.</p></div>
            <section class="content-grid settings-layout"><article class="panel"><h2>General</h2><form id="workspace-form"><div class="input-group"><label>Name</label><input id="workspace-name" required></div><div class="input-group"><label>Slug</label><input id="workspace-slug" disabled></div><div class="form-grid"><div class="input-group"><label>Currency</label><select id="workspace-currency"><option>BRL</option><option>USD</option><option>EUR</option></select></div><div class="input-group"><label>Locale</label><select id="workspace-locale"><option>pt-BR</option><option>en-US</option></select></div></div><div class="input-group"><label>Timezone</label><input id="workspace-timezone" value="America/Sao_Paulo"></div><div class="input-group"><label>Business type</label><input id="workspace-business-type"></div><button class="primary-button admin-only">Save settings</button><p id="workspace-error" class="form-error"></p></form></article><article class="panel"><h2>Usage</h2><div class="plan-card"><span>Current plan</span><strong id="settings-plan">—</strong></div><div class="usage-row"><span>Products</span><strong id="settings-products-usage">—</strong></div><div class="usage-row"><span>Members</span><strong id="settings-members-usage">—</strong></div><div class="usage-row"><span>Warehouses</span><strong id="settings-warehouses-usage">—</strong></div></article></section>
        </section>
    </main>
</div>

<div class="modal-backdrop hidden" id="generic-modal"><div class="modal"><div class="modal-header"><div><span class="eyebrow" id="generic-modal-eyebrow">FORM</span><h2 id="generic-modal-title">Edit</h2></div><button class="icon-button" id="generic-modal-close">×</button></div><form id="generic-form"><div id="generic-fields"></div><p id="generic-error" class="form-error"></p><div class="modal-actions"><button type="button" class="ghost-button" id="generic-cancel">Cancel</button><button class="primary-button" id="generic-save">Save</button></div></form></div></div>

<div class="modal-backdrop hidden" id="product-modal"><div class="modal large-modal"><div class="modal-header"><div><span class="eyebrow">PRODUCT FORM</span><h2 id="product-modal-title">New Product</h2></div><button class="icon-button" id="close-product-modal">×</button></div><form id="product-form" class="form-grid"><div class="input-group"><label>Name</label><input id="product-name" required></div><div class="input-group"><label>SKU</label><input id="product-sku" required></div><div class="input-group"><label>Barcode</label><input id="product-barcode"></div><div class="input-group"><label>Category</label><select id="product-category"></select></div><div class="input-group"><label>Supplier</label><select id="product-supplier"></select></div><div class="input-group"><label>Sale price</label><input id="product-price" type="number" step="0.01" min="0" required></div><div class="input-group"><label>Cost price</label><input id="product-cost" type="number" step="0.01" min="0" value="0"></div><div class="input-group"><label>Minimum stock</label><input id="product-min-stock" type="number" min="0" value="0"></div><div class="input-group" id="initial-stock-group"><label>Initial stock (default warehouse)</label><input id="product-initial-stock" type="number" min="0" value="0"></div><div class="input-group"><label>Active</label><select id="product-active"><option value="true">Yes</option><option value="false">No</option></select></div><div class="input-group full-width"><label>Description</label><textarea id="product-description" rows="3"></textarea></div><p id="product-error" class="form-error full-width"></p><div class="modal-actions full-width"><button type="button" class="ghost-button" id="cancel-product">Cancel</button><button class="primary-button">Save product</button></div></form></div></div>

<div class="modal-backdrop hidden" id="order-modal"><div class="modal large-modal"><div class="modal-header"><div><span class="eyebrow">ORDER BUILDER</span><h2>New Order</h2></div><button class="icon-button" id="close-order-modal">×</button></div><form id="order-form"><div class="form-grid"><div class="input-group"><label>Customer</label><select id="order-customer"></select></div><div class="input-group"><label>Warehouse</label><select id="order-warehouse" required></select></div><div class="input-group"><label>Status</label><select id="order-status"><option value="pending">Pending</option><option value="processing">Processing</option><option value="completed">Completed</option></select></div><div class="input-group"><label>Discount</label><input id="order-discount" type="number" min="0" step="0.01" value="0"></div><div class="input-group"><label>Tax</label><input id="order-tax" type="number" min="0" step="0.01" value="0"></div><div class="input-group full-width"><label>Notes</label><input id="order-notes"></div></div><div class="order-builder"><div class="order-builder-head"><h3>Items</h3><button type="button" class="ghost-button" id="add-order-item">+ Item</button></div><div id="order-items"></div><div class="order-total-preview">Estimated total: <strong id="order-total-preview">—</strong></div></div><p id="order-error" class="form-error"></p><div class="modal-actions"><button type="button" class="ghost-button" id="cancel-order">Cancel</button><button class="primary-button">Save order</button></div></form></div></div>

<div class="notification-drawer hidden" id="notification-drawer"><div class="drawer-header"><h3>Notifications</h3><div><button class="small-button" id="read-all-notifications">Read all</button><button class="icon-button" id="close-notifications">×</button></div></div><div id="notifications-list" class="stack-list"></div></div>
<div id="toast" class="toast hidden"></div>
<script src="{{ asset('assets/i18n.js') }}"></script>
<script src="{{ asset('assets/app.js') }}"></script>
</body>
</html>