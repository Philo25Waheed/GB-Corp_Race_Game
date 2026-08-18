/**
 * Pre-populated Corporate & General Knowledge Challenge Questions with Answer Keys
 */

const QuestionsManager = (function () {
    const sampleQuestions = [
        {
            id: 1,
            category: "General Knowledge",
            question: "What is the official capital city of Egypt?",
            options: ["Alexandria", "Cairo", "Giza", "Luxor"],
            correctAnswer: 1 // Cairo (B)
        },
        {
            id: 2,
            category: "Company Trivia",
            question: "Which department is responsible for managing organizational talent and hiring?",
            options: ["Operations", "Finance", "HR", "IT"],
            correctAnswer: 2 // HR (C)
        },
        {
            id: 3,
            category: "Technology",
            question: "What does the abbreviation 'HTML' stand for in web application development?",
            options: ["HyperText Markup Language", "HighTech Machine Learning", "Hyper Transfer Main Logic", "Home Tool Markup Language"],
            correctAnswer: 0 // HyperText Markup Language (A)
        },
        {
            id: 4,
            category: "Business & Finance",
            question: "What does the financial acronym 'ROI' stand for?",
            options: ["Return On Investment", "Rate Of Inflation", "Risk Of Insolvency", "Revenue On Income"],
            correctAnswer: 0 // Return On Investment (A)
        },
        {
            id: 5,
            category: "Marketing Strategy",
            question: "Which of the following is commonly known as the 4 Ps of Marketing?",
            options: ["People, Process, Profit, Product", "Product, Price, Place, Promotion", "Plan, Performance, Pricing, Publicity", "Purpose, Positioning, Partner, Power"],
            correctAnswer: 1 // Product, Price, Place, Promotion (B)
        },
        {
            id: 6,
            category: "Operations & Supply Chain",
            question: "What management term describes continuous incremental improvement in business processes?",
            options: ["Six Sigma", "Kaizen", "Agile", "Scrum"],
            correctAnswer: 1 // Kaizen (B)
        },
        {
            id: 7,
            category: "Cybersecurity",
            question: "What is the primary purpose of Firewall software in corporate networks?",
            options: ["Speed up internet connection", "Filter and block unauthorized traffic", "Store backup database files", "Encrypt email messages"],
            correctAnswer: 1 // Filter and block unauthorized traffic (B)
        },
        {
            id: 8,
            category: "Science & Innovation",
            question: "Which elemental gas makes up the largest percentage of Earth's atmosphere?",
            options: ["Oxygen", "Carbon Dioxide", "Nitrogen", "Argon"],
            correctAnswer: 2 // Nitrogen (C)
        },
        {
            id: 9,
            category: "Team Leadership",
            question: "Which communication style focuses on active listening, empathy, and constructive feedback?",
            options: ["Autocratic", "Laissez-faire", "Transformational", "Micromanagement"],
            correctAnswer: 2 // Transformational (C)
        },
        {
            id: 10,
            category: "Corporate Grand Finale",
            question: "Which department is going to win tonight's Corporate Car Racing Championship?",
            options: ["IT 🏎️", "Finance 🚙", "Marketing 🏎️", "HR / Operations 🚕🚗"],
            correctAnswer: 0 // IT (A)
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
