<div class="modal" popover="manual" id="my-task-modal">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span>Your Quest</span>
            </h2>
            <div class="body__text" style="padding:10px 0">
                <div class="task-field">
                    <label class="status-label" for="my-task-description">What did you complete?</label>
                    <textarea class="task-input" id="my-task-description" rows="5" placeholder="Describe what you did..."></textarea>
                </div>
                <div id="my-task-message" style="min-height:1.2em"></div>
            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span>Your Quest</span>
                </h2>
                <div class="body__text" style="padding:10px 0"></div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button type="button" aria-label="Cancel" class="cyber-btn" id="my-task-cancel" data-action="Cancel">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Cancel</span>
        </button>
        <button type="button" aria-label="Proceed" class="cyber-btn" id="my-task-proceed" data-action="Proceed">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 4v7a4 4 0 0 1-4 4H4"></path>
                    <path d="m9 10-5 5 5 5"></path>
                </svg></kbd>
            <span>Proceed</span>
        </button>
    </div>
</div>
