function formatTime(secs) {
    secs = Math.max(0, parseInt(secs, 10) || 0);
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

function stateChanged(previousState, newState) {
    if (!previousState) return true;
    if (previousState.timestamp !== newState.timestamp) return true;
    if (previousState.question !== newState.question) return true;
    if (previousState.num_questions !== newState.num_questions) return true;
    for (let i = 0; i < newState.questions.length; i++) {
        if (previousState.questions[i] !== newState.questions[i]) return true;
        if (previousState.votes[i] !== newState.votes[i]) return true;
    }
    return false;
}

function questionChanged(previousState, newState) {
    if (!previousState) return true;
    if (previousState.question !== newState.question) return true;
    if (previousState.num_questions !== newState.num_questions) return true;
    for (let i = 0; i < newState.num_questions; i++)  if (previousState.questions[i] !== newState.questions[i]) return true;
    return false;
}

function renderResults(state) {
    let html = '<h2>Results</h2>';
    for (const question of state.questions) {
        html += `<div>
            <span class="result-text"></span>
            <span class="result-vote"></span>
            <progress class="result-bar" value="0" max="100"></progress>
        </div>`;
    }
    return html;
}

function updateResults(state, resultsDiv) {
    const tally = state.votes.slice(0, state.num_questions).reduce((sum, val) => sum + val, 0);

    const textSpans = Array.from(resultsDiv.querySelectorAll('.result-text'));
    const voteSpans = Array.from(resultsDiv.querySelectorAll('.result-vote'));
    const progressBars = Array.from(resultsDiv.querySelectorAll('.result-bar'));

    for (let i = 0; i < state.num_questions; i++) {
        const vote = state.votes[i];
        textSpans[i].textContent = `${state.questions[i]}:`;
        voteSpans[i].textContent = `${vote} ${vote === 1 ? "vote" : "votes"}`;
        progressBars[i].value = tally > 0 ? Math.round((vote / tally) * 100) : 0;
    }
}
