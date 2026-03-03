// js/quiz.js - Updated for "See Leaderboard" button and filter

const originalQuizData = {
  kinder: [
    { question: "What color is the sky?", options: ["Blue", "Green", "Red"], answer: 0 },
    { question: "Which animal says 'meow'?", options: ["Dog", "Cat", "Cow"], answer: 1 },
    { question: "What is 1 + 1?", options: ["1", "2", "3"], answer: 1 },
    { question: "Which fruit is red?", options: ["Banana", "Apple", "Grapes"], answer: 1 },
    { question: "What is the color of the grass?", options: ["Red", "Yellow", "Green"], answer: 2 },
    { question: "Which animal can fly?", options: ["Dog", "Cat", "Bird"], answer: 2 },
    { question: "How many legs does a dog have?", options: ["2", "4", "6"], answer: 1 },
    { question: "Which one is a vegetable?", options: ["Apple", "Carrot", "Banana"], answer: 1 },
    { question: "What is the color of a banana?", options: ["Red", "Yellow", "Green"], answer: 1 },
    { question: "What sound does a cat make?", options: ["Meow", "Bark", "Moo"], answer: 0 }
  ],
  grade1: [
    { question: "What is 2 + 2?", options: ["3", "4", "5"], answer: 1 },
    { question: "What day comes after Monday?", options: ["Tuesday", "Friday", "Sunday"], answer: 0 },
    { question: "What is 5 + 3?", options: ["8", "6", "9"], answer: 0 },
    { question: "Which planet is closest to the Sun?", options: ["Mars", "Earth", "Mercury"], answer: 2 },
    { question: "How many continents are there?", options: ["5", "6", "7"], answer: 2 },
    { question: "What color is the sun?", options: ["Red", "Yellow", "Blue"], answer: 1 },
    { question: "What is 10 - 3?", options: ["7", "5", "9"], answer: 0 },
    { question: "Which month is the first month of the year?", options: ["January", "February", "March"], answer: 0 },
    { question: "Which animal is known as the king of the jungle?", options: ["Lion", "Elephant", "Tiger"], answer: 0 },
    { question: "What do you call a baby cat?", options: ["Kitten", "Puppy", "Cub"], answer: 0 }
  ],
  grade2: [
    { question: "How many days are in a week?", options: ["5", "6", "7"], answer: 2 },
    { question: "What is the color of an orange?", options: ["Orange", "Blue", "Purple"], answer: 0 },
    { question: "Which of these is a primary color?", options: ["Green", "Red", "Purple"], answer: 1 }
  ],
  grade3: [
    { question: "What is 7 × 6?", options: ["42", "36", "48"], answer: 0 },
    { question: "Which part of a plant makes food?", options: ["Root", "Leaf", "Stem"], answer: 1 },
    { question: "Who wrote 'Jack and the Beanstalk'?", options: ["Unknown", "Dr. Seuss", "J.K. Rowling"], answer: 0 },
    { question: "Which is a mammal?", options: ["Frog", "Shark", "Dolphin"], answer: 2 },
    { question: "What planet do we live on?", options: ["Mars", "Earth", "Venus"], answer: 1 },
    { question: "What is 100 divided by 10?", options: ["10", "5", "20"], answer: 0 },
    { question: "How many hours are in a day?", options: ["24", "12", "30"], answer: 0 },
    { question: "Which shape has four equal sides?", options: ["Triangle", "Rectangle", "Square"], answer: 2 },
    { question: "What do bees produce?", options: ["Milk", "Honey", "Juice"], answer: 1 },
    { question: "Which is the largest land animal?", options: ["Lion", "Elephant", "Tiger"], answer: 1 }
  ],
  grade4: [
    { question: "What is the capital of the Philippines?", options: ["Manila", "Cebu", "Davao"], answer: 0 },
    { question: "What is 25 × 4?", options: ["100", "75", "50"], answer: 0 },
    { question: "Who invented the light bulb?", options: ["Albert Einstein", "Thomas Edison", "Isaac Newton"], answer: 1 },
    { question: "Which gas do we breathe in to survive?", options: ["Oxygen", "Carbon Dioxide", "Nitrogen"], answer: 0 },
    { question: "How many legs does a spider have?", options: ["6", "8", "10"], answer: 1 },
    { question: "What is the opposite of 'heavy'?", options: ["Small", "Light", "Big"], answer: 1 },
    { question: "Which is a natural satellite of Earth?", options: ["Mars", "Sun", "Moon"], answer: 2 },
    { question: "How many continents are there?", options: ["5", "6", "7"], answer: 2 },
    { question: "What do you call a polygon with 5 sides?", options: ["Hexagon", "Pentagon", "Octagon"], answer: 1 },
    { question: "Which body system pumps blood?", options: ["Digestive", "Circulatory", "Respiratory"], answer: 1 }
  ],
  grade5: [
    { question: "What is 144 ÷ 12?", options: ["12", "10", "14"], answer: 0 },
    { question: "Who discovered gravity?", options: ["Newton", "Galileo", "Einstein"], answer: 0 },
    { question: "What is H2O?", options: ["Salt", "Water", "Oxygen"], answer: 1 },
    { question: "Which is the longest river in the world?", options: ["Amazon", "Nile", "Yangtze"], answer: 1 },
    { question: "What is the main function of the lungs?", options: ["Digestion", "Breathing", "Circulation"], answer: 1 },
    { question: "Which organ pumps the blood?", options: ["Brain", "Heart", "Liver"], answer: 1 },
    { question: "What is 3/4 of 100?", options: ["25", "50", "75"], answer: 2 },
    { question: "Which country is known as the Land of the Rising Sun?", options: ["China", "Japan", "Thailand"], answer: 1 },
    { question: "How many bones are in the human body?", options: ["206", "208", "210"], answer: 0 },
    { question: "Which planet is known for rings?", options: ["Earth", "Saturn", "Venus"], answer: 1 }
  ],
  grade6: [
    { question: "What is the square root of 81?", options: ["7", "8", "9"], answer: 2 },
    { question: "Which element has the chemical symbol 'O'?", options: ["Oxygen", "Gold", "Osmium"], answer: 0 },
    { question: "What is the process of water turning into vapor?", options: ["Condensation", "Evaporation", "Precipitation"], answer: 1 },
    { question: "Who is the national hero of the Philippines?", options: ["Andres Bonifacio", "Jose Rizal", "Emilio Aguinaldo"], answer: 1 },
    { question: "Which part of the brain controls movement?", options: ["Cerebellum", "Cerebrum", "Medulla"], answer: 0 },
    { question: "How many degrees in a right angle?", options: ["90", "180", "45"], answer: 0 },
    { question: "Which continent is the largest?", options: ["Africa", "Asia", "Antarctica"], answer: 1 },
    { question: "What is the boiling point of water?", options: ["100°C", "90°C", "80°C"], answer: 0 },
    { question: "What is the next prime number after 7?", options: ["8", "9", "11"], answer: 2 },
    { question: "Which layer of the Earth do we live on?", options: ["Core", "Mantle", "Crust"], answer: 2 }
  ]
    ,
    grade7: [
        { question: "What is 15 × 6?", options: ["90", "85", "95"], answer: 0 },
        { question: "Which organ helps with digestion by producing bile?", options: ["Liver", "Heart", "Lungs"], answer: 0 },
        { question: "What is the main gas found in the air we breathe?", options: ["Oxygen", "Nitrogen", "Carbon Dioxide"], answer: 1 },
        { question: "Which is a prime number?", options: ["21", "23", "25"], answer: 1 },
        { question: "Who wrote the play 'Romeo and Juliet'?", options: ["Charles Dickens", "William Shakespeare", "Mark Twain"], answer: 1 },
        { question: "What is the opposite of 'ascend'?", options: ["Descend", "Improve", "Increase"], answer: 0 },
        { question: "Which planet is known as the Red Planet?", options: ["Venus", "Mars", "Jupiter"], answer: 1 },
        { question: "What force pulls objects toward Earth?", options: ["Friction", "Gravity", "Magnetism"], answer: 1 },
        { question: "Which device measures temperature?", options: ["Barometer", "Thermometer", "Ammeter"], answer: 1 },
        { question: "What is the boiling point of water in Fahrenheit?", options: ["212°F", "100°F", "32°F"], answer: 0 }
    ],
    grade8: [
        { question: "What is the value of 2^5?", options: ["16", "32", "64"], answer: 1 },
        { question: "Which part of the cell contains genetic material?", options: ["Nucleus", "Mitochondria", "Ribosome"], answer: 0 },
        { question: "What is the chemical formula for table salt?", options: ["NaCl", "H2O", "CO2"], answer: 0 },
        { question: "Which gas is produced by plants during photosynthesis?", options: ["Oxygen", "Nitrogen", "Methane"], answer: 0 },
        { question: "What is the term for animals that eat both plants and meat?", options: ["Herbivores", "Carnivores", "Omnivores"], answer: 2 },
        { question: "Which is an example of physical change?", options: ["Rusting", "Melting", "Burning"], answer: 1 },
        { question: "What is the perimeter of a rectangle with sides 5 and 8?", options: ["26", "40", "13"], answer: 0 },
        { question: "Who proposed the theory of relativity?", options: ["Isaac Newton", "Albert Einstein", "Galileo"], answer: 1 },
        { question: "Which instrument is used to view stars and planets?", options: ["Microscope", "Telescope", "Thermometer"], answer: 1 },
        { question: "What is the unit of electrical resistance?", options: ["Volt", "Ohm", "Ampere"], answer: 1 }
    ],
    grade9: [
        { question: "What is the quadratic formula used to solve ax^2+bx+c=0?", options: ["(-b±√(b^2-4ac))/2a", "(b±√(b^2+4ac))/2a", "(b^2-4ac)/2a"], answer: 0 },
        { question: "Which organelle is the powerhouse of the cell?", options: ["Chloroplast", "Mitochondria", "Golgi apparatus"], answer: 1 },
        { question: "What is the chemical symbol for sodium?", options: ["S", "Na", "N"], answer: 1 },
        { question: "Which process converts glucose to energy in cells?", options: ["Photosynthesis", "Respiration", "Fermentation"], answer: 1 },
        { question: "Who wrote 'Noli Me Tangere'?", options: ["Nick Joaquin", "Jose Rizal", "Lualhati Bautista"], answer: 1 },
        { question: "What is the slope of a horizontal line?", options: ["0", "1", "Undefined"], answer: 0 },
        { question: "Which gas is a greenhouse gas contributing to global warming?", options: ["Oxygen", "Carbon Dioxide", "Helium"], answer: 1 },
        { question: "What is the value of π (pi) approximated?", options: ["2.14", "3.14", "4.14"], answer: 1 },
        { question: "Which historical period came before the Industrial Revolution?", options: ["Middle Ages", "Renaissance", "Modern Era"], answer: 0 },
        { question: "What is the main component of natural gas?", options: ["Methane", "Propane", "Butane"], answer: 0 }
    ],
    grade10: [
        { question: "What is the derivative of x^2?", options: ["x", "2x", "x^2"], answer: 1 },
        { question: "Which metal is liquid at room temperature?", options: ["Mercury", "Iron", "Gold"], answer: 0 },
        { question: "What is the chemical formula for carbon dioxide?", options: ["CO", "CO2", "C2O"], answer: 1 },
        { question: "Which branch of biology studies heredity?", options: ["Ecology", "Genetics", "Anatomy"], answer: 1 },
        { question: "What is an acute angle?", options: ["An angle less than 90°", "An angle of 90°", "An angle greater than 90°"], answer: 0 },
        { question: "Which law explains action and reaction forces?", options: ["Newton's First Law", "Newton's Second Law", "Newton's Third Law"], answer: 2 },
        { question: "What is the chemical symbol for iron?", options: ["Ir", "Fe", "I"], answer: 1 },
        { question: "Which economic term describes a general rise in prices?", options: ["Deflation", "Inflation", "Recession"], answer: 1 },
        { question: "What is the SI unit of force?", options: ["Newton", "Joule", "Watt"], answer: 0 },
        { question: "Which process separates mixtures by boiling points?", options: ["Filtration", "Distillation", "Chromatography"], answer: 1 }
    ],
    grade11: [
        { question: "Which molecule carries genetic information?", options: ["DNA", "RNA", "ATP"], answer: 0 },
        { question: "What is the integral of 2x?", options: ["x^2 + C", "2x + C", "x + C"], answer: 0 },
        { question: "Which economist wrote 'The Wealth of Nations'?", options: ["John Maynard Keynes", "Adam Smith", "Karl Marx"], answer: 1 },
        { question: "What type of reaction releases energy to the surroundings?", options: ["Endothermic", "Exothermic", "Neutral"], answer: 1 },
        { question: "Which wave has the longest wavelength?", options: ["Gamma rays", "Radio waves", "X-rays"], answer: 1 },
        { question: "What is the pH value of pure water at 25°C?", options: ["7", "5", "9"], answer: 0 },
        { question: "Which programming concept uses functions calling themselves?", options: ["Iteration", "Recursion", "Abstraction"], answer: 1 },
        { question: "What is the formula for calculating density?", options: ["mass/volume", "force/area", "mass×volume"], answer: 0 },
        { question: "Which period in the geologic timescale came first?", options: ["Jurassic", "Cretaceous", "Triassic"], answer: 2 },
        { question: "Who is known for the law of universal gravitation?", options: ["Galileo", "Isaac Newton", "Einstein"], answer: 1 }
    ],
    grade12: [
        { question: "What is the solution to the equation ln(x)=0?", options: ["x=0", "x=1", "x=e"], answer: 1 },
        { question: "Which topic studies the behavior of markets and prices?", options: ["Microeconomics", "Genetics", "Thermodynamics"], answer: 0 },
        { question: "What is the limit of (1+1/n)^n as n approaches infinity?", options: ["e", "π", "1"], answer: 0 },
        { question: "Which optical instrument uses lenses to form a magnified image?", options: ["Telescope", "Microscope", "Both"], answer: 2 },
        { question: "What is the derivative of sin(x)?", options: ["cos(x)", "-cos(x)", "sin(x)"], answer: 0 },
        { question: "Which acid is found in the stomach?", options: ["Hydrochloric acid", "Acetic acid", "Sulfuric acid"], answer: 0 },
        { question: "What is the conjugate base of HCl?", options: ["Cl-", "H2O", "OH-"], answer: 0 },
        { question: "Which concept describes particles behaving like waves?", options: ["Relativity", "Wave-particle duality", "Entropy"], answer: 1 },
        { question: "What is the empirical formula of glucose (C6H12O6)?", options: ["CH2O", "C2H4O2", "C6H12O6"], answer: 0 },
        { question: "Which country launched the first satellite (Sputnik)?", options: ["USA", "USSR", "China"], answer: 1 }
    ]
};
let currentQuizData = [];
let currentQuestionIndex = 0;
let score = 0;
let selectedAnswerMade = false;
let currentSelectedGrade = '';

