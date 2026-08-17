<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>InventoryFlow</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-logo nav-button" data-section="dashboard"><span class="logo-icon">IF</span><span>InventoryFlow</span></button>
        <nav class="sidebar-nav">
            <button class="nav-button active" data-section="dashboard"><span>▦</span><span data-i18n="navDashboard">Dashboard</span></button>
            <button class="nav-button" data-section="products"><span>□</span><span data-i18n="navProducts">Products</span></button>
            <button class="nav-button" data-section="inventory"><span>↕</span><span data-i18n="navInventory">Inventory</span></button>
            <button class="nav-button" data-section="orders"><span>☰</span><span data-i18n="navOrders">Orders</span></button>
        </nav>
        <div class="sidebar-footer"><span class="user-dot"></span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button class="mobile-menu" id="mobile-menu">☰</button>
            <div class="topbar-status"><span class="status-dot"></span><span data-i18n="apiConnected">API connected</span></div>
            <div class="topbar-spacer"></div>
            <select id="language-select" class="top-language"><option value="en">🇺🇸 EN</option><option value="pt">🇧🇷 PT</option></select>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="ghost-button" type="submit" data-i18n="logout">Logout</button></form>
        </header>

        <section class="page-content app-section" id="section-dashboard">
            <div class="page-heading"><span class="eyebrow" data-i18n="overview">OVERVIEW</span><h1 data-i18n="dashboardTitle">Dashboard</h1><p data-i18n="dashboardSubtitle">Monitor your inventory and business activity.</p></div>
            <section class="stats-grid">
                <article class="stat-card"><span data-i18n="totalProducts">Total Products</span><strong id="stat-products">—</strong><small data-i18n="registeredProducts">registered products</small></article>
                <article class="stat-card"><span data-i18n="totalUnits">Units in Stock</span><strong id="stat-stock">—</strong><small data-i18n="availableUnits">available units</small></article>
                <article class="stat-card warning"><span data-i18n="lowStock">Low Stock</span><strong id="stat-low-stock">—</strong><small data-i18n="needsAttention">needs attention</small></article>
                <article class="stat-card"><span data-i18n="totalOrders">Orders</span><strong id="stat-orders">—</strong><small data-i18n="registeredOrders">registered orders</small></article>
            </section>
            <section class="content-grid">
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="recentMovements">Recent Inventory Movements</h2><p data-i18n="recentMovementsSubtitle">Latest stock entries and exits.</p></div></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th data-i18n="type">Type</th><th data-i18n="quantity">Quantity</th><th data-i18n="date">Date</th></tr></thead><tbody id="recent-movements"></tbody></table></div></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="lowStockProducts">Low Stock Products</h2><p data-i18n="lowStockProductsSubtitle">Products at or below the minimum level.</p></div></div><div id="low-stock-list" class="stack-list"></div></article>
            </section>
        </section>

        <section class="page-content app-section hidden" id="section-products">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow" data-i18n="catalog">CATALOG</span><h1 data-i18n="productsTitle">Products</h1><p data-i18n="productsSubtitle">Create, search, edit and organize your products.</p></div><button class="primary-button" id="open-product-modal" data-i18n="newProduct">+ New product</button></div>
            <section class="panel"><div class="toolbar"><input id="product-search" class="search-input" type="search" data-i18n-placeholder="searchProducts" placeholder="Search products..."><select id="category-filter" class="filter-select"><option value="" data-i18n="allCategories">All categories</option></select></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th data-i18n="sku">SKU</th><th data-i18n="category">Category</th><th data-i18n="price">Price</th><th data-i18n="stock">Stock</th><th data-i18n="actions">Actions</th></tr></thead><tbody id="products-table"></tbody></table></div></section>
        </section>

        <section class="page-content app-section hidden" id="section-inventory">
            <div class="page-heading"><span class="eyebrow" data-i18n="stockControl">STOCK CONTROL</span><h1 data-i18n="inventoryTitle">Inventory</h1><p data-i18n="inventorySubtitle">Register stock entries and exits and review movement history.</p></div>
            <section class="content-grid inventory-layout">
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="newMovement">New movement</h2><p data-i18n="newMovementSubtitle">Update the stock level of a product.</p></div></div><form id="movement-form"><div class="input-group"><label data-i18n="product">Product</label><select id="movement-product" required></select></div><div class="input-group"><label data-i18n="type">Type</label><select id="movement-type" required><option value="in" data-i18n="stockIn">Stock in</option><option value="out" data-i18n="stockOut">Stock out</option></select></div><div class="input-group"><label data-i18n="quantity">Quantity</label><input id="movement-quantity" type="number" min="1" required></div><p id="movement-error" class="form-error"></p><button type="submit" class="primary-button full-button" data-i18n="registerMovement">Register movement</button></form></article>
                <article class="panel"><div class="panel-header"><div><h2 data-i18n="movementHistory">Movement history</h2><p data-i18n="movementHistorySubtitle">All registered inventory changes.</p></div></div><div class="table-wrapper"><table><thead><tr><th data-i18n="product">Product</th><th data-i18n="type">Type</th><th data-i18n="quantity">Quantity</th><th data-i18n="date">Date</th></tr></thead><tbody id="movements-table"></tbody></table></div></article>
            </section>
        </section>

        <section class="page-content app-section hidden" id="section-orders">
            <div class="page-heading page-heading-actions"><div><span class="eyebrow" data-i18n="sales">SALES</span><h1 data-i18n="ordersTitle">Orders</h1><p data-i18n="ordersSubtitle">Track customer orders and their current status.</p></div><button class="primary-button" id="open-order-modal" data-i18n="newOrder">+ New order</button></div>
            <section class="stats-grid compact-stats"><article class="stat-card"><span data-i18n="pending">Pending</span><strong id="orders-pending">0</strong></article><article class="stat-card"><span data-i18n="processing">Processing</span><strong id="orders-processing">0</strong></article><article class="stat-card"><span data-i18n="completed">Completed</span><strong id="orders-completed">0</strong></article><article class="stat-card"><span data-i18n="cancelled">Cancelled</span><strong id="orders-cancelled">0</strong></article></section>
            <section class="panel"><div class="toolbar"><input id="order-search" class="search-input" type="search" data-i18n-placeholder="searchOrders" placeholder="Search by customer..."><select id="order-status-filter" class="filter-select"><option value="" data-i18n="allStatuses">All statuses</option><option value="pending" data-i18n="pending">Pending</option><option value="processing" data-i18n="processing">Processing</option><option value="completed" data-i18n="completed">Completed</option><option value="cancelled" data-i18n="cancelled">Cancelled</option></select></div><div class="table-wrapper"><table><thead><tr><th data-i18n="orderId">Order</th><th data-i18n="customer">Customer</th><th data-i18n="total">Total</th><th data-i18n="status">Status</th><th data-i18n="date">Date</th><th data-i18n="actions">Actions</th></tr></thead><tbody id="orders-table"></tbody></table></div></section>
        </section>
    </main>
