@php
    $user = auth()->user();
    $expIntoLevel = $user->exp % 1000;
@endphp
<div class="modal" popover="auto" id="status-modal" style="position:fixed; top:60px; right:20px; inset:auto;">
    <section class="modal__body">
        <div class="body__backdrop">
            <div class="backdrop">
                <div class="corner"></div>
            </div>
        </div>
        <div class="body__content">
            <h2><span>Hunter Status</span></h2>
            <div class="body__text" style="padding:10px 0">
                <div class="status-row">
                    <span class="status-label">Name</span>
                    <span class="status-value">{{ $user->name }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">Level</span>
                    <span class="status-value" id="status-level-value">{{ $user->level }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">EXP</span>
                    <span class="status-value" id="status-exp-value">{{ $expIntoLevel }} / 1000</span>
                </div>
                <div class="status-exp-bar">
                    <div class="status-exp-fill" id="status-exp-fill" style="width: {{ $expIntoLevel / 10 }}%"></div>
                </div>

                <div class="status-row">
                    <span class="status-label">STR</span>
                    <span class="status-value" id="status-str-value">{{ $user->str }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">AGI</span>
                    <span class="status-value" id="status-agi-value">{{ $user->agi }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">PER</span>
                    <span class="status-value" id="status-per-value">{{ $user->per }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">VIT</span>
                    <span class="status-value" id="status-vit-value">{{ $user->vit }}</span>
                </div>
                <div class="status-row">
                    <span class="status-label">INT</span>
                    <span class="status-value" id="status-int-value">{{ $user->intelligence }}</span>
                </div>

                <form id="status-form">
                    <div class="status-row status-row--input">
                        <label class="status-label" for="status-weight">Weight (kg)</label>
                        <input type="number" step="0.1" class="status-input" name="weight" id="status-weight" value="{{ $user->weight }}">
                    </div>
                    <div class="status-row status-row--input">
                        <label class="status-label" for="status-height">Height (cm)</label>
                        <input type="number" step="0.1" class="status-input" name="height" id="status-height" value="{{ $user->height }}">
                    </div>
                    <div class="status-row status-row--input">
                        <label class="status-label" for="status-age">Age (years)</label>
                        <input type="number" class="status-input" name="age" id="status-age" value="{{ $user->age }}">
                    </div>
                    <div id="status-message" style="min-height:1.2em"></div>
                </form>
            </div>
        </div>
    </section>
    <div class="modal__actions">
        <button aria-label="Cancel" class="cyber-btn" data-action="Cancel" popovertarget="status-modal" popovertargetaction="hide">
            <span class="backdrop"><span class="corner"></span></span>
            <kbd>esc</kbd>
            <span>Cancel</span>
        </button>
        <button aria-label="Save" class="cyber-btn" id="status-save" data-action="Save">
            <span class="backdrop"><span class="corner"></span></span>
            <span>Save</span>
        </button>
    </div>
</div>
