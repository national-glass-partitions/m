var config = {
    paths: {
        'owlcarousel': 'Smartwave_Filterproducts/js/owl.carousel/owl.carousel.min',
        'lazyload': 'Smartwave_Filterproducts/js/lazyload/jquery.lazyload',
        'imagesloaded': 'Smartwave_Filterproducts/js/imagesloaded',
        'packery': 'Smartwave_Filterproducts/js/packery.pkgd',
    },
    shim: {
        'Smartwave_Filterproducts/js/owl.carousel/owl.carousel.min': {
            deps: ['jquery']
        },
        'Smartwave_Filterproducts/js/lazyload/jquery.lazyload': {
            deps: ['jquery']
        },
        'packery': {
            deps: ['jquery','imagesloaded']
        }
    }
};
