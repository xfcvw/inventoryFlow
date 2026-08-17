const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content??'';
let productsCache=[],ordersCache=[],movementsCache=[],editingProductId=null,toastTimer=null;
const esc=v=>String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
function money(v){return new Intl.NumberFormat(getCurrentLanguage()==='pt'?'pt-BR':'en-US',{style:'currency',currency:getCurrentLanguage()==='pt'?'BRL':'USD'}).format(Number(v));}
function when(v){return new Intl.DateTimeFormat(getCurrentLanguage()==='pt'?'pt-BR':'en-US',{dateStyle:'short',timeStyle:'short'}).format(new Date(v));}
async function api(url,options={}){
  const headers={Accept:'application/json','X-CSRF-TOKEN':csrfToken,...options.headers}; if(options.body&&!(options.body instanceof FormData))headers['Content-Type']='application/json';
  const response=await fetch(url,{credentials:'same-origin',...options,headers});
  if(response.status===401){location.href='/login';throw new Error('Unauthenticated');}
  const isJson=(response.headers.get('content-type')||'').includes('application/json'); const payload=isJson?await response.json():null;
  if(!response.ok){const validation=payload?.errors?Object.values(payload.errors).flat()[0]:null;throw new Error(validation||payload?.message||t('requestFailed'));}
  return payload;
}
function toast(message){const el=document.getElementById('toast');el.textContent=message;el.classList.remove('hidden');clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.classList.add('hidden'),2600);}
function showSection(section){document.querySelectorAll('.app-section').forEach(el=>el.classList.toggle('hidden',el.id!==`section-${section}`));document.querySelectorAll('.sidebar-nav .nav-button').forEach(b=>b.classList.toggle('active',b.dataset.section===section));document.getElementById('sidebar').classList.remove('open');if(section==='dashboard')loadDashboard();if(section==='products')loadProducts();if(section==='inventory')loadInventory();if(section==='orders')loadOrders();}
async function loadDashboard(){try{const d=await api('/api/dashboard');document.getElementById('stat-products').textContent=d.total_products;document.getElementById('stat-stock').textContent=d.total_stock;document.getElementById('stat-low-stock').textContent=d.low_stock;document.getElementById('stat-orders').textContent=d.total_orders;document.getElementById('recent-movements').innerHTML=d.recent_movements.length?d.recent_movements.map(m=>`<tr><td>${esc(m.product?.name??'—')}</td><td><span class="badge badge-${m.type}">${m.type==='in'?t('stockIn'):t('stockOut')}</span></td><td>${m.type==='in'?'+':'-'}${m.quantity}</td><td>${when(m.created_at)}</td></tr>`).join(''):`<tr><td colspan="4" class="empty-state">${t('noMovements')}</td></tr>`;document.getElementById('low-stock-list').innerHTML=d.low_stock_products.length?d.low_stock_products.map(p=>`<div class="stack-item"><div><strong>${esc(p.name)}</strong><br><small>${esc(p.sku)} · ${esc(p.category)}</small></div><strong class="stock-low">${p.stock}</strong></div>`).join(''):`<div class="empty-state">${t('noLowStock')}</div>`;}catch(e){toast(e.message);}}
async function loadProducts(){try{productsCache=await api('/api/products');renderCategoryFilter();renderProducts();}catch(e){toast(e.message);}}
function renderCategoryFilter(){const s=document.getElementById('category-filter'),cur=s.value,cats=[...new Set(productsCache.map(p=>p.category))].sort();s.innerHTML=`<option value="">${t('allCategories')}</option>`+cats.map(c=>`<option value="${esc(c)}">${esc(c)}</option>`).join('');if(cats.includes(cur))s.value=cur;}
function renderProducts(){const q=document.getElementById('product-search').value.trim().toLowerCase(),cat=document.getElementById('category-filter').value,rows=productsCache.filter(p=>[p.name,p.sku,p.category].some(v=>String(v).toLowerCase().includes(q))&&(!cat||p.category===cat));document.getElementById('products-table').innerHTML=rows.length?rows.map(p=>`<tr><td><strong>${esc(p.name)}</strong></td><td>${esc(p.sku)}</td><td>${esc(p.category)}</td><td>${money(p.price)}</td><td class="${Number(p.stock)<=Number(p.min_stock)?'stock-low':''}">${p.stock}</td><td><div class="table-actions"><button class="small-button" data-pa="edit" data-id="${p.id}">${t('edit')}</button><button class="small-button danger" data-pa="delete" data-id="${p.id}">${t('delete')}</button></div></td></tr>`).join(''):`<tr><td colspan="6" class="empty-state">${t('noProducts')}</td></tr>`;}
function openProduct(p=null){editingProductId=p?Number(p.id):null;document.getElementById('product-modal').classList.remove('hidden');document.getElementById('product-modal-title').textContent=p?t('editProductTitle'):t('newProductTitle');document.getElementById('product-error').textContent='';const f=document.getElementById('product-form');f.reset();if(p){document.getElementById('product-name').value=p.name;document.getElementById('product-sku').value=p.sku;document.getElementById('product-category').value=p.category;document.getElementById('product-price').value=p.price;document.getElementById('product-stock').value=p.stock;document.getElementById('product-min-stock').value=p.min_stock;}}
function closeProduct(){document.getElementById('product-modal').classList.add('hidden');editingProductId=null;}
async function saveProduct(e){e.preventDefault();const payload={name:document.getElementById('product-name').value.trim(),sku:document.getElementById('product-sku').value.trim(),category:document.getElementById('product-category').value.trim(),price:Number(document.getElementById('product-price').value),stock:Number(document.getElementById('product-stock').value),min_stock:Number(document.getElementById('product-min-stock').value)};try{await api(editingProductId?`/api/products/${editingProductId}`:'/api/products',{method:editingProductId?'PUT':'POST',body:JSON.stringify(payload)});closeProduct();await loadProducts();toast(t('productSaved'));}catch(err){document.getElementById('product-error').textContent=err.message;}}
async function productClick(e){const b=e.target.closest('[data-pa]');if(!b)return;const p=productsCache.find(x=>Number(x.id)===Number(b.dataset.id));if(!p)return;if(b.dataset.pa==='edit')return openProduct(p);if(confirm(t('confirmDeleteProduct'))){try{await api(`/api/products/${p.id}`,{method:'DELETE'});await loadProducts();toast(t('productDeleted'));}catch(err){toast(err.message);}}}
async function loadInventory(){try{[productsCache,movementsCache]=await Promise.all([api('/api/products'),api('/api/inventory/movements')]);renderMovementProducts();renderMovements();}catch(e){toast(e.message);}}
function renderMovementProducts(){const s=document.getElementById('movement-product'),cur=s.value;s.innerHTML=`<option value="">${t('selectProduct')}</option>`+productsCache.map(p=>`<option value="${p.id}">${esc(p.name)} (${p.stock})</option>`).join('');if(productsCache.some(p=>String(p.id)===cur))s.value=cur;}
function renderMovements(){document.getElementById('movements-table').innerHTML=movementsCache.length?movementsCache.map(m=>`<tr><td>${esc(m.product?.name??'—')}</td><td><span class="badge badge-${m.type}">${m.type==='in'?t('stockIn'):t('stockOut')}</span></td><td>${m.type==='in'?'+':'-'}${m.quantity}</td><td>${when(m.created_at)}</td></tr>`).join(''):`<tr><td colspan="4" class="empty-state">${t('noMovements')}</td></tr>`;}
async function saveMovement(e){e.preventDefault();const er=document.getElementById('movement-error');er.textContent='';try{await api('/api/inventory/movements',{method:'POST',body:JSON.stringify({product_id:Number(document.getElementById('movement-product').value),type:document.getElementById('movement-type').value,quantity:Number(document.getElementById('movement-quantity').value)})});e.target.reset();await loadInventory();toast(t('movementSaved'));}catch(err){er.textContent=err.message;}}
let orderItemsDraft = [];


