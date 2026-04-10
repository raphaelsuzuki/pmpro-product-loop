# PMPro Product Loop Hooks

## Actions

### `pmpro_pl_event`

Fired for sanitized observability events across orchestration, lifecycle, and checkout handling.

Payload shape:

```php
array(
    'type'     => (string) $event_type,
    'hash'     => (string) $intent_hash,
    'level_id' => (int) $membership_level_id,
)
```

Known event types currently emitted:

- `invalid_intent`
- `idempotent_hit`
- `stock_exhausted`
- `execution_failed`
- `subscription_persisted`
- `subscription_cancelled`
- `lifecycle_cancelled`
- `checkout_invalid_input`
- `checkout_nonce_failed`
- `checkout_resolver_missing`
- `checkout_intent_rejected`
- `checkout_core_failed`
- `checkout_intent_processed`

## Filters

No public filters are currently exposed.
