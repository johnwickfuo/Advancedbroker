<?php
/** Reusable component helpers. All values are escaped unless explicitly supplied as pre-rendered HTML. */
function ui_button(string $label, string $href = '#', string $variant = ''): string { return '<a class="button ' . e($variant) . '" href="' . e($href) . '">' . e($label) . '</a>'; }
function ui_badge(string $label, string $status = 'info'): string { return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>'; }
function ui_money(\App\Support\Money $money, string $symbol = ''): string { return '<span class="money">' . e($money->format($symbol)) . '</span>'; }
function ui_csrf(): string { return app('csrf')->input(); }
