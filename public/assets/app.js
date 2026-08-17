const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let productsCache = [];
let ordersCache = [];
let movementsCache = [];
let editingProductId = null;
let toastTimer = null;
let orderItemsDraft = [];

const esc = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const byId = (id) => document.getElementById(id);

function on(id, eventName, handler) {
    const el = byId(id);

    if (el) {
        el.addEventListener(eventName, handler);
    }
}

function currentLanguage() {
    try {
        return typeof getCurrentLanguage === 'function'
            ? getCurrentLanguage()
            : 'pt';
    } catch {
        return 'pt';
    }
}

function tr(key, fallback = key) {
    try {
        return typeof t === 'function'
            ? t(key)
            : fallback;
    } catch {
        return fallback;
    }
}

function money(value) {
    const pt = currentLanguage() === 'pt';

    return new Intl.NumberFormat(
        pt ? 'pt-BR' : 'en-US',
        {
            style: 'currency',
            currency: pt ? 'BRL' : 'USD',
        }
    ).format(Number(value ?? 0));
}

function when(value) {
    if (!value) return '—';

    const pt = currentLanguage() === 'pt';

    return new Intl.DateTimeFormat(
        pt ? 'pt-BR' : 'en-US',
        {
            dateStyle: 'short',
            timeStyle: 'short',
        }
    ).format(new Date(value));
}

function toast(message) {
    const el = byId('toast');

    if (!el) {
        console.log(message);
        return;
    }

    el.textContent = message;
    el.classList.remove('hidden');

    clearTimeout(toastTimer);

    toastTimer = setTimeout(() => {
        el.classList.add('hidden');
    }, 2600);
}


/* =========================================================
   API
========================================================= */

async function api(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };

    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    if (response.status === 401) {
        if (location.pathname !== '/login') {
            location.assign('/login');
        }

        throw new Error('Unauthenticated');
    }

    if (response.status === 419) {
        throw new Error(
            'Sua sessão expirou. Atualize a página e entre novamente.'
        );
    }

    const isJson =
        (response.headers.get('content-type') || '')
            .includes('application/json');

    const payload = isJson
        ? await response.json()
        : null;

    if (!response.ok) {
        const validation = payload?.errors
            ? Object.values(payload.errors).flat()[0]
            : null;

        throw new Error(
            validation ||
            payload?.message ||
            tr(
                'requestFailed',
                'Não foi possível concluir a requisição.'
            )
        );
    }

    return payload;
}


/* =========================================================
   NAVEGAÇÃO
========================================================= */

function showSection(section) {
    document
        .querySelectorAll('.app-section')
        .forEach((el) => {
            el.classList.toggle(
                'hidden',
                el.id !== `section-${section}`
            );
        });

    document
        .querySelectorAll('.sidebar-nav .nav-button')
        .forEach((button) => {
            button.classList.toggle(
                'active',
                button.dataset.section === section
            );
        });

    byId('sidebar')?.classList.remove('open');

    if (section === 'dashboard') {
        loadDashboard();
    }

    if (section === 'products') {
        loadProducts();
    }

    if (section === 'inventory') {
        loadInventory();
    }

    if (section === 'orders') {
        loadOrders();
    }
}


/* =========================================================
   DASHBOARD
========================================================= */