const gradeSelectionDiv = document.getElementById('grade-selection');
const gradeSelectEl = document.getElementById('gradeSelect');
const startQuizBtn = document.getElementById('startQuizBtn');
const seeLeaderboardBtn = document.getElementById('seeLeaderboardBtn');

const quizContentArea = document.getElementById('quiz-content-area');
const questionContainer = document.getElementById('question-container');
const optionsContainer = document.getElementById('options-container');
const nextQuestionBtn = document.getElementById('nextQuestionBtn');

const quizResultsDiv = document.getElementById('quiz-results');
const scoreDisplayEl = document.getElementById('score-display');
const leaderboardNameEntryDiv = document.getElementById('leaderboard-name-entry');
const playerNameInput = document.getElementById('playerName');
const submitScoreBtn = document.getElementById('submitScoreBtn');
const playAgainBtnFromResults = document.getElementById('playAgainBtnFromResults');
const scoreSubmittedMessage = document.getElementById('score-submitted-message');

const leaderboardDisplayArea = document.getElementById('leaderboard-display-area');
const leaderboardTableBody = document.getElementById('leaderboard-table-body');
const leaderboardGradeFilter = document.getElementById('leaderboardGradeFilter');
const playAgainBtnFromLeaderboard = document.getElementById('playAgainBtnFromLeaderboard');
const backToQuizSelectionBtn = document.getElementById('backToQuizSelectionBtn');

