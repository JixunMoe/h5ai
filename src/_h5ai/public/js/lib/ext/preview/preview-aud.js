const {dom} = require('../../util');
const format = require('../../core/format');
const allsettings = require('../../core/settings');
const preview = require('./preview');
const VideoAudioKeypress = require('./VideoAudioKeypress');

const settings = Object.assign({
    enabled: false,
    autoplay: true,
    types: []
}, allsettings['preview-aud']);
const tpl = '<audio id="pv-content-aud"/>';

let autoPlayNext = false;

const adjust = () => {
    const el = dom('#pv-content-aud')[0];
    if (!el) {
        return;
    }

    preview.setLabels([
        preview.item.label,
        format.formatDate(el.duration * 1000, 'm:ss')
    ]);
};

const addUnloadFn = el => {
    el.unload = () => {
        el.src = '';
        el.load();
    };
};

// eslint-disable-next-line func-style
const load = function loadAudio(item) {
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

const keypress = VideoAudioKeypress.create('pv-content-aud', false);
const init = () => {
    if (settings.enabled) {
        preview.register(settings.types, {load, adjust, keypress});
    }
};

init();
