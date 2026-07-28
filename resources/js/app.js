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