const progressBarFill = document.getElementById('progress-bar-fill');
const questionCounterText = document.getElementById('question-counter-text');

function shuffleArray(array) {
    return array.map(value => ({ value, sort: Math.random() }))
                .sort((a, b) => a.sort - b.sort)
                .map(({ value }) => value);
}

function startQuiz() {
    currentSelectedGrade = gradeSelectEl.value;
    if (!currentSelectedGrade || !originalQuizData[currentSelectedGrade]) {
        alert('Please select a valid grade level to start the quiz.');
        return;
    }

    currentQuizData = shuffleArray([...originalQuizData[currentSelectedGrade]].slice(0, 10));
    currentQuestionIndex = 0;
    score = 0;
    selectedAnswerMade = false;

    gradeSelectionDiv.style.display = 'none';
    quizResultsDiv.style.display = 'none';
    leaderboardNameEntryDiv.style.display = 'none';
    leaderboardDisplayArea.style.display = 'none';
    scoreSubmittedMessage.style.display = 'none';
    quizContentArea.style.display = 'block';
    nextQuestionBtn.style.display = 'none';
    playerNameInput.value = '';
    backToQuizSelectionBtn.style.display = 'none';

    loadQuestion();
}

function loadQuestion() {
    if (currentQuestionIndex >= currentQuizData.length) {
        showResult();
        return;
    }
    selectedAnswerMade = false;
    nextQuestionBtn.style.display = 'none';

    const currentQuestion = currentQuizData[currentQuestionIndex];
    questionContainer.innerHTML = `<div class="question-text">${currentQuestion.question}</div>`;

    optionsContainer.innerHTML = '';
    if (currentQuestion.options.length <= 2 && currentQuestion.options.every(opt => opt.length < 20)) {
        optionsContainer.classList.add('two-columns');
    } else {
        optionsContainer.classList.remove('two-columns');
    }

    currentQuestion.options.forEach((option, index) => {
        const optionId = `q${currentQuestionIndex}_option${index}`;
        const optionDiv = document.createElement('div');
        optionDiv.innerHTML = `
            <input type="radio" id="${optionId}" name="quizOption" value="${index}" class="option-input">
            <label for="${optionId}" class="option-label">${option}</label>
        `;
        optionsContainer.appendChild(optionDiv);
        const label = optionDiv.querySelector('.option-label');
        label.addEventListener('click', () => handleOptionSelect(index, currentQuestion.answer));
    });

    updateProgress();
}

