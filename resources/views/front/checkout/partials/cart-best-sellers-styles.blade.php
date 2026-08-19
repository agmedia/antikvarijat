<style>
    .cart-best-sellers {
        position: relative;
        padding: clamp(2.5rem, 5vw, 4.5rem) 0 clamp(2.25rem, 4.5vw, 4rem);
        overflow: hidden;
        border-top: 1px solid rgba(49, 72, 55, .1);
        background:
            radial-gradient(circle at 8% 18%, rgba(185, 155, 82, .12), transparent 23rem),
            linear-gradient(180deg, #f6f5f0 0%, #efeee8 100%);
    }

    .cart-best-sellers::after {
        position: absolute;
        right: -4rem;
        bottom: -7rem;
        width: 20rem;
        height: 20rem;
        border: 1px solid rgba(49, 72, 55, .08);
        border-radius: 50%;
        content: '';
        pointer-events: none;
    }

    .cart-best-sellers__header {
        position: relative;
        z-index: 1;
        max-width: 46rem;
        margin-bottom: 1.7rem;
    }

    .cart-best-sellers__eyebrow {
        display: inline-flex;
        margin-bottom: .7rem;
        align-items: center;
        gap: .45rem;
        color: #9f8339;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .cart-best-sellers__title {
        margin: 0 0 .55rem;
        color: #314837;
        font-size: clamp(1.65rem, 3vw, 2.35rem);
        line-height: 1.12;
    }

    .cart-best-sellers__subtitle {
        margin: 0;
        color: #66736a;
        font-size: clamp(.92rem, 1.4vw, 1.05rem);
    }

    .cart-best-sellers__carousel {
        position: relative;
        z-index: 1;
    }

    .cart-best-sellers__carousel .tns-inner {
        padding: .2rem .1rem 1.1rem;
    }

    .cart-best-sellers__slide {
        height: 100%;
    }

    .cart-best-sellers__card {
        display: flex;
        height: 100%;
        min-width: 0;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(49, 72, 55, .1);
        border-radius: .75rem;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 .45rem 1.3rem rgba(34, 51, 40, .075);
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .cart-best-sellers__card:hover {
        border-color: rgba(159, 131, 57, .28);
        box-shadow: 0 .8rem 1.8rem rgba(34, 51, 40, .12);
        transform: translateY(-3px);
    }

    .cart-best-sellers__cover {
        position: relative;
        display: flex;
        height: 16.75rem;
        padding: 1rem 1rem .65rem;
        align-items: center;
        justify-content: center;
        background: linear-gradient(150deg, #fbfaf7, #f0eee6);
    }

    .cart-best-sellers__cover img {
        display: block;
        width: auto;
        max-width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 .45rem .5rem rgba(29, 42, 33, .16));
    }

    .cart-best-sellers__rank {
        position: absolute;
        z-index: 1;
        top: .8rem;
        left: .8rem;
        display: inline-flex;
        width: 2rem;
        height: 2rem;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255, 255, 255, .9);
        border-radius: 50%;
        background: #b99b52;
        box-shadow: 0 .25rem .6rem rgba(82, 66, 29, .2);
        color: #fff;
        font-size: .76rem;
        font-weight: 700;
    }

    .cart-best-sellers__body {
        display: flex;
        min-height: 12rem;
        padding: 1rem;
        flex: 1 1 auto;
        flex-direction: column;
    }

    .cart-best-sellers__author {
        display: block;
        margin-bottom: .3rem;
        overflow: hidden;
        color: #7a867e;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-overflow: ellipsis;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .cart-best-sellers__product-title {
        display: -webkit-box;
        min-height: 2.65rem;
        margin: 0 0 .7rem;
        overflow: hidden;
        color: #314837;
        font-size: .93rem;
        font-weight: 700;
        line-height: 1.42;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .cart-best-sellers__product-title a {
        color: inherit;
    }

    .cart-best-sellers__product-title a:hover,
    .cart-best-sellers__author:hover {
        color: #9f8339;
    }

    .cart-best-sellers__badge {
        display: inline-flex;
        width: fit-content;
        margin-bottom: 1rem;
        padding: .3rem .55rem;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        background: rgba(185, 155, 82, .13);
        color: #806a32;
        font-size: .66rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .cart-best-sellers__purchase {
        display: flex;
        margin-top: auto;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
    }

    .cart-best-sellers__price {
        display: flex;
        min-width: 0;
        flex-direction: column;
        color: #314837;
        font-size: .96rem;
        line-height: 1.25;
    }

    .cart-best-sellers__price del {
        color: #8a938d;
        font-size: .72rem;
    }

    .cart-best-sellers__price strong,
    .cart-best-sellers__price del {
        white-space: nowrap;
    }

    .cart-best-sellers__purchase .btn {
        display: inline-flex;
        min-height: 2.25rem;
        padding: .45rem .65rem;
        align-items: center;
        justify-content: center;
        gap: .22rem;
        border-color: #314837;
        background: #314837;
        font-size: .72rem;
        font-weight: 700;
        text-transform: none;
        white-space: nowrap;
    }

    .cart-best-sellers__purchase .btn:hover,
    .cart-best-sellers__purchase .btn:focus {
        border-color: #9f8339;
        background: #9f8339;
    }

    .cart-best-sellers__carousel .tns-controls button {
        border-color: rgba(49, 72, 55, .14);
        background: #fff;
        box-shadow: 0 .35rem .9rem rgba(34, 51, 40, .1);
        color: #314837;
    }

    .cart-best-sellers__carousel .tns-nav button {
        background-color: rgba(49, 72, 55, .25);
    }

    .cart-best-sellers__carousel .tns-nav button.tns-nav-active {
        background-color: #b99b52;
    }

    @media (max-width: 1199.98px) {
        .cart-best-sellers__cover {
            height: 15.25rem;
        }
    }

    @media (max-width: 767.98px) {
        .cart-best-sellers__header {
            margin-bottom: 1.25rem;
        }

        .cart-best-sellers__cover {
            height: 14rem;
        }

        .cart-best-sellers__body {
            min-height: 11.5rem;
            padding: .85rem;
        }

        .cart-best-sellers__purchase {
            align-items: flex-end;
        }

        .cart-best-sellers__purchase .btn {
            width: 2.35rem;
            padding-right: .4rem;
            padding-left: .4rem;
        }

        .cart-best-sellers__purchase .add-to-cart-btn-simple__label {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .cart-best-sellers__card {
            transition: none;
        }
    }
</style>
