import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { gsap } from 'gsap';

import.meta.glob(['../images/**'], { eager: true });

window.Alpine = Alpine;
window.Chart = Chart;
window.gsap = gsap;
Alpine.start();
