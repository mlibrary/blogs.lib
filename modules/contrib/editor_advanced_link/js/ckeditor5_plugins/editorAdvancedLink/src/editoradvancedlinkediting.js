// eslint-disable-next-line import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
// eslint-disable-next-line import/no-extraneous-dependencies
import { findAttributeRange } from 'ckeditor5/src/typing';
import { additionalFormElements } from './utils';

export default class EditorAdvancedLinkEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EditorAdvancedLinkEditing';
  }

  init() {
    const editorAdvancedLinkConfig =
      this.editor.config.get('editorAdvancedLink');

    if (!editorAdvancedLinkConfig.options) {
      this.enabledModelNames = [];
      return;
    }
    const enabledViewAttributes = Object.values(
      editorAdvancedLinkConfig.options,
    );

    this.enabledModelNames = Object.keys(additionalFormElements).filter(
      (modelName) => {
        return enabledViewAttributes.includes(
          additionalFormElements[modelName].viewAttribute,
        );
      },
    );

    // Setup link attribute on all inline nodes.
    this.enabledModelNames.forEach((modelName) => {
      this._allowAndConvertExtraAttribute(
        modelName,
        additionalFormElements[modelName].viewAttribute,
      );
      this._registerUnlinkCommand(modelName);
      this._refreshExtraAttributeValue(modelName);
    });

    // Update linking commands.
    this._registerLinkCommand(
      Object.keys(additionalFormElements),
    );
  }

  _allowAndConvertExtraAttribute(modelName, attributeName) {
    const { editor } = this;

    // Allow link attribute on all inline nodes.
    editor.model.schema.extend('$text', { allowAttributes: modelName });

    // Model -> View (DOM)
    editor.conversion.for('downcast').attributeToElement({
      model: modelName,
      view: (value, { writer }) => {
        if (!value) {
          return;
        }

        const linkViewElement = writer.createAttributeElement(
          'a',
          {
            [attributeName]: value,
          },
          { priority: 5 },
        );

        writer.setCustomProperty('link', true, linkViewElement);
        return linkViewElement;
      },
    });

    // View (DOM/DATA) -> Model
    let view = {
      name: 'a',
      attributes: {
        [attributeName]: true,
      },
    };

    // Fixes warning: https://ckeditor.com/docs/ckeditor5/latest/support/error-codes.html#error-matcher-pattern-deprecated-attributes-class-key.
    if (attributeName === 'class') {
      view = {
        name: 'a',
        classes: true
      };
    }

    editor.conversion.for('upcast').elementToAttribute({
      view,
      model: {
        key: modelName,
        value: (viewElement) => {
          return viewElement.getAttribute(attributeName);
        }
      },
    });
  }

  _registerLinkCommand(modelNames) {
    const { editor } = this;
    const linkCommand = editor.commands.get('link');
    let linkCommandExecuting = false;

    linkCommand.on('execute', (evt, args) => {
      if (linkCommandExecuting) {
        linkCommandExecuting = false;
        return;
      }

      // If the additional attribute was passed, we stop the default execution
      // of the LinkCommand. We're going to create Model#change() block for undo
      // and execute the LinkCommand together with setting the extra attribute.
      evt.stop();

      // Prevent infinite recursion by keeping records of when link command is
      // being executed by this function.
      linkCommandExecuting = true;

      // If no advanced_attributes passed in event arguments (eg decorator updated), then get values from state.
      let advancedAttributeValues = [];
      const decoratorsArgIndex = 1;
      if (args && args[decoratorsArgIndex] && !args[decoratorsArgIndex]['advanced_attributes']) {
          this.enabledModelNames.forEach((attribute) => {
          advancedAttributeValues[attribute] = evt.source[attribute];
        });
        args[decoratorsArgIndex]['advanced_attributes'] = advancedAttributeValues;
      }
      else {
        advancedAttributeValues = args[decoratorsArgIndex]['advanced_attributes'];
      }
      args[decoratorsArgIndex]['advanced_attributes'] = advancedAttributeValues;

      const model = this.editor.model;
      const selection = model.document.selection;
      const displayedText = args[args.length - 1] || args[decoratorsArgIndex]['advanced_attributes']['displayedText'];

      // Wrapping the original command execution in a model.change() block to make sure there's a single undo step
      // when the extra attribute is added.
      model.change((writer) => {

        // Returns a link range based on selection:
        // Selection.getAttribute('linkHref') or
        // Based on finding a link range by href attribute at selection's first position.
        const getCurrentLinkRange = (model, selection, hrefSourceValue) => {
          const position = selection.getFirstPosition();

          // When selection is inside text with `linkHref` attribute or text of link.
          const range = findAttributeRange(position, 'linkHref', hrefSourceValue, model);
          return range;
        };

        // Returns a text of a link range.
        // If the returned value is `undefined`, the range contains elements other than text nodes.
        const extractTextFromLinkRange = (range) => {
          let text = '';
          for (const item of range.getItems()) {
            if (!item.is('$text') && !item.is('$textProxy')) {
              return;
            }
            text += item.data;
          }
          return text;
        }

        const updateLinkTextIfNeeded = (range, displayedText) => {
          const linkText = extractTextFromLinkRange(range);
          if (!linkText || typeof displayedText == "object") {
            // In case 'target' attribute is updated, args do no pass any values
            // for displayed text.
            return range;
          }

          // In a scenario where the displayedText is blank, fall back on the
          // linkText, and if that is empty, use the href from args[0].
          let newText = displayedText || linkText || args[0];
          let newRange = writer.createRange(range.start, range.start.getShiftedBy(newText.length));
          return newRange;
        };

        const updateAttributes = (range, removeSelection) => {
          this.enabledModelNames.forEach((attribute) => {
            if (advancedAttributeValues[attribute]) {
              writer.setAttribute(attribute, advancedAttributeValues[attribute], range);
            } else {
              writer.removeAttribute(attribute, range);
            }

            if (removeSelection) {
              writer.setSelection(range.end);
              const { plugins } = this.editor;
              if (plugins.has('TwoStepCaretMovement')) {
                // After replacing the text of the link, we need to move the caret to the end of the link,
                // override it's gravity to forward to prevent keeping e.g. bold attribute on the caret
                // which was previously inside the link.
                //
                // If the plugin is not available, the caret will be placed at the end of the link and the
                // bold attribute will be kept even if command moved caret outside the link.
                plugins.get('TwoStepCaretMovement')._handleForwardMovement();
              } else {
                // Remove any attributes to prevent link splitting.
                writer.removeSelectionAttribute(attribute);
              }
            }
          });
        };

        editor.execute('link', ...args);

        if (selection.isCollapsed) {
          // The user has clicked somewhere within the link, so we need to
          // calculate the range of characters the attributes should apply
          // to.
          const currentHref = args[0] || evt.source.value;

          let range = getCurrentLinkRange(model, selection, currentHref);
          if (!range) {
            console.info('No link range found');
            return;
          }

          // In CKEditor v45, a new displayText input is present in the
          // link widget. So we need to recalculate the range in case the
          // text has changed.
          range = updateLinkTextIfNeeded(range, displayedText);
          updateAttributes(range, true);
        } else {
          if (this.enabledModelNames.length > 0) {
            const ranges = model.schema.getValidRanges(selection.getRanges(), this.enabledModelNames[0]);
            for (const range of ranges) {
              updateAttributes(range);
            }
          }
        }
      });
    },
    { priority: 'high' });
  }

  _registerUnlinkCommand(modelName) {
    const { editor } = this;
    const unlinkCommand = editor.commands.get('unlink');
    const { model } = this.editor;
    const { selection } = model.document;

    let isUnlinkingInProgress = false;

    // Make sure all changes are in a single undo step so cancel the original unlink first in the high priority.
    unlinkCommand.on('execute', (evt) => {
        // This single block wraps all changes that should be in a single undo step.
        model.change(() => {
          // The actual integration that removes the extra attribute.
          model.change((writer) => {
            // Get ranges to unlink.
            let ranges;

            if (selection.isCollapsed) {
              ranges = [
                findAttributeRange(
                  selection.getFirstPosition(),
                  modelName,
                  selection.getAttribute(modelName),
                  model,
                ),
              ];
            } else {
              ranges = model.schema.getValidRanges(
                selection.getRanges(),
                modelName,
              );
            }

            // Remove the extra attribute from specified ranges.
            // eslint-disable-next-line max-nested-callbacks
            ranges.forEach((range) => {
              writer.removeAttribute(modelName, range);
            });
          });
        });
      },
      { priority: 'highest' },
    );
  }

  _refreshExtraAttributeValue(modelName) {
    const { editor } = this;
    const linkCommand = editor.commands.get('link');
    const { model } = this.editor;
    const { selection } = model.document;

    linkCommand.set(modelName, null);
    model.document.on('change', (event, args) => {
      linkCommand[modelName] = selection.getAttribute(modelName);
    });
  }
}
