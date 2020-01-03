const seekDelta = 5;
const volumeDelta = 0.07;

const togglePlay = el => {
    if (el.paused) {
        el.play();
    } else {
        el.pause();
    }
};

// eslint-disable-next-line complexity
const createKeypressHandler = (id, allowFullscreen) => key => {
    const el = document.getElementById(id);
    if (!el) {
        return false;
    }

    let handled = true;
    switch (key) {
    case 37: // left
        el.currentTime -= seekDelta;
        break;

    case 39: // right
        el.currentTime += seekDelta;
        break;

    case 38: // up
        el.volume += volumeDelta;
        break;

    case 40: // down
        el.volume -= volumeDelta;
        break;

    case 32: // space
        togglePlay(el);
        break;

    case 77: // 'm' -- mute
        el.muted = !el.muted;
        break;

    case 70: // 'f' -- full screen
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else if (allowFullscreen) {
            el.requestFullscreen();
        }
        break;

    default:
        handled = false;
    }

    return handled;
};

module.exports = {
    create: createKeypressHandler,
    togglePlay
};
