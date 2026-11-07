import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

import.meta.glob(['../images/**'], { eager: true });

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();