function handleOptionSelect(selectedIndex, correctIndex) {
    if (selectedAnswerMade) return;
    selectedAnswerMade = true;

    const optionLabels = optionsContainer.querySelectorAll('.option-label');
    const inputs = optionsContainer.querySelectorAll('.option-input');
    inputs.forEach(input => input.disabled = true);
    optionLabels.forEach(label => label.classList.add('disabled'));

    if (selectedIndex === correctIndex) {
        score++;
        optionLabels[selectedIndex].classList.add('correct');
    } else {
        optionLabels[selectedIndex].classList.add('incorrect');
        if (optionLabels[correctIndex]) { // Ensure correctIndex is valid
            optionLabels[correctIndex].classList.add('correct');
        }
    }

    nextQuestionBtn.style.display = 'inline-block';
    if (currentQuestionIndex >= currentQuizData.length -1) {
        nextQuestionBtn.textContent = 'Show Results';
    } else {
        nextQuestionBtn.textContent = 'Next Question';
    }
}

function goToNextQuestion() {
    currentQuestionIndex++;
    loadQuestion();
}

function updateProgress() {
    if (currentQuizData.length === 0) return; // Prevent division by zero
    const progressPercentage = ((currentQuestionIndex + 1) / currentQuizData.length) * 100; // +1 for current question
    progressBarFill.style.width = `${progressPercentage}%`;
    // progressBarFill.textContent = `${Math.round(progressPercentage)}%`;
    questionCounterText.textContent = `Question ${currentQuestionIndex + 1} / ${currentQuizData.length}`;
}

