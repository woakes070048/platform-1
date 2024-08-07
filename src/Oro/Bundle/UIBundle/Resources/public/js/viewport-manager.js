import mediator from 'oroui/js/mediator';
import error from 'oroui/js/error';

const viewportManager = {
    /**
     * @property {Object}
     */
    mediaTypes: null,

    /**
     * @inheritdoc
     */
    initialize() {
        this.mediaTypes = this._prepareMediaTypes(this.getBreakpoints());

        this._subscribeToAll();
    },

    /**
     * Check viewport ability
     * @param mediaTypes
     * @returns {boolean}
     */
    isApplicable(mediaTypes) {
        return [mediaTypes || 'all']
            .flat(Infinity)
            .some(item => {
                const type = this.getMediaType(item);

                if (type === void 0) {
                    return false;
                }

                return type.matches;
            });
    },

    /**
     * Get applicable breakpoint name from the list
     * @param {Array} mediaTypes
     * @returns {string}|void 0
     */
    getApplicableBreakpointName(mediaTypes) {
        return mediaTypes.find(mediaType => this.isApplicable([mediaType]));
    },

    /**
     * @param {HTMLElement} [context]
     * @param {Function} [callback]
     * @returns {any}
     */
    getBreakpoints(context, callback) {
        if (!context) {
            context = document.documentElement;
        }

        const cssProperty =
            window.getComputedStyle(context).getPropertyValue('--breakpoints').trim() || '{}';
        const result = {all: 'all', ...JSON.parse(cssProperty)};

        return typeof callback === 'function' ? callback(result) : result;
    },

    _prepareMediaTypes(breakpoints) {
        return Object.entries(breakpoints)
            .reduce((mediaTypes, [key, value]) => Object.assign(mediaTypes, {
                [key]: Object.assign(window.matchMedia(value), {mediaType: key})
            }), {});
    },

    _subscribe(mediaType, handler) {
        // There is no reason to subscribe on 'all' event because it never changes
        if (mediaType === 'all') {
            return;
        }

        const mql = this.getMediaType(mediaType);

        if (mql) {
            mql.addEventListener('change', handler);
        } else {
            error.showErrorInConsole(`The media type "${mediaType}" is not defined`);
        }
    },

    getMediaType(mediaType) {
        return this.mediaTypes[mediaType];
    },

    _subscribeToAll() {
        Object.keys(this.mediaTypes).forEach(mediaType => this._subscribe(mediaType, this._onChangeHandler));
    },

    _onChangeHandler(event) {
        mediator.trigger(`viewport:change`, event);
        mediator.trigger(`viewport:${event.target.mediaType}`, event);
    }
};

export default viewportManager;
