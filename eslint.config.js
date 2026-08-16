import compat from 'eslint-plugin-compat'
import pluginVue from 'eslint-plugin-vue'

export default [
    // flat/base parses .vue for compat without vue/essential style rules
    ...pluginVue.configs['flat/base'],
    {
        ...compat.configs['flat/recommended'],
        files: ['resources/js/**/*.{js,vue}'],
        settings: {
            lintAllEsApis: true,
            // some new features can be polyfilled:
            //   check out corejs+vite-legacy before adding an exclusion here
            polyfills: ['structuredClone'],
        },
    },
]
