/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './scss/app.scss';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

const slider = document.getElementById('price-slider');

if (slider) {
    const range = noUiSlider.create(slider, {
        start: [20, 80],
        connect: true,
        range: {
            'min': 0,
            'max': 100
        }
    });
    const min = document.getElementById('min');
    const max = document.getElementById('max');
    range.on('slider', function(values, handle) {
        if (handle === 0) {
            min.value = Math.round(values[0]);
        }
        if (handle === 1) {
            max.value = Math.round(values[1]);
        }
    })
}
