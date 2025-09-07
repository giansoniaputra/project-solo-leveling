<div class="modal" popover="auto" id="upgrade">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="version">v001.e1349837856</div>
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2>
                <span id="modal-title">Login</span>
            </h2>
            <div class="body__text" id="modal-body" style="padding:10px 0">
                <div>
                    <input type="password" maxlength="1" class="otp-input input">
                    <input type="password" maxlength="1" class="otp-input input" disabled>
                    <input type="password" maxlength="1" class="otp-input input" disabled>
                    <input type="password" maxlength="1" class="otp-input input" disabled>
                    <input type="password" maxlength="1" class="otp-input input" disabled>
                </div>


            </div>
            <div class="modal__glitch" aria-hidden="true">
                <h2>
                    <span id="modal-title-glitch">Login</span>
                </h2>
                <div class="body__text" id="modal-body-glitch" style="padding:10px 0">
                    <div>
                        <input type="password" maxlength="1" class="otp-input" disabled>
                        <input type="password" maxlength="1" class="otp-input" disabled>
                        <input type="password" maxlength="1" class="otp-input" disabled>
                        <input type="password" maxlength="1" class="otp-input" disabled>
                        <input type="password" maxlength="1" class="otp-input" disabled>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button aria-label="Cancel" class="cyber-btn" data-action="Cancel" popovertarget="upgrade" popovertargetaction="close">
            <span class="backdrop">
                <span class="corner"></span>
            </span>
            <kbd>esc</kbd>
            <span>Cancel</span>
        </button>
        <button autofocus aria-label="Proceed" class="cyber-btn" id="prossess" data-action="Proceed" popovertarget="upgrade" popovertargetaction="close">
            <span class="backdrop">
                <span class="corner"></span>
            </span>
            <kbd><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 4v7a4 4 0 0 1-4 4H4"></path>
                    <path d="m9 10-5 5 5 5"></path>
                </svg></kbd>
            <span>Proceed</span>
        </button>
    </div>
</div>
<input type="hidden" name="token" value="{{ csrf_token() }}">
