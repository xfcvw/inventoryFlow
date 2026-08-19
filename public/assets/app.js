const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let workspaceMeta = null;
let workspacesCache = [];
let productsCache = [];
let categoriesCache = [];
let suppliersCache = [];
let warehousesCache = [];
let customersCache = [];
let ordersCache = [];
let movementsCache = [];
let editingProductId = null;
let genericSubmitHandler = null;

const roleRank = { viewer: 0, member: 1, manager: 2, admin: 3, owner: 4 };

async function api(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            ...(options.headers || {}),
        },
    });

    if (response.status === 401) {
        window.location.href = '/login';
        throw new Error('Unauthenticated');
    }

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        const validationMessage = data?.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validationMessage || data?.message || t('requestFailed'));
    }

    return data;
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function money(value) {
    const locale = workspaceMeta?.locale || (getCurrentLanguage() === 'pt' ? 'pt-BR' : 'en-US');
    const currency = workspaceMeta?.currency || (getCurrentLanguage() === 'pt' ? 'BRL' : 'USD');
    return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(Number(value || 0));
}

function when(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat(
        workspaceMeta?.locale || (getCurrentLanguage() === 'pt' ? 'pt-BR' : 'en-US'),
        { dateStyle: 'short', timeStyle: 'short' },
    ).format(new Date(value));
}

function toast(message) {
    const element = document.getElementById('toast');
    element.textContent = message;
    element.classList.remove('hidden');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => element.classList.add('hidden'), 3000);
}

function currentRank() {
    return roleRank[workspaceMeta?.role] ?? 0;
}

function can(minimumRole) {
    return currentRank() >= roleRank[minimumRole];
}

function applyRoleUI() {
    document.querySelectorAll('.owner-only').forEach(el => el.classList.toggle('hidden', workspaceMeta?.role !== 'owner'));
    document.querySelectorAll('.admin-only').forEach(el => el.classList.toggle('hidden', !can('admin')));
    document.querySelectorAll('.manager-only').forEach(el => {
        const forbidden = !can('manager');
        if ('disabled' in el) el.disabled = forbidden;
        el.classList.toggle('permission-disabled', forbidden);
    });
    document.querySelectorAll('.member-only').forEach(el => {
        const forbidden = !can('member');
        if ('disabled' in el) el.disabled = forbidden;
        el.querySelectorAll?.('input,select,button,textarea').forEach(child => child.disabled = forbidden);
        el.classList.toggle('permission-disabled', forbidden);
    });
}

function showSection(name) {
    document.querySelectorAll('.app-section').forEach(section => section.classList.toggle('hidden', section.id !== `section-${name}`));
    document.querySelectorAll('.nav-button[data-section]').forEach(button => button.classList.toggle('active', button.dataset.section === name));
    document.getElementById('sidebar').classList.remove('open');

    const loaders = {
        dashboard: loadDashboard,
        products: loadProducts,
        catalog: loadCatalog,
        inventory: loadInventory,
        orders: loadOrders,
        customers: loadCustomers,
        reports: prepareReports,
        team: loadTeam,
        billing: loadBilling,
        audit: loadAudit,
        settings: loadWorkspaceSettings,
    };
    loaders[name]?.();
}

function renderWorkspaceHeader() {
    const selector = document.getElementById('workspace-select');
    selector.innerHTML = workspacesCache.map(workspace => `<option value="${workspace.id}">${esc(workspace.name)}</option>`).join('');
    if (workspaceMeta?.id) selector.value = String(workspaceMeta.id);
    document.getElementById('workspace-role').textContent = workspaceMeta?.role || '—';
    document.getElementById('workspace-plan').textContent = workspaceMeta?.plan || '—';
    applyRoleUI();
}

async function loadWorkspaceContext() {
    const list = await api('/api/workspaces');
    workspacesCache = list.workspaces || [];
    workspaceMeta = await api('/api/workspace');
    renderWorkspaceHeader();
}

async function switchWorkspace(id) {
    await api(`/api/workspaces/${id}/switch`, { method: 'POST' });
    productsCache = []; categoriesCache = []; suppliersCache = []; warehousesCache = []; customersCache = []; ordersCache = []; movementsCache = [];
    await loadWorkspaceContext();
    await loadDashboard();
    toast(t('workspaceChanged'));
}

