<div class="modal" popover="manual" id="my-task-result-modal">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span>Quest Logged</span>
            </h2>
            <div class="body__text" style="padding:10px 0">
                <p id="my-task-result-summary"></p>
                <div class="status-row">
                    <span class="status-label">Total EXP</span>
                    <span class="status-value" id="my-task-result-exp"></span>
                </div>
                <div class="status-row">
                    <span class="status-label">Level</span>
                    <span class="status-value" id="my-task-result-level"></span>
                </div>
            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span>Quest Logged</span>
                </h2>
                <div class="body__text" style="padding:10px 0"></div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button type="button" aria-label="Close" class="cyber-btn" id="my-task-result-close" data-action="Cancel">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Close</span>
        </button>
    </div>
</div>