function showResult() {
    quizContentArea.style.display = 'none';
    quizResultsDiv.style.display = 'block';
    leaderboardNameEntryDiv.style.display = 'block';
    submitScoreBtn.disabled = false;
    submitScoreBtn.textContent = 'Submit to Leaderboard';
    scoreSubmittedMessage.style.display = 'none';
    backToQuizSelectionBtn.style.display = 'none';

    scoreDisplayEl.innerHTML = `You scored <span class="score-highlight">${score}</span> out of <span class="score-highlight">${currentQuizData.length}</span>!`;
}

async function submitScoreToLeaderboard() {
    const playerName = playerNameInput.value.trim();
    submitScoreBtn.disabled = true;
    submitScoreBtn.textContent = 'Submitting...';

    const dataToSend = {
        playerName: playerName,
        gradeLevel: currentSelectedGrade,
        score: score,
        totalQuestions: currentQuizData.length
    };

    try {
        const response = await fetch('api/quiz/submit_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataToSend)
        });
        const result = await response.json();
        if (result.success) {
            scoreSubmittedMessage.textContent = result.message;
            scoreSubmittedMessage.style.display = 'block';
            leaderboardNameEntryDiv.style.display = 'none';
            leaderboardGradeFilter.value = currentSelectedGrade;
            await fetchAndDisplayLeaderboard(currentSelectedGrade);
            playAgainBtnFromLeaderboard.textContent = 'Play Quiz Again?';
            backToQuizSelectionBtn.style.display = 'inline-block';
        } else {
            alert(`Error: ${result.message}`);
            submitScoreBtn.disabled = false;
            submitScoreBtn.textContent = 'Submit to Leaderboard';
        }
    } catch (error) {
        console.error('Error submitting score:', error);
        alert('An error occurred while submitting your score. Please try again.');
        submitScoreBtn.disabled = false;
        submitScoreBtn.textContent = 'Submit to Leaderboard';
    }
}

