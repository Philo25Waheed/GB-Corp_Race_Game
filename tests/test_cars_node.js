const GBCars = require('../js/cars.js');

const depts = GBCars.getAllDepartmentsCars();
const count = Object.keys(depts).length;
console.log('COUNT:' + count);

const models = ['formula', 'hypercar', 'gt_coupe', 'streamliner', 'speedster', 'aero_fastback', 'rally_suv', 'hauler_truck', 'aero_van', 'trike_racer', 'transporter', 'hse_rapid'];
let ok = true;
for (const m of models) {
    const svg = GBCars.getDepartmentCarSvg({ model: m, primary: '#2b51a4', secondary: '#d9d8d6' });
    if (!svg.includes('<svg') || !svg.includes('</svg>')) {
        ok = false;
        break;
    }
}
console.log('ALL_MODELS_OK:' + ok);

const palette = GBCars.PALETTE_SET;
let invalid = 0;
for (const id in depts) {
    const svg = GBCars.getDepartmentCarSvg(depts[id]);
    const matches = svg.match(/#[0-9a-fA-F]{6}/g) || [];
    for (const c of matches) {
        if (!palette.has(c.toLowerCase())) invalid++;
    }
}
console.log('INVALID_SVG_COLORS:' + invalid);