let orderItemsDraft = [];

/* =========================
   CARREGAR PEDIDOS
========================= */

async function loadOrders() {
    try {
        const [orders, products] = await Promise.all([
            api('/api/orders'),
            api('/api/products')
        ]);

        ordersCache = orders;
        productsCache = products;

        renderOrderStats();
        renderOrders();

    } catch (e) {
        toast(e.message);
    }
}


/* =========================
   CONTADORES
========================= */

function renderOrderStats() {
    const statuses = [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ];

    statuses.forEach(status => {
        const element = document.getElementById(`orders-${status}`);

        if (!element) return;

        element.textContent = ordersCache.filter(
            order => order.status === status
        ).length;
    });
}


/* =========================
   LISTA DE PEDIDOS
========================= */

function renderOrders() {
    const search = document
        .getElementById('order-search')
        .value
        .trim()
        .toLowerCase();

    const status = document
        .getElementById('order-status-filter')
        .value;

    const rows = ordersCache.filter(order => {
        const customer = String(
            order.customer_name ?? ''
        ).toLowerCase();

        const matchesSearch = customer.includes(search);

        const matchesStatus =
            !status || order.status === status;

        return matchesSearch && matchesStatus;
    });

    document.getElementById('orders-table').innerHTML =
        rows.length
            ? rows.map(order => {
                const itemCount =
                    order.items?.reduce(
                        (total, item) =>
                            total + Number(item.quantity),
                        0
                    ) ?? 0;

                return `
                    <tr>
                        <td>
                            <strong>#${esc(order.id)}</strong>
                        </td>

                        <td>
                            ${esc(order.customer_name ?? '-')}

                            ${
                                order.customer_email
                                    ? `<small style="display:block;">
                                        ${esc(order.customer_email)}
                                       </small>`
                                    : ''
                            }
                        </td>

                        <td>
                            ${itemCount}
                        </td>

                        <td>
                            <strong>
                                ${money(order.total)}
                            </strong>
                        </td>

                        <td>
                            <span class="badge badge-${esc(order.status)}">
                                ${esc(order.status)}
                            </span>
                        </td>

                        <td>
                            ${when(order.created_at)}
                        </td>

                        <td>
                            <button
                                class="small-button"
                                data-oa="view"
                                data-id="${order.id}"
                            >
                                Ver
                            </button>
                        </td>
                    </tr>
                `;
            }).join('')
            : `
                <tr>
                    <td colspan="7" class="empty-state">
                        Nenhum pedido encontrado.
                    </td>
                </tr>
            `;
}


