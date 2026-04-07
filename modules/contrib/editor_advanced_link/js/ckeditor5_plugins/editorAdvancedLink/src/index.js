// eslint-disable-next-line import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
import EditorAdvancedLinkEditing from './editoradvancedlinkediting';
import EditorAdvancedLinkUI from './editoradvancedlinkui';

class EditorAdvancedLink extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [EditorAdvancedLinkEditing, EditorAdvancedLinkUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EditorAdvancedLink';
  }
}

export default {
  EditorAdvancedLink,
};