async function loadDashboard() {
    try {
        const d = await api('/api/dashboard');

        if (byId('stat-products')) {
            byId('stat-products').textContent =
                d.total_products;
        }

        if (byId('stat-stock')) {
            byId('stat-stock').textContent =
                d.total_stock;
        }

        if (byId('stat-low-stock')) {
            byId('stat-low-stock').textContent =
                d.low_stock;
        }

        if (byId('stat-orders')) {
            byId('stat-orders').textContent =
                d.total_orders;
        }

        const recent =
            byId('recent-movements');

        if (recent) {
            recent.innerHTML =
                d.recent_movements?.length

                    ? d.recent_movements
                        .map((m) => `
                            <tr>

                                <td>
                                    ${esc(
                                        m.product?.name ?? '—'
                                    )}
                                </td>

                                <td>

                                    <span
                                        class="
                                            badge
                                            badge-${esc(m.type)}
                                        "
                                    >
                                        ${
                                            m.type === 'in'
                                                ? tr(
                                                    'stockIn',
                                                    'Entrada'
                                                )
                                                : tr(
                                                    'stockOut',
                                                    'Saída'
                                                )
                                        }
                                    </span>

                                </td>

                                <td>
                                    ${
                                        m.type === 'in'
                                            ? '+'
                                            : '-'
                                    }${m.quantity}
                                </td>

                                <td>
                                    ${when(m.created_at)}
                                </td>

                            </tr>
                        `)
                        .join('')

                    : `
                        <tr>
                            <td
                                colspan="4"
                                class="empty-state"
                            >
                                ${tr(
                                    'noMovements',
                                    'Nenhuma movimentação registrada.'
                                )}
                            </td>
                        </tr>
                    `;
        }

        const lowStock =
            byId('low-stock-list');

        if (lowStock) {
            lowStock.innerHTML =
                d.low_stock_products?.length

                    ? d.low_stock_products
                        .map((p) => `
                            <div class="stack-item">

                                <div>
                                    <strong>
                                        ${esc(p.name)}
                                    </strong>

                                    <br>

                                    <small>
                                        ${esc(p.sku)}
                                        ·
                                        ${esc(p.category)}
                                    </small>
                                </div>

                                <strong class="stock-low">
                                    ${p.stock}
                                </strong>

                            </div>
                        `)
                        .join('')

                    : `
                        <div class="empty-state">
                            ${tr(
                                'noLowStock',
                                'Nenhum produto com estoque baixo.'
                            )}
                        </div>
                    `;
        }

    } catch (error) {
        toast(error.message);
    }
}


/* =========================================================
   PRODUTOS
========================================================= */

async function loadProducts() {
    try {
        const data =
            await api('/api/products');

        productsCache =
            Array.isArray(data)
                ? data
                : (data?.data ?? []);

        renderCategoryFilter();

        renderProducts();

    } catch (error) {
        toast(error.message);
    }
}


function renderCategoryFilter() {
    const select =
        byId('category-filter');

    if (!select) return;

    const current =
        select.value;

    const categories =
        [
            ...new Set(
                productsCache
                    .map(
                        (p) => p.category
                    )
                    .filter(Boolean)
            )
        ].sort();

    select.innerHTML =
        `
            <option value="">
                ${tr(
                    'allCategories',
                    'Todas as categorias'
                )}
            </option>
        `
        +
        categories
            .map(
                (category) => `
                    <option
                        value="${esc(category)}"
                    >
                        ${esc(category)}
                    </option>
                `
            )
            .join('');

    if (
        categories.includes(current)
    ) {
        select.value = current;
    }
}


function renderProducts() {
    const table =
        byId('products-table');

    if (!table) return;

    const search =
        (
            byId('product-search')
                ?.value
            ?? ''
        )
        .trim()
        .toLowerCase();

    const category =
        byId('category-filter')
            ?.value
        ?? '';

    const rows =
        productsCache.filter(
            (p) => {

                const matchesSearch =
                    [
                        p.name,
                        p.sku,
                        p.category
                    ]
                    .some(
                        (v) =>
                            String(
                                v ?? ''
                            )
                            .toLowerCase()
                            .includes(search)
                    );

                return (
                    matchesSearch
                    &&
                    (
                        !category
                        ||
                        p.category === category
                    )
                );
            }
        );

    table.innerHTML =
        rows.length

            ? rows
                .map(
                    (p) => `
                        <tr>

                            <td>
                                <strong>
                                    ${esc(p.name)}
                                </strong>
                            </td>

                            <td>
                                ${esc(p.sku)}
                            </td>

                            <td>
                                ${esc(p.category)}
                            </td>

                            <td>
                                ${money(p.price)}
                            </td>

                            <td
                                class="
                                    ${
                                        Number(p.stock)
                                        <=
                                        Number(p.min_stock)
                                            ? 'stock-low'
                                            : ''
                                    }
                                "
                            >
                                ${p.stock}
                            </td>

                            <td>

                                <div class="table-actions">

                                    <button
                                        class="small-button"
                                        data-pa="edit"
                                        data-id="${p.id}"
                                    >
                                        ${tr(
                                            'edit',
                                            'Editar'
                                        )}
                                    </button>

                                    <button
                                        class="
                                            small-button
                                            danger
                                        "
                                        data-pa="delete"
                                        data-id="${p.id}"
                                    >
                                        ${tr(
                                            'delete',
                                            'Excluir'
                                        )}
                                    </button>

                                </div>

                            </td>

                        </tr>
                    `
                )
                .join('')

            : `
                <tr>

                    <td
                        colspan="6"
                        class="empty-state"
                    >
                        ${tr(
                            'noProducts',
                            'Nenhum produto cadastrado.'
                        )}
                    </td>

                </tr>
            `;
}