/* =========================
   ABRIR MODAL
========================= */

function openOrder() {
    orderItemsDraft = [];

    document
        .getElementById('order-form')
        .reset();

    document
        .getElementById('order-error')
        .textContent = '';

    document
        .getElementById('order-quantity')
        .value = 1;

    populateOrderProducts();

    updateOrderProductPrice();

    renderOrderItems();

    document
        .getElementById('order-modal')
        .classList.remove('hidden');
}


function closeOrder() {
    orderItemsDraft = [];

    document
        .getElementById('order-modal')
        .classList.add('hidden');
}


/* =========================
   PRODUTOS DO SELECT
========================= */

function populateOrderProducts() {
    const select =
        document.getElementById('order-product');

    select.innerHTML = `
        <option value="">
            Selecione um produto
        </option>
    `;

    productsCache.forEach(product => {
        const option =
            document.createElement('option');

        option.value = product.id;

        option.textContent =
            `${product.name} - ${product.sku} - ${money(product.price)}`;

        select.appendChild(option);
    });
}


/* =========================
   PRODUTO SELECIONADO
========================= */

function getSelectedOrderProduct() {
    const id = Number(
        document.getElementById('order-product').value
    );

    return productsCache.find(
        product => Number(product.id) === id
    );
}


/* =========================
   PREÇO AUTOMÁTICO
========================= */

function updateOrderProductPrice() {
    const product =
        getSelectedOrderProduct();

    const quantity = Math.max(
        1,
        Number(
            document.getElementById('order-quantity').value
        ) || 1
    );

    const price =
        product
            ? Number(product.price)
            : 0;

    document
        .getElementById('order-unit-price')
        .value = money(price);

    document
        .getElementById('order-item-subtotal')
        .value = money(price * quantity);
}


/* =========================
   ADICIONAR PRODUTO
========================= */

