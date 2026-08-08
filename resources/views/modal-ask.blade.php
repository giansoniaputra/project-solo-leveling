<div class="modal" popover="manual" id="ask-modal">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span id="ask-question">Question</span>
            </h2>
            <div class="body__text" style="padding:10px 0">
                <p id="ask-answer"></p>
                <p class="quest-exp" id="ask-source"></p>
            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span id="ask-question-glitch">Question</span>
                </h2>
                <div class="body__text" style="padding:10px 0">
                    <p id="ask-answer-glitch"></p>
                    <p class="quest-exp" id="ask-source-glitch"></p>
                </div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button aria-label="Cancel" class="cyber-btn" data-action="Cancel" popovertarget="ask-modal" popovertargetaction="hide">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Cancel</span>
        </button>
        <button type="button" aria-label="Open Reference" class="cyber-btn" id="ask-open-reference" data-action="Reference">
            <span class="backdrop"><span class="corner"></span></span>
            <span>Reference</span>
        </button>
    </div>
</div>