function openProduct(product = null) {
    editingProductId =
        product
            ? Number(product.id)
            : null;

    byId('product-modal')
        ?.classList
        .remove('hidden');

    if (
        byId('product-modal-title')
    ) {
        byId(
            'product-modal-title'
        ).textContent =
            product
                ? tr(
                    'editProductTitle',
                    'Editar Produto'
                )
                : tr(
                    'newProductTitle',
                    'Novo Produto'
                );
    }

    if (
        byId('product-error')
    ) {
        byId(
            'product-error'
        ).textContent = '';
    }

    const form =
        byId('product-form');

    if (!form) return;

    form.reset();

    if (!product) return;

    byId('product-name').value =
        product.name ?? '';

    byId('product-sku').value =
        product.sku ?? '';

    byId('product-category').value =
        product.category ?? '';

    byId('product-price').value =
        product.price ?? '';

    byId('product-stock').value =
        product.stock ?? 0;

    byId('product-min-stock').value =
        product.min_stock ?? 0;
}


function closeProduct() {
    byId('product-modal')
        ?.classList
        .add('hidden');

    editingProductId = null;
}


async function saveProduct(event) {
    event.preventDefault();

    const payload = {

        name:
            byId('product-name')
                ?.value
                .trim()
            ?? '',

        sku:
            byId('product-sku')
                ?.value
                .trim()
            ?? '',

        category:
            byId('product-category')
                ?.value
                .trim()
            ?? '',

        price:
            Number(
                byId('product-price')
                    ?.value
                ?? 0
            ),

        stock:
            Number(
                byId('product-stock')
                    ?.value
                ?? 0
            ),

        min_stock:
            Number(
                byId('product-min-stock')
                    ?.value
                ?? 0
            ),
    };

    try {

        await api(
            editingProductId

                ? `/api/products/${editingProductId}`

                : '/api/products',

            {
                method:
                    editingProductId
                        ? 'PUT'
                        : 'POST',

                body:
                    JSON.stringify(payload),
            }
        );

        closeProduct();

        await loadProducts();

        toast(
            tr(
                'productSaved',
                'Produto salvo com sucesso.'
            )
        );

    } catch (error) {

        if (
            byId('product-error')
        ) {
            byId(
                'product-error'
            ).textContent =
                error.message;
        }
    }
}


async function productClick(event) {
    const button =
        event.target.closest(
            '[data-pa]'
        );

    if (!button) return;

    const product =
        productsCache.find(
            (p) =>
                Number(p.id)
                ===
                Number(
                    button.dataset.id
                )
        );

    if (!product) return;

    if (
        button.dataset.pa === 'edit'
    ) {
        openProduct(product);

        return;
    }

    if (
        button.dataset.pa
        !==
        'delete'
    ) {
        return;
    }

    if (
        !confirm(
            tr(
                'confirmDeleteProduct',
                'Deseja realmente excluir este produto?'
            )
        )
    ) {
        return;
    }

    try {

        await api(
            `/api/products/${product.id}`,
            {
                method: 'DELETE',
            }
        );

        await loadProducts();

        toast(
            tr(
                'productDeleted',
                'Produto excluído.'
            )
        );

    } catch (error) {
        toast(error.message);
    }
}


/* =========================================================
   ESTOQUE
========================================================= */

async function loadInventory() {
    try {

        const [
            products,
            movements
        ] =
            await Promise.all([
                api('/api/products'),
                api(
                    '/api/inventory/movements'
                ),
            ]);

        productsCache =
            Array.isArray(products)
                ? products
                : (
                    products?.data
                    ?? []
                );

        movementsCache =
            Array.isArray(movements)
                ? movements
                : (
                    movements?.data
                    ?? []
                );

        renderMovementProducts();

        renderMovements();

    } catch (error) {
        toast(error.message);
    }
}