function addOrderItem() {
    const product =
        getSelectedOrderProduct();

    const quantity = Number(
        document.getElementById('order-quantity').value
    );

    if (!product) {
        toast('Selecione um produto.');
        return;
    }

    if (!Number.isInteger(quantity) || quantity < 1) {
        toast('Quantidade inválida.');
        return;
    }

    if (quantity > Number(product.stock)) {
        toast(
            `Estoque insuficiente. Disponível: ${product.stock}`
        );

        return;
    }

    const existing =
        orderItemsDraft.find(
            item =>
                Number(item.product_id) ===
                Number(product.id)
        );

    if (existing) {
        const newQuantity =
            existing.quantity + quantity;

        if (newQuantity > Number(product.stock)) {
            toast(
                `Estoque insuficiente. Disponível: ${product.stock}`
            );

            return;
        }

        existing.quantity = newQuantity;

    } else {
        orderItemsDraft.push({
            product_id: Number(product.id),
            product_name: product.name,
            sku: product.sku,
            quantity: quantity,
            unit_price: Number(product.price)
        });
    }

    document
        .getElementById('order-product')
        .value = '';

    document
        .getElementById('order-quantity')
        .value = 1;

    updateOrderProductPrice();

    renderOrderItems();
}


/* =========================
   MOSTRAR ITENS
========================= */

function renderOrderItems() {
    const tbody =
        document.getElementById('order-items-table');

    if (!orderItemsDraft.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">
                    Nenhum produto adicionado.
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML =
            orderItemsDraft
                .map((item, index) => {
                    const subtotal =
                        Number(item.unit_price) *
                        Number(item.quantity);

                    return `
                        <tr>
                            <td>
                                <strong>
                                    ${esc(item.product_name)}
                                </strong>

                                <small style="display:block;">
                                    ${esc(item.sku ?? '')}
                                </small>
                            </td>

                            <td>
                                ${item.quantity}
                            </td>

                            <td>
                                ${money(item.unit_price)}
                            </td>

                            <td>
                                <strong>
                                    ${money(subtotal)}
                                </strong>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="small-button danger"
                                    data-remove-order-item="${index}"
                                >
                                    Remover
                                </button>
                            </td>
                        </tr>
                    `;
                })
                .join('');
    }

    const total =
        orderItemsDraft.reduce(
            (sum, item) =>
                sum +
                (
                    Number(item.unit_price) *
                    Number(item.quantity)
                ),
            0
        );

    document
        .getElementById('order-total-display')
        .textContent = money(total);
}


/* =========================
   REMOVER ITEM
========================= */

function removeOrderItem(index) {
    orderItemsDraft.splice(index, 1);

    renderOrderItems();
}


/* =========================
   SALVAR PEDIDO
========================= */

async function saveOrder(e) {
    e.preventDefault();

    const error =
        document.getElementById('order-error');

    error.textContent = '';

    const customerName =
        document
            .getElementById('order-customer-name')
            .value
            .trim();

    const customerEmail =
        document
            .getElementById('order-customer-email')
            .value
            .trim();

    if (!customerName) {
        error.textContent =
            'Informe o nome do cliente.';

        return;
    }

    if (!orderItemsDraft.length) {
        error.textContent =
            'Adicione pelo menos um produto ao pedido.';

        return;
    }

    const payload = {
        customer_name: customerName,

        customer_email:
            customerEmail || null,

        items:
            orderItemsDraft.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity
            }))
    };

    try {
        await api('/api/orders', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        closeOrder();

        await loadOrders();

        toast('Pedido salvo com sucesso!');

    } catch (err) {
        error.textContent =
            err.message;
    }
}


/* =========================
   VER PEDIDO
========================= */

async function viewOrder(id) {
    try {
        const order =
            await api(`/api/orders/${id}`);

        const items =
            order.items ?? [];

        const text = [
            `Pedido #${order.id}`,
            '',
            `Cliente: ${order.customer_name}`,

            order.customer_email
                ? `E-mail: ${order.customer_email}`
                : '',

            '',
            'Itens:',

            ...items.map(item =>
                `${item.quantity}x ${item.product_name} | ${money(item.unit_price)} cada | ${money(item.subtotal)}`
            ),

            '',
            `TOTAL: ${money(order.total)}`
        ]
        .filter(Boolean)
        .join('\n');

        alert(text);

    } catch (err) {
        toast(err.message);
    }
}


