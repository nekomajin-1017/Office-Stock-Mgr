const purchaseForm = document.querySelector("[data-purchase-form]");

if (purchaseForm) {
    const items = purchaseForm.querySelector("[data-purchase-items]");
    const template = document.querySelector("[data-purchase-template]");
    const total = purchaseForm.querySelector("[data-purchase-total]");
    const supplier = purchaseForm.querySelector("[data-purchase-supplier]");

    const filterProductsBySupplier = () => {
        const supplierId = supplier.value;

        items.querySelectorAll('[name$="[product_id]"]').forEach((select) => {
            [...select.options].forEach((option) => {
                if (!option.dataset.supplier) return;

                option.hidden =
                    supplierId !== "" && option.dataset.supplier !== supplierId;
            });

            if (
                select.selectedOptions[0]?.dataset.supplier &&
                select.selectedOptions[0].dataset.supplier !== supplierId
            ) {
                select.value = "";
            }
        });
    };

    const updateTotals = () => {
        let purchaseTotal = 0;
        items.querySelectorAll("[data-purchase-item]").forEach((item) => {
            const quantity =
                Number(item.querySelector('[name$="[quantity]"]').value) || 0;
            const unitPrice =
                Number(item.querySelector('[name$="[unit_price]"]').value) || 0;
            const lineTotal = quantity * unitPrice;
            item.querySelector("[data-line-total]").textContent =
                `${lineTotal.toFixed(0)} 円`;
            purchaseTotal += lineTotal;
        });
        total.textContent = `${purchaseTotal.toFixed(0)} 円`;
    };

    purchaseForm.addEventListener("input", updateTotals);
    supplier.addEventListener("change", filterProductsBySupplier);
    purchaseForm.addEventListener("click", (event) => {
        const addButton = event.target.closest("[data-add-item]");
        const removeButton = event.target.closest("[data-remove-item]");

        if (addButton) {
            const index = items.querySelectorAll("[data-purchase-item]").length;
            items.insertAdjacentHTML(
                "beforeend",
                template.innerHTML.replaceAll("__INDEX__", index),
            );
        }
        if (
            removeButton &&
            items.querySelectorAll("[data-purchase-item]").length > 1
        ) {
            removeButton.closest("[data-purchase-item]").remove();
        }
        filterProductsBySupplier();
        updateTotals();
    });
    filterProductsBySupplier();
    updateTotals();
}

const saleForm = document.querySelector("[data-sale-form]");
if (saleForm) {
    const items = saleForm.querySelector("[data-sale-items]");
    const total = saleForm.querySelector("[data-sale-total]");
    const update = () => {
        let sum = 0;
        items.querySelectorAll("[data-sale-item]").forEach((item) => {
            const line =
                (Number(item.querySelector('[name$="[quantity]"]').value) ||
                    0) *
                (Number(item.querySelector('[name$="[unit_price]"]').value) ||
                    0);
            item.querySelector("[data-line-total]").textContent =
                `${line.toFixed(0)} 円`;
            sum += line;
        });
        total.textContent = `${sum.toFixed(0)} 円`;
    };
    saleForm.addEventListener("input", update);
    saleForm.addEventListener("click", (event) => {
        const addButton = event.target.closest("[data-add-sale-item]");
        const removeButton = event.target.closest("[data-remove-sale-item]");

        if (addButton) {
            const item = items.firstElementChild.cloneNode(true);
            const index = items.children.length;
            item.querySelectorAll("[name]").forEach((input) => {
                input.name = input.name.replace(
                    /items\[\d+]/,
                    `items[${index}]`,
                );
                if (input.tagName === "INPUT")
                    input.value = input.name.endsWith("[quantity]") ? 1 : "";
                else input.value = "";
            });
            items.append(item);
        }
        if (removeButton && items.children.length > 1) {
            removeButton.closest("[data-sale-item]").remove();
        }
        update();
    });
    update();
}
