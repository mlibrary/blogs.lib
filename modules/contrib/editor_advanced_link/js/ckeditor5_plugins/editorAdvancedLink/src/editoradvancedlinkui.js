// eslint-disable-next-line import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
  LabeledFieldView,
  createLabeledInputText,
  CollapsibleView,
  FormRowView,
} from 'ckeditor5/src/ui';
import { additionalFormElements, additionalFormGroups } from './utils';

export default class EditorAdvancedLinkUI extends Plugin {
  init() {
    // Using example from CKEditor5 docs:
    // https://ckeditor.com/docs/ckeditor5/latest/framework/how-tos.html#how-to-add-a-custom-button-to-the-link-dialog
    const editor = this.editor;
    const contextualBalloonPlugin = editor.plugins.get('ContextualBalloon');
    const linkUI = editor.plugins.get('LinkUI');

    this.listenTo(contextualBalloonPlugin, 'change:visibleView', (evt, name, visibleView) => {
      const formView = linkUI.formView;

      // If the link form has not been initialized yet or the visible view does not match it,
      // do nothing.
      if (!formView || visibleView !== formView) {
        return;
      }

      // Detach the listener.
      this.stopListening(contextualBalloonPlugin, 'change:visibleView');
      this.linkFormView = formView;

      this._registerComponents();
    });
  }

  /**
   * Add advanced fields to link popup.
   */
  _registerComponents() {
    const editor = this.editor;
    const linkFormView = editor.plugins.get('LinkUI').formView;

    // If for any reason the link form is not available,
    // bail out early to avoid runtime errors.
    if (!linkFormView) {
      return;
    }

    const linkCommand = editor.commands.get('link');
    const { enabledModelNames } = editor.plugins.get(
      'EditorAdvancedLinkEditing',
    );

    // Insert below CKEditor5's "Displayed text" field.
    let insertIndex = 2;
    let fieldCount = 0;

    enabledModelNames.forEach((modelName) => {
      // Skip if field already exists.
      if (typeof linkFormView[modelName] === 'undefined') {
        const options = additionalFormElements[modelName];
        let parentGroup = linkFormView[options.group];
        let parentForm = parentGroup ?? linkFormView;

        // Skip if group already exists.
        if (options.group && !parentGroup) {
          const groupOptions = additionalFormGroups[options.group];
          parentGroup = this._createGroup(options.group, groupOptions.label);

          // Insert group into link form.
          linkFormView.children.add(
            parentGroup,
            insertIndex
          );
          // Increase insert index for link form.
          insertIndex++;

          // Add group to focus array.
          linkFormView._focusables.add(parentGroup);
          linkFormView.focusTracker.add(parentGroup.element);

          // Track group in link form object.
          linkFormView[options.group] = parentGroup;
          parentForm = parentGroup;
        }

        const newTextField = this._createTextField(options.label);

        // Insert new row/field into parent form (group or link form).
        parentForm.children.add(
          this._createFormRow(newTextField),
          parentForm === linkFormView ? insertIndex : parentForm.children.length
        );

        // Increase insert index if field was added to link form.
        if (parentForm === linkFormView) {
          insertIndex++;
        }

        linkFormView._focusables.add(newTextField);
        linkFormView.focusTracker.add(newTextField.element);
        // Track field in link form object.
        linkFormView[modelName] = newTextField;
        fieldCount++;

        // Bind values of new fields.
        linkFormView[modelName].fieldView
          .bind('value').to(linkCommand, modelName);

        // Note: Copy & pasted from LinkUI.
        // https://github.com/ckeditor/ckeditor5/blob/f0a093339631b774b2d3422e2a579e27be79bbeb/packages/ckeditor5-link/src/linkui.js#L333-L333
        linkFormView[modelName].fieldView.value = linkCommand[modelName] || '';
      }
    });

    if (fieldCount > 0) {
      this._handleExtraFormFieldSubmit(enabledModelNames);
    }
  }

  /**
   * Creates a labeled input view for text field with a label.
   */
  _createTextField(label) {
    const { editor } = this;
    const { locale } = editor;

    const t = locale.t;
    const labeledInput = new LabeledFieldView(locale, createLabeledInputText);
    labeledInput.label = label;
    labeledInput.class = 'ck-labeled-field-view_full-width';
    return labeledInput;
  }

  /**
   * Creates a row.
   */
  _createFormRow(child) {
    const { editor } = this;
    const { locale } = editor;

    return new FormRowView(locale, {
      children: [
        child
      ],
      class: [
        'ck-form__row_large-bottom-padding'
      ]
    });
  }

  /**
   * Creates a collapsible group.
   */
  _createGroup(groupName, label) {
    const { editor } = this;
    const { locale } = editor;

    const group = new CollapsibleView(locale);
    group.label = label;
    group.set('isCollapsed', true);
    return group;
  }

  /**
   * Update link form state.
   */
  _handleExtraFormFieldSubmit(modelNames) {
    const { editor } = this;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');

    // Attach listener to link editing form submit.
    // This event listener is not executed when link properties (target) are updated.
    this.listenTo(linkFormView, 'submit', () => {
      if (linkFormView.isValid()) {
        const advancedAttributeValues = this._getSubmittedValues(linkFormView, modelNames);

        linkCommand.once('execute', (evt, args) => {
          // CKEditor v45 includes a 'displayed text' input value. If present,
          // send this information along so we can properly update the selection.
          let displayedText = '';
          if (typeof linkFormView.displayedTextInputView != 'undefined') {
            displayedText = linkFormView.displayedTextInputView.fieldView.element.value;
          }
          // Inject advanced attributes into link command arguments.
          args[1]['advanced_attributes'] = advancedAttributeValues;
          args[1]['advanced_attributes']['displayedText'] = displayedText;
        }, {
          priority: 'highest'
        });
      }
    }, {
      priority: 'high',
    });
  }

  _getSubmittedValues(linkFormView, modelNames) {
    const values = modelNames.reduce((state, modelName) => {
      const oldValue = linkFormView[modelName].fieldView.value;
      const newValue = linkFormView[modelName].fieldView.element.value;
      state[modelName] = linkFormView[modelName].fieldView.element.value;
      return state;
    }, {});

    return values;
  }

}
