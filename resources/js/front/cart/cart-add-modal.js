import { resolveCartItemImage } from './cart-image';

const DEFAULT_IMAGE = '/media/img/logo-biblos.png';

const CHECK_ICON = `
    <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M9.55 17.3 4.8 12.55l1.4-1.4 3.35 3.35 8.25-8.25 1.4 1.4Z"/>
    </svg>
`;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function labels() {
    const translations = window.FrontTranslations?.js?.cart?.add_modal || {};
    const english = (document.documentElement.lang || 'hr').toLowerCase() === 'en';
    const fallback = english ? {
        heading: 'Your book is in the cart 📚',
        lead: 'Great choice! You can keep browsing or complete your purchase.',
        productFallback: 'Selected book',
        imageAlt: 'Added book',
        added: 'Added',
        totalInCart: 'Total in cart',
        unit: 'pcs',
        confirm: 'Complete purchase',
        cancel: 'Continue shopping',
        close: 'Close'
    } : {
        heading: 'Knjiga je u košarici 📚',
        lead: 'Odličan izbor! Možete nastaviti pregledavati ili dovršiti kupnju.',
        productFallback: 'Odabrana knjiga',
        imageAlt: 'Dodana knjiga',
        added: 'Dodano',
        totalInCart: 'Ukupno u košarici',
        unit: 'kom',
        confirm: 'Dovrši kupnju',
        cancel: 'Nastavi kupovati',
        close: 'Zatvori'
    };

    return { ...fallback, ...translations };
}

function cartItems(cart) {
    return Object.values(cart?.items || {});
}

function findCartItem(cart, itemId) {
    const directMatch = cart?.items?.[itemId];

    if (directMatch) {
        return directMatch;
    }

    return cartItems(cart).find((item) => String(item?.id) === String(itemId));
}

function resolveQuantity(value) {
    return Math.max(1, parseInt(value, 10) || 1);
}

function hasConditions(cartItem) {
    return Object.keys(cartItem?.conditions || {}).length > 0;
}

function resolvePrice(cartItem) {
    const product = cartItem?.associatedModel || {};

    if (hasConditions(cartItem) && product.main_special_text) {
        return product.main_special_text;
    }

    return product.main_price_text || product.main_special_text || cartItem?.price || '';
}

function resolveImage(cartItem) {
    return resolveCartItemImage(cartItem, DEFAULT_IMAGE);
}

function buildModalHtml(payload) {
    const copy = labels();
    const cartItem = payload.cartItem;
    const requestedItem = payload.requestedItem;
    const quantityAdded = resolveQuantity(requestedItem?.quantity);
    const quantityInCart = resolveQuantity(cartItem?.quantity);
    const productName = cartItem?.name || copy.productFallback;

    return `
        <div class="cart-add-modal">
            <div class="cart-add-modal__hero">
                <span class="cart-add-modal__hero-icon">${CHECK_ICON}</span>
                <div class="cart-add-modal__hero-copy">
                    <h2 class="cart-add-modal__heading">${escapeHtml(copy.heading)}</h2>
                    <p class="cart-add-modal__lead">${escapeHtml(copy.lead)}</p>
                </div>
            </div>

            <div class="cart-add-modal__card">
                <div class="cart-add-modal__image-wrap">
                    <img
                        class="cart-add-modal__image"
                        src="${escapeHtml(resolveImage(cartItem))}"
                        alt="${escapeHtml(productName || copy.imageAlt)}"
                        onerror="this.onerror=null;this.src='${DEFAULT_IMAGE}'"
                    >
                </div>

                <div class="cart-add-modal__body">
                    ${resolvePrice(cartItem) ? `<span class="cart-add-modal__price">${escapeHtml(resolvePrice(cartItem))}</span>` : ''}
                    <h3 class="cart-add-modal__name">${escapeHtml(productName)}</h3>

                    <div class="cart-add-modal__chips">
                        <span class="cart-add-modal__chip">${escapeHtml(copy.added)}: <strong>${quantityAdded} ${escapeHtml(copy.unit)}</strong></span>
                        ${quantityInCart > quantityAdded ? `<span class="cart-add-modal__chip">${escapeHtml(copy.totalInCart)}: <strong>${quantityInCart} ${escapeHtml(copy.unit)}</strong></span>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
}

export function showCartAddSuccessModal(swal, payload = {}) {
    const cart = payload.cart || {};
    const requestedItem = payload.item || {};
    const cartItem = findCartItem(cart, requestedItem.id);
    const copy = labels();

    if (!swal || !cartItem) {
        return null;
    }

    return swal.fire({
        html: buildModalHtml({
            cart,
            cartItem,
            requestedItem
        }),
        showCloseButton: true,
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText: copy.confirm,
        cancelButtonText: copy.cancel,
        closeButtonAriaLabel: copy.close,
        focusConfirm: false,
        buttonsStyling: false,
        customClass: {
            container: 'cart-add-modal-container',
            popup: 'cart-add-modal-popup',
            htmlContainer: 'cart-add-modal-html',
            closeButton: 'cart-add-modal-close',
            actions: 'cart-add-modal-actions',
            confirmButton: 'btn btn-shadow cart-add-modal-confirm',
            cancelButton: 'btn btn-outline-primary cart-add-modal-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = window.FrontCartUrl || '/kosarica';
        }

        return result;
    });
}
