---
trigger: model_decision
description: Enforce jQuery usage rules specific to WordPress (noConflict, events, enqueue, performance).
globs: |
  wp-content/**/*.js
---
# jQuery Best Practices for WordPress

## Context
WordPress ships jQuery in noConflict mode. Scripts must be enqueued (not inlined) and use delegated events for dynamic DOM. Prefer modern JS for new code; jQuery is for legacy or DOM-heavy admin UI.

## Enqueuing (Must)
```php
wp_enqueue_script( 'jquery' );
wp_enqueue_script(
  'plugin-admin',
  plugins_url( 'assets/admin.js', __FILE__ ),
  [ 'jquery' ],
  WPMR_VERSION,
  true
);
```

## noConflict Pattern
Use the safe wrapper so $ refers to jQuery only inside:
```js
(function( $ ) {
  $( function() {
    // DOM ready
  } );
})( jQuery );
```

## Events
- Use delegated handlers for dynamic elements: $(document).on('click', '.btn', handler).
- Do not use deprecated .live() or inline onclick.
- Throttle/debounce scroll/resize handlers.

## DOM & AJAX
- Read data via data-* attributes and .data().
- Use wp_localize_script to pass ajax_url and nonces; post to admin-ajax.php or use REST.
```js
$.post( window.pluginData.ajax_url, {
  action: 'wpmr_do',
  _ajax_nonce: window.pluginData.nonce,
  id: id
} );
```
- Validate responses; handle failures; never trust server output without escaping on render.

## Accessibility & UX
- Maintain focus; use ARIA attrs for toggles; avoid display:none on focusable elements without managing focus.

## Performance
- Batch DOM writes; cache selectors; avoid layout thrashing.
- Detach/re-attach for large updates; prefer CSS transitions over JS animation.

## Examples

### Bad
```js
jQuery('.row').click(function(){ doStuff(); });
```

### Good
```js
(function( $ ) {
  $( document ).on( 'click', '.row', function( e ) {
    e.preventDefault();
    // ...
  } );
})( jQuery );
```

## Checklist
- [ ] Scripts enqueued with jquery dependency
- [ ] Uses noConflict wrapper
- [ ] Delegated events; no deprecated APIs
- [ ] AJAX requests include nonce
- [ ] Throttled expensive events