/* =========================
   BOTÕES DA TABELA
========================= */

async function orderClick(e) {
    const button =
        e.target.closest('[data-oa]');

    if (!button) return;

    const id =
        Number(button.dataset.id);

    if (button.dataset.oa === 'view') {
        await viewOrder(id);
    }
}


function renderOrderStats(){['pending','processing','completed','cancelled'].forEach(s=>document.getElementById(`orders-${s}`).textContent=ordersCache.filter(o=>o.status===s).length);}
function renderOrders(){const q=document.getElementById('order-search').value.trim().toLowerCase(),s=document.getElementById('order-status-filter').value,rows=ordersCache.filter(o=>o.customer.toLowerCase().includes(q)&&(!s||o.status===s));document.getElementById('orders-table').innerHTML=rows.length?rows.map(o=>`<tr><td>#${o.id}</td><td>${esc(o.customer)}</td><td>${money(o.total)}</td><td><span class="badge badge-${o.status}">${t(o.status)}</span></td><td>${when(o.created_at)}</td><td><div class="table-actions"><button class="small-button" data-oa="advance" data-id="${o.id}">→</button><button class="small-button danger" data-oa="delete" data-id="${o.id}">${t('delete')}</button></div></td></tr>`).join(''):`<tr><td colspan="6" class="empty-state">${t('noOrders')}</td></tr>`;}
function openOrder(){document.getElementById('order-form').reset();document.getElementById('order-error').textContent='';document.getElementById('order-modal').classList.remove('hidden');}
function closeOrder(){document.getElementById('order-modal').classList.add('hidden');}
async function saveOrder(e){e.preventDefault();try{await api('/api/orders',{method:'POST',body:JSON.stringify({customer:document.getElementById('order-customer').value.trim(),total:Number(document.getElementById('order-total').value),status:document.getElementById('order-status').value})});closeOrder();await loadOrders();toast(t('orderSaved'));}catch(err){document.getElementById('order-error').textContent=err.message;}}
function nextStatus(s){const f=['pending','processing','completed'],i=f.indexOf(s);return i>=0&&i<f.length-1?f[i+1]:s;}
async function orderClick(e){const b=e.target.closest('[data-oa]');if(!b)return;const o=ordersCache.find(x=>Number(x.id)===Number(b.dataset.id));if(!o)return;try{if(b.dataset.oa==='delete'){if(!confirm(t('confirmDeleteOrder')))return;await api(`/api/orders/${o.id}`,{method:'DELETE'});toast(t('orderDeleted'));}else await api(`/api/orders/${o.id}`,{method:'PUT',body:JSON.stringify({status:nextStatus(o.status)})});await loadOrders();}catch(err){toast(err.message);}}
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.nav-button[data-section]').forEach(b=>b.addEventListener('click',()=>showSection(b.dataset.section)));document.getElementById('mobile-menu').addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));document.getElementById('open-product-modal').addEventListener('click',()=>openProduct());document.getElementById('close-product-modal').addEventListener('click',closeProduct);document.getElementById('cancel-product').addEventListener('click',closeProduct);document.getElementById('product-form').addEventListener('submit',saveProduct);document.getElementById('products-table').addEventListener('click',productClick);document.getElementById('product-search').addEventListener('input',renderProducts);document.getElementById('category-filter').addEventListener('change',renderProducts);document.getElementById('movement-form').addEventListener('submit',saveMovement);document.getElementById('open-order-modal').addEventListener('click',openOrder);document.getElementById('close-order-modal').addEventListener('click',closeOrder);document.getElementById('cancel-order').addEventListener('click',closeOrder);document.getElementById('order-form').addEventListener('submit',saveOrder);document.getElementById('orders-table').addEventListener('click',orderClick);document.getElementById('order-search').addEventListener('input',renderOrders);document.getElementById('order-status-filter').addEventListener('change',renderOrders);loadDashboard();});
document.addEventListener('languageChanged',()=>{if(productsCache.length){renderCategoryFilter();renderProducts();renderMovementProducts();}if(movementsCache.length)renderMovements();if(ordersCache.length){renderOrderStats();renderOrders();}loadDashboard();});
