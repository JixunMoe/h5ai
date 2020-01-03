const {dom} = require('../../util');
const allsettings = require('../../core/settings');
const preview = require('./preview');
const VideoAudioKeypress = require('./VideoAudioKeypress');

const settings = Object.assign({
    enabled: false,
    autoplay: true,
    types: []
}, allsettings['preview-vid']);
const tpl = '<video id="pv-content-vid"/>';

let autoPlayNext = false;

const adjust = () => {
    const el = dom('#pv-content-vid')[0];
    if (!el) {
        return;
    }

    const elW = el.offsetWidth;
    const elVW = el.videoWidth;
    const elVH = el.videoHeight;

    preview.setLabels([
        preview.item.label,
        String(elVW) + 'x' + String(elVH),
        String((100 * elW / elVW).toFixed(0)) + '%'
    ]);
};

const addUnloadFn = el => {
    el.unload = () => {
        el.src = '';
        el.load();
    };
};

// eslint-disable-next-line func-style
const load = function loadVideo(item) {
    return new Promise(resolve => {
        const $el = dom(tpl)
            .on('loadedmetadata', () => {
                if (settings.autoplay || autoPlayNext) {
                    $el[0].play();
                }
                resolve($el);
            })
            .on('click', ev => {
                VideoAudioKeypress.togglePlay($el[0]);
                ev.preventDefault();
            })
            .on('ended', () => {
                autoPlayNext = true;
                this.next();
            })
            .attr('controls', 'controls');
        addUnloadFn($el[0]);
        $el.attr('src', item.absHref);
    });
};

const keypress = VideoAudioKeypress.create('pv-content-vid');

const init = () => {
    if (settings.enabled) {
        preview.register(settings.types, {load, adjust, keypress});
    }
};

init();