function renderMovementProducts() {
    const select =
        byId('movement-product');

    if (!select) return;

    const current =
        select.value;

    select.innerHTML =
        `
            <option value="">
                ${tr(
                    'selectProduct',
                    'Selecione um produto'
                )}
            </option>
        `
        +
        productsCache
            .map(
                (p) => `
                    <option
                        value="${p.id}"
                    >
                        ${esc(p.name)}
                        (${p.stock})
                    </option>
                `
            )
            .join('');

    if (
        productsCache
            .some(
                (p) =>
                    String(p.id)
                    ===
                    current
            )
    ) {
        select.value = current;
    }
}


function renderMovements() {
    const table =
        byId('movements-table');

    if (!table) return;

    table.innerHTML =
        movementsCache.length

            ? movementsCache
                .map(
                    (m) => `
                        <tr>

                            <td>
                                ${esc(
                                    m.product?.name
                                    ?? '—'
                                )}
                            </td>

                            <td>

                                <span
                                    class="
                                        badge
                                        badge-${esc(m.type)}
                                    "
                                >
                                    ${
                                        m.type === 'in'
                                            ? tr(
                                                'stockIn',
                                                'Entrada'
                                            )
                                            : tr(
                                                'stockOut',
                                                'Saída'
                                            )
                                    }
                                </span>

                            </td>

                            <td>
                                ${
                                    m.type === 'in'
                                        ? '+'
                                        : '-'
                                }${m.quantity}
                            </td>

                            <td>
                                ${when(m.created_at)}
                            </td>

                        </tr>
                    `
                )
                .join('')

            : `
                <tr>

                    <td
                        colspan="4"
                        class="empty-state"
                    >
                        ${tr(
                            'noMovements',
                            'Nenhuma movimentação registrada.'
                        )}
                    </td>

                </tr>
            `;
}


async function saveMovement(event) {
    event.preventDefault();

    const errorEl =
        byId('movement-error');

    if (errorEl) {
        errorEl.textContent = '';
    }

    const payload = {

        product_id:
            Number(
                byId('movement-product')
                    ?.value
                ?? 0
            ),

        type:
            byId('movement-type')
                ?.value
            ?? '',

        quantity:
            Number(
                byId('movement-quantity')
                    ?.value
                ?? 0
            ),
    };

    try {

        await api(
            '/api/inventory/movements',
            {
                method: 'POST',

                body:
                    JSON.stringify(payload),
            }
        );

        event.target.reset();

        await loadInventory();

        toast(
            tr(
                'movementSaved',
                'Movimentação salva.'
            )
        );

    } catch (error) {

        if (errorEl) {
            errorEl.textContent =
                error.message;
        }
    }
}


/* =========================================================
   PEDIDOS
========================================================= */

async function loadOrders() {
    try {

        const [
            orders,
            products
        ] =
            await Promise.all([
                api('/api/orders'),
                api('/api/products'),
            ]);

        ordersCache =
            Array.isArray(orders)
                ? orders
                : (
                    orders?.data
                    ?? []
                );

        productsCache =
            Array.isArray(products)
                ? products
                : (
                    products?.data
                    ?? []
                );

        renderOrderStats();

        renderOrders();

    } catch (error) {
        toast(error.message);
    }
}


function renderOrderStats() {
    [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ]
    .forEach(
        (status) => {

            const el =
                byId(
                    `orders-${status}`
                );

            if (el) {
                el.textContent =
                    ordersCache
                        .filter(
                            (o) =>
                                o.status
                                ===
                                status
                        )
                        .length;
            }
        }
    );
}