async function fetchAndDisplayLeaderboard(grade = 'all') {
    quizContentArea.style.display = 'none';
    quizResultsDiv.style.display = 'none';
    gradeSelectionDiv.style.display = 'none';
    leaderboardNameEntryDiv.style.display = 'none';
    scoreSubmittedMessage.style.display = 'none';
    leaderboardDisplayArea.style.display = 'block';
    leaderboardTableBody.innerHTML = `<tr><td colspan="5" class="text-center">Loading leaderboard...</td></tr>`;

    try {
        const response = await fetch(`api/quiz/get_leaderboard.php?gradeLevel=${encodeURIComponent(grade)}`);
        const data = await response.json();

        if (data.success && data.leaderboard) {
            leaderboardTableBody.innerHTML = '';
            if (data.leaderboard.length === 0) {
                let message = 'No scores yet for this selection!';
                if (grade !== 'all') {
                    message = `No scores yet for ${htmlspecialchars(grade)}! Be the first!`;
                }
                leaderboardTableBody.innerHTML = `<tr><td colspan="5" class="text-center">${message}</td></tr>`;
            } else {
                data.leaderboard.forEach((entry, index) => {
                    const rank = index + 1;
                    const row = document.createElement('tr');
                    row.classList.add(`rank-${rank}`);
                    row.innerHTML = `
                        <td><strong>#${rank}</strong></td>
                        <td class="player-name-col">${htmlspecialchars(entry.player_name)}</td>
                        <td>${htmlspecialchars(entry.grade_level)}</td>
                        <td>${entry.score}/${entry.total_questions} (${entry.percentage}%)</td>
                        <td>${new Date(entry.date_played).toLocaleDateString()}</td>
                    `;
                    leaderboardTableBody.appendChild(row);
                });
            }
        } else {
            leaderboardTableBody.innerHTML = `<tr><td colspan="5" class="text-center">${data.message || 'Could not load leaderboard.'}</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching leaderboard:', error);
        leaderboardTableBody.innerHTML = `<tr><td colspan="5" class="text-center">Error loading leaderboard.</td></tr>`;
    }
}

function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return str.replace(/[&<>"']/g, m => map[m]);
}

function showGradeSelection() {
    gradeSelectionDiv.style.display = 'block';
    quizContentArea.style.display = 'none';
    quizResultsDiv.style.display = 'none';
    leaderboardNameEntryDiv.style.display = 'none';
    leaderboardDisplayArea.style.display = 'none';
    scoreSubmittedMessage.style.display = 'none';
    gradeSelectEl.value = "";
    playerNameInput.value = '';
    if (progressBarFill) progressBarFill.style.width = '0%'; // Check if element exists
    if (questionCounterText) questionCounterText.textContent = ''; // Check if element exists
    currentSelectedGrade = '';
    backToQuizSelectionBtn.style.display = 'none';
    playAgainBtnFromLeaderboard.textContent = 'Play Quiz';
}

function restartQuiz() {
    let gradeForQuiz = gradeSelectEl.value;

    // If leaderboard is visible and a specific grade is filtered, prioritize that
    if (leaderboardDisplayArea.style.display === 'block' && leaderboardGradeFilter.value !== 'all') {
        gradeForQuiz = leaderboardGradeFilter.value;
    } else if (!gradeForQuiz && leaderboardDisplayArea.style.display === 'block' && leaderboardGradeFilter.value === 'all') {
        // If leaderboard is on 'all' and no grade selected in main dropdown, prompt or default
        alert("Please select a specific grade from the main dropdown or the filter to play.");
        showGradeSelection(); // Go back to selection
        return;
    }


    if (!gradeForQuiz) {
         alert("Please select a grade to start the quiz.");
         showGradeSelection(); // Ensure grade selection is visible
        return;
    }

    gradeSelectEl.value = gradeForQuiz;
    startQuiz();
}

// Event Listeners
if(startQuizBtn) startQuizBtn.addEventListener('click', startQuiz);
if(nextQuestionBtn) nextQuestionBtn.addEventListener('click', goToNextQuestion);
if(submitScoreBtn) submitScoreBtn.addEventListener('click', submitScoreToLeaderboard);
if(playAgainBtnFromResults) playAgainBtnFromResults.addEventListener('click', restartQuiz);
if(playAgainBtnFromLeaderboard) playAgainBtnFromLeaderboard.addEventListener('click', restartQuiz);

if(seeLeaderboardBtn) {
    seeLeaderboardBtn.addEventListener('click', async () => {
        const selectedGradeForLeaderboard = gradeSelectEl.value || 'all';
        leaderboardGradeFilter.value = selectedGradeForLeaderboard;
        await fetchAndDisplayLeaderboard(selectedGradeForLeaderboard);
        playAgainBtnFromLeaderboard.textContent = 'Play Quiz';
        backToQuizSelectionBtn.style.display = 'inline-block';
    });
}

if(leaderboardGradeFilter) {
    leaderboardGradeFilter.addEventListener('change', async (event) => {
        await fetchAndDisplayLeaderboard(event.target.value);
    });
}

if(backToQuizSelectionBtn) backToQuizSelectionBtn.addEventListener('click', showGradeSelection);

document.addEventListener('DOMContentLoaded', () => {
    showGradeSelection();
});