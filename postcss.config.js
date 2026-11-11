const prefixSelector = require('postcss-prefix-selector');

const ROOT = '#mint-lms-root';

/** @type {import('postcss').AcceptedPlugin[]} */
const plugins = [
  require('postcss-import'),
  require('tailwindcss'),
  prefixSelector({
    prefix: ROOT,
    transform(prefix, selector, prefixedSelector) {
      if (selector.includes(ROOT)) {
        return selector;
      }

      if (
        selector === '*, ::before, ::after' ||
        selector === '*,::before,::after' ||
        selector === '*, ::backdrop, ::after, ::before' ||
        selector === '*,::backdrop,:after,:before'
      ) {
        return `${ROOT} *, ${ROOT} ::before, ${ROOT} ::after, ${ROOT} ::backdrop`;
      }

      if (selector === 'html' || selector === 'body' || selector === ':root') {
        return prefix;
      }

      return prefixedSelector;
    },
  }),
  require('autoprefixer'),
];

if (process.env.NODE_ENV === 'production') {
  plugins.push(require('cssnano'));
}

module.exports = { plugins };
