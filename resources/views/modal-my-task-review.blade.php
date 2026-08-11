<div class="modal" popover="manual" id="my-task-review-modal">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span>System Analysis</span>
            </h2>
            <div class="body__text" style="padding:10px 0">
                <p id="my-task-review-summary"></p>
                <div class="status-row">
                    <span class="status-label">EXP</span>
                    <span class="status-value" id="my-task-review-exp"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">STR</span>
                    <span class="status-value" id="my-task-review-str"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">AGI</span>
                    <span class="status-value" id="my-task-review-agi"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">PER</span>
                    <span class="status-value" id="my-task-review-per"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">VIT</span>
                    <span class="status-value" id="my-task-review-vit"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">INT</span>
                    <span class="status-value" id="my-task-review-int"></span>
                </div>
            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span>System Analysis</span>
                </h2>
                <div class="body__text" style="padding:10px 0"></div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button type="button" aria-label="Revise" class="cyber-btn" id="my-task-review-revise" data-action="Cancel">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Revise</span>
        </button>
        <button type="button" aria-label="Confirm" class="cyber-btn" id="my-task-review-confirm" data-action="Proceed">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 4v7a4 4 0 0 1-4 4H4"></path>
                    <path d="m9 10-5 5 5 5"></path>
                </svg></kbd>
            <span>Confirm</span>
        </button>
    </div>
</div>