function renderOrders() {
    const table =
        byId('orders-table');

    if (!table) return;

    const search =
        (
            byId('order-search')
                ?.value
            ?? ''
        )
        .trim()
        .toLowerCase();

    const status =
        byId('order-status-filter')
            ?.value
        ?? '';

    const rows =
        ordersCache.filter(
            (order) => {

                const customer =
                    String(
                        order.customer_name
                        ??
                        order.customer
                        ??
                        ''
                    )
                    .toLowerCase();

                return (
                    customer.includes(search)
                    &&
                    (
                        !status
                        ||
                        order.status
                        ===
                        status
                    )
                );
            }
        );

    table.innerHTML =
        rows.length

            ? rows
                .map(
                    (order) => {

                        const totalUnits =
                            Array.isArray(
                                order.items
                            )

                                ? order.items
                                    .reduce(
                                        (
                                            sum,
                                            item
                                        ) =>
                                            sum
                                            +
                                            Number(
                                                item.quantity
                                                ??
                                                0
                                            ),
                                        0
                                    )

                                : 0;

                        return `
                            <tr>

                                <td>
                                    <strong>
                                        #${esc(order.id)}
                                    </strong>
                                </td>

                                <td>

                                    ${esc(
                                        order.customer_name
                                        ??
                                        order.customer
                                        ??
                                        '-'
                                    )}

                                    ${
                                        order.customer_email

                                            ? `
                                                <small
                                                    style="
                                                        display:block;
                                                    "
                                                >
                                                    ${esc(
                                                        order.customer_email
                                                    )}
                                                </small>
                                            `

                                            : ''
                                    }

                                </td>

                                <td>
                                    ${totalUnits}
                                </td>

                                <td>
                                    <strong>
                                        ${money(order.total)}
                                    </strong>
                                </td>

                                <td>

                                    <span
                                        class="
                                            badge
                                            badge-${esc(
                                                order.status
                                                ??
                                                'pending'
                                            )}
                                        "
                                    >
                                        ${tr(
                                            order.status
                                            ??
                                            'pending',

                                            order.status
                                            ??
                                            'pending'
                                        )}
                                    </span>

                                </td>

                                <td>
                                    ${when(order.created_at)}
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="small-button"
                                        data-oa="view"
                                        data-id="${order.id}"
                                    >
                                        Ver
                                    </button>

                                </td>

                            </tr>
                        `;
                    }
                )
                .join('')

            : `
                <tr>

                    <td
                        colspan="7"
                        class="empty-state"
                    >
                        ${tr(
                            'noOrders',
                            'Nenhum pedido encontrado.'
                        )}
                    </td>

                </tr>
            `;
}


async function openOrder() {
    orderItemsDraft = [];

    const form =
        byId('order-form');

    if (!form) {
        toast(
            'O formulário de pedido não foi encontrado.'
        );

        return;
    }

    form.reset();

    if (
        byId('order-error')
    ) {
        byId(
            'order-error'
        ).textContent = '';
    }

    if (
        !productsCache.length
    ) {
        try {

            const products =
                await api(
                    '/api/products'
                );

            productsCache =
                Array.isArray(products)

                    ? products

                    : (
                        products?.data
                        ?? []
                    );

        } catch (error) {

            toast(error.message);

            return;
        }
    }

    if (
        byId('order-quantity')
    ) {
        byId(
            'order-quantity'
        ).value = 1;
    }

    populateOrderProducts();

    updateOrderProductPrice();

    renderOrderItems();

    byId('order-modal')
        ?.classList
        .remove('hidden');
}


function closeOrder() {
    orderItemsDraft = [];

    byId('order-modal')
        ?.classList
        .add('hidden');
}


function populateOrderProducts() {
    const select =
        byId('order-product');

    if (!select) return;

    select.innerHTML =
        `
            <option value="">
                Selecione um produto
            </option>
        `;

    productsCache.forEach(
        (product) => {

            const option =
                document
                    .createElement(
                        'option'
                    );

            option.value =
                product.id;

            option.disabled =
                Number(
                    product.stock
                )
                <=
                0;

            option.textContent =
                `${product.name}`
                +
                ` - ${product.sku}`
                +
                ` - ${money(product.price)}`
                +
                ` - Estoque: ${product.stock}`;

            select.appendChild(
                option
            );
        }
    );
}


function getSelectedOrderProduct() {
    const id =
        Number(
            byId('order-product')
                ?.value
            ??
            0
        );

    if (!id) {
        return null;
    }

    return (
        productsCache.find(
            (p) =>
                Number(p.id)
                ===
                id
        )
        ??
        null
    );
}


function updateOrderProductPrice() {
    const unitPrice =
        byId('order-unit-price');

    const subtotal =
        byId(
            'order-item-subtotal'
        );

    if (
        !unitPrice
        ||
        !subtotal
    ) {
        return;
    }

    const product =
        getSelectedOrderProduct();

    const quantity =
        Math.max(
            1,

            Number(
                byId('order-quantity')
                    ?.value
                ??
                1
            )
            ||
            1
        );

    const price =
        product
            ? Number(product.price)
            : 0;

    unitPrice.value =
        money(price);

    subtotal.value =
        money(
            price * quantity
        );
}