</div>

<div class="modal-backdrop hidden" id="product-modal"><div class="modal"><div class="modal-header"><div><span class="eyebrow" data-i18n="productForm">PRODUCT FORM</span><h2 id="product-modal-title" data-i18n="newProductTitle">New Product</h2></div><button class="icon-button" id="close-product-modal">×</button></div><form id="product-form" class="form-grid"><div class="input-group"><label data-i18n="productName">Product name</label><input id="product-name" required></div><div class="input-group"><label data-i18n="sku">SKU</label><input id="product-sku" required></div><div class="input-group"><label data-i18n="category">Category</label><input id="product-category" required></div><div class="input-group"><label data-i18n="price">Price</label><input id="product-price" type="number" min="0" step="0.01" required></div><div class="input-group"><label data-i18n="stock">Stock</label><input id="product-stock" type="number" min="0" required></div><div class="input-group"><label data-i18n="minimumStock">Minimum stock</label><input id="product-min-stock" type="number" min="0" required></div><p id="product-error" class="form-error full-width"></p><div class="modal-actions full-width"><button type="button" class="ghost-button" id="cancel-product" data-i18n="cancel">Cancel</button><button type="submit" class="primary-button" data-i18n="saveProduct">Save product</button></div></form></div></div>
<div class="modal-backdrop hidden" id="order-modal">
    <div class="modal">

        <div class="modal-header">
            <div>
                <span class="eyebrow">
                    FORMULÁRIO DE PEDIDO
                </span>

                <h2>Novo Pedido</h2>
            </div>

            <button
                class="icon-button"
                id="close-order-modal"
                type="button"
            >
                ×
            </button>
        </div>


        <form id="order-form">

            <!-- CLIENTE -->
            <div class="form-grid">

                <div class="input-group">
                    <label for="order-customer-name">
                        Nome do cliente
                    </label>

                    <input
                        id="order-customer-name"
                        type="text"
                        required
                        placeholder="Ex: João Silva"
                    >
                </div>


                <div class="input-group">
                    <label for="order-customer-email">
                        E-mail
                    </label>

                    <input
                        id="order-customer-email"
                        type="email"
                        placeholder="Ex: joao@email.com"
                    >
                </div>

            </div>


            <!-- ADICIONAR PRODUTO -->
            <div
                class="panel"
                style="margin-top: 18px;"
            >

                <div class="panel-header">
                    <div>
                        <h3>Adicionar produto</h3>

                        <p>
                            O preço será preenchido automaticamente.
                        </p>
                    </div>
                </div>


                <div class="form-grid">

                    <!-- PRODUTO -->
                    <div class="input-group">

                        <label for="order-product">
                            Produto
                        </label>

                        <select id="order-product">

                            <option value="">
                                Selecione um produto
                            </option>

                        </select>

                    </div>


                    <!-- QUANTIDADE -->
                    <div class="input-group">

                        <label for="order-quantity">
                            Quantidade
                        </label>

                        <input
                            id="order-quantity"
                            type="number"
                            min="1"
                            value="1"
                        >

                    </div>


                    <!-- PREÇO -->
                    <div class="input-group">

                        <label for="order-unit-price">
                            Preço unitário
                        </label>

                        <input
                            id="order-unit-price"
                            type="text"
                            value="R$ 0,00"
                            readonly
                        >

                    </div>


                    <!-- SUBTOTAL -->
                    <div class="input-group">

                        <label for="order-item-subtotal">
                            Subtotal
                        </label>

                        <input
                            id="order-item-subtotal"
                            type="text"
                            value="R$ 0,00"
                            readonly
                        >

                    </div>

                </div>


                <button
                    type="button"
                    class="primary-button"
                    id="add-order-item"
                    style="margin-top: 12px;"
                >
                    + Adicionar item
                </button>

            </div>


            <!-- ITENS DO PEDIDO -->
            <div
                class="table-wrapper"
                style="margin-top: 18px;"
            >

                <table>

                    <thead>
                        <tr>
    <th>Pedido</th>
    <th>Cliente</th>
    <th>Itens</th>
    <th>Total</th>
    <th>Status</th>
    <th>Data</th>
    <th>Ações</th>
</tr>
                    </thead>


                    <tbody id="order-items-table">

                        <tr>
                            <td
                                colspan="5"
                                class="empty-state"
                            >
                                Nenhum produto adicionado.
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->
            <div
                style="
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                    align-items: center;
                    margin-top: 18px;
                    font-size: 18px;
                "
            >

                <strong>Total:</strong>

                <strong id="order-total-display">
                    R$ 0,00
                </strong>

            </div>


            <!-- ERROS -->
            <p
                id="order-error"
                class="form-error"
            ></p>


            <!-- BOTÕES -->
            <div class="modal-actions">

                <button
                    type="button"
                    class="ghost-button"
                    id="cancel-order"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="primary-button"
                >
                    Salvar pedido
                </button>

            </div>

        </form>

    </div>
</div>
<script src="{{ asset('assets/i18n.js') }}"></script><script src="{{ asset('assets/app.js') }}"></script>
</body></html>
