/**
 * @module ui/details/detailsview
 */

// eslint-disable-next-line import/no-extraneous-dependencies
import { View } from 'ckeditor5/src/ui';
import './details.css';

/**
 * The class component representing a details element. It should be used in more
 * advanced forms to group fields.
 *
 * @extends module:ui/view~View
 */
export default class DetailsView extends View {
  /**
   * Creates an instance of the details class.
   *
   * @param {module:utils/locale~Locale} locale The locale instance.
   * @param {Object} options The options.
   * @param {String} options.label The summary label.
   * @param {String} [options.class] An additional class.
   */
  constructor(locale, options = {}) {
    super(locale);

    const bind = this.bindTemplate;

    /**
     * An observable flag set to `true` when {@link #fieldView} is currently
     * focused by the user (`false` otherwise).
     *
     * @readonly
     * @observable
     * @member {Boolean} #isFocused
     * @default false
     */
    this.set( 'isFocused', false );

    /**
     * The label of the details element.
     *
     * @observable
     * @member {String} #label
     */
    this.set('label', options.label || '');

    /**
     * An additional CSS class added to the {@link #element}.
     *
     * @observable
     * @member {String} #class
     */
    this.set('class', options.class || null);

    /**
     * A collection of items.
     *
     * @readonly
     * @member {module:ui/viewcollection~ViewCollection}
     */
    this.children = this.createCollection();

    this.setTemplate({
      tag: 'details',
      attributes: {
        class: [
          'ck',
          'ck-form__details',
          bind.if( 'isFocused', 'ck-form__details--focused' ),
          bind.to('class'),
        ],
      },
      children: this.children,
    });

    this.summary = new View(locale);

    this.summary.setTemplate({
      tag: 'summary',
      attributes: {
        class: ['ck', 'ck-form__details__summary'],
      },
      children: [{ text: bind.to('label') }],
    });

    this.children.add(this.summary);
  }

  render() {
    super.render();

    this.element.addEventListener('toggle', this.onToggle.bind(this));
  }

  focus() {
    this.summary.element.focus();
  }

  onToggle(evt) {
    if (evt.target.open) {
      const groupIndex = this.parent._focusables.getIndex(this);
      Object.values(this.children._items).slice(1).reverse().forEach((child) => {
        this.parent._focusables.add(child, groupIndex+1);
        this.parent.focusTracker.add(child.element);
      });
    }
    else {
      Object.values(this.children._items).slice(1).forEach((child) => {
        this.parent._focusables.remove(child);
        this.parent.focusTracker.remove(child.element);
      });
    }
  }
}
