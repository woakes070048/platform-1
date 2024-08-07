define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const BaseModel = require('oroui/js/app/models/base/model');
    const InlineEditorWrapperView = require('../views/inline-editable-wrapper-view');
    const frontendTypeMap = require('../../tools/frontend-type-map');
    const overlayTool = require('oroui/js/tools/overlay');
    const loadModules = require('oroui/js/app/services/load-modules');

    /**
     * Allows to connect inline editors on view pages.
     * Currently used only for tags-editor. See [index of supported editors](../editor)
     * @TODO update after connecting other editors
     *
     * Sample:
     *
     * ```twig
     * {% import '@OroUI/macros.html.twig' as UI %}
     * <div {{ UI.renderPageComponentAttributes({
     *    module: 'oroform/js/app/components/inline-editable-view-component',
     *    options: {
     *        frontend_type: 'tags',
     *        inputAriaLabel: 'Input aria-label text',
     *        value: oro_tag_get_list(entity),
     *        fieldName: 'tags',
     *        insertEditorMethod: 'overlay', // Possible values are 'overlay' or [any of supported by the containerMethod](https://github.com/chaplinjs/chaplin/blob/master/docs/chaplin.view.md#containerMethod)
     *        metadata: {
     *            inline_editing: {
     *                enable: is_granted('oro_tag_assign_unassign'),
     *                save_api_accessor: {
     *                    route: 'oro_api_post_taggable',
     *                    http_method: 'POST',
     *                    default_route_parameters: {
     *                        entity: oro_class_name(entity, true),
     *                        entityId: entity.id
     *                    }
     *                },
     *                autocomplete_api_accessor: {
     *                    class: 'oroui/js/tools/search-api-accessor',
     *                    search_handler_name: 'tags',
     *                    label_field_name: 'name'
     *                },
     *                editor: {
     *                    view_options: {
     *                        permissions: {
     *                            oro_tag_create: is_granted('oro_tag_create')
     *                        }
     *                    }
     *                }
     *            },
     *            view_options: {
     *              tooltip: 'Tooltip text'
     *            }
     *        }
     *    }
     * }) }}></div>
     * ```
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options._sourceElement - the element to which the view should be connected (passed automatically when
     *                                          page component is [connected through DOM attributes](../../../../UIBundle/Resources/doc/reference/page-component.md))
     * @param {string} options.frontend_type - frontend type, please find [available keys here](../../public/js/tools/frontend-type-map.js)
     * @param {*} options.value - value to edit
     * @param {string} options.fieldName - field name to use when sending value to server
     * @param {string} options.inputAriaLabel - text to aria-label attr for input field
     * @param {string} options.insertEditorMethod - 'overlay', // Possible values are 'overlay' or [any of supported by the containerMethod](https://github.com/chaplinjs/chaplin/blob/master/docs/chaplin.view.md#containerMethod)
     * @param {Object} options.metadata.inline_editing - inline-editing configuration
     *
     * @augments BaseComponent
     * @exports InlineEditableViewComponent
     */
    const InlineEditableViewComponent = BaseComponent.extend(/** @lends InlineEditableViewComponent.prototype */{
        options: {
            overlay: {
                zIndex: 1,
                position: {
                    my: 'left top',
                    at: 'left-7 top-7',
                    collision: 'flipfit'
                }
            },
            metadata: {
                inline_editing: {
                    enable: false,
                    save_api_accessor: {
                        'class': 'oroui/js/tools/api-accessor'
                    }
                }
            },
            insertEditorMethod: 'overlay',
            widthIncrement: 15,
            fieldName: 'value',
            messages: {
                success: __('oro.form.inlineEditing.successMessage'),
                processingMessage: __('oro.form.inlineEditing.saving_progress'),
                preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
            },
            id: null
        },

        ESCAPE_KEY_CODE: 27,

        /**
         * @inheritdoc
         */
        constructor: function InlineEditableViewComponent(options) {
            InlineEditableViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.options, options);

            this.insertEditorMethod = options.insertEditorMethod;
            this.overlayOptions = options.overlay;
            this.widthIncrement = options.widthIncrement;
            this.messages = options.messages;
            this.eventChannelId = options.eventChannelId;
            this.inlineEditingOptions = options.metadata.inline_editing;
            const waitors = [];
            this.fieldName = options.fieldName;
            this.inputAriaLabel = options.inputAriaLabel;
            // frontend type mapped to viewer/editor/reader
            const classes = frontendTypeMap[options.frontend_type];
            this.classes = classes;
            this.metadata = options.metadata;
            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);
            const viewOptions = this.getViewOptions();
            if (this.inlineEditingOptions.enable) {
                const ViewerWrapper = classes.viewerWrapper || InlineEditorWrapperView;
                this.wrapper = new ViewerWrapper({
                    el: options._sourceElement,
                    autoRender: true
                });

                viewOptions.el = this.wrapper.getContainer();
                this.view = new classes.viewer(viewOptions);
                if (this.classes.editor.processMetadata) {
                    waitors.push(this.classes.editor.processMetadata(this.metadata));
                }
                this.wrapper.on('start-editing', this.enterEditMode, this);
                waitors.push(loadModules.fromObjectProp(this.inlineEditingOptions.save_api_accessor, 'class').then(
                    () => {
                        const ConcreteApiAccessor = this.inlineEditingOptions.save_api_accessor['class'];
                        this.saveApiAccessor = new ConcreteApiAccessor(
                            _.omit(this.inlineEditingOptions.save_api_accessor, 'class'));
                    }
                ));
            } else {
                viewOptions.el = options._sourceElement;
                this.view = new classes.viewer(viewOptions);
            }
            this.deferredInit = $.when(...waitors);
        },

        isInsertEditorModeOverlay: function() {
            return this.insertEditorMethod === 'overlay';
        },

        getViewOptions: function() {
            return $.extend(true, {}, _.result(this.metadata, 'view_options', {}), {
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName
            });
        },

        enterEditMode: function() {
            if (!this.view.disposed && this.view.$el) {
                this.view.$el.removeClass('save-fail');
            }

            const viewInstance = this.createEditorViewInstance();

            if (this.isInsertEditorModeOverlay()) {
                const overlayOptions = $.extend(true, {}, this.overlayOptions, {
                    position: {
                        of: this.wrapper.$el
                    }
                });

                const overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
                this.listenTo(viewInstance, 'dispose', overlay.remove.bind(overlay));
            } else {
                this.view.$el.hide();
            }

            this.initializeEditorListeners(this.editorView);

            return viewInstance;
        },

        createEditorViewInstance: function() {
            const View = this.classes.editor;

            this.editorView = new View(this.getEditorOptions());
            this.resizeTo(this.editorView, this.wrapper);

            return this.editorView;
        },

        getEditorOptions: function() {
            const viewConfiguration = this.inlineEditingOptions.editor
                ? this.inlineEditingOptions.editor.view_options
                : {};

            if (!this.isInsertEditorModeOverlay()) {
                viewConfiguration.container = this.view.$el;
                viewConfiguration.containerMethod = this.insertEditorMethod;
                viewConfiguration.autoAttach = true;
            }

            return $.extend(true, {}, viewConfiguration, {
                className: 'inline-view-editor inline-editor-wrapper',
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName,
                inputAriaLabel: this.inputAriaLabel
            });
        },

        initializeEditorListeners: function(viewInstance) {
            this.listenTo(viewInstance, {
                keydown: this.onGenericEscapeKeydown,
                focus: function() {
                    mediator.trigger('inlineEditor:focus', viewInstance);
                },
                blur: function() {
                    if (viewInstance.isChanged()) {
                        this.saveCurrentCell();
                    }
                }
            });

            viewInstance.trigger('focus');

            this.listenTo(viewInstance, 'saveAction', this.saveCurrentCell);
            this.listenTo(viewInstance, 'saveAndExitAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAction', this.exitEditMode, true);
            this.listenTo(viewInstance, 'saveAndEditNextAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditNextAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditPrevAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditPrevAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditNextRowAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditNextRowAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditPrevRowAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditPrevRowAction', this.exitEditMode);
            this.listenTo(mediator, 'inlineEditor:focus', this.onInlineEditorFocus);
        },

        onInlineEditorFocus: function(view) {
            if (!this.editorView || view === this.editorView) {
                return;
            }
            if (!this.editorView.isChanged()) {
                this.exitEditMode();
            }
        },

        exitEditMode: function() {
            this.editorView.dispose();
            if (!this.isInsertEditorModeOverlay()) {
                this.view.$el.show();
            }
            delete this.editorView;
        },

        saveCurrentCellAndExit: function() {
            if (this.saveCurrentCell(false)) {
                this.exitEditMode(true);
            }
        },

        saveCurrentCell: function(exit) {
            if (!this.editorView) {
                throw Error('Edit mode disabled');
            }
            if (!this.editorView.isChanged()) {
                return true;
            }
            if (!this.editorView.isValid()) {
                this.editorView.trigger('focus');
                return false;
            }
            const wrapper = this.wrapper;
            let serverUpdateData = this.editorView.getServerUpdateData();
            const modelUpdateData = this.editorView.getModelUpdateData();
            wrapper.$el.addClass('loading');
            const ctx = {
                view: wrapper,
                model: this.model,
                oldState: _.pick(this.model.toJSON(), _.keys(modelUpdateData)),
                messages: this.messages,
                eventChannelId: this.eventChannelId,
                updateData: modelUpdateData
            };
            this.updateModel(this.model, this.editorView, modelUpdateData);
            if (this.saveApiAccessor.initialOptions.field_name) {
                const keys = _.keys(serverUpdateData);
                if (keys.length > 1) {
                    throw new Error('Only single field editors are supported with field_name option');
                }
                const newData = {};
                newData[this.saveApiAccessor.initialOptions.field_name] = serverUpdateData[keys[0]];
                serverUpdateData = newData;
            }
            let savePromise = this.saveApiAccessor.send(this.model.toJSON(), serverUpdateData, {}, {
                processingMessage: this.messages.processingMessage,
                preventWindowUnload: this.messages.preventWindowUnload
            });

            if (this.classes.editor.processSavePromise) {
                savePromise = this.classes.editor.processSavePromise(savePromise, this.metadata);
            }
            savePromise.done(InlineEditableViewComponent.onSaveSuccess.bind(ctx))
                .fail(InlineEditableViewComponent.onSaveError.bind(ctx))
                .always(function() {
                    wrapper.$el.removeClass('loading');
                });
            if (exit !== false) {
                this.exitEditMode();
            }
            return true;
        },

        updateModel: function(model, editorView, updateData) {
            // assume "undefined" as delete value request
            for (const key in updateData) {
                if (updateData.hasOwnProperty(key)) {
                    if (updateData[key] === void 0) {
                        model.unset(key);
                        delete updateData[key];
                    }
                }
            }
            model.set(updateData);
        },

        /**
         * Resizes editor to base view width
         */
        resizeTo: function(view, baseView) {
            view.$el.css({
                width: baseView.$el.outerWidth() + this.widthIncrement
            });
        },

        /**
         * Generic keydown handler, which handles ESCAPE
         *
         * @param {$.Event} e
         */
        onGenericEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                this.exitEditMode(true);
                e.preventDefault();
            }
        }
    }, {
        onSaveSuccess: function(response) {
            if (!this.view.disposed && this.view.$el) {
                this.view.$el.addClassTemporarily('save-success', 2000);
            }
            if (response && !this.model.disposed) {
                _.each(response, function(item, i) {
                    if (this.model.has(i)) {
                        this.model.set(i, item);
                    }
                }, this);
            }
            mediator.execute('showFlashMessage', 'success', this.messages.success);
            if (this.eventChannelId) {
                mediator.trigger('inlineEditor:' + this.eventChannelId + ':update', this.updateData);
            }
        },

        onSaveError: function(jqXHR) {
            const errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            if (!this.view.disposed && this.view.$el) {
                this.view.$el.addClass('save-fail');
            }
            if (!this.model.disposed) {
                this.model.set(this.oldState);
            }

            const errors = [];
            switch (errorCode) {
                case 400:
                    const jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (const i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push(..._.values(jqXHRerrors[i].errors));
                        }
                    }
                    break;
                case 403:
                    errors.push(__('You do not have permission to perform this action.'));
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });
        }
    });

    return InlineEditableViewComponent;
});