async function loadDashboard() {
    try {
        const data = await api('/api/dashboard');
        workspaceMeta = { ...workspaceMeta, ...data.workspace };
        renderWorkspaceHeader();
        document.getElementById('stat-products').textContent = data.total_products;
        document.getElementById('stat-stock').textContent = data.total_stock;
        document.getElementById('stat-low-stock').textContent = data.low_stock;
        document.getElementById('stat-orders').textContent = data.total_orders;
        document.getElementById('stat-month-orders').textContent = data.month_orders;
        document.getElementById('stat-revenue').textContent = money(data.month_revenue);

        document.getElementById('recent-movements').innerHTML = data.recent_movements.length
            ? data.recent_movements.map(m => `<tr><td>${esc(m.product?.name)}</td><td><span class="badge badge-${m.type}">${m.type === 'in' ? t('stockIn') : t('stockOut')}</span></td><td>${m.type === 'in' ? '+' : '-'}${m.quantity}</td><td>${esc(m.warehouse?.name || '—')}</td><td>${esc(m.actor?.name || '—')}</td><td>${when(m.created_at)}</td></tr>`).join('')
            : `<tr><td colspan="6" class="empty-state">${t('noMovements')}</td></tr>`;

        document.getElementById('low-stock-list').innerHTML = data.low_stock_products.length
            ? data.low_stock_products.map(row => `<div class="stack-item"><div><strong>${esc(row.product?.name)}</strong><br><small>${esc(row.product?.sku)} · ${esc(row.warehouse?.name)}</small></div><strong class="stock-low">${row.quantity}</strong></div>`).join('')
            : `<div class="empty-state">${t('noLowStock')}</div>`;
    } catch (error) { toast(error.message); }
}

async function ensureCatalogCaches() {
    const [categories, suppliers, warehouses] = await Promise.all([api('/api/categories'), api('/api/suppliers'), api('/api/warehouses')]);
    categoriesCache = categories; suppliersCache = suppliers; warehousesCache = warehouses;
}

async function loadProducts() {
    try {
        await ensureCatalogCaches();
        productsCache = await api('/api/products');
        renderProductCategoryFilter();
        renderProducts();
    } catch (error) { toast(error.message); }
}

function renderProductCategoryFilter() {
    const filter = document.getElementById('product-category-filter');
    const current = filter.value;
    filter.innerHTML = `<option value="">${t('allCategories')}</option>` + categoriesCache.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    filter.value = current;
}

function renderProducts() {
    const search = document.getElementById('product-search').value.toLowerCase().trim();
    const categoryId = document.getElementById('product-category-filter').value;
    const rows = productsCache.filter(p => {
        const matchesSearch = [p.name, p.sku, p.barcode].some(v => String(v || '').toLowerCase().includes(search));
        const matchesCategory = !categoryId || Number(p.category_id) === Number(categoryId);
        return matchesSearch && matchesCategory;
    });
    document.getElementById('products-table').innerHTML = rows.length ? rows.map(p => `
        <tr><td><strong>${esc(p.name)}</strong>${p.active ? '' : '<br><small class="muted-text">inactive</small>'}</td><td>${esc(p.sku)}</td><td>${esc(p.category_relation?.name || p.category || '—')}</td><td>${esc(p.supplier?.name || '—')}</td><td>${money(p.price)}</td><td class="${Number(p.stock) <= Number(p.min_stock) ? 'stock-low' : ''}">${p.stock}</td><td><div class="table-actions">${can('manager') ? `<button class="small-button" data-product-edit="${p.id}">${t('edit')}</button>` : ''}${can('admin') ? `<button class="small-button danger" data-product-delete="${p.id}">${t('delete')}</button>` : ''}</div></td></tr>
    `).join('') : `<tr><td colspan="7" class="empty-state">${t('noProducts')}</td></tr>`;
}

function fillProductSelects(product = null) {
    document.getElementById('product-category').innerHTML = `<option value="">Uncategorized</option>` + categoriesCache.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    document.getElementById('product-supplier').innerHTML = `<option value="">No supplier</option>` + suppliersCache.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
    if (product) {
        document.getElementById('product-category').value = product.category_id || '';
        document.getElementById('product-supplier').value = product.supplier_id || '';
    }
}

function openProductModal(product = null) {
    editingProductId = product?.id || null;
    document.getElementById('product-form').reset();
    fillProductSelects(product);
    document.getElementById('product-modal-title').textContent = product ? t('editProductTitle') : t('newProductTitle');
    document.getElementById('initial-stock-group').classList.toggle('hidden', Boolean(product));
    if (product) {
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-sku').value = product.sku;
        document.getElementById('product-barcode').value = product.barcode || '';
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-cost').value = product.cost_price || 0;
        document.getElementById('product-min-stock').value = product.min_stock;
        document.getElementById('product-active').value = String(Boolean(product.active));
        document.getElementById('product-description').value = product.description || '';
    }
    document.getElementById('product-error').textContent = '';
    document.getElementById('product-modal').classList.remove('hidden');
}

function closeProductModal() { document.getElementById('product-modal').classList.add('hidden'); editingProductId = null; }

