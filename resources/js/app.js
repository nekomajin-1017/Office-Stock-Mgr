const initializeLineItemForm = ({
    formSelector,
    itemsSelector,
    itemSelector,
    totalSelector,
    addSelector,
    removeSelector,
    addItem,
    afterChange = () => {},
}) => {
    const form = document.querySelector(formSelector);

    if (!form) return;

    const items = form.querySelector(itemsSelector);
    const total = form.querySelector(totalSelector);

    const updateTotals = () => {
        let formTotal = 0;

        items.querySelectorAll(itemSelector).forEach((item) => {
            const quantity =
                Number(item.querySelector('[name$="[quantity]"]').value) || 0;
            const unitPrice =
                Number(item.querySelector('[name$="[unit_price]"]').value) || 0;
            const lineTotal = quantity * unitPrice;

            item.querySelector("[data-line-total]").textContent =
                `${lineTotal.toFixed(0)} 円`;
            formTotal += lineTotal;
        });

        total.textContent = `${formTotal.toFixed(0)} 円`;
    };

    form.addEventListener("input", updateTotals);
    form.addEventListener("click", (event) => {
        const addButton = event.target.closest(addSelector);
        const removeButton = event.target.closest(removeSelector);

        if (addButton) addItem(items);
        if (removeButton && items.querySelectorAll(itemSelector).length > 1) {
            removeButton.closest(itemSelector).remove();
        }

        afterChange();
        updateTotals();
    });

    afterChange();
    updateTotals();
};

const purchaseForm = document.querySelector("[data-purchase-form]");

if (purchaseForm) {
    const supplier = purchaseForm.querySelector("[data-purchase-supplier]");
    const template = document.querySelector("[data-purchase-template]");

    const filterProductsBySupplier = () => {
        const supplierId = supplier.value;

        purchaseForm
            .querySelectorAll('[name$="[product_id]"]')
            .forEach((select) => {
                [...select.options].forEach((option) => {
                    if (!option.dataset.supplier) return;

                    option.hidden =
                        supplierId !== "" &&
                        option.dataset.supplier !== supplierId;
                });

                if (
                    select.selectedOptions[0]?.dataset.supplier &&
                    select.selectedOptions[0].dataset.supplier !== supplierId
                ) {
                    select.value = "";
                }
            });
    };

    initializeLineItemForm({
        formSelector: "[data-purchase-form]",
        itemsSelector: "[data-purchase-items]",
        itemSelector: "[data-purchase-item]",
        totalSelector: "[data-purchase-total]",
        addSelector: "[data-add-item]",
        removeSelector: "[data-remove-item]",
        addItem: (items) => {
            const index = items.querySelectorAll("[data-purchase-item]").length;
            items.insertAdjacentHTML(
                "beforeend",
                template.innerHTML.replaceAll("__INDEX__", index),
            );
        },
        afterChange: filterProductsBySupplier,
    });

    supplier.addEventListener("change", filterProductsBySupplier);
}

initializeLineItemForm({
    formSelector: "[data-sale-form]",
    itemsSelector: "[data-sale-items]",
    itemSelector: "[data-sale-item]",
    totalSelector: "[data-sale-total]",
    addSelector: "[data-add-sale-item]",
    removeSelector: "[data-remove-sale-item]",
    addItem: (items) => {
        const item = items.firstElementChild.cloneNode(true);
        const index = items.children.length;

        item.querySelectorAll("[name]").forEach((input) => {
            input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
            input.value =
                input.tagName === "INPUT" && input.name.endsWith("[quantity]")
                    ? 1
                    : "";
        });

        items.append(item);
    },
});
