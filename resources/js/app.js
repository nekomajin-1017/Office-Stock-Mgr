const purchaseForm = document.querySelector('[data-purchase-form]');

if (purchaseForm) {
  const items = purchaseForm.querySelector('[data-purchase-items]');
  const template = document.querySelector('[data-purchase-template]');
  const total = purchaseForm.querySelector('[data-purchase-total]');

  const updateTotals = () => {
    let purchaseTotal = 0;
    items.querySelectorAll('[data-purchase-item]').forEach((item) => {
      const quantity = Number(item.querySelector('[name$="[quantity]"]').value) || 0;
      const unitPrice = Number(item.querySelector('[name$="[unit_price]"]').value) || 0;
      const lineTotal = quantity * unitPrice;
      item.querySelector('[data-line-total]').textContent = `${lineTotal.toFixed(2)} 円`;
      purchaseTotal += lineTotal;
    });
    total.textContent = `${purchaseTotal.toFixed(2)} 円`;
  };

  purchaseForm.addEventListener('input', updateTotals);
  purchaseForm.addEventListener('click', (event) => {
    if (event.target.matches('[data-add-item]')) {
      const index = items.querySelectorAll('[data-purchase-item]').length;
      items.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
    }
    if (event.target.matches('[data-remove-item]') && items.querySelectorAll('[data-purchase-item]').length > 1) event.target.closest('[data-purchase-item]').remove();
    updateTotals();
  });
  updateTotals();
}

const saleForm = document.querySelector('[data-sale-form]');
if (saleForm) {
  const items = saleForm.querySelector('[data-sale-items]');
  const total = saleForm.querySelector('[data-sale-total]');
  const update = () => { let sum = 0; items.querySelectorAll('[data-sale-item]').forEach((item) => { const line = (Number(item.querySelector('[name$="[quantity]"]').value) || 0) * (Number(item.querySelector('[name$="[unit_price]"]').value) || 0); item.querySelector('[data-line-total]').textContent = `${line.toFixed(2)} 円`; sum += line; }); total.textContent = `${sum.toFixed(2)} 円`; };
  saleForm.addEventListener('input', update);
  saleForm.addEventListener('click', (event) => { if (event.target.matches('[data-add-sale-item]')) { const item = items.firstElementChild.cloneNode(true); const index = items.children.length; item.querySelectorAll('[name]').forEach((input) => { input.name = input.name.replace(/items\[\d+]/, `items[${index}]`); if (input.tagName === 'INPUT') input.value = input.name.endsWith('[quantity]') ? 1 : ''; else input.value = ''; }); items.append(item); } if (event.target.matches('[data-remove-sale-item]') && items.children.length > 1) event.target.closest('[data-sale-item]').remove(); update(); }); update();
}