async function saveProduct(event) {
    event.preventDefault();
    const payload = {
        name: document.getElementById('product-name').value.trim(), sku: document.getElementById('product-sku').value.trim(),
        barcode: document.getElementById('product-barcode').value.trim() || null,
        category_id: document.getElementById('product-category').value || null, supplier_id: document.getElementById('product-supplier').value || null,
        price: Number(document.getElementById('product-price').value), cost_price: Number(document.getElementById('product-cost').value || 0),
        min_stock: Number(document.getElementById('product-min-stock').value || 0), active: document.getElementById('product-active').value === 'true',
        description: document.getElementById('product-description').value.trim() || null,
    };
    if (!editingProductId) payload.initial_stock = Number(document.getElementById('product-initial-stock').value || 0);
    try {
        await api(editingProductId ? `/api/products/${editingProductId}` : '/api/products', { method: editingProductId ? 'PUT' : 'POST', body: JSON.stringify(payload) });
        closeProductModal(); await loadProducts(); toast(t('productSaved'));
    } catch (error) { document.getElementById('product-error').textContent = error.message; }
}

async function deleteProduct(id) {
    if (!confirm(t('confirmDeleteProduct'))) return;
    try { await api(`/api/products/${id}`, { method: 'DELETE' }); await loadProducts(); toast(t('productDeleted')); } catch (error) { toast(error.message); }
}

function openGenericModal({ title, fields, values = {}, onSubmit }) {
    genericSubmitHandler = onSubmit;
    document.getElementById('generic-modal-title').textContent = title;
    document.getElementById('generic-error').textContent = '';
    document.getElementById('generic-fields').innerHTML = fields.map(field => {
        const value = values[field.name] ?? '';
        if (field.type === 'select') return `<div class="input-group"><label>${esc(field.label)}</label><select name="${field.name}" ${field.required ? 'required' : ''}>${field.options.map(o => `<option value="${esc(o.value)}" ${String(o.value) === String(value) ? 'selected' : ''}>${esc(o.label)}</option>`).join('')}</select></div>`;
        if (field.type === 'checkbox') return `<label class="remember"><input type="checkbox" name="${field.name}" value="1" ${value ? 'checked' : ''}> ${esc(field.label)}</label>`;
        if (field.type === 'textarea') return `<div class="input-group"><label>${esc(field.label)}</label><textarea name="${field.name}" rows="3">${esc(value)}</textarea></div>`;
        return `<div class="input-group"><label>${esc(field.label)}</label><input name="${field.name}" type="${field.type || 'text'}" value="${esc(value)}" ${field.required ? 'required' : ''}></div>`;
    }).join('');
    document.getElementById('generic-modal').classList.remove('hidden');
}

function closeGenericModal() { document.getElementById('generic-modal').classList.add('hidden'); genericSubmitHandler = null; }

async function genericSubmit(event) {
    event.preventDefault();
    if (!genericSubmitHandler) return;
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());
    event.currentTarget.querySelectorAll('input[type="checkbox"]').forEach(input => payload[input.name] = input.checked);
    try { await genericSubmitHandler(payload); closeGenericModal(); } catch (error) { document.getElementById('generic-error').textContent = error.message; }
}

async function loadCatalog() {
    try { await ensureCatalogCaches(); renderCatalogLists(); } catch (error) { toast(error.message); }
}

function renderCatalogLists() {
    document.getElementById('categories-list').innerHTML = categoriesCache.length ? categoriesCache.map(c => `<div class="stack-item"><div><strong>${esc(c.name)}</strong><br><small>${c.products_count} products</small></div><div class="table-actions">${can('manager') ? `<button class="small-button" data-category-edit="${c.id}">Edit</button>` : ''}${can('admin') ? `<button class="small-button danger" data-category-delete="${c.id}">×</button>` : ''}</div></div>`).join('') : '<div class="empty-state">No categories</div>';
    document.getElementById('suppliers-list').innerHTML = suppliersCache.length ? suppliersCache.map(s => `<div class="stack-item"><div><strong>${esc(s.name)}</strong><br><small>${esc(s.email || s.contact_name || 'No contact')} · ${s.products_count} products</small></div><div class="table-actions">${can('manager') ? `<button class="small-button" data-supplier-edit="${s.id}">Edit</button>` : ''}${can('admin') ? `<button class="small-button danger" data-supplier-delete="${s.id}">×</button>` : ''}</div></div>`).join('') : '<div class="empty-state">No suppliers</div>';
    document.getElementById('warehouses-list').innerHTML = warehousesCache.length ? warehousesCache.map(w => `<div class="stack-item"><div><strong>${esc(w.name)}</strong> ${w.is_default ? '<span class="badge badge-in">default</span>' : ''}<br><small>${esc(w.code)} · ${w.stocks_count} stock rows</small></div><div class="table-actions">${can('admin') ? `<button class="small-button" data-warehouse-edit="${w.id}">Edit</button>${w.is_default ? '' : `<button class="small-button danger" data-warehouse-delete="${w.id}">×</button>`}` : ''}</div></div>`).join('') : '<div class="empty-state">No warehouses</div>';
}

