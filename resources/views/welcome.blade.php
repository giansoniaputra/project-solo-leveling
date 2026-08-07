<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/assets-front-end/style.css">
    <title>Solo Leveling</title>
</head>
<body>
    <button class="cyber-btn" id="status-btn" popovertarget="status-modal" popovertargetaction="show" aria-label="Status" data-action="Status" style="position:fixed; top:20px; right:20px;">
        <span class="backdrop"><span class="corner"></span></span>
        <span>Status</span>
    </button>

    <button type="button" class="cyber-btn voice-toggle-btn" id="voice-toggle-btn" aria-label="Voice Mode" data-action="Voice Mode" style="position:fixed; top:20px; right:180px;">
        <span class="backdrop"><span class="corner"></span></span>
        <span>Voice</span>
    </button>

    <div class="voice-indicator" id="voice-indicator">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
            <line x1="12" y1="19" x2="12" y2="23"></line>
            <line x1="8" y1="23" x2="16" y2="23"></line>
        </svg>
        <span class="voice-text" id="voice-text">Voice mode off</span>
    </div>

    <button class="cyber-btn" id="daily-quest" popovertarget="upgrade" popovertargetaction="show" aria-label="Daily Quest" data-action="Daily Quest">
        <span class="backdrop">
            <span class="corner"></span>
        </span>
        <kbd>U</kbd>
        <span>Daily Quest</span>
        <div class="glitch" aria-hidden="true">
            <span class="backdrop">
                <span class="corner"></span>
            </span>
            <kbd>U</kbd>
            <span class="letters">
                <span>U</span>
                <span>p</span>
                <span>g</span>
                <span>r</span>
                <span>a</span>
                <span>d</span>
                <span>e</span>
            </span>
        </div>
    </button>
    <button class="cyber-btn" id="main-quest" popovertarget="upgrade" popovertargetaction="show" aria-label="Main Quest" data-action="Main Quest">
        <span class="backdrop">
            <span class="corner"></span>
        </span>
        <kbd>U</kbd>
        <span>Main Quest</span>
        <div class="glitch" aria-hidden="true">
            <span class="backdrop">
                <span class="corner"></span>
            </span>
            <kbd>U</kbd>
            <span class="letters">
                <span>U</span>
                <span>p</span>
                <span>g</span>
                <span>r</span>
                <span>a</span>
                <span>d</span>
                <span>e</span>
            </span>
        </div>
    </button>
    @include('modal')
    @include('modal-quest-detail')
    @include('modal-status')
    <a aria-label="Follow Jhey" class="bear-link" href="https://twitter.com/intent/follow?screen_name=jh3yy" target="_blank" rel="noreferrer noopener">
        <svg class="w-9" viewBox="0 0 969 955" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="161.191" cy="320.191" r="133.191" stroke="currentColor" stroke-width="20"></circle>
            <circle cx="806.809" cy="320.191" r="133.191" stroke="currentColor" stroke-width="20"></circle>
            <circle cx="695.019" cy="587.733" r="31.4016" fill="currentColor"></circle>
            <circle cx="272.981" cy="587.733" r="31.4016" fill="currentColor"></circle>
            <path d="M564.388 712.083C564.388 743.994 526.035 779.911 483.372 779.911C440.709 779.911 402.356 743.994 402.356 712.083C402.356 680.173 440.709 664.353 483.372 664.353C526.035 664.353 564.388 680.173 564.388 712.083Z" fill="currentColor"></path>
            <rect x="310.42" y="448.31" width="343.468" height="51.4986" fill="#FF1E1E"></rect>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M745.643 288.24C815.368 344.185 854.539 432.623 854.539 511.741H614.938V454.652C614.938 433.113 597.477 415.652 575.938 415.652H388.37C366.831 415.652 349.37 433.113 349.37 454.652V511.741L110.949 511.741C110.949 432.623 150.12 344.185 219.845 288.24C289.57 232.295 384.138 200.865 482.744 200.865C581.35 200.865 675.918 232.295 745.643 288.24Z" fill="currentColor"></path>
        </svg>
    </a>
    <script type="module" src="/assets-front-end/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let currentType = 'daily';
        let currentQuests = [];
        let title = document.querySelector('#modal-title');
        let titleG = document.querySelector('#modal-title-glitch');
        let bodyy = document.querySelector('#modal-body');
        let bodyG = document.querySelector('#modal-body-glitch');
        let timerInterval = null;

        let questDetail = document.querySelector('#quest-detail');
        let qdTitle = document.querySelector('#quest-detail-title');
        let qdTitleG = document.querySelector('#quest-detail-title-glitch');
        let qdBody = document.querySelector('#quest-detail-body');
        let qdBodyG = document.querySelector('#quest-detail-body-glitch');
        let qdProceed = document.querySelector('#quest-detail-proceed');
        let activeQuestId = null;

        // Wrapping every content swap in a fresh .quest-anim element replays
        // the fade/slide-in keyframe (new nodes always start their animation).
        function setModalContent(html) {
            bodyy.innerHTML = `<div class="quest-anim">${html}</div>`;
            bodyG.innerHTML = `<div class="quest-anim">${html}</div>`;
        }

        function statLabel(stat) {
            if (!stat) return '';
            return stat === 'intelligence' ? 'INT' : stat.toUpperCase();
        }

        function clearQuestTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }

        function startQuestTimer() {
            clearQuestTimer();
            let timerEls = document.querySelectorAll('#content-modal .quest-timer');
            if (!timerEls.length) return;

            function tick() {
                let now = new Date();
                let midnight = new Date();
                midnight.setHours(24, 0, 0, 0);
                let diff = Math.max(0, midnight - now);

                let h = String(Math.floor(diff / 3600000)).padStart(2, '0');
                let m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                let s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                let isLow = diff < 2 * 60 * 60 * 1000; // under 2 hours left

                timerEls.forEach(function(el) {
                    let text = el.querySelector('.timer-text');
                    if (text) text.textContent = `${h}:${m}:${s}`;
                    el.classList.toggle('is-low', isLow);
                });
            }

            tick();
            timerInterval = setInterval(tick, 1000);
        }

        function renderQuestList(quests, type) {
            let timerHtml = type === 'daily' ? `
                <div class="quest-timer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span class="timer-text">00:00:00</span>
                </div>
            ` : '';

            if (!quests.length) {
                if (type === 'main') {
                    return timerHtml + renderStatPicker();
                }

                return `
                    ${timerHtml}
                    <div class="quest-empty">
                        <p>No quests available right now.</p>
                        <button type="button" class="cyber-btn generate-quest-btn">
                            <span class="backdrop"><span class="corner"></span></span>
                            <span>Generate Quest</span>
                        </button>
                    </div>
                `;
            }

            let rows = quests.map(function(quest) {
                let done = quest.progress && quest.progress.status;
                let statBadge = quest.stat ? ` · +${quest.stat_reward} ${statLabel(quest.stat)}` : '';
                return `
                    <div class="quest-row" data-id="${quest.id}">
                        <div class="quest-info">
                            <span class="quest-title">${quest.title}</span>
                            <span class="quest-desc">${quest.description}</span>
                            <span class="quest-exp">+${quest.exp_reward} EXP${statBadge}</span>
                        </div>
                        <span class="quest-circle ${done ? 'is-done' : ''}" aria-hidden="true"></span>
                    </div>
                `;
            }).join('');

            let warning = type === 'daily' ? `
                <p class="quest-warning">WARNING: Failure to complete the daily quest will result in an appropriate <span class="danger-text">penalty</span>.</p>
            ` : '';

            return timerHtml + rows + warning;
        }

        function renderStatPicker() {
            let stats = [{
                    key: 'str'
                    , label: 'STR'
                }
                , {
                    key: 'agi'
                    , label: 'AGI'
                }
                , {
                    key: 'per'
                    , label: 'PER'
                }
                , {
                    key: 'vit'
                    , label: 'VIT'
                }
                , {
                    key: 'intelligence'
                    , label: 'INT'
                }
            , ];

            let buttons = stats.map(function(s) {
                return `
                    <button type="button" class="cyber-btn stat-choice-btn" data-stat="${s.key}" style="width:100%; margin-bottom:6px;">
                        <span class="backdrop"><span class="corner"></span></span>
                        <span>${s.label}</span>
                    </button>
                `;
            }).join('');

            return `
                <div class="quest-empty">
                    <p>Choose which stat this Main Quest should raise:</p>
                    ${buttons}
                </div>
            `;
        }

        function loadQuests(type) {
            currentType = type;
            title.innerHTML = type === 'main' ? 'Main Quest' : 'Daily Quest';
            titleG.innerHTML = title.innerHTML;
            clearQuestTimer();
            setModalContent('Loading...');

            $.ajax({
                url: '/quests'
                , type: 'GET'
                , data: {
                    type: type
                }
                , dataType: 'json'
                , success: function(response) {
                    currentQuests = response.quests;
                    setModalContent(renderQuestList(response.quests, type));
                    if (type === 'daily') startQuestTimer();
                }
            });
        }

        function updateStatusDisplay(exp, level, stats) {
            let levelEl = document.querySelector('#status-level-value');
            let expEl = document.querySelector('#status-exp-value');
            let fillEl = document.querySelector('#status-exp-fill');
            let expIntoLevel = exp % 1000;

            if (levelEl) levelEl.textContent = level;
            if (expEl) expEl.textContent = expIntoLevel + ' / 1000';
            if (fillEl) fillEl.style.width = (expIntoLevel / 10) + '%';

            if (stats) {
                ['str', 'agi', 'per', 'vit', 'intelligence'].forEach(function(key) {
                    let el = document.querySelector('#status-' + (key === 'intelligence' ? 'int' : key) + '-value');
                    if (el) el.textContent = stats[key];
                });
            }
        }

        function openQuestDetail(quest) {
            activeQuestId = quest.id;
            let done = quest.progress && quest.progress.status;

            qdTitle.textContent = quest.title;
            qdTitleG.textContent = quest.title;

            let statBadge = quest.stat ? ` · +${quest.stat_reward} ${statLabel(quest.stat)}` : '';
            let detailHtml = `
                <p>${quest.description}</p>
                <p class="quest-exp">+${quest.exp_reward} EXP${statBadge}</p>
                ${done ? '<p class="quest-warning">This quest is already completed.</p>' : ''}
            `;
            qdBody.innerHTML = detailHtml;
            qdBodyG.innerHTML = detailHtml;

            qdProceed.disabled = false;
            qdProceed.style.display = done ? 'none' : '';

            document.querySelector('#upgrade').hidePopover();
            questDetail.showPopover();
        }

        document.querySelector('#quest-detail-cancel').addEventListener('click', function() {
            questDetail.hidePopover();
            document.querySelector('#upgrade').showPopover();
            // hiding #upgrade to show the detail modal fired its "closed" toggle,
            // which stopped the countdown — restart it since we're back on the list.
            if (currentType === 'daily') startQuestTimer();
        });

        document.querySelector('#quest-detail-proceed').addEventListener('click', function() {
            if (!activeQuestId) return;
            qdProceed.disabled = true;

            $.ajax({
                url: '/quests/' + activeQuestId + '/complete'
                , type: 'POST'
                , dataType: 'json'
                , success: function(response) {
                    questDetail.hidePopover();
                    document.querySelector('#upgrade').showPopover();
                    clearQuestTimer();
                    updateStatusDisplay(response.exp, response.level, response.stats);
                    setModalContent(`
                        <div class="quest-empty">
                            <p>${response.message}</p>
                            <p class="quest-exp">EXP: ${response.exp} | Level: ${response.level}</p>
                        </div>
                    `);
                    setTimeout(() => loadQuests(currentType), 1500);
                }
                , error: function(response) {
                    qdProceed.disabled = false;
                    let msg = response.responseJSON ? response.responseJSON.message : 'Failed to complete quest.';
                    qdBody.innerHTML = `<p>${msg}</p>`;
                    qdBodyG.innerHTML = qdBody.innerHTML;
                }
            });
        });

        document.querySelector('#daily-quest').addEventListener('click', function() {
            loadQuests('daily');
        });
        document.querySelector('#main-quest').addEventListener('click', function() {
            loadQuests('main');
        });

        document.querySelector('#upgrade').addEventListener('toggle', function(e) {
            if (e.newState === 'closed') clearQuestTimer();
        });

        document.querySelector('#content-modal').addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('generate-quest-btn')) {
                clearQuestTimer();
                setModalContent('The System is preparing your quest...');
                $.ajax({
                    url: '/quests/generate'
                    , type: 'POST'
                    , data: {
                        type: currentType
                    }
                    , dataType: 'json'
                    , success: function() {
                        loadQuests(currentType);
                    }
                    , error: function(response) {
                        let msg = response.responseJSON ? response.responseJSON.message : 'Failed to generate quest.';
                        setModalContent(`<p>${msg}</p>`);
                    }
                });
            }

            let statBtn = e.target.closest ? e.target.closest('.stat-choice-btn') : null;
            if (statBtn) {
                let stat = statBtn.getAttribute('data-stat');
                clearQuestTimer();
                setModalContent('The System is preparing your quest...');
                $.ajax({
                    url: '/quests/generate'
                    , type: 'POST'
                    , data: {
                        type: 'main'
                        , stat: stat
                    }
                    , dataType: 'json'
                    , success: function() {
                        loadQuests('main');
                    }
                    , error: function(response) {
                        let msg = response.responseJSON ? response.responseJSON.message : 'Failed to generate quest.';
                        setModalContent(`<p>${msg}</p>`);
                    }
                });
            }

            let row = e.target.closest ? e.target.closest('.quest-row') : null;
            if (row) {
                let id = Number(row.getAttribute('data-id'));
                let quest = currentQuests.find(q => q.id === id);
                if (quest) openQuestDetail(quest);
            }
        });

        function showPenaltyToast(penalized) {
            let totalLost = penalized.reduce((sum, p) => sum + Number(p.exp_reward), 0);
            let list = penalized.map(p => p.title).join(', ');

            let toast = document.createElement('div');
            toast.className = 'penalty-toast';
            toast.innerHTML = `<strong>Penalty!</strong><br>Missed: ${list}<br>-${totalLost} EXP`;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('is-leaving');
                setTimeout(() => toast.remove(), 350);
            }, 5000);
        }

        $.ajax({
            url: '/penalties/check'
            , type: 'GET'
            , dataType: 'json'
            , success: function(response) {
                updateStatusDisplay(response.exp, response.level, response.stats);
                if (response.penalized && response.penalized.length) {
                    showPenaltyToast(response.penalized);
                }
            }
        });

        document.querySelector('#status-save').addEventListener('click', function() {
            let msg = document.querySelector('#status-message');
            $.ajax({
                url: '/status'
                , type: 'POST'
                , data: {
                    weight: document.querySelector('#status-weight').value
                    , height: document.querySelector('#status-height').value
                    , age: document.querySelector('#status-age').value
                }
                , dataType: 'json'
                , success: function(response) {
                    msg.innerHTML = response.message;
                }
                , error: function(response) {
                    msg.innerHTML = 'Failed to save status.';
                }
            });
        });

        // ---- Voice Mode ----
        // Every command below reuses a real button's .click() (not a
        // synthetic dispatchEvent) so native popovertarget behavior fires
        // and the existing click handlers/delegation do all the actual work
        // — this file never duplicates that logic.
        (function() {
            let voiceEnabled = false;
            let suppressRestart = false;
            let voiceToggleBtn = document.querySelector('#voice-toggle-btn');
            let voiceIndicator = document.querySelector('#voice-indicator');
            let voiceText = document.querySelector('#voice-text');
            let SpeechRecognitionImpl = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognitionImpl) {
                voiceToggleBtn.disabled = true;
                voiceToggleBtn.title = 'Voice mode is not supported in this browser';
                return;
            }

            function hasWord(text, word) {
                return new RegExp('\\b' + word + '\\b').test(text);
            }

            // Speaks a reply out loud using OpenAI TTS (female "nova" voice,
            // synthesized server-side and cached — see VoiceController).
            // Recognition is paused while speaking and while suppressRestart
            // is true — otherwise the mic would pick up the System's own
            // voice through the speakers and the "end" handler's
            // auto-restart would race with it.
            function speakSystemReply(message) {
                voiceText.textContent = 'System: "' + message + '"';

                suppressRestart = true;
                try {
                    recognition.stop();
                } catch (e) {
                    // already stopped — ignore
                }

                function resumeListening() {
                    suppressRestart = false;
                    if (voiceEnabled) {
                        try {
                            recognition.start();
                        } catch (e) {
                            // already running — ignore
                        }
                    }
                }

                function speakWithBrowserVoice() {
                    if (!('speechSynthesis' in window)) return resumeListening();
                    let utterance = new SpeechSynthesisUtterance(message);
                    utterance.onend = utterance.onerror = resumeListening;
                    window.speechSynthesis.speak(utterance);
                }

                fetch('/voice/speak', {
                        method: 'POST'
                        , headers: {
                            'Content-Type': 'application/json'
                            , 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                        , body: JSON.stringify({
                            text: message
                        })
                    })
                    .then(function(response) {
                        if (!response.ok) throw new Error('TTS request failed');
                        return response.blob();
                    })
                    .then(function(blob) {
                        let audio = new Audio(URL.createObjectURL(blob));
                        audio.onended = resumeListening;
                        audio.onerror = speakWithBrowserVoice;
                        audio.play();
                    })
                    .catch(speakWithBrowserVoice);
            }

            function handleVoiceCommand(text) {
                if (text.includes('you up')) return speakSystemReply('For you sir, always');

                if (text.includes('let\'s begin friday')) return speakSystemReply('Okay sir, how can I help you this time?');

                if (text.includes('open daily quest')) return document.querySelector('#daily-quest').click();
                if (text.includes('open main quest')) return document.querySelector('#main-quest').click();
                if (hasWord(text, 'open status')) return document.querySelector('#status-btn').click();

                let modal = document.querySelector('[popover]:popover-open');
                if (!modal) return;

                if (hasWord(text, 'generate')) {
                    let btn = modal.querySelector('.generate-quest-btn');
                    if (btn) return btn.click();
                }

                let ordinals = ['first', 'second', 'third', 'fourth', 'fifth'];
                for (let i = 0; i < ordinals.length; i++) {
                    if (text.includes(ordinals[i] + ' quest')) {
                        let rows = modal.querySelectorAll('.quest-row');
                        if (rows[i]) rows[i].click();
                        return;
                    }
                }

                let statMap = {
                    str: ['strength', 'str']
                    , agi: ['agility', 'agi']
                    , per: ['perception', 'per']
                    , vit: ['vitality', 'vit']
                    , intelligence: ['intelligence', 'int']
                , };
                for (let key in statMap) {
                    if (statMap[key].some((word) => hasWord(text, word))) {
                        let btn = modal.querySelector('.stat-choice-btn[data-stat="' + key + '"]');
                        if (btn) return btn.click();
                    }
                }

                if (hasWord(text, 'proceed') || hasWord(text, 'confirm')) {
                    let btn = modal.querySelector('[data-action="Proceed"]');
                    if (btn) return btn.click();
                }

                if (hasWord(text, 'cancel') || hasWord(text, 'back')) {
                    let btn = modal.querySelector('[data-action="Cancel"]');
                    if (btn) return btn.click();
                }

                if (hasWord(text, 'save') && modal.id === 'status-modal') {
                    let btn = document.querySelector('#status-save');
                    if (btn) return btn.click();
                }
            }

            let recognition = new SpeechRecognitionImpl();
            recognition.lang = 'en-US';
            recognition.continuous = true;
            recognition.interimResults = false;

            recognition.addEventListener('result', function(event) {
                let last = event.results[event.results.length - 1];
                let transcript = last[0].transcript.toLowerCase().trim();
                voiceText.textContent = 'Heard: "' + transcript + '"';
                handleVoiceCommand(transcript);
            });

            recognition.addEventListener('end', function() {
                if (voiceEnabled && !suppressRestart) {
                    try {
                        recognition.start();
                    } catch (e) {
                        // already running — ignore
                    }
                }
            });

            recognition.addEventListener('error', function(event) {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    voiceEnabled = false;
                    voiceToggleBtn.classList.remove('is-listening');
                    voiceIndicator.classList.remove('is-active', 'is-listening');
                    alert('Microphone access was denied. Voice mode has been turned off.');
                } else if (event.error !== 'no-speech') {
                    console.warn('Speech recognition error:', event.error);
                }
            });

            voiceToggleBtn.addEventListener('click', function() {
                voiceEnabled = !voiceEnabled;
                voiceToggleBtn.classList.toggle('is-listening', voiceEnabled);
                voiceIndicator.classList.toggle('is-active', voiceEnabled);
                voiceIndicator.classList.toggle('is-listening', voiceEnabled);

                if (voiceEnabled) {
                    voiceText.textContent = 'Listening...';
                    try {
                        recognition.start();
                    } catch (e) {
                        // already running — ignore
                    }
                } else {
                    voiceText.textContent = 'Voice mode off';
                    recognition.stop();
                }
            });
        })();

    </script>
</body>
</html>
