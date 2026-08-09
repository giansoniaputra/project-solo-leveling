<div class="modal" popover="manual" id="task-create">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span>New Task</span>
            </h2>
            <div class="body__text" style="padding:10px 0">
                <div class="task-field">
                    <label class="status-label" for="task-create-date">Date</label>
                    <input type="date" class="task-input" id="task-create-date">
                </div>
                <div class="task-field">
                    <label class="status-label" for="task-create-name">Name</label>
                    <input type="text" class="task-input" id="task-create-name" placeholder="Task name">
                </div>
                <div class="task-field">
                    <label class="status-label" for="task-create-description">Description (optional)</label>
                    <textarea class="task-input" id="task-create-description" rows="3" placeholder="Details..."></textarea>
                </div>
                <div id="task-create-message" style="min-height:1.2em"></div>
            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span>New Task</span>
                </h2>
                <div class="body__text" style="padding:10px 0"></div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button type="button" aria-label="Cancel" class="cyber-btn" id="task-create-cancel" data-action="Cancel">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Cancel</span>
        </button>
        <button type="button" aria-label="Proceed" class="cyber-btn" id="task-create-proceed" data-action="Proceed">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 4v7a4 4 0 0 1-4 4H4"></path>
                    <path d="m9 10-5 5 5 5"></path>
                </svg></kbd>
            <span>Proceed</span>
        </button>
    </div>
</div>