function categoryModal(category = null) {
    openGenericModal({ title: category ? 'Edit category' : 'New category', fields: [{ name: 'name', label: 'Name', required: true }], values: category || {}, onSubmit: async payload => { await api(category ? `/api/categories/${category.id}` : '/api/categories', { method: category ? 'PUT' : 'POST', body: JSON.stringify(payload) }); await loadCatalog(); } });
}
function supplierModal(supplier = null) {
    const fields = [{name:'name',label:'Name',required:true},{name:'email',label:'Email',type:'email'},{name:'phone',label:'Phone'},{name:'contact_name',label:'Contact name'},{name:'notes',label:'Notes',type:'textarea'}];
    openGenericModal({ title: supplier ? 'Edit supplier' : 'New supplier', fields, values: supplier || {}, onSubmit: async payload => { await api(supplier ? `/api/suppliers/${supplier.id}` : '/api/suppliers', { method: supplier ? 'PUT' : 'POST', body: JSON.stringify(payload) }); await loadCatalog(); } });
}
function warehouseModal(warehouse = null) {
    const fields = [{name:'name',label:'Name',required:true},{name:'code',label:'Code',required:true},{name:'is_default',label:'Default warehouse',type:'checkbox'}];
    openGenericModal({ title: warehouse ? 'Edit warehouse' : 'New warehouse', fields, values: warehouse || {}, onSubmit: async payload => { await api(warehouse ? `/api/warehouses/${warehouse.id}` : '/api/warehouses', { method: warehouse ? 'PUT' : 'POST', body: JSON.stringify(payload) }); await loadCatalog(); } });
}

async function loadInventory() {
    try {
        if (!productsCache.length) productsCache = await api('/api/products');
        if (!warehousesCache.length) warehousesCache = await api('/api/warehouses');
        movementsCache = await api('/api/inventory/movements');
        document.getElementById('movement-product').innerHTML = `<option value="">${t('selectProduct')}</option>` + productsCache.filter(p => p.active).map(p => `<option value="${p.id}">${esc(p.name)} (${esc(p.sku)})</option>`).join('');
        document.getElementById('movement-warehouse').innerHTML = warehousesCache.map(w => `<option value="${w.id}">${esc(w.name)}</option>`).join('');
        document.getElementById('movements-table').innerHTML = movementsCache.length ? movementsCache.map(m => `<tr><td>${esc(m.product?.name)}</td><td>${esc(m.warehouse?.name || '—')}</td><td><span class="badge badge-${m.type}">${m.type === 'in' ? t('stockIn') : t('stockOut')}</span></td><td>${m.type === 'in' ? '+' : '-'}${m.quantity}</td><td>${m.balance_after ?? '—'}</td><td>${esc(m.actor?.name || '—')}</td><td>${when(m.created_at)}</td></tr>`).join('') : `<tr><td colspan="7" class="empty-state">${t('noMovements')}</td></tr>`;
    } catch (error) { toast(error.message); }
}

async function saveMovement(event) {
    event.preventDefault();
    const payload = { product_id: Number(document.getElementById('movement-product').value), warehouse_id: Number(document.getElementById('movement-warehouse').value), type: document.getElementById('movement-type').value, quantity: Number(document.getElementById('movement-quantity').value), reason: document.getElementById('movement-reason').value.trim() || null };
    try { await api('/api/inventory/movements', { method:'POST', body:JSON.stringify(payload) }); event.currentTarget.reset(); productsCache=[]; await loadInventory(); toast(t('movementSaved')); loadNotifications(); } catch(error) { document.getElementById('movement-error').textContent=error.message; }
}

