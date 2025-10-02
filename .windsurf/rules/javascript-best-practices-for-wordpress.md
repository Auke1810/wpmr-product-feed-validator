---
trigger: model_decision
description: Enforce modern JavaScript practices for WordPress (ESM, @wordpress packages, REST, i18n).
globs: |
  wp-content/**/*.js
  wp-content/**/*.ts
---
# JavaScript (ESNext) Best Practices for WordPress

## Context
Prefer modern JS/TS with modules. For editor and admin UIs, use the @wordpress/* packages, REST API with nonces, and script translations. Keep business logic in modules; minimize globals.

## Build & Enqueue
- Use @wordpress/scripts or your bundler to output files referenced by PHP:
  ```bash
  npm i -D @wordpress/scripts
  npx wp-scripts build
  ```
- Enqueue with dependencies and versions:
  ```php
  wp_register_script(
    'wpmr-app',
    plugins_url( 'build/app.js', __FILE__ ),
    [ 'wp-i18n', 'wp-element', 'wp-components', 'wp-api-fetch' ],
    WPMR_VERSION,
    true
  );
  wp_set_script_translations( 'wpmr-app', 'your-text-domain', plugin_dir_path( __FILE__ ) . 'languages' );
  wp_localize_script( 'wpmr-app', 'wpmrData', [ 'nonce' => wp_create_nonce( 'wp_rest' ) ] );
  wp_enqueue_script( 'wpmr-app' );
  ```
- For front-end where supported, consider wp_enqueue_script_module() for native ESM.

## REST & Security
- Use @wordpress/api-fetch with nonces:
  ```js
  import apiFetch from '@wordpress/api-fetch';
  apiFetch.use( apiFetch.createNonceMiddleware( window.wpmrData.nonce ) );
  const res = await apiFetch( { path: '/wpmr/v1/items', method: 'GET' } );
  ```
- Validate and escape all server responses before render.

## i18n (JS)
- Use @wordpress/i18n: import { __, _x, _n, sprintf } from '@wordpress/i18n';
- Provide translations via wp_set_script_translations.
- No hardcoded text in UI without translation wrappers.

## Coding Style
- Modules only; avoid globals. Wrap bootstraps in IIFEs if no build step.
- Typescript preferred for complex apps. Strict ESLint & Prettier config.
- Avoid DOM queries for React/Block UIs; use component state.
- Accessibility: focus management, ARIA roles, keyboard navigation.

## Error Handling
- Handle network errors and timeouts; show actionable messages.
- Do not swallow exceptions; log in dev, fail gracefully in prod.

## Examples

### apiFetch with nonce
```js
import apiFetch from '@wordpress/api-fetch';

apiFetch.use( apiFetch.createNonceMiddleware( window.wpmrData.nonce ) );

export async function loadItems() {
  try {
    return await apiFetch( { path: '/wpmr/v1/items' } );
  } catch (e) {
    // Show notice
    throw e;
  }
}
```

## ESLint/Prettier (template)
- Use @wordpress/eslint-plugin:
  ```bash
  npm i -D eslint prettier @wordpress/eslint-plugin
  ```
  .eslintrc.json
  ```json
  { "extends": [ "plugin:@wordpress/eslint-plugin/recommended" ] }
  ```

## Checklist
- [ ] Built with modules; dependencies declared
- [ ] REST calls via apiFetch + nonce
- [ ] JS i18n wrappers present
- [ ] ESLint passes with WP config
- [ ] No global leaks; no inline scripts
