const additionalFormElements = {
  linkTitle: {
    label: Drupal.t('Title'),
    viewAttribute: 'title',
  },
  linkAriaLabel: {
    label: Drupal.t('ARIA label'),
    viewAttribute: 'aria-label',
    group: 'advanced',
  },
  linkClass: {
    label: Drupal.t('CSS classes'),
    viewAttribute: 'class',
    group: 'advanced',
  },
  linkId: {
    label: Drupal.t('ID'),
    viewAttribute: 'id',
    group: 'advanced',
  },
  linkRel: {
    label: Drupal.t('Link relationship'),
    viewAttribute: 'rel',
    group: 'advanced',
  },
};

const additionalFormGroups = {
  advanced: {
    label: Drupal.t('Advanced'),
  },
};

export { additionalFormElements, additionalFormGroups };