async function loadCustomers() {
    try { customersCache = await api('/api/customers'); renderCustomers(); } catch(error){ toast(error.message); }
}
function renderCustomers() {
    const search=document.getElementById('customer-search').value.toLowerCase();
    const rows=customersCache.filter(c=>[c.name,c.email,c.phone].some(v=>String(v||'').toLowerCase().includes(search)));
    document.getElementById('customers-table').innerHTML=rows.length?rows.map(c=>`<tr><td><strong>${esc(c.name)}</strong></td><td>${esc(c.email||'—')}</td><td>${esc(c.phone||'—')}</td><td>${c.orders_count}</td><td><div class="table-actions">${can('member')?`<button class="small-button" data-customer-edit="${c.id}">Edit</button>`:''}${can('manager')?`<button class="small-button danger" data-customer-delete="${c.id}">Delete</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="5" class="empty-state">No customers</td></tr>';
}
function customerModal(customer=null){const fields=[{name:'name',label:'Name',required:true},{name:'email',label:'Email',type:'email'},{name:'phone',label:'Phone'},{name:'document',label:'Document'},{name:'notes',label:'Notes',type:'textarea'}];openGenericModal({title:customer?'Edit customer':'New customer',fields,values:customer||{},onSubmit:async payload=>{await api(customer?`/api/customers/${customer.id}`:'/api/customers',{method:customer?'PUT':'POST',body:JSON.stringify(payload)});await loadCustomers();}})}

async function ensureOrderCaches() {
    const [products, customers, warehouses] = await Promise.all([api('/api/products'), api('/api/customers'), api('/api/warehouses')]);
    productsCache=products; customersCache=customers; warehousesCache=warehouses;
}
async function loadOrders(){try{ordersCache=await api('/api/orders');renderOrders();}catch(error){toast(error.message)}}
function renderOrders(){
    const search=document.getElementById('order-search').value.toLowerCase(); const status=document.getElementById('order-status-filter').value;
    const rows=ordersCache.filter(o=>(!search||String(o.customer||'').toLowerCase().includes(search)||String(o.id).includes(search))&&(!status||o.status===status));
    ['pending','processing','completed','cancelled'].forEach(s=>document.getElementById(`orders-${s}`).textContent=ordersCache.filter(o=>o.status===s).length);
    document.getElementById('orders-table').innerHTML=rows.length?rows.map(o=>`<tr><td>#${o.id}</td><td>${esc(o.customer)}</td><td>${o.items?.reduce((a,i)=>a+Number(i.quantity),0)||0}</td><td>${money(o.total)}</td><td><span class="badge badge-${o.status}">${t(o.status)}</span></td><td>${when(o.created_at)}</td><td><div class="table-actions">${can('member')&&o.status==='pending'?`<button class="small-button" data-order-status="processing" data-order-id="${o.id}">Process</button>`:''}${can('member')&&o.status==='processing'?`<button class="small-button" data-order-status="completed" data-order-id="${o.id}">Complete</button>`:''}${can('member')&&!['completed','cancelled'].includes(o.status)?`<button class="small-button danger" data-order-status="cancelled" data-order-id="${o.id}">Cancel</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="7" class="empty-state">No orders</td></tr>';
}

async function openOrderModal(){await ensureOrderCaches();document.getElementById('order-form').reset();document.getElementById('order-customer').innerHTML='<option value="">Walk-in customer</option>'+customersCache.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');document.getElementById('order-warehouse').innerHTML=warehousesCache.map(w=>`<option value="${w.id}">${esc(w.name)}</option>`).join('');document.getElementById('order-items').innerHTML='';addOrderItem();document.getElementById('order-error').textContent='';document.getElementById('order-modal').classList.remove('hidden');updateOrderPreview();}
function closeOrderModal(){document.getElementById('order-modal').classList.add('hidden')}
function addOrderItem(){
    const row=document.createElement('div');row.className='order-item-row';row.innerHTML=`<select class="order-item-product" required><option value="">Select product</option>${productsCache.filter(p=>p.active).map(p=>`<option value="${p.id}" data-price="${p.price}">${esc(p.name)} · ${esc(p.sku)}</option>`).join('')}</select><input class="order-item-qty" type="number" min="1" value="1" required><input class="order-item-price" type="number" min="0" step="0.01" value="0" required><button type="button" class="small-button danger order-item-remove">×</button>`;document.getElementById('order-items').appendChild(row);row.querySelector('.order-item-product').addEventListener('change',e=>{row.querySelector('.order-item-price').value=e.target.selectedOptions[0]?.dataset.price||0;updateOrderPreview()});row.querySelectorAll('input').forEach(i=>i.addEventListener('input',updateOrderPreview));row.querySelector('.order-item-remove').addEventListener('click',()=>{row.remove();updateOrderPreview()});
}
function getOrderItems(){return [...document.querySelectorAll('.order-item-row')].map(row=>({product_id:Number(row.querySelector('.order-item-product').value),quantity:Number(row.querySelector('.order-item-qty').value),unit_price:Number(row.querySelector('.order-item-price').value)})).filter(i=>i.product_id&&i.quantity>0)}
function updateOrderPreview(){const subtotal=getOrderItems().reduce((s,i)=>s+i.quantity*i.unit_price,0);const discount=Number(document.getElementById('order-discount')?.value||0);const tax=Number(document.getElementById('order-tax')?.value||0);document.getElementById('order-total-preview').textContent=money(Math.max(0,subtotal-discount+tax))}
async function saveOrder(event){event.preventDefault();const payload={customer_id:document.getElementById('order-customer').value||null,warehouse_id:Number(document.getElementById('order-warehouse').value),status:document.getElementById('order-status').value,discount:Number(document.getElementById('order-discount').value||0),tax:Number(document.getElementById('order-tax').value||0),notes:document.getElementById('order-notes').value.trim()||null,items:getOrderItems()};try{await api('/api/orders',{method:'POST',body:JSON.stringify(payload)});closeOrderModal();productsCache=[];await loadOrders();toast(t('orderSaved'));loadNotifications();}catch(error){document.getElementById('order-error').textContent=error.message}}
async function changeOrderStatus(id,status){try{await api(`/api/orders/${id}`,{method:'PUT',body:JSON.stringify({status})});await loadOrders();productsCache=[];toast('Order updated.');loadNotifications();}catch(error){toast(error.message)}}

function prepareReports(){const now=new Date();const first=new Date(now.getFullYear(),now.getMonth(),1);document.getElementById('report-from').value=first.toISOString().slice(0,10);document.getElementById('report-to').value=now.toISOString().slice(0,10);document.getElementById('report-content').classList.add('hidden');document.getElementById('report-gate').textContent=(workspaceMeta?.limits?.reports===false)?'Reports require Pro or Business plan.':'Choose a period and run the report.';}
async function loadReport(){try{const from=document.getElementById('report-from').value;const to=document.getElementById('report-to').value;const data=await api(`/api/reports/overview?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);document.getElementById('report-gate').textContent='';document.getElementById('report-content').classList.remove('hidden');document.getElementById('report-orders').textContent=data.sales.orders;document.getElementById('report-revenue').textContent=money(data.sales.revenue);document.getElementById('report-average').textContent=money(data.sales.average_ticket);document.getElementById('report-inventory-value').textContent=money(data.inventory_value);document.getElementById('report-top-products').innerHTML=data.top_products.length?data.top_products.map(p=>`<div class="stack-item"><div><strong>${esc(p.product_name)}</strong><br><small>${esc(p.sku||'—')}</small></div><div><strong>${p.units} units</strong><br><small>${money(p.revenue)}</small></div></div>`).join(''):'<div class="empty-state">No sales in period.</div>';document.getElementById('report-low-stock').innerHTML=data.low_stock.length?data.low_stock.map(r=>`<div class="stack-item"><div><strong>${esc(r.product?.name)}</strong><br><small>${esc(r.warehouse?.name)}</small></div><strong class="stock-low">${r.quantity}</strong></div>`).join(''):'<div class="empty-state">No low stock.</div>';}catch(error){document.getElementById('report-gate').textContent=error.message;document.getElementById('report-content').classList.add('hidden')}}

async function loadTeam(){try{const [team,invitations]=await Promise.all([api('/api/team'),api('/api/invitations')]);document.getElementById('team-table').innerHTML=team.map(m=>`<tr><td>${esc(m.name)}</td><td>${esc(m.email)}</td><td>${m.role==='owner'?'<span class="badge badge-in">owner</span>':`<select class="team-role-select" data-user-id="${m.id}"><option ${m.role==='admin'?'selected':''}>admin</option><option ${m.role==='manager'?'selected':''}>manager</option><option ${m.role==='member'?'selected':''}>member</option><option ${m.role==='viewer'?'selected':''}>viewer</option></select>`}</td><td>${m.role==='owner'?'—':`<button class="small-button danger" data-team-remove="${m.id}">Remove</button>`}</td></tr>`).join('');document.getElementById('invitations-list').innerHTML=invitations.length?invitations.map(i=>`<div class="stack-item"><div><strong>${esc(i.email)}</strong><br><small>${esc(i.role)} · expires ${when(i.expires_at)}</small></div><button class="small-button danger" data-invite-delete="${i.id}">×</button></div>`).join(''):'<div class="empty-state">No pending invitations.</div>';}catch(error){toast(error.message)}}
function inviteModal(){openGenericModal({title:'Invite team member',fields:[{name:'email',label:'Email',type:'email',required:true},{name:'role',label:'Role',type:'select',options:['admin','manager','member','viewer'].map(v=>({value:v,label:v}))}],onSubmit:async payload=>{const result=await api('/api/invitations',{method:'POST',body:JSON.stringify(payload)});await loadTeam();toast(`Invitation sent. Local link: ${result.accept_url}`);}})}

async function loadBilling(){try{const data=await api('/api/billing');document.getElementById('billing-mode').innerHTML='<strong>Local billing simulator</strong><br>No real money is charged. Plan limits are enforced so you can learn the SaaS architecture before connecting a payment provider.';document.getElementById('plans-grid').innerHTML=data.plans.map(plan=>`<article class="panel plan-option ${plan.key===data.current_plan?'current-plan':''}"><span class="eyebrow">${esc(plan.label)}</span><h2>${esc(plan.label)}</h2><div class="usage-row"><span>Products</span><strong>${plan.products??'Unlimited'}</strong></div><div class="usage-row"><span>Members</span><strong>${plan.members??'Unlimited'}</strong></div><div class="usage-row"><span>Warehouses</span><strong>${plan.warehouses??'Unlimited'}</strong></div><div class="usage-row"><span>Reports</span><strong>${plan.reports?'Yes':'No'}</strong></div><button class="${plan.key===data.current_plan?'ghost-button':'primary-button'} full-button" data-change-plan="${plan.key}" ${plan.key===data.current_plan?'disabled':''}>${plan.key===data.current_plan?'Current plan':'Switch plan'}</button></article>`).join('');}catch(error){toast(error.message)}}
async function changePlan(plan){if(!confirm(`Switch workspace to ${plan}? This is the local simulator and will not charge money.`))return;try{await api('/api/billing/change-plan',{method:'POST',body:JSON.stringify({plan})});await loadWorkspaceContext();await loadBilling();toast('Plan changed.');}catch(error){toast(error.message)}}

async function loadAudit(){try{const logs=await api('/api/audit-logs');document.getElementById('audit-table').innerHTML=logs.length?logs.map(log=>`<tr><td>${when(log.created_at)}</td><td>${esc(log.actor?.name||'System')}</td><td><code>${esc(log.action)}</code></td><td>${esc((log.subject_type||'').split('\\').pop())}${log.subject_id?' #'+log.subject_id:''}</td><td><small>${esc(JSON.stringify(log.metadata||{})).slice(0,180)}</small></td></tr>`).join(''):'<tr><td colspan="5" class="empty-state">No audit entries.</td></tr>';}catch(error){toast(error.message)}}

async function loadWorkspaceSettings(){try{workspaceMeta=await api('/api/workspace');renderWorkspaceHeader();document.getElementById('workspace-name').value=workspaceMeta.name;document.getElementById('workspace-slug').value=workspaceMeta.slug;document.getElementById('workspace-currency').value=workspaceMeta.currency;document.getElementById('workspace-locale').value=workspaceMeta.locale;document.getElementById('workspace-timezone').value=workspaceMeta.timezone;document.getElementById('workspace-business-type').value=workspaceMeta.business_type||'';document.getElementById('settings-plan').textContent=workspaceMeta.plan;const limitText=(usage,limit)=>`${usage} / ${limit??'∞'}`;document.getElementById('settings-products-usage').textContent=limitText(workspaceMeta.usage.products,workspaceMeta.limits.products);document.getElementById('settings-members-usage').textContent=limitText(workspaceMeta.usage.members,workspaceMeta.limits.members);document.getElementById('settings-warehouses-usage').textContent=limitText(workspaceMeta.usage.warehouses,workspaceMeta.limits.warehouses);}catch(error){toast(error.message)}}
async function saveWorkspace(event){event.preventDefault();const payload={name:document.getElementById('workspace-name').value.trim(),currency:document.getElementById('workspace-currency').value,locale:document.getElementById('workspace-locale').value,timezone:document.getElementById('workspace-timezone').value.trim(),business_type:document.getElementById('workspace-business-type').value.trim()||null,onboarding_completed:true};try{await api('/api/workspace',{method:'PUT',body:JSON.stringify(payload)});await loadWorkspaceContext();await loadWorkspaceSettings();toast(t('workspaceSaved'));}catch(error){document.getElementById('workspace-error').textContent=error.message}}

async function loadNotifications(){try{const notifications=await api('/api/notifications');const unread=notifications.filter(n=>!n.read_at).length;document.getElementById('notification-count').textContent=unread;document.getElementById('notifications-list').innerHTML=notifications.length?notifications.map(n=>`<button class="notification-item ${n.read_at?'':'unread'}" data-notification-id="${n.id}"><strong>${esc(n.data?.title||'Notification')}</strong><span>${esc(n.data?.message||'')}</span><small>${when(n.created_at)}</small></button>`).join(''):'<div class="empty-state">No notifications.</div>';}catch{}}
function toggleNotifications(open=true){document.getElementById('notification-drawer').classList.toggle('hidden',!open);if(open)loadNotifications()}

async function deleteEntity(endpoint, reload){if(!confirm('Delete this record?'))return;try{await api(endpoint,{method:'DELETE'});await reload();}catch(error){toast(error.message)}}

function bindEvents(){
    document.querySelectorAll('.nav-button[data-section]').forEach(button=>button.addEventListener('click',()=>showSection(button.dataset.section)));
    document.querySelector('[data-section-link="dashboard"]')?.addEventListener('click',e=>{e.preventDefault();showSection('dashboard')});
    document.getElementById('mobile-menu').addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));
    document.getElementById('workspace-select').addEventListener('change',e=>switchWorkspace(e.target.value));
    document.getElementById('product-search').addEventListener('input',renderProducts);document.getElementById('product-category-filter').addEventListener('change',renderProducts);
    document.getElementById('open-product-modal').addEventListener('click',()=>openProductModal());document.getElementById('close-product-modal').addEventListener('click',closeProductModal);document.getElementById('cancel-product').addEventListener('click',closeProductModal);document.getElementById('product-form').addEventListener('submit',saveProduct);
    document.getElementById('products-table').addEventListener('click',e=>{const edit=e.target.closest('[data-product-edit]');const del=e.target.closest('[data-product-delete]');if(edit)openProductModal(productsCache.find(p=>p.id===Number(edit.dataset.productEdit)));if(del)deleteProduct(del.dataset.productDelete)});
    document.getElementById('add-category').addEventListener('click',()=>categoryModal());document.getElementById('add-supplier').addEventListener('click',()=>supplierModal());document.getElementById('add-warehouse').addEventListener('click',()=>warehouseModal());
    document.getElementById('categories-list').addEventListener('click',e=>{const edit=e.target.closest('[data-category-edit]');const del=e.target.closest('[data-category-delete]');if(edit)categoryModal(categoriesCache.find(x=>x.id===Number(edit.dataset.categoryEdit)));if(del)deleteEntity(`/api/categories/${del.dataset.categoryDelete}`,loadCatalog)});
    document.getElementById('suppliers-list').addEventListener('click',e=>{const edit=e.target.closest('[data-supplier-edit]');const del=e.target.closest('[data-supplier-delete]');if(edit)supplierModal(suppliersCache.find(x=>x.id===Number(edit.dataset.supplierEdit)));if(del)deleteEntity(`/api/suppliers/${del.dataset.supplierDelete}`,loadCatalog)});
    document.getElementById('warehouses-list').addEventListener('click',e=>{const edit=e.target.closest('[data-warehouse-edit]');const del=e.target.closest('[data-warehouse-delete]');if(edit)warehouseModal(warehousesCache.find(x=>x.id===Number(edit.dataset.warehouseEdit)));if(del)deleteEntity(`/api/warehouses/${del.dataset.warehouseDelete}`,loadCatalog)});
    document.getElementById('movement-form').addEventListener('submit',saveMovement);
    document.getElementById('customer-search').addEventListener('input',renderCustomers);document.getElementById('add-customer').addEventListener('click',()=>customerModal());document.getElementById('customers-table').addEventListener('click',e=>{const edit=e.target.closest('[data-customer-edit]');const del=e.target.closest('[data-customer-delete]');if(edit)customerModal(customersCache.find(x=>x.id===Number(edit.dataset.customerEdit)));if(del)deleteEntity(`/api/customers/${del.dataset.customerDelete}`,loadCustomers)});
    document.getElementById('order-search').addEventListener('input',renderOrders);document.getElementById('order-status-filter').addEventListener('change',renderOrders);document.getElementById('open-order-modal').addEventListener('click',openOrderModal);document.getElementById('close-order-modal').addEventListener('click',closeOrderModal);document.getElementById('cancel-order').addEventListener('click',closeOrderModal);document.getElementById('add-order-item').addEventListener('click',addOrderItem);document.getElementById('order-discount').addEventListener('input',updateOrderPreview);document.getElementById('order-tax').addEventListener('input',updateOrderPreview);document.getElementById('order-form').addEventListener('submit',saveOrder);document.getElementById('orders-table').addEventListener('click',e=>{const button=e.target.closest('[data-order-status]');if(button)changeOrderStatus(button.dataset.orderId,button.dataset.orderStatus)});
    document.getElementById('load-report').addEventListener('click',loadReport);
    document.getElementById('invite-member').addEventListener('click',inviteModal);document.getElementById('team-table').addEventListener('change',async e=>{if(!e.target.matches('.team-role-select'))return;try{await api(`/api/team/${e.target.dataset.userId}`,{method:'PUT',body:JSON.stringify({role:e.target.value})});toast('Role updated.')}catch(error){toast(error.message);loadTeam()}});document.getElementById('team-table').addEventListener('click',e=>{const b=e.target.closest('[data-team-remove]');if(b)deleteEntity(`/api/team/${b.dataset.teamRemove}`,loadTeam)});document.getElementById('invitations-list').addEventListener('click',e=>{const b=e.target.closest('[data-invite-delete]');if(b)deleteEntity(`/api/invitations/${b.dataset.inviteDelete}`,loadTeam)});
    document.getElementById('plans-grid').addEventListener('click',e=>{const b=e.target.closest('[data-change-plan]');if(b)changePlan(b.dataset.changePlan)});
    document.getElementById('workspace-form').addEventListener('submit',saveWorkspace);
    document.getElementById('generic-form').addEventListener('submit',genericSubmit);document.getElementById('generic-modal-close').addEventListener('click',closeGenericModal);document.getElementById('generic-cancel').addEventListener('click',closeGenericModal);
    document.getElementById('notification-button').addEventListener('click',()=>toggleNotifications(true));document.getElementById('close-notifications').addEventListener('click',()=>toggleNotifications(false));document.getElementById('read-all-notifications').addEventListener('click',async()=>{await api('/api/notifications/read-all',{method:'POST'});loadNotifications()});document.getElementById('notifications-list').addEventListener('click',async e=>{const b=e.target.closest('[data-notification-id]');if(!b)return;await api(`/api/notifications/${b.dataset.notificationId}/read`,{method:'POST'});loadNotifications()});
}

document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    try { await loadWorkspaceContext(); await loadDashboard(); await loadNotifications(); } catch(error) { toast(error.message); }
});

document.addEventListener('languageChanged', () => {
    if (workspaceMeta) { renderProducts(); renderOrders(); loadDashboard(); }
});