function addOrderItem() {
    const product =
        getSelectedOrderProduct();

    const quantity =
        Number(
            byId('order-quantity')
                ?.value
            ??
            0
        );

    if (!product) {
        toast(
            'Selecione um produto.'
        );

        return;
    }

    if (
        !Number.isInteger(quantity)
        ||
        quantity < 1
    ) {
        toast(
            'Informe uma quantidade válida.'
        );

        return;
    }

    const stock =
        Number(
            product.stock
            ??
            0
        );

    const existing =
        orderItemsDraft.find(
            (item) =>
                Number(
                    item.product_id
                )
                ===
                Number(
                    product.id
                )
        );

    const newQuantity =
        (
            existing?.quantity
            ??
            0
        )
        +
        quantity;

    if (
        newQuantity > stock
    ) {
        toast(
            `Estoque insuficiente. Disponível: ${stock}`
        );

        return;
    }

    if (existing) {

        existing.quantity =
            newQuantity;

    } else {

        orderItemsDraft.push({

            product_id:
                Number(
                    product.id
                ),

            product_name:
                product.name,

            sku:
                product.sku,

            quantity,

            unit_price:
                Number(
                    product.price
                ),
        });
    }

    if (
        byId('order-product')
    ) {
        byId(
            'order-product'
        ).value = '';
    }

    if (
        byId('order-quantity')
    ) {
        byId(
            'order-quantity'
        ).value = 1;
    }

    updateOrderProductPrice();

    renderOrderItems();
}


function renderOrderItems() {
    const table =
        byId(
            'order-items-table'
        );

    if (!table) return;

    table.innerHTML =
        orderItemsDraft.length

            ? orderItemsDraft
                .map(
                    (
                        item,
                        index
                    ) => {

                        const subtotal =
                            Number(
                                item.unit_price
                            )
                            *
                            Number(
                                item.quantity
                            );

                        return `
                            <tr>

                                <td>

                                    <strong>
                                        ${esc(
                                            item.product_name
                                        )}
                                    </strong>

                                    ${
                                        item.sku

                                            ? `
                                                <small
                                                    style="
                                                        display:block;
                                                    "
                                                >
                                                    ${esc(
                                                        item.sku
                                                    )}
                                                </small>
                                            `

                                            : ''
                                    }

                                </td>

                                <td>
                                    ${item.quantity}
                                </td>

                                <td>
                                    ${money(
                                        item.unit_price
                                    )}
                                </td>

                                <td>

                                    <strong>
                                        ${money(
                                            subtotal
                                        )}
                                    </strong>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="
                                            small-button
                                            danger
                                        "
                                        data-remove-order-item="${index}"
                                    >
                                        Remover
                                    </button>

                                </td>

                            </tr>
                        `;
                    }
                )
                .join('')

            : `
                <tr>

                    <td
                        colspan="5"
                        class="empty-state"
                    >
                        Nenhum produto adicionado.
                    </td>

                </tr>
            `;

    const total =
        orderItemsDraft.reduce(
            (
                sum,
                item
            ) =>
                sum
                +
                Number(
                    item.unit_price
                )
                *
                Number(
                    item.quantity
                ),

            0
        );

    if (
        byId(
            'order-total-display'
        )
    ) {
        byId(
            'order-total-display'
        ).textContent =
            money(total);
    }
}


function removeOrderItem(index) {
    if (
        index < 0
        ||
        index >= orderItemsDraft.length
    ) {
        return;
    }

    orderItemsDraft.splice(
        index,
        1
    );

    renderOrderItems();
}


async function saveOrder(event) {
    event.preventDefault();

    const errorEl =
        byId('order-error');

    if (errorEl) {
        errorEl.textContent = '';
    }

    const customerName =
        byId(
            'order-customer-name'
        )
        ?.value
        .trim()
        ??
        '';

    const customerEmail =
        byId(
            'order-customer-email'
        )
        ?.value
        .trim()
        ??
        '';

    if (!customerName) {

        if (errorEl) {
            errorEl.textContent =
                'Informe o nome do cliente.';
        }

        return;
    }

    if (
        !orderItemsDraft.length
    ) {

        if (errorEl) {
            errorEl.textContent =
                'Adicione pelo menos um produto ao pedido.';
        }

        return;
    }

    const payload = {

        customer_name:
            customerName,

        customer_email:
            customerEmail
                ||
                null,

        items:
            orderItemsDraft.map(
                (item) => ({

                    product_id:
                        item.product_id,

                    quantity:
                        item.quantity,
                })
            ),
    };

    try {

        await api(
            '/api/orders',
            {
                method: 'POST',

                body:
                    JSON.stringify(
                        payload
                    ),
            }
        );

        closeOrder();

        await Promise.all([
            loadOrders(),
            loadDashboard(),
        ]);

        toast(
            'Pedido salvo com sucesso!'
        );

    } catch (error) {

        if (errorEl) {
            errorEl.textContent =
                error.message;
        } else {
            toast(error.message);
        }
    }
}


