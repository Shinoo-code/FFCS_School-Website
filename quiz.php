<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fun Quiz Challenge! - FFCS</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/quiz.css" />
</head>
<body class="quiz-page-body">

    <div class="quiz-main-container">
        <div class="quiz-header text-center">
            <h2>Quiz Challenge!</h2>
            <p class="quiz-subtitle">Test your knowledge and have fun!</p>
        </div>

        <div id="grade-selection">
            <h4>Select Your Grade Level</h4>
            <div class="text-center">
                <select class="form-select" id="gradeSelect">
                    <option value="" disabled selected>-- Choose Your Grade --</option>
                    <option value="kinder">Kindergarten</option>
                    <option value="grade1">Grade 1</option>
                    <option value="grade2">Grade 2</option>
                    <option value="grade3">Grade 3</option>
                    <option value="grade4">Grade 4</option>
                    <option value="grade5">Grade 5</option>
                    <option value="grade6">Grade 6</option>
                    <option value="grade7">Grade 7</option>
                    <option value="grade8">Grade 8</option>
                    <option value="grade9">Grade 9</option>
                    <option value="grade10">Grade 10</option>
                    <option value="grade11">Grade 11</option>
                    <option value="grade12">Grade 12</option>
                </select>
                <div class="mt-3">
                    <button class="quiz-button" id="startQuizBtn">Start Quiz</button>
                    <button class="quiz-button mt-2 mt-sm-0 ms-sm-2" id="seeLeaderboardBtn" style="background-color: var(--kinderly-blue-accent);"><i class="fas fa-trophy"></i> See Leaderboard</button>
                </div>
            </div>
        </div>

        <div id="quiz-content-area" style="display: none;">
            <div class="question-counter" id="question-counter-text">Question 1 / 10</div>
            <div class="progress-bar-container">
                <div class="progress-bar-custom" id="progress-bar-fill" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div id="question-container">
            </div>
            <div id="options-container" class="options-grid">
            </div>
            <div class="text-center mt-4">
                <button class="quiz-button" id="nextQuestionBtn" style="display: none;">Next Question</button>
            </div>
        </div>

        <div id="quiz-results" style="display: none;">
            <h3>Quiz Complete!</h3>
            <p id="score-display">You scored <span class="score-highlight">X</span> out of <span class="score-highlight">Y</span>!</p>

            <div id="leaderboard-name-entry" class="my-4" style="max-width: 400px; margin-left:auto; margin-right:auto;">
                <label for="playerName" class="form-label" style="font-weight:600;">Enter your name for the Leaderboard (Optional):</label>
                <input type="text" class="form-control form-control-lg text-center" id="playerName" placeholder="Your Name / Nickname">
                <button class="quiz-button mt-3" id="submitScoreBtn" style="background-color: var(--kinderly-blue-accent);">Submit to Leaderboard</button>
                <button class="quiz-button mt-3 ms-2 restart-btn" id="playAgainBtnFromResults">Play Again?</button>
            </div>
             <p id="score-submitted-message" class="text-success fw-bold" style="display:none;">Score submitted!</p>
        </div>

        <div id="leaderboard-display-area" class="mt-5" style="display: none;">
            <h3 class="text-center" style="color: var(--kinderly-blue-accent); font-family: var(--font-decorative); font-size:2rem; margin-bottom:20px;">🏆 Leaderboard 🏆</h3>
            <div class="mb-3 text-center">
                <label for="leaderboardGradeFilter" class="form-label me-2" style="font-weight:600;">Filter by Grade:</label>
                <select class="form-select d-inline-block" id="leaderboardGradeFilter" style="max-width: 200px;">
                    <option value="all">All Grades</option>
                    <option value="kinder">Kindergarten</option>
                    <option value="grade1">Grade 1</option>
                    <option value="grade2">Grade 2</option>
                    <option value="grade3">Grade 3</option>
                    <option value="grade4">Grade 4</option>
                    <option value="grade5">Grade 5</option>
                    <option value="grade6">Grade 6</option>
                    <option value="grade7">Grade 7</option>
                    <option value="grade8">Grade 8</option>
                    <option value="grade9">Grade 9</option>
                    <option value="grade10">Grade 10</option>
                    <option value="grade11">Grade 11</option>
                    <option value="grade12">Grade 12</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover kinderly-leaderboard-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboard-table-body">
                    </tbody>
                </table>
            </div>
             <div class="text-center mt-3">
                <button class="quiz-button" id="backToQuizSelectionBtn" style="background-color: var(--kinderly-pink-red-primary); display:none;">Back to Quiz Selection</button>
                <button class="quiz-button restart-btn ms-2" id="playAgainBtnFromLeaderboard">Play Quiz</button>
            </div>
        </div>

        <div class="quiz-footer-link mt-4">
            <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/quiz.js"></script>

</body>
</html>