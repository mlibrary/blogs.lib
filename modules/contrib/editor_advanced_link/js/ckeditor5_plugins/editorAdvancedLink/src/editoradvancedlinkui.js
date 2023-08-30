// eslint-disable-next-line import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
// eslint-disable-next-line import/no-extraneous-dependencies
import { LabeledFieldView, createLabeledInputText } from 'ckeditor5/src/ui';
import { additionalFormElements, additionalFormGroups } from './utils';
import DetailsView from './details/detailsview';

export default class EditorAdvancedLinkUi extends Plugin {
  init() {
    this.groups = {};

    // TRICKY: Work-around until the CKEditor team offers a better solution:
    // force the ContextualBalloon to get instantiated early thanks to
    // DrupalImage not yet being optimized like
    // https://github.com/ckeditor/ckeditor5/commit/c276c45a934e4ad7c2a8ccd0bd9a01f6442d4cd3#diff-1753317a1a0b947ca8b66581b533616a5309f6d4236a527b9d21ba03e13a78d8.
    if (this.editor.plugins.get('LinkUI')._createViews) {
      this.editor.plugins.get('LinkUI')._createViews();
    }
    this._changeFormToVertical();
    this._addExtraFormFields();
  }

  _addExtraFormFields() {
    const { editor } = this;
    // Copy the same solution from LinkUI as pointed out on
    // https://www.drupal.org/project/drupal/issues/3317769#comment-14985648 and
    // https://git.drupalcode.org/project/drupal/-/merge_requests/2909/diffs?commit_id=cc2cece3be1a9513b02a53d8a6862a6841ef4d5a.
    editor.plugins
      .get('ContextualBalloon')
      .on('set:visibleView', (evt, propertyName, newValue, oldValue) => {
        const linkFormView = editor.plugins.get('LinkUI').formView;
        if (newValue === oldValue || newValue !== linkFormView) {
          return;
        }

        const { enabledModelNames } = editor.plugins.get(
          'EditorAdvancedLinkEditing',
        );
        enabledModelNames.reverse().forEach((modelName) => {
          this._createExtraFormField(
            modelName,
            additionalFormElements[modelName],
          );
        });
        this._handleExtraFormFieldSubmit(enabledModelNames);
        // Add groups to form view last to ensure they're not beetween fields.
        this._addGroupsToFormView();
        this._moveTargetDecoratorToAdvancedGroup();
      });
  }

  _changeFormToVertical() {
    const linkFormView = this.editor.plugins.get('LinkUI').formView;
    linkFormView.extendTemplate({
      attributes: {
        class: ['ck-vertical-form', 'ck-link-form_layout-vertical'],
      },
    });
  }

  _createExtraFormField(modelName, options) {
    const { editor } = this;
    const { locale } = editor;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');
    if (typeof linkFormView[modelName] === 'undefined') {
      const fieldParent = options.group
        ? this._getGroup(options.group)
        : linkFormView;

      const extraFieldView = new LabeledFieldView(
        locale,
        createLabeledInputText,
      );
      extraFieldView.label = options.label;
      fieldParent.children.add(extraFieldView, 1);

      if (!options.group) {
        linkFormView._focusables.add(extraFieldView, 1);
        linkFormView.focusTracker.add(extraFieldView.element);
      }

      linkFormView[modelName] = extraFieldView;
      linkFormView[modelName].fieldView
        .bind('value')
        .to(linkCommand, modelName);
      // Note: Copy & pasted from LinkUI.
      // https://github.com/ckeditor/ckeditor5/blob/f0a093339631b774b2d3422e2a579e27be79bbeb/packages/ckeditor5-link/src/linkui.js#L333-L333
      linkFormView[modelName].fieldView.element.value =
        linkCommand[modelName] || '';
    }
  }

  _addGroupsToFormView() {
    if (Object.entries(this.groups).length === 0) {
      return;
    }

    const { editor } = this;
    const linkFormView = editor.plugins.get('LinkUI').formView;

    Object.values(this.groups).reverse().forEach((group) => {
      if (!group.added) {
        linkFormView.children.add(group, 2);
        group.parent = linkFormView;

        linkFormView._focusables.add(group, 2);
        linkFormView.focusTracker.add(group.element);

        group.added = true;
      }
    });

    const buttons = [
      linkFormView.children.find((child) => child.template.attributes.class.indexOf('ck-button-save') > -1),
      linkFormView.children.find((child) => child.template.attributes.class.indexOf('ck-button-cancel') > -1),
    ];
    buttons.forEach((item, i) => {
      linkFormView.children.remove(item);
      linkFormView._focusables.remove(item);
      linkFormView.focusTracker.remove(item.element);

      linkFormView.children.add(item, 3 + i);
      linkFormView._focusables.add(item, 3 + i);
      linkFormView.focusTracker.add(item.element);
    });
  }

  _getGroup(groupName) {
    if (!this.groups[groupName]) {
      const { editor } = this;
      const { locale } = editor;

      this.groups[groupName] = new DetailsView(locale, {
        label: additionalFormGroups[groupName].label,
      });
    }
    return this.groups[groupName];
  }

  _moveTargetDecoratorToAdvancedGroup() {
    const { editor } = this;
    const linkFormView = editor.plugins.get('LinkUI').formView;

    if (linkFormView.targetMoved) {
      return;
    }

    linkFormView._manualDecoratorSwitches._items.forEach((item) => {
      if (item.label === Drupal.t('Open in new window')) {
        this._getGroup('advanced').children.add(item);
      }
    });
    linkFormView.targetMoved = true;
  }

  _handleExtraFormFieldSubmit(modelNames) {
    const { editor } = this;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');

    this.listenTo(
      linkFormView,
      'submit',
      () => {
        const values = modelNames.reduce((state, modelName) => {
          state[modelName] = linkFormView[modelName].fieldView.element.value;
          return state;
        }, {});
        // Stop the execution of the link command caused by closing the form.
        // Inject the extra attribute value. The highest priority listener here
        // injects the argument (here below 👇).
        // - The high priority listener in
        //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets
        //   the extra attribute.
        // - The normal (default) priority listener in ckeditor5-link sets
        //   (creates) the actual link.
        linkCommand.once(
          'execute',
          (evt, args) => {
            if (args.length < 3) {
              args.push(values);
            } else if (args.length === 3) {
              Object.assign(args[2], values);
            } else {
              throw Error('The link command has more than 3 arguments.');
            }
          },
          { priority: 'highest' },
        );
      },
      { priority: 'high' },
    );
  }
}