async function viewOrder(id) {
    try {

        const order =
            await api(
                `/api/orders/${id}`
            );

        const items =
            order.items
            ??
            [];

        const text =
            [

                `Pedido #${order.id}`,

                '',

                `Cliente: ${
                    order.customer_name
                    ??
                    order.customer
                    ??
                    '-'
                }`,

                order.customer_email
                    ? `E-mail: ${order.customer_email}`
                    : '',

                '',

                'Itens:',

                ...items.map(
                    (item) =>
                        `${item.quantity}x ${item.product_name}`
                        +
                        ` — ${money(item.unit_price)} cada`
                        +
                        ` — Subtotal: ${money(item.subtotal)}`
                ),

                '',

                `TOTAL: ${money(order.total)}`,

            ]
            .filter(Boolean)
            .join('\n');

        alert(text);

    } catch (error) {

        toast(
            error.message
        );
    }
}


async function orderClick(event) {
    const button =
        event.target.closest(
            '[data-oa]'
        );

    if (!button) return;

    const id =
        Number(
            button.dataset.id
        );

    if (!id) return;

    if (
        button.dataset.oa
        ===
        'view'
    ) {
        await viewOrder(id);
    }
}


/* =========================================================
   EVENTOS
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    () => {

        document
            .querySelectorAll(
                '.nav-button[data-section]'
            )
            .forEach(
                (button) => {

                    button
                        .addEventListener(
                            'click',
                            () =>
                                showSection(
                                    button.dataset.section
                                )
                        );
                }
            );


        on(
            'mobile-menu',
            'click',
            () =>
                byId('sidebar')
                    ?.classList
                    .toggle('open')
        );


        /* PRODUTOS */

        on(
            'open-product-modal',
            'click',
            () =>
                openProduct()
        );

        on(
            'close-product-modal',
            'click',
            closeProduct
        );

        on(
            'cancel-product',
            'click',
            closeProduct
        );

        on(
            'product-form',
            'submit',
            saveProduct
        );

        on(
            'products-table',
            'click',
            productClick
        );

        on(
            'product-search',
            'input',
            renderProducts
        );

        on(
            'category-filter',
            'change',
            renderProducts
        );


        /* ESTOQUE */

        on(
            'movement-form',
            'submit',
            saveMovement
        );


        /* PEDIDOS */

        on(
            'open-order-modal',
            'click',
            openOrder
        );

        on(
            'close-order-modal',
            'click',
            closeOrder
        );

        on(
            'cancel-order',
            'click',
            closeOrder
        );

        on(
            'order-form',
            'submit',
            saveOrder
        );

        on(
            'order-product',
            'change',
            updateOrderProductPrice
        );

        on(
            'order-quantity',
            'input',
            updateOrderProductPrice
        );

        on(
            'add-order-item',
            'click',
            addOrderItem
        );

        on(
            'order-items-table',
            'click',
            (event) => {

                const button =
                    event.target.closest(
                        '[data-remove-order-item]'
                    );

                if (!button) return;

                removeOrderItem(
                    Number(
                        button
                            .dataset
                            .removeOrderItem
                    )
                );
            }
        );

        on(
            'orders-table',
            'click',
            orderClick
        );

        on(
            'order-search',
            'input',
            renderOrders
        );

        on(
            'order-status-filter',
            'change',
            renderOrders
        );


        loadDashboard();
    }
);


document.addEventListener(
    'languageChanged',
    () => {

        if (
            productsCache.length
        ) {

            renderCategoryFilter();

            renderProducts();

            renderMovementProducts();

            populateOrderProducts();
        }

        if (
            movementsCache.length
        ) {
            renderMovements();
        }

        if (
            ordersCache.length
        ) {

            renderOrderStats();

            renderOrders();
        }

        renderOrderItems();

        updateOrderProductPrice();

        loadDashboard();
    }
);