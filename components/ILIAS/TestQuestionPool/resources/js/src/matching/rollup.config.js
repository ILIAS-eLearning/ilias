import terser from '@rollup/plugin-terser';
import copyright from '../../../../../../../scripts/Copyright-Checker/copyright';
import preserveCopyright from '../../../../../../../scripts/Copyright-Checker/preserveCopyright';

export default {
  external: [
    'document',
    'ilias',
  ],
  input: './matching.js',
  output: {
    file: '../../dist/matching.js',
    format: 'iife',
    banner: copyright,
    globals: {
      document: 'document',
      ilias: 'il',
    },
    plugins: [
      terser({
        format: {
          comments: preserveCopyright,
        },
      }),
    ],
  },
};
