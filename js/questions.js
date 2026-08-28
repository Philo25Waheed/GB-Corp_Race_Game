/**
 * Pre-populated Corporate & General Knowledge Challenge Questions
 * Full Bilingual English & Arabic Edition with Answer Keys
 */

const QuestionsManager = (function () {
    const sampleQuestions = [
        {
            id: 1,
            category: "General Knowledge | معلومات عامة",
            categoryEn: "General Knowledge",
            categoryAr: "معلومات عامة",
            questionEn: "What is the official capital city of Egypt?",
            questionAr: "ما هي العاصمة الرسمية لجمهورية مصر العربية؟",
            options: [
                "Alexandria | الإسكندرية",
                "Cairo | القاهرة",
                "Giza | الجيزة",
                "Luxor | الأقصر"
            ],
            correctAnswer: 1 // Cairo (B)
        },
        {
            id: 2,
            category: "Company & HR | الموارد البشرية والشركة",
            categoryEn: "Company & HR",
            categoryAr: "الموارد البشرية والشركة",
            questionEn: "Which department is responsible for managing organizational talent and hiring?",
            questionAr: "أي قسم في المؤسسة مسؤول عن استقطاب الكفاءات والتوظيف وإدارة المواهب؟",
            options: [
                "Operations | العمليات",
                "Finance | الإدارة المالية",
                "Human Resources (HR) | الموارد البشرية",
                "IT Support | الدعم الفني"
            ],
            correctAnswer: 2 // HR (C)
        },
        {
            id: 3,
            category: "Technology | تكنولوجيا المعلومات",
            categoryEn: "Technology",
            categoryAr: "تكنولوجيا المعلومات",
            questionEn: "What does the abbreviation 'HTML' stand for in web development?",
            questionAr: "إلى ماذا يرمز الاختصار 'HTML' في تطوير المواقع وتطبيقات الويب؟",
            options: [
                "HyperText Markup Language | لغة ترميز النص الفائق",
                "HighTech Machine Learning | تعلم الآلة عالي التقنية",
                "Hyper Transfer Main Logic | منطق النقل الرئيسي الفائق",
                "Home Tool Markup Language | لغة أدوات الترميز المنزلية"
            ],
            correctAnswer: 0 // HyperText Markup Language (A)
        },
        {
            id: 4,
            category: "Business & Finance | الأعمال والمالية",
            categoryEn: "Business & Finance",
            categoryAr: "الأعمال والمالية",
            questionEn: "What does the financial metric acronym 'ROI' stand for?",
            questionAr: "ماذا يعني الاختصار المالي الشهير 'ROI' في قياس نجاح الاستثمارات؟",
            options: [
                "Return On Investment | العائد على الاستثمار",
                "Rate Of Inflation | معدل التضخم السنوي",
                "Risk Of Insolvency | مخاطر التعثر المالي",
                "Revenue On Income | نسبة الإيرادات إلى الدخل"
            ],
            correctAnswer: 0 // Return On Investment (A)
        },
        {
            id: 5,
            category: "Marketing Strategy | استراتيجية التسويق",
            categoryEn: "Marketing Strategy",
            categoryAr: "استراتيجية التسويق",
            questionEn: "Which of the following represents the classic '4 Ps' of Marketing?",
            questionAr: "أي مما يلي يمثل العناصر الأربعة الأساسية للمزيج التسويقي (4Ps)؟",
            options: [
                "People, Process, Profit, Product | الناس، العمليات، الربح، المنتج",
                "Product, Price, Place, Promotion | المنتج، السعر، المكان، الترويج",
                "Plan, Performance, Pricing, Publicity | الخطة، الأداء، التسعير، الدعاية",
                "Purpose, Positioning, Partner, Power | الهدف، التمركز، الشريك، القوة"
            ],
            correctAnswer: 1 // Product, Price, Place, Promotion (B)
        },
        {
            id: 6,
            category: "Operations & Quality | العمليات والجودة",
            categoryEn: "Operations & Quality",
            categoryAr: "العمليات والجودة",
            questionEn: "What Japanese management philosophy stands for continuous incremental improvement?",
            questionAr: "ما هي الفلسفة الإدارية اليابانية التي تعني التحسين والتطوير المستمر للعمليات؟",
            options: [
                "Six Sigma | ستة سيجما",
                "Kaizen | كايزن",
                "Agile | أجايل الرشيقة",
                "Scrum | سكرام"
            ],
            correctAnswer: 1 // Kaizen (B)
        },
        {
            id: 7,
            category: "Cybersecurity & IT | الأمن السيبراني",
            categoryEn: "Cybersecurity & IT",
            categoryAr: "الأمن السيبراني",
            questionEn: "What is the primary role of a Firewall in corporate networks?",
            questionAr: "ما هي الوظيفة الأساسية لجدار الحماية (Firewall) في شبكات الشركات؟",
            options: [
                "Boost internet download speed | تسريع اتصال الإنترنت",
                "Filter and block unauthorized traffic | تصفية وحظر الدخول غير المصرح به",
                "Store encrypted database backups | تخزين النسخ الاحتياطية",
                "Cool down server hardware | تبريد خوادم الشبكة"
            ],
            correctAnswer: 1 // Filter and block unauthorized traffic (B)
        },
        {
            id: 8,
            category: "Science & Innovation | العلوم والابتكار",
            categoryEn: "Science & Innovation",
            categoryAr: "العلوم والابتكار",
            questionEn: "Which gas makes up the largest percentage (~78%) of Earth's atmosphere?",
            questionAr: "أي الغازات التالية يشكل النسبة الأكبر (حوالي 78٪) من الغلاف الجوي للأرض؟",
            options: [
                "Oxygen | الأكسجين",
                "Carbon Dioxide | ثاني أكسيد الكربون",
                "Nitrogen | النيتروجين",
                "Argon | الأرجون"
            ],
            correctAnswer: 2 // Nitrogen (C)
        },
        {
            id: 9,
            category: "Team Leadership | القيادة والعمل الجماعي",
            categoryEn: "Team Leadership",
            categoryAr: "القيادة والعمل الجماعي",
            questionEn: "Which leadership style focuses on inspiring teams through empathy and vision?",
            questionAr: "أي أسلوب قيادي يركز على إلهام وتحفيز أعضاء الفريق من خلال الرؤية والتعاطف؟",
            options: [
                "Autocratic | الأوتوقراطي / السلطوي",
                "Laissez-faire | الحر / المتساهل",
                "Transformational | القيادة التحويلية الملهمة",
                "Micromanagement | الإدارة التفصيلية الدقيقة"
            ],
            correctAnswer: 2 // Transformational (C)
        },
        {
            id: 10,
            category: "Summer Grand Finale | التحدي الصيفي الختامي",
            categoryEn: "Summer Grand Finale",
            categoryAr: "التحدي الصيفي الختامي",
            questionEn: "Which champion department is powering forward to win the Summer Road Trip Race?",
            questionAr: "أي قسم بطل سينطلق بأقصى سرعة ليتوج بطلاً لسباق رحلة الصيف؟",
            options: [
                "IT Team 🏎️ | فريق تكنولوجيا المعلومات",
                "Finance Team 🚙 | فريق الإدارة المالية",
                "Marketing Team 🏎️ | فريق التسويق",
                "HR & Operations 🚕🚗 | الموارد البشرية والعمليات"
            ],
            correctAnswer: 0 // IT (A) (or any winning team)
        }
    ];

    let questions = sampleQuestions;

    return {
        getQuestions: function () {
            return questions;
        },

        getQuestionCount: function () {
            return questions.length;
        },

        getQuestionByIndex: function (index) {
            if (index < 0 || index >= questions.length) return null;
            return questions[index];
        },

        checkAnswer: function (questionIndex, selectedOptionIndex) {
            const q = this.getQuestionByIndex(questionIndex);
            if (!q) return false;
            return q.correctAnswer === selectedOptionIndex;
        }
    };
})();
