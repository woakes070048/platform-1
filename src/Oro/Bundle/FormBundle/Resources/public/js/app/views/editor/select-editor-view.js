define(function(require) {
    'use strict';

    const TextEditorView = require('./text-editor-view');
    const _ = require('underscore');
    const $ = require('jquery');
    require('jquery.select2');

    /**
     * Select cell content editor. The cell value should be a value field.
     * The grid will render a corresponding label from the `options.choices` map.
     * The editor will use the same mapping
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by frontend type
     *       {column-name-1}:
     *         frontend_type: select
     *         choices: # required
     *           key-1: First
     *           key-2: Second
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         choices: # required
     *           key-1: First
     *           key-2: Second
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * choices                                             | Key-value set of available choices
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.key_type         | Optional. Specifies type of value that should be sent to server. Currently string/boolean/number key types are supported.
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.validation_rules                     | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.className - CSS class name for editor element
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {Object} options.choices - Key-value set of available choices
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports SelectEditorView
     */
    const SelectEditorView = TextEditorView.extend(/** @lends SelectEditorView.prototype */{
        className: 'select-editor',

        SELECTED_ITEMS_H_MARGIN_BETWEEN: 5,
        SELECTED_ITEMS_V_MARGIN_BETWEEN: 6,
        SELECTED_ITEMS_H_INCREMENT: 2,

        events: {
            'updatePosition': 'updatePosition',
            'select2-open': 'onSelect2Open',
            'select2-close': 'onSelect2Close',
            'mousedown .select2-choices': function() {
                this._isSelection = true;
            },
            'mouseup .select2-choices': function() {
                delete this._isSelection;
            },
            'focus .select2-choice': function() {
                this.$('.select2-focusser').trigger('focus');
            }
        },

        keyType: 'string',

        /**
         * @inheritdoc
         */
        constructor: function SelectEditorView(options) {
            SelectEditorView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            if (options.key_type) {
                this.keyType = options.key_type;
            } else {
                if (Array.isArray(options.choices)) {
                    this.keyType = 'number';
                }
            }
            SelectEditorView.__super__.initialize.call(this, options);
            this.availableChoices = this.getAvailableOptions(options);
            this.prestine = true;
        },

        getAvailableOptions: function(options) {
            let results;
            let restrictionExpectation;
            const choices = this.options.choices;
            const fieldRestrictions = _.result(this.options, 'fieldRestrictions');
            if (fieldRestrictions) {
                restrictionExpectation = _.result(fieldRestrictions, 'mode') === 'disallow';
                results = _.map(choices, function(value, label) {
                    const presentInRestriction = _.indexOf(fieldRestrictions.values, value) !== -1;
                    return {
                        id: value,
                        text: label,
                        disabled: presentInRestriction === restrictionExpectation
                    };
                });
            } else {
                results = _.map(choices, function(value, label) {
                    return {
                        id: value,
                        text: label
                    };
                });
            }
            return results;
        },

        render: function() {
            SelectEditorView.__super__.render.call(this);
            const select2options = this.getSelect2Options();
            this.$('input[name=value]').inputWidget('create', 'select2', {initializeOptions: select2options});
            // select2 stops propagation of keydown event if key === ENTER or TAB
            // need to restore this functionality
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(), e => {
                this.onGenericEnterKeydown(e);
                this.onGenericTabKeydown(e);
                this.onGenericArrowKeydown(e);
            });

            // must prevent selection on TAB
            this.$('input.select2-input').bindFirst('keydown' + this.eventNamespace(), e => {
                const prestine = this.prestine;
                this.prestine = false;
                switch (e.keyCode) {
                    case this.ENTER_KEY_CODE:
                        if (prestine) {
                            e.stopImmediatePropagation();
                            e.preventDefault();
                            this.$('input[name=value]').inputWidget('close');
                            this.onGenericEnterKeydown(e);
                        }
                        break;
                    case this.TAB_KEY_CODE:
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        this.$('input[name=value]').inputWidget('close');
                        this.onGenericTabKeydown(e);
                        break;
                }
                this.onGenericArrowKeydown(e);
            });
            this.$('input.select2-input').on('keydown' + this.eventNamespace(), e => {
                // Due to this view can be already disposed in bound first handler,
                // we have to check if it's disposed
                if (!this.disposed && !this.isChanged()) {
                    SelectEditorView.__super__.onGenericEnterKeydown.call(this, e);
                }
            });
            this.$('.select2-search-choice-close').on('mousedown', () => {
                this._isSelection = true;
                this.$('.select2-choice').one('focus', () => {
                    delete this._isSelection;
                });
            });
        },

        updatePosition: function() {
            this.$('input[name=value]').inputWidget('updatePosition');
        },

        /**
         * Prepares and returns Select2 options
         *
         * @returns {Object}
         */
        getSelect2Options: function() {
            return {
                placeholder: this.getPlaceholder(' '),
                allowClear: !this.getValidationRules().NotBlank,
                selectOnBlur: false,
                openOnEnter: false,
                dropdownCssClass: 'inline-editor__select2-drop',
                dontSelectFirstOptionOnOpen: true,
                data: {results: this.availableChoices}
            };
        },

        /**
         * Returns Select2 data from corresponding element
         *
         * @returns {Object}
         */
        getSelect2Data: function() {
            return this.$('.select2-choice').data('select2-data');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this._isFocused = false;
            this.$('.select2-focusser').off(this.eventNamespace());
            this.$('input.select2-input').off(this.eventNamespace());
            this.$('input[name=value]').inputWidget('dispose');
            SelectEditorView.__super__.dispose.call(this);
        },

        onSelect2Open: function(e) {
            const select2 = this.$(e.target).data('select2');
            if (!select2) {
                return;
            }
            select2.dropdown.on('mousedown' + this.eventNamespace(), () => {
                this._isSelection = true;// to suppress focusout event
            });
            select2.dropdown.on('mouseup' + this.eventNamespace(), () => {
                delete this._isSelection;
            });
        },

        onSelect2Close: function(e) {
            const select2 = this.$(e.target).data('select2');
            if (!select2) {
                return;
            }
            select2.dropdown.off(this.eventNamespace());
        },

        parseRawValue: function(value) {
            if (_.isBoolean(value)) {
                return value ? '1' : '0';
            } else {
                return '' + value;
            }
        },

        focus: function() {
            const isFocused = this.isFocused();
            this.$('input[name=value]').inputWidget('open');
            if (!isFocused) {
                // trigger custom focus event as select2 doesn't trigger 'select2-focus' when focused manually
                this.trigger('focus');
                this._isFocused = true;
            }
        },

        /**
         * Handles focusout event
         *
         * @param {jQuery.Event} e
         */
        onFocusout: function(e) {
            const select2 = this.$('input[name=value]').data('select2');

            if (!this._isSelection && !select2 || !select2.opened()) {
                _.defer(() => {
                    if (!this.disposed && !$.contains(this.el, document.activeElement)) {
                        SelectEditorView.__super__.onFocusout.call(this, e);
                    }
                });
            }
        },

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== this.getModelValue();
        },

        /**
         * Returns true if element is focused
         *
         * @returns {boolean}
         */
        isFocused: function() {
            return this.$('.select2-container-active').length;
        },

        /**
         * Returns data which should be sent to the server
         *
         * @returns {Object}
         */
        getServerUpdateData: function() {
            const data = {};
            let value = this.getValue();
            switch (this.keyType) {
                case 'number':
                    value = parseFloat(value);
                    break;
                case 'boolean':
                    value = (value === '1');
                    break;
                default:
                    break;
            }
            data[this.fieldName] = value;
            return data;
        },

        onGenericKeydown: function(e) {
            this.onGenericEnterKeydown(e);
            this.onGenericTabKeydown(e);
            this.onGenericArrowKeydown(e);
        }
    }, {
        processMetadata: function(columnMetadata) {
            if (_.isUndefined(columnMetadata.choices)) {
                throw new Error('`choices` is required option');
            }
            if (!columnMetadata.inline_editing.editor.view_options) {
                columnMetadata.inline_editing.editor.view_options = {};
            }
            columnMetadata.inline_editing.editor.view_options.choices = columnMetadata.choices;
        }
    });

    return SelectEditorView;
});
