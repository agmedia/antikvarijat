export function resolveCartItemImage(cartItem, fallback = '') {
    const product = cartItem && cartItem.associatedModel ? cartItem.associatedModel : {};

    if (product.thumb) {
        return product.thumb;
    }

    const image = product.image || '';

    if (!image || /-thumb\.webp(?:[?#]|$)/i.test(image)) {
        return image || fallback;
    }

    const thumbnail = image.replace(/\.webp(?=([?#]|$))/i, '-thumb.webp');

    return thumbnail !== image ? thumbnail : image;
}
