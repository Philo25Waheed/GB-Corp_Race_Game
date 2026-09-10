/**
 * GB Corp Summer Road Trip - Dedicated Unique Cars Engine
 * 
 * STRICT BRAND COLOR PALETTE (From Uploaded Image Reference):
 * Column 1 (Royal Navy):    #2b51a4, #4062ac, #5574b5, #6b85c0, #7f96c8, #95a8d2, #aab9da, #bfcbe5, #d5dcee, #eaeef7
 * Column 2 (Sky/Cyan Blue): #049eda, #1da9de, #35b2e2, #4fbce5, #68c4e9, #82cfed, #9bd9f0, #b4e1f4, #ceecf7, #e6f5fc
 * Column 3 (Tangerine):     #f78c2a, #f79740, #f7a454, #f9ae6a, #f9bb80, #fbc596, #fcd0a9, #fcddc0, #fde8d5, #fef4ea
 * Column 4 (Deep Teal):     #119aaa, #28a4b0, #40aebb, #58b8c4, #70c3cb, #87cbd4, #9fd6db, #b8e1e5, #cfebee, #e7f5f6
 * Column 5 (Emerald Green): #3bae49, #4eb75b, #62bd6e, #75c681, #89cf93, #9dd5a4, #b0deb7, #c4e7c9, #d8efdb, #ebf7ed
 * Column 6 (Slate Grey):    #7f8487, #8c9093, #989da0, #a5a9ac, #b1b5b8, #bec2c3, #cdced0, #d9dadc, #e5e6e8, #f3f3f3
 * Column 7 (Platinum):      #d9d8d6, #dcdcda, #e0e0de, #e4e4e2, #e8e8e6, #eceaeb, #f0eeef, #f3f3f3, #f7f7f7, #fbfbfb
 * 
 * 100% of colors used in all SVGs and vehicles are derived exclusively from this palette matrix!
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.GBCarsEngine = factory();
        root.getDepartmentCarSvg = root.GBCarsEngine.getDepartmentCarSvg;
        root.getDepartmentCarBadge = root.GBCarsEngine.getDepartmentCarBadge;
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    // Official Palette Set (for compliance verification)
    const PALETTE_SET = new Set([
        '#2b51a4', '#4062ac', '#5574b5', '#6b85c0', '#7f96c8', '#95a8d2', '#aab9da', '#bfcbe5', '#d5dcee', '#eaeef7',
        '#049eda', '#1da9de', '#35b2e2', '#4fbce5', '#68c4e9', '#82cfed', '#9bd9f0', '#b4e1f4', '#ceecf7', '#e6f5fc',
        '#f78c2a', '#f79740', '#f7a454', '#f9ae6a', '#f9bb80', '#fbc596', '#fcd0a9', '#fcddc0', '#fde8d5', '#fef4ea',
        '#119aaa', '#28a4b0', '#40aebb', '#58b8c4', '#70c3cb', '#87cbd4', '#9fd6db', '#b8e1e5', '#cfebee', '#e7f5f6',
        '#3bae49', '#4eb75b', '#62bd6e', '#75c681', '#89cf93', '#9dd5a4', '#b0deb7', '#c4e7c9', '#d8efdb', '#ebf7ed',
        '#7f8487', '#8c9093', '#989da0', '#a5a9ac', '#b1b5b8', '#bec2c3', '#cdced0', '#d9dadc', '#e5e6e8', '#f3f3f3',
        '#d9d8d6', '#dcdcda', '#e0e0de', '#e4e4e2', '#e8e8e6', '#eceaeb', '#f0eeef', '#f7f7f7', '#fbfbfb'
    ]);

    // Standard neutrals from the palette
    const TIRE_BASE = '#7f8487';      // Slate Grey 100%
    const TIRE_INNER = '#8c9093';     // Slate Grey 90%
    const RIM_COLOR = '#d9d8d6';      // Platinum Silver 100%
    const CHASSIS_BASE = '#7f8487';   // Slate Grey 100%
    const CANOPY_TINT = '#ceecf7';    // Sky Blue 20%
    const HEADLIGHT_COLOR = '#fef4ea';// Warm Tint 10%
    const TAILLIGHT_COLOR = '#f78c2a';// Tangerine Orange 100%

    // Dedicated Unique Configuration for all 43 Departments
    const DEPARTMENTS_CARS = {
        // 1. Business Units (BU) - 24 Departments
        'hr': {
            id: 'hr', code: 'BU-HR', name_en: 'Human Resources', name_ar: 'الموارد البشرية',
            model: 'gt_coupe', primary: '#2b51a4', secondary: '#d9d8d6', livery: 'stripes', num: 1
        },
        'it': {
            id: 'it', code: 'BU-IT', name_en: 'Information Technology', name_ar: 'تكنولوجيا المعلومات',
            model: 'formula', primary: '#049eda', secondary: '#f3f3f3', livery: 'velocity', num: 2
        },
        'finance': {
            id: 'finance', code: 'BU-FIN', name_en: 'Finance', name_ar: 'الإدارة المالية',
            model: 'speedster', primary: '#4062ac', secondary: '#dcdcda', livery: 'dual_bars', num: 3
        },
        'marketing': {
            id: 'marketing', code: 'BU-MKT', name_en: 'Marketing', name_ar: 'التسويق',
            model: 'hypercar', primary: '#f78c2a', secondary: '#2b51a4', livery: 'arrow', num: 4
        },
        'operations': {
            id: 'operations', code: 'BU-OPS', name_en: 'Operations', name_ar: 'العمليات والتشغيل',
            model: 'aero_fastback', primary: '#7f8487', secondary: '#3bae49', livery: 'side_swoop', num: 5
        },
        'administration': {
            id: 'administration', code: 'BU-ADM', name_en: 'Administration', name_ar: 'الشؤون الإدارية',
            model: 'gt_coupe', primary: '#8c9093', secondary: '#049eda', livery: 'stripes', num: 6
        },
        'procurement': {
            id: 'procurement', code: 'BU-PROC', name_en: 'Procurement', name_ar: 'المشتريات وسلاسل الإمداد',
            model: 'speedster', primary: '#119aaa', secondary: '#f79740', livery: 'dual_bars', num: 7
        },
        'legal_loans': {
            id: 'legal_loans', code: 'BU-LEG', name_en: 'Legal & Problem Loans', name_ar: 'الشؤون القانونية والقروض',
            model: 'aero_fastback', primary: '#5574b5', secondary: '#d9d8d6', livery: 'chevrons', num: 8
        },
        'manufacturing': {
            id: 'manufacturing', code: 'BU-MFG', name_en: 'Manufacturing', name_ar: 'التصنيع والإنتاج',
            model: 'hauler_truck', primary: '#3bae49', secondary: '#7f8487', livery: 'heavy_shield', num: 9
        },
        'central_warehousing': {
            id: 'central_warehousing', code: 'BU-WH', name_en: 'Central Warehousing', name_ar: 'المستودعات المركزية',
            model: 'aero_van', primary: '#f79740', secondary: '#2b51a4', livery: 'cargo_bars', num: 10
        },
        'quality_excellence': {
            id: 'quality_excellence', code: 'BU-QUAL', name_en: 'Quality / Business Excellence', name_ar: 'الجودة والتميز المؤسسي',
            model: 'hypercar', primary: '#4eb75b', secondary: '#dcdcda', livery: 'apex_fin', num: 11
        },
        'digital_transformation': {
            id: 'digital_transformation', code: 'BU-DIG', name_en: 'Digital Transformation', name_ar: 'التحول الرقمي والابتكار',
            model: 'formula', primary: '#1da9de', secondary: '#f7a454', livery: 'cyber_grid', num: 12
        },
        'data_dept': {
            id: 'data_dept', code: 'BU-DATA', name_en: 'Data', name_ar: 'إدارة وتحليل البيانات',
            model: 'streamliner', primary: '#28a4b0', secondary: '#eaeef7', livery: 'telemetry', num: 13
        },
        'projects_bu': {
            id: 'projects_bu', code: 'BU-PMO', name_en: 'Projects', name_ar: 'المشاريع الاستراتيجية',
            model: 'hypercar', primary: '#f7a454', secondary: '#119aaa', livery: 'arrow', num: 14
        },
        'internal_audit': {
            id: 'internal_audit', code: 'BU-AUD', name_en: 'Internal Audit', name_ar: 'المراجعة والتدقيق الداخلي',
            model: 'aero_fastback', primary: '#989da0', secondary: '#2b51a4', livery: 'radar_ring', num: 15
        },
        'crm_complaints': {
            id: 'crm_complaints', code: 'BU-CRM', name_en: 'CRM & Complaints', name_ar: 'علاقات العملاء والشكاوى',
            model: 'gt_coupe', primary: '#35b2e2', secondary: '#f78c2a', livery: 'side_swoop', num: 16
        },
        'gov_sales': {
            id: 'gov_sales', code: 'BU-GOV', name_en: 'Government Sales / Relations', name_ar: 'المبيعات والعلاقات الحكومية',
            model: 'aero_fastback', primary: '#6b85c0', secondary: '#e0e0de', livery: 'executive_trim', num: 17
        },
        'planning_performance': {
            id: 'planning_performance', code: 'BU-PLAN', name_en: 'Planning and Performance Monitoring', name_ar: 'التخطيط ومتابعة الأداء',
            model: 'rally_suv', primary: '#40aebb', secondary: '#f9ae6a', livery: 'vector_speed', num: 18
        },
        'passenger_cars': {
            id: 'passenger_cars', code: 'BU-PC', name_en: 'PC (Passenger Cars)', name_ar: 'قطاع سيارات الركوب',
            model: 'gt_coupe', primary: '#049eda', secondary: '#2b51a4', livery: 'twin_gt', num: 19
        },
        'cv_ce': {
            id: 'cv_ce', code: 'BU-CVCE', name_en: 'CV & CE (Commercial & Equipment)', name_ar: 'السيارات التجارية والمعدات',
            model: 'hauler_truck', primary: '#f78c2a', secondary: '#7f8487', livery: 'heavy_shield', num: 20
        },
        'two_three_wheelers': {
            id: 'two_three_wheelers', code: 'BU-23W', name_en: '2&3 Wheelers', name_ar: 'الدراجات والمركبات الخفيفة',
            model: 'trike_racer', primary: '#3bae49', secondary: '#049eda', livery: 'sprint_slash', num: 21
        },
        'tires': {
            id: 'tires', code: 'BU-TIRE', name_en: 'Tires', name_ar: 'قطاع الإطارات والخدمات',
            model: 'hypercar', primary: '#a5a9ac', secondary: '#f78c2a', livery: 'tread_edge', num: 22
        },
        'ghabbour_foundation': {
            id: 'ghabbour_foundation', code: 'BU-GF', name_en: 'Ghabbour Foundation', name_ar: 'مؤسسة غبور للتنمية',
            model: 'aero_fastback', primary: '#62bd6e', secondary: '#2b51a4', livery: 'star_beam', num: 23
        },
        'gb_group_companies': {
            id: 'gb_group_companies', code: 'BU-GBC', name_en: 'GB Bus / Group Companies', name_ar: 'جي بي باص وشركات المجموعة',
            model: 'transporter', primary: '#2b51a4', secondary: '#1da9de', livery: 'aero_express', num: 24
        },

        // 2. Job Families (JF) - 19 Departments
        'jf_hr': {
            id: 'jf_hr', code: 'JF-HR', name_en: 'Human Resources (Job Family)', name_ar: 'عائلة الموارد البشرية',
            model: 'gt_coupe', primary: '#4062ac', secondary: '#dcdcda', livery: 'stripes', num: 25
        },
        'jf_it_digital': {
            id: 'jf_it_digital', code: 'JF-IT', name_en: 'IT / Digital (Job Family)', name_ar: 'تكنولوجيا المعلومات والحلول الرقمية',
            model: 'formula', primary: '#1da9de', secondary: '#f78c2a', livery: 'cyber_grid', num: 26
        },
        'jf_finance_acct': {
            id: 'jf_finance_acct', code: 'JF-FIN', name_en: 'Finance and Accounting', name_ar: 'المالية والمحاسبة',
            model: 'speedster', primary: '#2b51a4', secondary: '#f79740', livery: 'dual_bars', num: 27
        },
        'jf_marketing': {
            id: 'jf_marketing', code: 'JF-MKT', name_en: 'Marketing (Job Family)', name_ar: 'التسويق والاتصال المؤسسي',
            model: 'hypercar', primary: '#f79740', secondary: '#049eda', livery: 'arrow', num: 28
        },
        'jf_sales': {
            id: 'jf_sales', code: 'JF-SALES', name_en: 'Sales', name_ar: 'المبيعات وتطوير الأعمال',
            model: 'formula', primary: '#f78c2a', secondary: '#d9d8d6', livery: 'velocity', num: 29
        },
        'jf_engineering': {
            id: 'jf_engineering', code: 'JF-ENG', name_en: 'Engineering', name_ar: 'الهندسة والعمليات الفنية',
            model: 'aero_fastback', primary: '#119aaa', secondary: '#3bae49', livery: 'gear_mesh', num: 30
        },
        'jf_production_mfg': {
            id: 'jf_production_mfg', code: 'JF-MFG', name_en: 'Production / Advanced Mfg', name_ar: 'الإنتاج والتصنيع المتقدم',
            model: 'hauler_truck', primary: '#3bae49', secondary: '#f7a454', livery: 'heavy_shield', num: 31
        },
        'jf_quality_assurance': {
            id: 'jf_quality_assurance', code: 'JF-QA', name_en: 'Quality Assurance', name_ar: 'توكيد الجودة والامتثال',
            model: 'hypercar', primary: '#4eb75b', secondary: '#2b51a4', livery: 'apex_fin', num: 32
        },
        'jf_logistics_supply': {
            id: 'jf_logistics_supply', code: 'JF-LOG', name_en: 'Logistics / Supply Chain', name_ar: 'اللوجستيات وسلاسل الإمداد',
            model: 'aero_van', primary: '#f7a454', secondary: '#7f8487', livery: 'cargo_bars', num: 33
        },
        'jf_legal': {
            id: 'jf_legal', code: 'JF-LEG', name_en: 'Legal (Job Family)', name_ar: 'الشؤون القانونية والاستشارات',
            model: 'aero_fastback', primary: '#5574b5', secondary: '#e4e4e2', livery: 'chevrons', num: 34
        },
        'jf_credit_collections': {
            id: 'jf_credit_collections', code: 'JF-CRED', name_en: 'Credit & Collections', name_ar: 'الائتمان وإدارة التحصيل',
            model: 'gt_coupe', primary: '#7f8487', secondary: '#119aaa', livery: 'side_swoop', num: 35
        },
        'jf_customer_service': {
            id: 'jf_customer_service', code: 'JF-CS', name_en: 'Customer Service / Call Center', name_ar: 'خدمة العملاء ومركز الاتصال',
            model: 'speedster', primary: '#35b2e2', secondary: '#d9d8d6', livery: 'wave_glide', num: 36
        },
        'jf_analytics_data': {
            id: 'jf_analytics_data', code: 'JF-DATA', name_en: 'Analytics & Data Science', name_ar: 'التحليلات وعلم البيانات',
            model: 'streamliner', primary: '#28a4b0', secondary: '#f9ae6a', livery: 'telemetry', num: 37
        },
        'jf_corporate_affairs': {
            id: 'jf_corporate_affairs', code: 'JF-CORP', name_en: 'Corporate Affairs', name_ar: 'الشؤون المؤسسية والعلاقات',
            model: 'aero_fastback', primary: '#6b85c0', secondary: '#4eb75b', livery: 'executive_trim', num: 38
        },
        'jf_educational_ops': {
            id: 'jf_educational_ops', code: 'JF-EDU', name_en: 'Educational Operations', name_ar: 'العمليات التعليمية والتدريبية',
            model: 'rally_suv', primary: '#75c681', secondary: '#2b51a4', livery: 'vector_speed', num: 39
        },
        'jf_healthcare_hse': {
            id: 'jf_healthcare_hse', code: 'JF-HSE', name_en: 'Healthcare Service Lines / HSE', name_ar: 'الخدمات الصحية والسلامة والصحة المهنية',
            model: 'hse_rapid', primary: '#3bae49', secondary: '#fef4ea', livery: 'cross_beacon', num: 40
        },
        'jf_project_program': {
            id: 'jf_project_program', code: 'JF-PPM', name_en: 'Project & Program Management', name_ar: 'إدارة المشاريع والبرامج',
            model: 'hypercar', primary: '#58b8c4', secondary: '#f78c2a', livery: 'arrow', num: 41
        },
        'jf_property_delivery': {
            id: 'jf_property_delivery', code: 'JF-PROP', name_en: 'Property Management / Construction', name_ar: 'إدارة الممتلكات والمشاريع الإنشائية',
            model: 'hauler_truck', primary: '#8c9093', secondary: '#f79740', livery: 'heavy_shield', num: 42
        },
        'jf_risk_asset': {
            id: 'jf_risk_asset', code: 'JF-RISK', name_en: 'Financial Risk / Asset Management', name_ar: 'إدارة المخاطر المالية وإدارة الأصول',
            model: 'speedster', primary: '#d9d8d6', secondary: '#2b51a4', livery: 'dual_bars', num: 43
        }
    };

    /**
     * Standardized Wheel Generator (Tire + Rim + Hub)
     * All fills strictly from the palette
     */
    function renderWheel(cx, cy, r, rimColor, hubColor) {
        const innerR = Math.max(3, r * 0.62);
        const hubR = Math.max(1.5, r * 0.28);
        return `
            <g class="car-wheel" transform="translate(${cx}, ${cy})">
                <circle r="${r}" fill="${TIRE_BASE}" stroke="${TIRE_INNER}" stroke-width="1.2" />
                <circle r="${innerR}" fill="${rimColor}" />
                <circle r="${innerR}" fill="none" stroke="${TIRE_BASE}" stroke-width="0.8" stroke-dasharray="2, 2" />
                <circle r="${hubR}" fill="${hubColor}" />
            </g>
        `;
    }

    /**
     * Racing Number Badge Generator
     */
    function renderNumberBadge(cx, cy, num, bgColor, textColor) {
        return `
            <g class="car-num-badge">
                <rect x="${cx - 8}" y="${cy - 6}" width="16" height="12" rx="3" fill="${bgColor}" />
                <text x="${cx}" y="${cy + 3.2}" font-family="'Cairo', sans-serif" font-size="8.5" font-weight="900" fill="${textColor}" text-anchor="middle">${num}</text>
            </g>
        `;
    }

    /**
     * 12 Distinct Aerodynamic Automotive Body Models
     */
    const MODEL_RENDERERS = {
        // 1. Formula 1 / GP Open Wheel Racer
        formula: function (p, s, num) {
            return `
                <path d="M 12 36 L 110 36 L 106 40 L 16 40 Z" fill="${CHASSIS_BASE}" />
                <path d="M 102 34 L 116 34 L 114 30 L 102 31 Z" fill="${s}" />
                <path d="M 112 28 L 117 28 L 115 36 L 112 36 Z" fill="${p}" />
                <path d="M 28 34 L 38 25 L 68 23 L 90 28 L 112 33 L 110 35 L 28 35 Z" fill="${p}" />
                <path d="M 52 23 L 60 17 L 68 17 L 72 23 Z" fill="${CANOPY_TINT}" />
                <path d="M 56 16 L 66 16 L 68 22 L 64 22 Z" fill="${s}" />
                <path d="M 38 24 L 46 13 L 56 13 L 53 23 Z" fill="${p}" />
                <path d="M 36 15 L 46 13 L 42 24 Z" fill="${s}" />
                <path d="M 14 18 L 26 18 L 24 23 L 14 23 Z" fill="${s}" />
                <path d="M 19 23 L 23 35 L 17 35 Z" fill="${CHASSIS_BASE}" />
                <path d="M 72 26 L 86 28 L 82 32 L 68 30 Z" fill="${s}" />
                <polygon points="112,32 116,33 112,34" fill="${HEADLIGHT_COLOR}" />
                <rect x="13" y="32" width="3" height="4" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(32, 35, 9.5, RIM_COLOR, p)}
                ${renderWheel(98, 35, 8.5, RIM_COLOR, s)}
                ${renderNumberBadge(78, 29, num, s, p)}
            `;
        },

        // 2. Le Mans Prototype / Hypercar
        hypercar: function (p, s, num) {
            return `
                <path d="M 10 36 L 114 36 L 110 39 L 14 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 26, 26 26, 34 35 L 78 35 C 82 27, 94 27, 100 35 L 112 35 C 112 31, 104 28, 92 28 L 76 21 C 62 16, 44 17, 34 22 L 20 25 C 14 28, 12 32, 14 35 Z" fill="${p}" />
                <path d="M 46 22 C 52 17, 68 17, 74 22 L 72 26 L 44 26 Z" fill="${CANOPY_TINT}" />
                <path d="M 22 17 L 44 17 L 38 23 L 22 23 Z" fill="${s}" />
                <path d="M 10 16 L 24 16 L 22 19 L 10 19 Z" fill="${s}" />
                <path d="M 16 19 L 18 26 L 14 26 Z" fill="${CHASSIS_BASE}" />
                <path d="M 52 27 L 70 27 L 66 33 L 48 33 Z" fill="${s}" />
                <polygon points="106,30 112,32 108,34" fill="${HEADLIGHT_COLOR}" />
                <rect x="11" y="27" width="3" height="7" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9, RIM_COLOR, p)}
                ${renderNumberBadge(58, 30, num, s, p)}
            `;
        },

        // 3. Modern Super GT Coupe
        gt_coupe: function (p, s, num) {
            return `
                <path d="M 12 36 L 112 36 L 108 39 L 16 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 27, 26 27, 34 35 L 78 35 C 82 28, 92 28, 98 35 L 110 35 C 112 32, 108 27, 92 26 L 76 18 C 66 16, 44 16, 32 23 L 20 27 C 14 29, 12 32, 14 35 Z" fill="${p}" />
                <path d="M 40 23 L 52 18 L 74 18 L 68 25 L 36 25 Z" fill="${CANOPY_TINT}" />
                <path d="M 53 18 L 56 18 L 54 25 L 51 25 Z" fill="${s}" />
                <path d="M 22 28 L 78 19 L 80 21 L 22 30 Z" fill="${s}" />
                <path d="M 10 20 L 22 20 L 20 23 L 10 23 Z" fill="${s}" />
                <path d="M 15 23 L 17 28 L 13 28 Z" fill="${CHASSIS_BASE}" />
                <polygon points="104,29 110,31 106,34" fill="${HEADLIGHT_COLOR}" />
                <rect x="12" y="28" width="3" height="6" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, p)}
                ${renderWheel(88, 35, 9, RIM_COLOR, s)}
                ${renderNumberBadge(58, 29, num, s, p)}
            `;
        },

        // 4. Futuristic Cyber Streamliner
        streamliner: function (p, s, num) {
            return `
                <path d="M 10 36 L 114 36 L 112 39 L 14 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 12 35 C 14 26, 26 26, 32 35 L 78 35 C 82 28, 94 28, 100 35 L 114 35 C 114 30, 96 23, 76 18 C 54 14, 30 18, 18 26 L 12 35 Z" fill="${p}" />
                <path d="M 40 22 C 54 16, 70 16, 80 20 L 74 25 L 36 25 Z" fill="${CANOPY_TINT}" />
                <path d="M 32 30 Q 60 22 92 31 L 90 33 Q 60 25 32 32 Z" fill="${s}" />
                <path d="M 18 28 L 36 28 L 32 35 L 20 35 Z" fill="${s}" opacity="0.85" />
                <polygon points="108,28 114,30 110,32" fill="${HEADLIGHT_COLOR}" />
                <rect x="10" y="28" width="3" height="6" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 8.5, RIM_COLOR, s)}
                ${renderWheel(88, 35, 8.5, RIM_COLOR, p)}
                ${renderNumberBadge(58, 28, num, s, p)}
            `;
        },

        // 5. Open-Cockpit Speedster / Twin-Nacelle Roadster
        speedster: function (p, s, num) {
            return `
                <path d="M 12 36 L 110 36 L 106 39 L 16 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 27, 26 27, 34 35 L 78 35 C 82 28, 92 28, 98 35 L 110 35 C 112 31, 106 27, 90 26 L 72 23 L 64 26 L 36 26 C 30 22, 22 23, 18 27 L 14 35 Z" fill="${p}" />
                <path d="M 40 26 C 42 20, 48 20, 50 26 Z" fill="${s}" />
                <path d="M 48 26 C 50 19, 56 19, 58 26 Z" fill="${p}" />
                <path d="M 64 23 L 74 23 L 70 26 L 62 26 Z" fill="${CANOPY_TINT}" />
                <rect x="74" y="26" width="18" height="3" rx="1" fill="${s}" />
                <rect x="74" y="30" width="14" height="2" rx="1" fill="${s}" />
                <polygon points="104,28 110,30 106,32" fill="${HEADLIGHT_COLOR}" />
                <rect x="12" y="29" width="3" height="5" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, p)}
                ${renderWheel(88, 35, 9, RIM_COLOR, s)}
                ${renderNumberBadge(56, 30, num, s, p)}
            `;
        },

        // 6. Executive Aero Fastback
        aero_fastback: function (p, s, num) {
            return `
                <path d="M 12 36 L 112 36 L 108 39 L 16 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 27, 26 27, 34 35 L 78 35 C 82 28, 92 28, 98 35 L 110 35 C 112 33, 108 28, 92 27 L 76 18 C 66 16, 42 16, 28 23 L 18 28 C 14 30, 12 33, 14 35 Z" fill="${p}" />
                <path d="M 34 23 L 48 18 L 74 18 L 68 25 L 30 25 Z" fill="${CANOPY_TINT}" />
                <path d="M 52 18 L 55 18 L 53 25 L 50 25 Z" fill="${s}" />
                <path d="M 32 25 L 72 25 L 74 26 L 30 26 Z" fill="${s}" />
                <path d="M 36 31 Q 62 27 82 31 L 80 33 Q 62 29 36 33 Z" fill="${s}" />
                <path d="M 12 27 L 18 26 L 16 29 L 12 29 Z" fill="${s}" />
                <polygon points="104,29 110,31 106,33" fill="${HEADLIGHT_COLOR}" />
                <rect x="12" y="29" width="3" height="5" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9, RIM_COLOR, p)}
                ${renderNumberBadge(58, 29, num, s, p)}
            `;
        },

        // 7. Dakar Rally 4x4 / Raid SUV
        rally_suv: function (p, s, num) {
            return `
                <path d="M 10 37 L 112 37 L 106 40 L 14 40 Z" fill="${CHASSIS_BASE}" />
                <path d="M 12 35 C 16 26, 28 26, 34 35 L 76 35 C 80 26, 92 26, 98 35 L 110 35 C 112 30, 106 25, 90 25 L 76 15 C 68 15, 34 15, 26 21 L 16 26 C 12 28, 10 32, 12 35 Z" fill="${p}" />
                <path d="M 32 21 L 46 17 L 72 17 L 70 24 L 28 24 Z" fill="${CANOPY_TINT}" />
                <rect x="34" y="13" width="36" height="2" rx="1" fill="${s}" />
                <path d="M 64 12 L 72 12 L 70 15 L 62 15 Z" fill="${s}" />
                <path d="M 104 31 L 112 31 L 110 36 L 104 36 Z" fill="${s}" />
                <path d="M 18 33 C 20 27, 28 27, 30 33 Z" fill="none" stroke="${s}" stroke-width="2" />
                <path d="M 80 33 C 82 27, 90 27, 92 33 Z" fill="none" stroke="${s}" stroke-width="2" />
                <circle cx="106" cy="30" r="2.5" fill="${HEADLIGHT_COLOR}" />
                <rect x="11" y="26" width="3" height="7" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(24, 35, 10, RIM_COLOR, s)}
                ${renderWheel(86, 35, 10, RIM_COLOR, p)}
                ${renderNumberBadge(54, 29, num, s, p)}
            `;
        },

        // 8. Dakar Racing Super Hauler / Heavy Truck
        hauler_truck: function (p, s, num) {
            return `
                <path d="M 10 37 L 114 37 L 110 41 L 14 41 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 36 C 18 27, 28 27, 34 36 L 74 36 C 78 27, 90 27, 96 36 L 112 36 L 112 21 C 112 18, 106 14, 96 14 L 68 14 L 64 25 L 14 25 Z" fill="${p}" />
                <path d="M 102 24 L 111 24 L 111 34 L 102 34 Z" fill="${s}" />
                <line x1="104" y1="27" x2="109" y2="27" stroke="${TIRE_BASE}" stroke-width="1" />
                <line x1="104" y1="30" x2="109" y2="30" stroke="${TIRE_BASE}" stroke-width="1" />
                <path d="M 72 22 L 86 16 L 102 16 L 98 22 Z" fill="${CANOPY_TINT}" />
                <rect x="84" y="13" width="22" height="2.5" rx="1" fill="${s}" />
                <rect x="65" y="7" width="3.5" height="18" rx="1" fill="${s}" />
                <polygon points="65,7 69,7 67,5" fill="${TIRE_BASE}" />
                <rect x="16" y="27" width="46" height="5" rx="1" fill="${s}" />
                <rect x="108" y="28" width="3" height="2.5" fill="${HEADLIGHT_COLOR}" />
                <rect x="108" y="32" width="3" height="2.5" fill="${HEADLIGHT_COLOR}" />
                ${renderWheel(24, 35, 9.5, RIM_COLOR, s)}
                ${renderWheel(44, 35, 9.5, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9.5, RIM_COLOR, p)}
                ${renderNumberBadge(80, 29, num, s, p)}
            `;
        },

        // 9. Aerodynamic Racing Cargo Van / Speed Hauler
        aero_van: function (p, s, num) {
            return `
                <path d="M 12 36 L 112 36 L 108 39 L 16 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 27, 26 27, 34 35 L 78 35 C 82 28, 92 28, 98 35 L 110 35 C 112 32, 108 26, 98 24 L 84 15 L 20 15 C 16 15, 12 19, 12 25 L 14 35 Z" fill="${p}" />
                <path d="M 68 23 L 80 17 L 94 17 L 90 23 Z" fill="${CANOPY_TINT}" />
                <path d="M 22 20 L 62 20 L 56 24 L 20 24 Z" fill="${s}" />
                <path d="M 26 27 L 66 27 L 62 31 L 24 31 Z" fill="${s}" />
                <rect x="12" y="14" width="14" height="2" rx="1" fill="${s}" />
                <polygon points="104,28 110,30 106,33" fill="${HEADLIGHT_COLOR}" />
                <rect x="12" y="20" width="3" height="12" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9, RIM_COLOR, p)}
                ${renderNumberBadge(46, 29, num, s, p)}
            `;
        },

        // 10. Sport 3-Wheeler / Trike Racer
        trike_racer: function (p, s, num) {
            return `
                <path d="M 14 36 L 108 36 L 104 39 L 18 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 18 35 L 24 33 C 32 33, 40 26, 54 26 L 82 26 C 96 26, 104 31, 108 35 L 18 35 Z" fill="${p}" />
                <path d="M 32 32 L 80 32 L 84 30 L 32 30 Z" fill="${s}" />
                <path d="M 52 26 L 62 19 L 68 19 L 66 26 Z" fill="${CANOPY_TINT}" />
                <path d="M 44 26 C 46 20, 50 20, 52 26 Z" fill="${s}" />
                <polygon points="70,27 82,27 76,32 64,32" fill="${s}" />
                <path d="M 16 28 L 26 28 L 24 35 L 16 35 Z" fill="${p}" />
                ${renderWheel(20, 35, 8, RIM_COLOR, s)}
                ${renderWheel(94, 35, 9.5, RIM_COLOR, p)}
                <circle cx="106" cy="30" r="3" fill="${HEADLIGHT_COLOR}" stroke="${p}" stroke-width="1" />
                <rect x="14" y="30" width="3" height="4" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderNumberBadge(58, 29, num, s, p)}
            `;
        },

        // 11. GB Bus / High-Speed Racing Coach Transporter
        transporter: function (p, s, num) {
            return `
                <path d="M 10 37 L 114 37 L 110 40 L 14 40 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 36 C 16 27, 26 27, 32 36 L 78 36 C 82 28, 92 28, 98 36 L 112 36 C 114 33, 112 21, 104 16 L 22 16 C 16 16, 12 21, 12 28 L 14 36 Z" fill="${p}" />
                <path d="M 24 19 L 102 19 L 100 24 L 22 24 Z" fill="${CANOPY_TINT}" />
                <line x1="42" y1="19" x2="42" y2="24" stroke="${p}" stroke-width="1.5" />
                <line x1="62" y1="19" x2="62" y2="24" stroke="${p}" stroke-width="1.5" />
                <line x1="82" y1="19" x2="82" y2="24" stroke="${p}" stroke-width="1.5" />
                <path d="M 16 27 L 76 27 Q 96 27 106 31 L 104 33 Q 94 29 16 29 Z" fill="${s}" />
                <rect x="106" y="32" width="6" height="3" rx="1" fill="${s}" />
                <rect x="110" y="27" width="2.5" height="2" fill="${HEADLIGHT_COLOR}" />
                <rect x="110" y="30" width="2.5" height="2" fill="${HEADLIGHT_COLOR}" />
                ${renderWheel(24, 35, 9, RIM_COLOR, s)}
                ${renderWheel(40, 35, 9, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9, RIM_COLOR, p)}
                ${renderNumberBadge(62, 31, num, s, p)}
            `;
        },

        // 12. First Responder / HSE Rapid Response Interceptor
        hse_rapid: function (p, s, num) {
            return `
                <path d="M 12 36 L 112 36 L 108 39 L 16 39 Z" fill="${CHASSIS_BASE}" />
                <path d="M 14 35 C 16 27, 26 27, 34 35 L 78 35 C 82 28, 92 28, 98 35 L 110 35 C 112 32, 108 26, 94 25 L 78 17 C 68 15, 38 15, 28 22 L 18 26 C 14 29, 12 32, 14 35 Z" fill="${p}" />
                <path d="M 36 22 L 50 17 L 74 17 L 68 24 L 32 24 Z" fill="${CANOPY_TINT}" />
                <rect x="48" y="13" width="16" height="3" rx="1.5" fill="${s}" />
                <rect x="53" y="12" width="6" height="2" rx="1" fill="${TAILLIGHT_COLOR}" />
                <g transform="translate(68, 29)">
                    <rect x="-4" y="-1.5" width="8" height="3" rx="0.5" fill="${s}" />
                    <rect x="-1.5" y="-4" width="3" height="8" rx="0.5" fill="${s}" />
                </g>
                <polygon points="18,28 26,28 22,33 14,33" fill="${s}" />
                <polygon points="28,28 36,28 32,33 24,33" fill="${s}" />
                <polygon points="104,28 110,30 106,33" fill="${HEADLIGHT_COLOR}" />
                <rect x="12" y="28" width="3" height="6" rx="1" fill="${TAILLIGHT_COLOR}" />
                ${renderWheel(26, 35, 9, RIM_COLOR, s)}
                ${renderWheel(88, 35, 9, RIM_COLOR, p)}
                ${renderNumberBadge(46, 29, num, s, p)}
            `;
        }
    };

    /**
     * Resolves department data from ID or object
     */
    function resolveDepartmentData(dept) {
        if (!dept) {
            return DEPARTMENTS_CARS['hr'];
        }

        let deptId = '';
        if (typeof dept === 'string') {
            deptId = dept.toLowerCase().trim();
        } else if (typeof dept === 'object') {
            deptId = (dept.id || dept.department_id || dept.code || '').toLowerCase().trim();
        }

        if (DEPARTMENTS_CARS[deptId]) {
            const base = DEPARTMENTS_CARS[deptId];
            if (typeof dept === 'object') {
                return {
                    id: base.id,
                    code: dept.code || base.code,
                    name_en: dept.name_en || dept.name || base.name_en,
                    name_ar: dept.name_ar || base.name_ar,
                    model: dept.car_model || base.model,
                    primary: dept.color || base.primary,
                    secondary: dept.secondary_color || base.secondary,
                    livery: dept.livery_style || base.livery,
                    num: parseInt(dept.racing_num || base.num, 10)
                };
            }
            return base;
        }

        for (const key in DEPARTMENTS_CARS) {
            const item = DEPARTMENTS_CARS[key];
            if (item.code.toLowerCase() === deptId) {
                return item;
            }
        }

        return DEPARTMENTS_CARS['hr'];
    }

    /**
     * Main Public Generator: Returns exact vector SVG markup for a department's car
     */
    function getDepartmentCarSvg(dept, options) {
        const opts = Object.assign({
            width: 120,
            height: 48,
            className: 'gb-dedicated-car-svg',
            showGlow: true,
            idPrefix: 'car-' + Math.random().toString(36).substr(2, 6)
        }, options || {});

        const data = resolveDepartmentData(dept);
        const renderer = MODEL_RENDERERS[data.model] || MODEL_RENDERERS['gt_coupe'];

        const primaryColor = PALETTE_SET.has(data.primary.toLowerCase()) ? data.primary : '#2b51a4';
        const secondaryColor = PALETTE_SET.has(data.secondary.toLowerCase()) ? data.secondary : '#d9d8d6';
        const racingNum = data.num || 1;

        const glowFilter = opts.showGlow ? `
            <defs>
                <filter id="${opts.idPrefix}-glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="${primaryColor}" flood-opacity="0.45"/>
                </filter>
            </defs>
        ` : '';

        const filterAttr = opts.showGlow ? `filter="url(#${opts.idPrefix}-glow)"` : '';

        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 48" width="${opts.width}" height="${opts.height}" class="${opts.className}" data-dept-id="${data.id}" data-dept-num="${racingNum}" data-car-model="${data.model}" role="img" aria-label="${data.name_en} Dedicated Racing Car #${racingNum}">
                ${glowFilter}
                <g class="car-assembly" ${filterAttr}>
                    ${renderer(primaryColor, secondaryColor, racingNum, data.livery)}
                </g>
            </svg>
        `.trim();
    }

    /**
     * Returns an inline HTML snippet suitable for small badges, leaderboards, and icons
     */
    function getDepartmentCarBadge(dept, size = 32) {
        const h = Math.round(size * 0.44);
        return `<span class="car-badge-icon" style="display:inline-flex;align-items:center;vertical-align:middle;">${getDepartmentCarSvg(dept, { width: size, height: h, showGlow: false })}</span>`;
    }

    return {
        getDepartmentCarSvg: getDepartmentCarSvg,
        getDepartmentCarBadge: getDepartmentCarBadge,
        resolveDepartmentData: resolveDepartmentData,
        getAllDepartmentsCars: function () { return DEPARTMENTS_CARS; },
        PALETTE_SET: PALETTE_SET
    };
}));
