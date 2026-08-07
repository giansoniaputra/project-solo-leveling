const config = {
    accent: 'hsl(175, 100%, 77%)',
    shadow: 'hsl(10, 100%, 60%)',
    muted: false,
    theme: 'dark',
}

const audio = {
    slide: new Audio(
        'https://cdn.freesound.org/previews/367/367997_6512973-lq.mp3'
    ),
    accept: new Audio(
        'https://cdn.freesound.org/previews/220/220166_4100837-lq.mp3'
    ),
    reject: new Audio(
        'https://cdn.freesound.org/previews/657/657950_6142149-lq.mp3'
    ),
}
const update = () => {
    audio.slide.muted = audio.accept.muted = audio.reject.muted = config.muted
    document.documentElement.dataset.theme = config.theme
    document.documentElement.style.setProperty('--accent', config.accent)
    document.documentElement.style.setProperty('--shadow', config.shadow)
}
update()

// The Tweakpane panel is just a dev-only nicety for tweaking accent
// color/theme/mute live. It loads from an external CDN that's prone to
// CORS/availability failures — a static top-level `import` would let that
// failure take the WHOLE module down with it (including sound/popover
// wiring below). Load it dynamically and swallow failures instead.
import('https://cdn.skypack.dev/tweakpane@4.0.4')
    .then(({ Pane }) => {
        const ctrl = new Pane({
            title: 'config',
            expanded: false,
        })

        const sync = (event) => {
            if (
                !document.startViewTransition ||
                event.target.controller.view.labelElement.innerText !== 'theme'
            )
                return update()
            document.startViewTransition(() => update())
        }

        ctrl.addBinding(config, 'accent')
        ctrl.addBinding(config, 'shadow')
        ctrl.addBinding(config, 'muted')
        ctrl.addBinding(config, 'theme', {
            label: 'theme',
            options: {
                system: 'system',
                light: 'light',
                dark: 'dark',
            },
        })

        ctrl.on('change', sync)
    })
    .catch((error) => {
        console.warn('Tweakpane config panel failed to load, skipping it:', error)
    })

// Wires sound + glitch-timing + keyboard shortcuts onto one popover modal.
// Called once per [popover] element on the page (see bottom of file) so
// pages with several modals (quest list, quest detail, status, ...) each
// get their own independent state instead of only the first modal in the DOM.
const wireModal = (popover) => {
    const actions = popover.querySelector('.modal__actions')
    if (!actions) return
    const modalGlitch = popover.querySelector('.modal__glitch')

    const handleKeyPress = ({ key }) => {
        if (key === 'Escape') {
            popover.dataset.action = 'Cancel'
        } else if (key === 'Enter' && !actions.matches(':focus-within')) {
            popover.dataset.action = 'Proceed'
            popover.hidePopover()
        }
    }

    let glitched
    let glitchClock
    const kickOff = () => {
        if (!modalGlitch) return
        glitchClock = setTimeout(
            () => {
                modalGlitch.style.setProperty('animation-name', 'glitch')
                requestAnimationFrame(async () => {
                    await Promise.allSettled(
                        modalGlitch.getAnimations().map((a) => a.finished)
                    )
                    glitched = true
                    modalGlitch.style.removeProperty('animation-name')
                    kickOff()
                })
            },
            !glitched ? 1_500 : Math.random() * 10_000 + 2_000
        )
    }

    popover.addEventListener('toggle', async (event) => {
        if (event.newState === 'open') {
            setTimeout(() => {
                audio.slide.currentTime = 0
                audio.slide.play()
            }, 200)
            window.addEventListener('keydown', handleKeyPress)
            kickOff()
        } else {
            if (glitchClock !== undefined) clearTimeout(glitchClock)
            if (!config.muted) {
                audio[
                    popover.dataset.action === 'Proceed' ? 'accept' : 'reject'
                ].currentTime = 0
                audio[popover.dataset.action === 'Proceed' ? 'accept' : 'reject'].play()
            }
            glitched = false
            await Promise.allSettled(popover.getAnimations().map((a) => a.finished))
            window.removeEventListener('keydown', handleKeyPress)
            delete popover.dataset.action
        }
    })

    actions.addEventListener('click', (event) => {
        if (event.target.tagName === 'BUTTON') {
            popover.dataset.action = event.target.dataset.action
        }
    })
}

document.querySelectorAll('[popover]').forEach(wireModal)

const upgradeButton = document.querySelector('[aria-label="Upgrade"]')
if (upgradeButton) {
    const handleUpgrade = ({ key }) => {
        if (
            upgradeButton.matches('[data-upgrading="true"]') ||
            document.querySelector('[popover]:popover-open')
        )
            return
        if (key === 'u') {
            upgradeButton.dataset.upgrading = true
            requestAnimationFrame(async () => {
                await Promise.allSettled(
                    upgradeButton.getAnimations({ subtree: true }).map((a) => a.finished)
                )
                document.querySelector('[popover]').showPopover()
                delete upgradeButton.dataset.upgrading
            })
        }
    }
    window.addEventListener('keydown', handleUpgrade)
}
