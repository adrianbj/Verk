<?php namespace ProcessWire;
/** @var Verk $this
 *  @var string $content */
?>
<style>
.vk-shell {
    --vk-text: var(--pw-text-color);
    --vk-muted: var(--pw-muted-color);
    --vk-border: var(--pw-border-color);
    --vk-surface: var(--pw-blocks-background);
    --vk-muted-surface: var(--pw-inputs-background);
    --vk-accent: var(--pw-main-color);
    --vk-accent-strong: color-mix(in srgb, var(--vk-accent) 86%, #000);
    --vk-accent-contrast: var(--pw-button-color);
    --vk-radius: 6px;
    --vk-radius-sm: 4px;
    --vk-shadow: 0 1px 2px rgba(0,0,0,.06), 0 10px 28px rgba(0,0,0,.035);
    --vk-shadow-hover: 0 2px 6px rgba(0,0,0,.08), 0 14px 34px rgba(0,0,0,.05);
    --vk-shadow-strong: 0 2px 8px rgba(0,0,0,.1), 0 18px 42px rgba(0,0,0,.08);
    --vk-skeleton-a: rgba(0,0,0,.05);
    --vk-skeleton-b: rgba(0,0,0,.12);
    max-width: 1520px;
    margin: 0 auto;
    color: var(--vk-text);
    font-weight: 400;
}

body.dark-theme .vk-shell {
    --vk-skeleton-a: rgba(255,255,255,.08);
    --vk-skeleton-b: rgba(255,255,255,.2);
}

.vk-shell strong,
.vk-shell b,
.vk-shell h2,
.vk-shell h4,
.vk-shell .uk-button,
.vk-shell .uk-label {
    font-weight: 400 !important;
}

.vk-shell h3,
.vk-shell .uk-card-title,
.vk-shell .vk-card-title {
    font-weight: 500 !important;
}

.vk-shell .vk-rich-text strong,
.vk-shell .vk-rich-text b {
    font-weight: 600 !important;
}

.vk-shell h2 {
    font-size: 1.45rem;
}

.vk-page-title,
.vk-card-title {
    margin: 0;
}

.vk-page-title {
    font-size: 1.45rem;
    line-height: 1.2;
}

.vk-card-title {
    color: var(--vk-text);
    font-size: 1rem;
    line-height: 1.25;
}

.vk-dashboard-head .vk-page-title,
.vk-page-head .vk-page-title {
    display: none;
}

.vk-content {
    margin-top: 14px;
}

.vk-shell a,
.vk-shell a:hover,
.vk-shell a:focus {
    text-decoration: none !important;
}

.vk-shell .vk-rich-text a {
    text-decoration: underline !important;
    text-underline-offset: 2px;
}

.vk-shell .vk-rich-text a:hover,
.vk-shell .vk-rich-text a:focus {
    text-decoration: underline !important;
}

.vk-is-hidden {
    display: none !important;
}

.vk-text-danger {
    color: var(--pw-error-inline-text-color);
}

.vk-admin-nav {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    margin-bottom: 12px;
}

.vk-admin-nav .uk-subnav {
    margin-bottom: 0;
    margin-left: -13px;
    row-gap: 12px;
}

.vk-admin-nav .uk-subnav > * {
    padding-left: 13px;
}

.vk-admin-nav .uk-subnav-pill > * > :first-child {
    align-items: center;
    background: transparent;
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: inline-flex;
    font-weight: 400;
    line-height: 1.5;
    min-height: 0;
    padding: 7px 10px;
    text-transform: uppercase;
}

.vk-admin-nav .uk-subnav-pill > * > a:hover,
.vk-admin-nav .uk-subnav-pill > * > a:focus {
    background: var(--vk-muted-surface);
    color: var(--vk-text);
}

.vk-admin-nav .uk-subnav-pill > .uk-active > a {
    background: var(--vk-accent);
    color: var(--vk-accent-contrast);
}

.vk-settings-link {
    align-items: center;
    color: var(--vk-muted);
    display: inline-flex;
    flex: 0 0 48px;
    font-size: 30px;
    height: 48px;
    justify-content: center;
    line-height: 1;
    margin-left: auto;
    text-decoration: none;
    width: 48px;
}

.vk-settings-link:hover,
.vk-settings-link:focus,
.vk-settings-link.is-active {
    color: var(--vk-text);
    text-decoration: none;
}

.vk-settings-link svg {
    display: block;
    height: 32px;
    width: 32px;
}

.vk-shell .uk-card,
.vk-panel {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    overflow: hidden;
    transition: border-color .14s ease, box-shadow .14s ease, transform .14s ease;
}

.vk-shell .uk-card-header {
    min-height: 0;
    padding: .82rem 1rem;
    background: transparent;
    border-bottom: 1px solid var(--vk-border);
}

.vk-shell .uk-card-body {
    padding: 1rem;
}

.vk-shell .uk-card-title {
    font-size: 1rem;
}

.vk-shell .uk-button {
    align-items: center;
    border-radius: var(--vk-radius-sm);
    display: inline-flex;
    gap: 7px;
    justify-content: center;
    min-height: 36px;
    padding: 0 14px;
    text-transform: none;
    white-space: nowrap;
}

.vk-shell .uk-button .fa {
    margin-right: 0;
}

.vk-shell .uk-button-danger {
    background: var(--pw-error-inline-text-color);
    border-color: var(--pw-error-inline-text-color);
    color: #fff;
}

.vk-shell .uk-button-danger:hover,
.vk-shell .uk-button-danger:focus {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 86%, #000);
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 86%, #000);
    color: #fff;
}

.vk-shell .uk-table {
    color: var(--vk-text);
    margin: 0;
}

.vk-shell .uk-table-divider > tr:not(:first-child),
.vk-shell .uk-table-divider > :not(:first-child) > tr,
.vk-shell .uk-table-divider > :first-child > tr:not(:first-child) {
    border-top-color: var(--vk-border);
}

.vk-shell .uk-table th {
    color: var(--vk-muted);
    font-size: .76rem;
    font-weight: 400;
}

.vk-shell .uk-table td {
    vertical-align: middle;
}

.vk-shell .uk-table-hover tbody tr:hover {
    background: var(--vk-muted-surface);
}

.vk-stat {
    color: var(--vk-text);
    display: block;
    min-height: 84px;
    padding: .86rem .95rem;
    position: relative;
    text-decoration: none;
    text-align: left;
}

.vk-stat:hover,
.vk-stat:focus {
    color: var(--vk-text);
    text-decoration: none;
}

.vk-stat-link:hover,
.vk-stat-link:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
}

.vk-stat-link:hover::before,
.vk-stat-link:focus::before {
    opacity: 1;
}

.vk-stat::before {
    background: var(--vk-accent);
    border-radius: 999px;
    content: "";
    height: 3px;
    left: 12px;
    opacity: .42;
    position: absolute;
    right: 12px;
    top: 0;
}

.vk-stat-n {
    color: var(--vk-text);
    font-size: 1.55rem;
    font-weight: 500;
    line-height: 1.05;
    margin-top: 3px;
}

.vk-stat-n.is-accent {
    color: var(--vk-accent);
}

.vk-stat-n.is-success,
.vk-audit-count.is-success {
    color: var(--pw-alert-success);
}

.vk-stat-n.is-warning,
.vk-audit-count.is-warning {
    color: color-mix(in srgb, var(--pw-alert-warning) 52%, var(--vk-text));
}

.vk-stat-n.is-danger {
    color: var(--pw-error-inline-text-color);
}

.vk-stat-l {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-stat-note {
    color: var(--vk-muted);
    display: block;
    font-size: .76rem;
    margin-top: .2rem;
}

.vk-label {
    border-radius: 999px;
    font-size: .66rem;
    letter-spacing: .04em;
    line-height: 1.6;
    text-transform: uppercase;
}

.vk-label-open,
.vk-label-medium      { background:color-mix(in srgb, var(--vk-text) 13%, transparent); color:var(--vk-text) !important; }
.vk-label-review      { background:color-mix(in srgb, var(--vk-accent) 16%, transparent); color:var(--vk-accent) !important; }
.vk-label-in_progress,
.vk-label-high        { background:var(--pw-alert-warning); color:var(--pw-text-color) !important; }
.vk-label-done,
.vk-label-low         { background:var(--pw-alert-success); color:var(--pw-text-color) !important; }
.vk-label-critical    { background:var(--pw-error-inline-text-color); color:var(--pw-blocks-background) !important; }

.vk-chip {
    display:inline-flex;
    align-items:center;
    max-width: 100%;
    gap:.35rem;
    padding:.18rem .55rem;
    background:var(--vk-muted-surface);
    border:1px solid var(--vk-border);
    border-radius: 999px;
    color:var(--pw-text-color);
    font-size:.78rem;
    line-height: 1.5;
    text-decoration:none;
    white-space:nowrap;
}

.vk-chip:hover {
    background:var(--pw-main-background);
    text-decoration: none;
}

.vk-chip .fa {
    color:var(--pw-main-color);
    font-size:.75rem;
}

.vk-comment-list {
    margin-bottom: 1rem;
}

.vk-comment {
    background:var(--vk-muted-surface);
    border:1px solid var(--vk-border);
    border-left:3px solid var(--vk-accent);
    border-radius: var(--vk-radius-sm);
    padding:.65rem .75rem;
}

.vk-comment + .vk-comment {
    margin-top: 8px;
}

.vk-comment-head {
    align-items: center;
    display: flex;
    gap: 8px;
    margin-bottom: 6px;
}

.vk-comment-text {
    font-size:.86rem;
    line-height:1.55;
    white-space:pre-wrap;
    word-break:break-word;
}

.vk-comment-text.vk-rich-text {
    white-space: normal;
}

.vk-cal {
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
    width: 100%;
}

.vk-cal th {
    padding:.55rem .45rem;
    background: var(--vk-muted-surface);
    border-bottom:1px solid var(--vk-border);
    color:var(--vk-muted);
    font-size:.7rem;
    font-weight: 400;
    letter-spacing: .04em;
    text-align:center;
    text-transform: uppercase;
}

.vk-cal td {
    height: 104px;
    min-width:80px;
    padding:.45rem;
    border-right:1px solid var(--vk-border);
    border-bottom:1px solid var(--vk-border);
    vertical-align:top;
    font-size:.8rem;
}

.vk-cal tr td:last-child {
    border-right: 0;
}

.vk-cal tbody tr:last-child td {
    border-bottom: 0;
}

.vk-cal-today {
    background: color-mix(in srgb, var(--vk-accent) 11%, var(--vk-surface));
}

.vk-cal-other {
    background: var(--vk-muted-surface);
    opacity:.55;
}

.vk-cal-day-n {
    align-items: center;
    display: flex;
    gap: 6px;
    justify-content: space-between;
    margin-bottom:.25rem;
    color:var(--vk-muted);
    font-size:.72rem;
    font-weight:400;
}

.vk-cal-day-add {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: 999px;
    color: var(--vk-muted);
    display: inline-flex;
    flex: 0 0 18px;
    font-size: .78rem;
    height: 18px;
    justify-content: center;
    line-height: 1;
    opacity: 0;
    text-decoration: none;
    transition: opacity .12s ease, color .12s ease, background-color .12s ease;
    width: 18px;
}

.vk-cal td:hover .vk-cal-day-add,
.vk-cal-day-add:focus {
    opacity: 1;
}

.vk-cal-day-add:hover {
    background: var(--vk-accent);
    border-color: var(--vk-accent);
    color: var(--vk-accent-contrast);
}

.vk-cal-item {
    display:block;
    margin-bottom:3px;
    overflow:hidden;
    padding:.16rem .32rem;
    border-radius: var(--vk-radius-sm);
    font-size:.7rem;
    font-weight: 400;
    line-height: 1.35;
    text-decoration:none;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.vk-cal-item span,
.vk-cal-item small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vk-cal-task-date {
    font-size: .62rem;
    line-height: 1.2;
    opacity: .72;
}

.vk-cal-task.is-done {
    opacity: .62;
}

.vk-cal-task.is-priority-critical {
    border-left: 3px solid var(--pw-error-inline-text-color);
}

.vk-cal-task.is-priority-high {
    border-left: 3px solid var(--pw-alert-warning);
}

.vk-cal-task.is-priority-low {
    border-left: 3px solid var(--pw-alert-success);
}

.vk-cal-item:hover {
    opacity:.84;
    text-decoration: none;
}

.vk-cal-pub  { background:var(--pw-alert-success); color:var(--pw-text-color); }
.vk-cal-task { background:var(--pw-alert-warning); color:var(--pw-text-color); }

.vk-audit-item {
    display:flex;
    align-items:center;
    gap:.7rem;
    min-height: 42px;
    padding:.58rem .85rem;
    border-bottom:1px solid var(--vk-border);
    font-size:.86rem;
    color: inherit;
    text-decoration: none;
}

.vk-audit-item:hover,
.vk-audit-item:focus {
    background:var(--vk-muted-surface);
    color: inherit;
    text-decoration: none;
}

.vk-audit-item:last-child {
    border-bottom:none;
}

.vk-audit-item-label {
    flex: 1 1 auto;
    min-width: 0;
}

.vk-audit-count {
    font-size: .82rem;
    white-space: nowrap;
}

.vk-audit-rule-tabs {
    margin-bottom: 16px;
}

.vk-audit-summary-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin: 0 0 16px;
    max-width: 1180px;
}

.vk-audit-summary-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    display: grid;
    gap: 2px;
    min-height: 74px;
    padding: 10px 12px;
}

.vk-audit-summary-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-audit-summary-card strong {
    color: var(--vk-text);
    font-size: 1.35rem;
    line-height: 1.05;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-audit-summary-card small {
    color: var(--vk-muted);
    font-size: .72rem;
}

.vk-audit-summary-card.is-warning strong {
    color: var(--pw-alert-warning);
}

.vk-audit-summary-card.is-success strong {
    color: var(--pw-alert-success);
}

.vk-audit-tab-count {
    background: color-mix(in srgb, currentColor 12%, transparent);
    border-radius: 999px;
    display: inline-flex;
    font-size: .68rem;
    line-height: 1;
    margin-left: 5px;
    min-width: 18px;
    padding: 3px 5px;
    justify-content: center;
}

.vk-audit-notice {
    align-items: center;
    display: flex;
    gap: 13px;
    margin-bottom: 16px;
    padding: 14px 16px;
    border-radius: var(--vk-radius);
}

.vk-audit-setup {
    background: var(--pw-alert-warning);
    border: 1px solid var(--vk-border);
}

.vk-audit-error {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 8%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--pw-error-inline-text-color) 28%, var(--vk-border));
    color: var(--pw-error-inline-text-color);
}

.vk-audit-error p {
    margin: 0;
}

.vk-audit-setup .fa {
    color: var(--vk-muted);
    font-size: 1.2rem;
}

.vk-audit-setup p {
    color: var(--vk-muted);
    margin: 3px 0 0;
}

.vk-audit-config {
    max-width: 1180px;
}

.vk-audit-rule-format {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 14px 0;
    padding: 10px 12px;
}

.vk-audit-rule-format span {
    color: var(--vk-muted);
    font-size: .78rem;
    text-transform: uppercase;
}

.vk-audit-rule-format code,
.vk-audit-reference code {
    background: transparent;
    color: var(--vk-text);
    font-size: .8rem;
}

.vk-audit-rules {
    font-family: monospace;
    font-size: .82rem;
    min-height: 128px;
}

.vk-audit-reference {
    background: var(--vk-muted-surface);
    border-top: 1px solid var(--vk-border);
    display: grid;
    gap: 6px;
    margin: 16px -16px -16px;
    padding: 13px 16px;
}

.vk-audit-reference-title {
    color: var(--vk-muted);
    font-size: .72rem;
    margin-bottom: 2px;
    text-transform: uppercase;
}

.vk-audit-page-list {
    display: grid;
}

.vk-audit-page-row {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 12px;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    min-height: 58px;
    padding: 10px 12px;
    position: relative;
}

.vk-audit-page-row:last-child {
    border-bottom: 0;
}

.vk-audit-page-row:hover {
    background: color-mix(in srgb, var(--vk-accent) 5%, var(--vk-muted-surface));
}

.vk-audit-page-row:hover::before {
    background: var(--vk-accent);
    bottom: 10px;
    content: "";
    left: 0;
    position: absolute;
    top: 10px;
    width: 3px;
}

.vk-audit-page-check {
    text-align: center;
}

.vk-audit-page-main {
    min-width: 0;
}

.vk-audit-page-title {
    color: var(--vk-text);
    display: block;
    font-size: .9rem;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-audit-page-title:hover {
    color: var(--vk-accent);
}

.vk-audit-page-meta {
    color: var(--vk-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: .74rem;
    gap: 7px;
    margin-top: 2px;
}

.vk-audit-page-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-audit-page-actions {
    align-items: center;
    display: flex;
    gap: 7px;
    justify-content: flex-end;
}

.vk-bulk-panel {
    border-color: color-mix(in srgb, var(--vk-accent) 44%, var(--vk-border)) !important;
}

.vk-bulk-workspace {
    align-items: start;
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 640px) minmax(320px, 520px);
    max-width: 1180px;
}

.vk-bulk-panel > .uk-card-header {
    background: color-mix(in srgb, var(--vk-accent) 8%, var(--vk-surface));
    color: var(--vk-accent);
}

.vk-note-body {
    font-size:.92rem;
    line-height:1.65;
    word-break:break-word;
}

.vk-picker {
    position:relative;
}

.vk-picker-results {
    position:absolute;
    top:calc(100% + 4px);
    left:0;
    right:0;
    display:none;
    max-height:240px;
    overflow-y:auto;
    background:var(--vk-surface);
    border:1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow:var(--vk-shadow-hover);
    z-index:100;
}

.vk-picker-results.open {
    display:block;
}

.vk-picker-result {
    padding:.55rem .75rem;
    border-bottom:1px solid var(--vk-border);
    cursor:pointer;
    font-size:.84rem;
}

.vk-picker-result:hover {
    background:var(--vk-muted-surface);
}

.vk-picker-result.is-empty {
    color: var(--vk-muted);
}

.vk-picker-result:last-child {
    border-bottom: 0;
}

.vk-picker-result small {
    color:var(--vk-muted);
}

.vk-dashboard-head,
.vk-page-head {
    align-items: flex-start;
    display: flex;
    gap: 20px;
    justify-content: space-between;
    margin-bottom: 18px;
}

.vk-compact-head {
    margin-bottom: 10px;
}

.vk-compact-head p {
    display: none;
}

.vk-dashboard-head p,
.vk-page-head p {
    color: var(--vk-muted);
    font-size: .95rem;
    margin: .25rem 0 0;
}

.vk-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}

.vk-dashboard-head > .vk-actions,
.vk-page-head > .vk-actions {
    align-self: flex-start;
    flex: 0 0 auto;
    margin-left: auto;
    max-width: 52%;
}

.vk-dashboard-grid {
    align-items: start;
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 1.1fr) minmax(340px, .9fr);
}

.vk-dashboard-side {
    display: flex;
    flex-direction: column;
}

.vk-dashboard-card-sprints {
    order: -1;
}

.vk-dashboard-card-recent {
    order: 3;
}

.vk-dashboard-toolbar {
    align-items: center;
    margin-bottom: 16px;
}

.vk-dashboard-focus {
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) 190px 190px;
    margin: 0 0 16px;
}

.vk-dashboard-focus-main,
.vk-dashboard-focus-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
}

.vk-dashboard-focus-main {
    align-items: center;
    display: grid;
    gap: 18px;
    grid-template-columns: minmax(0, 1fr) 190px;
    min-height: 94px;
    padding: 14px 16px;
}

.vk-dashboard-focus-main .vk-section-label {
    margin: 0 0 5px;
}

.vk-dashboard-focus-main h3 {
    font-size: 1.35rem;
    line-height: 1.1;
    margin: 0;
}

.vk-dashboard-focus-main p {
    color: var(--vk-muted);
    margin: 5px 0 0;
}

.vk-dashboard-focus-progress span {
    color: var(--vk-text);
    display: block;
    font-size: 1.55rem;
    line-height: 1.05;
    margin-bottom: 7px;
    text-align: right;
}

.vk-dashboard-focus-card {
    color: var(--vk-text);
    display: grid;
    gap: 2px;
    min-height: 94px;
    padding: 14px 16px;
    text-decoration: none;
}

.vk-dashboard-focus-card:hover,
.vk-dashboard-focus-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-dashboard-focus-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-dashboard-focus-card strong {
    font-size: 1.55rem;
    line-height: 1.05;
}

.vk-dashboard-focus-card small {
    color: var(--vk-muted);
}

.vk-dashboard-focus-card.is-danger strong {
    color: var(--pw-error-inline-text-color);
}

.vk-dashboard-stats {
    display: grid !important;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
    margin-bottom: 16px;
    margin-left: 0;
}

.vk-dashboard-stats > * {
    min-width: 0;
    padding-left: 0;
    width: auto !important;
}

.vk-dashboard-queue {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    margin: -4px 0 16px;
}

.vk-dashboard-queue-item {
    align-items: center;
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    color: var(--vk-text);
    display: flex;
    gap: 8px;
    justify-content: space-between;
    min-height: 42px;
    padding: 8px 10px;
    text-decoration: none;
}

.vk-dashboard-queue-item:hover,
.vk-dashboard-queue-item:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-dashboard-queue-item span {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-dashboard-queue-item strong {
    color: var(--vk-text);
    font-size: 1rem;
    line-height: 1;
}

.vk-dashboard-queue-item.is-danger strong {
    color: var(--pw-error-inline-text-color);
}

.vk-dashboard-queue-item.is-warning strong {
    color: var(--pw-alert-warning);
}

.vk-card-pager {
    align-items: center;
    border-top: 1px solid var(--vk-border);
    display: flex;
    gap: 12px;
    justify-content: space-between;
    min-height: 38px;
    padding: 8px 13px;
}

.vk-card-header-row {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
}

.vk-card-header-row > .vk-card-title {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-card-header-row > .vk-card-action,
.vk-card-header-row > .vk-inline-actions,
.vk-card-header-row > .vk-card-pager-links {
    flex: 0 0 auto;
}

.vk-card-body-flush {
    padding: 0 !important;
}

.vk-card-stack {
    margin-top: 14px !important;
}

.vk-dashboard-card,
.vk-audit-results-card,
.vk-bulk-pages-card,
.vk-calendar-card,
.vk-sprint-board-card {
    min-width: 0;
}

.vk-dashboard-card {
    overflow: hidden;
}

.vk-dashboard-card > .uk-card-header {
    min-height: 48px;
}

.vk-dashboard-card .vk-card-body-flush,
.vk-dashboard-card .vk-mini-list,
.vk-dashboard-card .vk-sprint-summary {
    min-height: 0;
}

.vk-sprint-board-card {
    overflow: visible;
}

.vk-calendar-card {
    overflow-x: auto;
}

.vk-inline-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.vk-inline-meta {
    margin-left: 8px;
}

.vk-inline-meta.is-right {
    margin-left: 0;
    margin-right: 8px;
}

.vk-card-pager-links {
    align-items: center;
    display: inline-flex;
    gap: 10px;
}

.vk-card-pager-meta {
    color: var(--vk-muted);
    font-size: .74rem;
    line-height: 1.35;
    white-space: nowrap;
}

.vk-card-action,
.vk-pager-link {
    align-items: center;
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .74rem;
    gap: 6px;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
}

.vk-card-action {
    border: 1px solid transparent;
    border-radius: var(--vk-radius-sm);
    min-height: 28px;
    padding: 0 8px;
}

.vk-card-action:hover,
.vk-card-action:focus,
.vk-pager-link:hover,
.vk-pager-link:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-card-action:hover,
.vk-card-action:focus {
    background: var(--vk-muted-surface);
    border-color: var(--vk-border);
}

.vk-card-action .fa,
.vk-pager-link .fa {
    font-size: .68rem;
}

.vk-card-pager a {
    text-decoration: none;
}

.vk-card-pager a:hover,
.vk-card-pager a:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-dashboard-empty {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    gap: 8px;
    justify-content: center;
    min-height: 66px;
    padding: 15px 18px;
    text-align: center;
}

.vk-dashboard-empty .fa {
    color: var(--vk-muted);
    font-size: 1rem;
}

.vk-dashboard-empty a {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-dashboard-empty a:hover,
.vk-dashboard-empty a:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-mini-list {
    display: grid;
}

.vk-mini-row {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) auto;
    min-height: 58px;
    padding: 10px 13px 10px 15px;
    position: relative;
}

.vk-mini-row:last-child {
    border-bottom: 0;
}

.vk-mini-row:hover {
    background: color-mix(in srgb, var(--vk-accent) 5%, var(--vk-muted-surface));
}

.vk-mini-main {
    min-width: 0;
}

.vk-mini-title {
    color: var(--vk-text);
    display: block;
    font-size: .88rem;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-mini-title:hover {
    color: var(--vk-accent);
}

.vk-mini-meta,
.vk-mini-side {
    align-items: center;
    display: flex;
    gap: 7px;
}

.vk-mini-meta {
    color: var(--vk-muted);
    flex-wrap: wrap;
    font-size: .72rem;
    margin-top: 2px;
}

.vk-mini-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-mini-meta .is-overdue {
    color: var(--pw-error-inline-text-color);
}

.vk-mini-side {
    justify-content: flex-end;
}

.vk-mini-row .vk-chip {
    margin-top: 6px;
}

.vk-sprint-summary {
    padding: 5px 0;
}

.vk-sprint-row {
    border-bottom: 1px solid var(--vk-border);
    color: var(--vk-text);
    display: block;
    padding: 11px 14px;
    text-decoration: none;
}

.vk-sprint-row:last-child {
    border-bottom: 0;
}

.vk-sprint-row:hover {
    background: color-mix(in srgb, var(--vk-accent) 5%, var(--vk-muted-surface));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-sprint-row-head,
.vk-sprint-row-meta {
    align-items: center;
    display: flex;
    gap: 8px;
    justify-content: space-between;
}

.vk-sprint-row-head {
    font-size: .88rem;
    margin-bottom: 5px;
}

.vk-sprint-row-meta {
    color: var(--vk-muted);
    font-size: .74rem;
    margin-bottom: 6px;
}

.vk-sprint-progress {
    height: 4px;
    margin: 0;
    border-radius: 999px;
    overflow: hidden;
}

.vk-settings-grid {
    align-items: start;
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 520px) minmax(460px, 1fr);
    max-width: 1240px;
}

.vk-settings-overview {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    margin: 0 0 16px;
    max-width: 1136px;
}

.vk-settings-overview-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    color: var(--vk-text);
    display: grid;
    gap: 2px;
    min-height: 76px;
    padding: 10px 12px;
    text-decoration: none;
}

.vk-settings-overview-card:hover,
.vk-settings-overview-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-settings-overview-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-settings-overview-card strong {
    color: var(--vk-text);
    display: block;
    font-size: 1.1rem;
    line-height: 1.15;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-settings-overview-card small {
    color: var(--vk-muted);
    font-size: .72rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-settings-overview-card.is-ready strong {
    color: var(--vk-accent);
}

.vk-settings-overview-card.is-warning strong {
    color: var(--pw-alert-warning);
}

.vk-settings-overview-card.is-muted {
    background: var(--vk-muted-surface);
}

.vk-settings-side {
    display: grid;
    gap: 18px;
}

.vk-settings-card .uk-card-body p:last-child {
    margin-bottom: 0;
}

.vk-settings-card.is-primary {
    border-color: color-mix(in srgb, var(--vk-accent) 30%, var(--vk-border));
}

.vk-settings-card .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

.vk-settings-card-note,
.vk-settings-intro {
    color: var(--vk-muted);
}

.vk-settings-card-note {
    font-size: .72rem;
    white-space: nowrap;
}

.vk-settings-intro {
    font-size: .88rem;
    line-height: 1.45;
    margin: 0 0 .85rem;
}

.vk-settings-summary {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: grid;
    gap: 0;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-bottom: .85rem;
    overflow: hidden;
}

.vk-settings-summary > div {
    padding: .65rem .75rem;
}

.vk-settings-summary > div + div {
    border-left: 1px solid var(--vk-border);
}

.vk-settings-summary span {
    color: var(--vk-muted);
    display: block;
    font-size: .7rem;
    letter-spacing: .06em;
    margin-bottom: 2px;
    text-transform: uppercase;
}

.vk-settings-summary strong {
    color: var(--vk-text);
    display: block;
    font-size: .86rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-field-help {
    color: var(--vk-muted);
    font-size: .76rem;
    line-height: 1.35;
    margin-top: 4px;
}

.vk-inline-note {
    color: var(--vk-muted);
    font-size: .76rem;
    line-height: 1.3;
}

.vk-settings-examples {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: -.1rem;
}

.vk-settings-examples span {
    color: var(--vk-muted);
    font-size: .72rem;
    text-transform: uppercase;
}

.vk-settings-examples code {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    font-size: .78rem;
    padding: 3px 7px;
}

.vk-settings-mini-list {
    display: grid;
    gap: 7px;
    margin: 0 0 .9rem;
}

.vk-settings-mini-list div {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    font-size: .84rem;
    gap: 8px;
}

.vk-settings-mini-list .fa {
    color: var(--vk-accent);
    font-size: .78rem;
}

.vk-widget-settings-form {
    display: grid;
    gap: 10px;
}

.vk-widget-config-layout {
    align-items: start;
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(0, 1fr) minmax(240px, 300px);
}

.vk-settings-options {
    display: grid;
    gap: 7px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.vk-settings-options.is-compact {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vk-settings-options label {
    align-items: flex-start;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    display: flex;
    font-size: .84rem;
    gap: 8px;
    line-height: 1.35;
    padding: 7px 9px;
}

.vk-settings-options input[type="checkbox"] {
    flex: 0 0 auto;
    margin: 2px 0 0;
}

.vk-settings-options label.is-checked {
    background: color-mix(in srgb, var(--vk-accent) 8%, var(--vk-surface));
    border-color: color-mix(in srgb, var(--vk-accent) 35%, var(--vk-border));
}

.vk-settings-options label > span {
    display: grid;
    gap: 1px;
    min-width: 0;
}

.vk-settings-options label strong {
    color: var(--vk-text);
    font-size: .84rem;
    line-height: 1.25;
}

.vk-settings-options label small {
    color: var(--vk-muted);
    font-size: .7rem;
    line-height: 1.3;
}

.vk-settings-field-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 86px;
}

.vk-settings-field-grid .uk-form-label {
    display: block;
    font-size: .76rem;
    margin-bottom: 3px;
}

.vk-settings-subtitle {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-widget-preview {
    border-left: 1px solid var(--vk-border);
    margin-top: 0;
    padding-left: 14px;
    position: sticky;
    top: 14px;
}

.vk-widget-preview-box {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    margin-top: 7px;
    padding: 9px;
}

.vk-widget-preview.is-disabled .vk-widget-preview-box {
    opacity: .72;
}

.vk-widget-preview-head {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: 7px;
}

.vk-widget-preview-head span {
    color: var(--vk-text);
    font-size: .84rem;
}

.vk-widget-preview-head a {
    color: var(--vk-accent);
    font-size: .76rem;
    text-decoration: none;
    white-space: nowrap;
}

.vk-widget-preview-head a:hover,
.vk-widget-preview-head a:focus {
    color: var(--vk-accent-strong);
    text-decoration: none;
}

.vk-widget-preview-collapsed {
    background: var(--vk-surface);
    border: 1px dashed var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .76rem;
    margin-bottom: 7px;
    padding: 6px 8px;
}

.vk-widget-preview-row {
    align-items: center;
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: flex;
    gap: 8px;
    min-height: 34px;
    padding: 6px 8px;
}

.vk-widget-preview-dot {
    background: var(--pw-alert-warning);
    border-radius: 50%;
    flex: 0 0 8px;
    height: 8px;
    width: 8px;
}

.vk-widget-preview-title {
    color: var(--vk-text);
    flex: 1 1 auto;
    font-size: .82rem;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-widget-preview-meta {
    color: var(--vk-muted);
    flex: 0 0 auto;
    font-size: .72rem;
    text-align: right;
}

.vk-widget-preview-disabled {
    color: var(--vk-muted);
    font-size: .78rem;
    margin-top: 8px;
}

.vk-widget-preview-empty,
.vk-widget-preview-placement {
    color: var(--vk-muted);
    font-size: .76rem;
    margin-top: 8px;
}

.vk-widget-preview-placement {
    border-top: 1px solid var(--vk-border);
    padding-top: 7px;
}

.vk-document-panel {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    margin: 18px 0 14px;
    max-width: 1240px;
}

.vk-document-header {
    align-items: flex-start;
    background: color-mix(in srgb, var(--vk-accent) 4%, var(--vk-surface));
    border-bottom: 1px solid var(--vk-border);
    display: flex;
    gap: 18px;
    justify-content: space-between;
    padding: 15px 17px;
}

.vk-document-title {
    display: block;
    font-size: 1.5rem !important;
    line-height: 1.22;
    margin: 0 0 7px;
}

.vk-document-meta {
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .78rem;
    line-height: 1.4;
}

.vk-document-body {
    font-size: .92rem;
    line-height: 1.62;
    max-width: 920px;
    min-height: 76px;
    padding: 17px;
}

.vk-note-form {
    min-width: 0;
}

.vk-note-workspace {
    align-items: start;
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 820px) minmax(260px, 300px);
    max-width: 1136px;
}

.vk-note-side {
    position: sticky;
    top: 12px;
}

.vk-note-side > .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

.vk-panel-note {
    color: var(--vk-muted);
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-note-fields {
    display: grid;
    gap: 0 10px;
    grid-template-columns: minmax(300px, 1fr) 240px;
}

.vk-note-fields #vk-new-cat-row {
    grid-column: 2;
}

.vk-note-info-list {
    display: grid;
    gap: 10px;
    margin: 0 0 12px;
}

.vk-note-info-list > div {
    background: linear-gradient(180deg, var(--vk-surface), var(--vk-muted-surface));
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    padding: 10px 11px;
}

.vk-note-info-list dt {
    color: var(--vk-muted);
    font-size: .68rem;
    letter-spacing: .06em;
    margin-bottom: 2px;
    text-transform: uppercase;
}

.vk-note-info-list dd {
    color: var(--vk-text);
    font-size: .9rem;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-note-side-actions {
    border-top: 1px solid var(--vk-border);
    display: grid;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
}

.vk-note-side-actions .uk-button {
    justify-content: center;
    width: 100%;
}

.vk-kb-filters {
    margin-bottom: 12px;
}

.vk-search-toolbar {
    margin: 0 0 12px;
    max-width: 1180px;
}

.vk-kb-summary {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin: 0 0 16px;
    max-width: 1180px;
}

.vk-kb-summary-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    display: grid;
    gap: 2px;
    min-height: 74px;
    padding: 10px 12px;
}

.vk-kb-summary-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-kb-summary-card strong {
    color: var(--vk-text);
    font-size: 1.35rem;
    line-height: 1.05;
}

.vk-kb-summary-card small {
    color: var(--vk-muted);
    font-size: .72rem;
}

.vk-kb-group {
    margin-bottom: 12px;
    max-width: 1180px;
}

.vk-kb-list-head {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    color: var(--vk-muted);
    display: grid;
    font-size: .68rem;
    gap: 12px;
    grid-template-columns: 30px minmax(260px, 1fr) 104px 84px;
    letter-spacing: .08em;
    padding: 9px 12px;
    text-transform: uppercase;
}

.vk-kb-list-head span:first-child {
    grid-column: 1 / 3;
}

.vk-kb-list-head span:nth-child(2),
.vk-kb-list-head span:nth-child(3) {
    text-align: right;
}

.vk-kb-documents {
    display: grid;
}

.vk-kb-document {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 12px;
    grid-template-columns: 30px minmax(260px, 1fr) 104px 84px;
    min-height: 62px;
    padding: 8px 12px;
    position: relative;
}

.vk-kb-document:last-child {
    border-bottom: 0;
}

.vk-kb-document:hover {
    background: color-mix(in srgb, var(--vk-accent) 5%, var(--vk-muted-surface));
}

.vk-kb-document:hover::before {
    background: var(--vk-accent);
    bottom: 10px;
    content: "";
    left: 0;
    position: absolute;
    top: 10px;
    width: 3px;
}

.vk-kb-icon {
    color: var(--vk-muted);
    font-size: 1rem;
    text-align: center;
}

.vk-kb-title {
    color: var(--vk-text);
    display: block;
    font-size: .92rem;
    text-decoration: none;
}

.vk-kb-title:hover {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-kb-document-meta {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: .72rem;
    gap: 7px;
    margin-top: 2px;
}

.vk-kb-document-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-kb-document-meta .fa {
    margin-right: 3px;
}

.vk-kb-excerpt,
.vk-kb-date {
    color: var(--vk-muted);
    font-size: .78rem;
}

.vk-kb-date {
    white-space: nowrap;
}

.vk-kb-actions {
    display: flex;
    gap: 5px;
    justify-content: flex-end;
}

.vk-kb-actions .vk-icon-button {
    height: 31px;
    width: 31px;
}

.vk-issue-panel {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    margin: 18px 0 14px;
    max-width: 1240px;
}

.vk-issue-header {
    align-items: flex-start;
    background: color-mix(in srgb, var(--vk-accent) 4%, var(--vk-surface));
    border-bottom: 1px solid var(--vk-border);
    display: flex;
    gap: 18px;
    justify-content: space-between;
    padding: 14px 16px;
}

.vk-issue-key {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    margin-bottom: 4px;
}

.vk-issue-title {
    display: block;
    font-size: 1.45rem !important;
    line-height: 1.2;
    margin: 0 0 10px;
}

.vk-issue-badges {
    align-items: center;
    display: flex;
    gap: 10px;
}

.vk-priority {
    align-items: center;
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .78rem;
    gap: 5px;
}

.vk-priority-high,
.vk-priority-critical {
    color: var(--pw-error-inline-text-color);
}

.vk-priority-low .fa {
    transform: rotate(180deg);
}

.vk-issue-body {
    display: grid;
    gap: 20px;
    grid-template-columns: minmax(280px, 1.15fr) minmax(400px, .85fr);
    padding: 14px 16px;
}

.vk-issue-label {
    color: var(--vk-muted);
    font-size: .69rem;
    letter-spacing: .06em;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.vk-issue-description p {
    font-size: .9rem;
    line-height: 1.5;
    margin: 0;
}

.vk-muted-line {
    color: var(--vk-muted);
    font-size: .86rem;
    line-height: 1.5;
    margin: 0;
}

.vk-rich-text > :first-child {
    margin-top: 0;
}

.vk-rich-text > :last-child {
    margin-bottom: 0;
}

.vk-rich-text p,
.vk-rich-text ul,
.vk-rich-text ol {
    font-size: .9rem;
    line-height: 1.5;
    margin: 0 0 .55rem;
}

.vk-rich-text ul,
.vk-rich-text ol {
    padding-left: 1.3rem;
}

.vk-issue-meta {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin: 0;
}

.vk-issue-meta > div {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    min-width: 0;
    padding: 8px 9px;
}

.vk-issue-meta dt {
    color: var(--vk-muted);
    font-size: .7rem;
    letter-spacing: .04em;
    margin-bottom: 3px;
    text-transform: uppercase;
}

.vk-issue-meta dd {
    font-size: .86rem;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-task-layout {
    align-items: start;
    max-width: 1240px;
}

.vk-task-workspace {
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 760px) minmax(360px, 440px);
}

.vk-task-main,
.vk-task-sidebar {
    min-width: 0;
}

.vk-task-create {
    max-width: 960px;
}

.vk-task-create .vk-task-main {
    max-width: 960px;
}

.vk-sprint-workspace {
    display: grid;
    gap: 16px;
    grid-template-columns: minmax(0, 620px) minmax(360px, 1fr);
}

.vk-sprint-main {
    min-width: 0;
}

.vk-task-layout.has-sidebar > .vk-sprint-sidebar {
    align-self: flex-start;
    position: sticky;
    top: 14px;
}

.vk-task-layout:not(.has-sidebar) {
    max-width: 1040px;
}

.vk-sprint-create {
    max-width: 840px !important;
}

.vk-sprint-create .vk-sprint-main {
    max-width: 840px;
}

.vk-sprint-tasks .uk-card-header {
    gap: 12px;
    position: relative;
    z-index: 1;
}

.vk-card-header-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    justify-content: flex-end;
}

.vk-sprint-plan-card {
    margin-bottom: 12px;
}

.vk-sprint-plan-card .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    min-height: 0;
    padding: .65rem .85rem;
}

.vk-sprint-plan-head-metrics {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}

.vk-sprint-plan-head-metrics span {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprint-plan-card .uk-card-body {
    padding: .8rem .85rem;
}

.vk-sprint-plan-progress {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: grid;
    gap: 10px;
    grid-template-columns: 46px minmax(0, 1fr);
    margin-bottom: 10px;
    padding: 8px 10px;
}

.vk-sprint-plan-progress span {
    color: var(--vk-text);
    font-size: .92rem;
}

.vk-sprint-plan-health {
    display: grid;
    gap: 6px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-bottom: 8px;
}

.vk-sprint-plan-health span {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.2;
    min-width: 0;
    overflow: hidden;
    padding: 6px 7px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-plan-health strong {
    color: var(--vk-text);
}

.vk-sprint-plan-health .is-good strong {
    color: var(--vk-accent);
}

.vk-sprint-plan-health .is-danger,
.vk-sprint-plan-health .is-danger strong {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-plan-alert {
    align-items: center;
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 7%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--pw-error-inline-text-color) 24%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--pw-error-inline-text-color);
    display: flex;
    flex-wrap: wrap;
    font-size: .76rem;
    gap: 7px;
    line-height: 1.35;
    margin: -2px 0 8px;
    padding: 7px 8px;
}

.vk-sprint-plan-alert a {
    color: var(--pw-error-inline-text-color);
    margin-left: auto;
    text-decoration: none;
}

.vk-sprint-plan-alert a:hover,
.vk-sprint-plan-alert a:focus {
    color: var(--pw-error-inline-text-color);
    text-decoration: none;
}

.vk-sprint-plan-kpis {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vk-sprint-plan-kpis > div {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    min-width: 0;
    padding: 8px 9px;
}

.vk-sprint-plan-kpis span {
    color: var(--vk-muted);
    display: block;
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-sprint-plan-kpis strong {
    color: var(--vk-text);
    display: block;
    font-size: 1rem;
    line-height: 1.2;
    margin-top: 2px;
}

.vk-sprint-plan-kpis strong.is-danger {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-plan-kpis strong.is-accent {
    color: var(--vk-accent);
}

.vk-sprint-schedule-card {
    margin-bottom: 12px;
}

.vk-sprint-schedule-card .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    min-height: 0;
    padding: .65rem .85rem;
}

.vk-sprint-schedule-state {
    background: color-mix(in srgb, var(--vk-accent) 12%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--vk-accent) 24%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--vk-accent);
    font-size: .72rem;
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprint-schedule-card .uk-card-body {
    padding: .7rem .75rem;
}

.vk-sprint-schedule-summary {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.vk-sprint-schedule-summary > div {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    min-width: 0;
    padding: 7px 8px;
}

.vk-sprint-schedule-summary span {
    color: var(--vk-muted);
    display: block;
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-sprint-schedule-summary strong {
    color: var(--vk-text);
    display: block;
    font-size: .88rem;
    line-height: 1.25;
    margin-top: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-schedule-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.vk-sprint-readiness-card {
    margin-bottom: 12px;
}

.vk-sprint-readiness-card .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    min-height: 0;
    padding: .65rem .85rem;
}

.vk-sprint-readiness-score {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    font-size: .72rem;
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprint-readiness-card .uk-card-body {
    padding: .7rem .75rem;
}

.vk-sprint-readiness-progress {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    margin-bottom: 7px;
    padding: 7px 8px;
}

.vk-sprint-readiness-progress .uk-progress {
    height: 6px;
    margin: 0;
}

.vk-sprint-readiness-list {
    display: grid;
    gap: 6px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.vk-readiness-item {
    align-items: center;
    border: 1px solid transparent;
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    display: grid;
    gap: 7px;
    grid-template-columns: 20px minmax(0, 1fr);
    padding: 6px;
    text-decoration: none;
}

.vk-readiness-item:hover,
.vk-readiness-item:focus {
    background: var(--vk-muted-surface);
    border-color: var(--vk-border);
    color: var(--vk-text);
    text-decoration: none;
}

.vk-readiness-check {
    align-items: center;
    border: 1px solid var(--vk-border);
    border-radius: 50%;
    color: var(--vk-muted);
    display: inline-flex;
    height: 20px;
    justify-content: center;
    width: 20px;
}

.vk-readiness-item.is-done .vk-readiness-check {
    background: color-mix(in srgb, var(--vk-accent) 12%, var(--vk-surface));
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-accent);
}

.vk-readiness-item strong,
.vk-readiness-item small {
    display: block;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-readiness-item strong {
    font-size: .78rem;
}

.vk-readiness-item small {
    color: var(--vk-muted);
    font-size: .68rem;
    margin-top: 1px;
}

.vk-sprint-picker {
    background: var(--vk-muted-surface);
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 8px;
    padding: 12px 14px;
}

.vk-sprint-picker[hidden] {
    display: none;
}

.vk-sprint-search {
    position: relative;
}

.vk-sprint-picker .uk-form-label {
    color: var(--vk-muted);
    font-size: .7rem;
    letter-spacing: .08em;
    margin: 0;
    text-transform: uppercase;
}

.vk-sprint-search .fa {
    color: var(--vk-muted);
    left: 11px;
    position: absolute;
    top: 10px;
}

.vk-sprint-search .uk-input {
    height: 36px;
    padding-left: 31px;
}

.vk-sprint-task-results {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    max-height: 255px;
    overflow-y: auto;
}

.vk-sprint-issue-list {
    display: grid;
}

.vk-sprint-issue {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(0, 1fr) minmax(150px, auto);
    min-height: 58px;
    padding: 10px 12px 10px 14px;
    position: relative;
}

.vk-sprint-issue:last-child {
    border-bottom: 0;
}

.vk-sprint-issue:hover {
    background: var(--vk-muted-surface);
}

.vk-sprint-issue.is-due-outside {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 5%, var(--vk-surface));
    box-shadow: inset 3px 0 0 var(--pw-error-inline-text-color);
}

.vk-sprint-issue.is-due-outside .vk-sprint-issue-meta .fa,
.vk-sprint-issue.is-due-outside .vk-sprint-issue-meta span {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-issue.is-due-missing {
    box-shadow: inset 3px 0 0 color-mix(in srgb, var(--vk-muted) 45%, var(--vk-border));
}

.vk-sprint-issue.is-due-in-window {
    box-shadow: inset 3px 0 0 color-mix(in srgb, var(--vk-accent) 70%, var(--vk-border));
}

.vk-issue-row-list {
    display: grid;
}

.vk-issue-list-head {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    color: var(--vk-muted);
    display: grid;
    font-size: .68rem;
    gap: 14px;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 340px);
    letter-spacing: .08em;
    padding: 10px 12px 9px 16px;
    text-transform: uppercase;
}

.vk-issue-list-head span:last-child {
    text-align: right;
}

.vk-task-list-card {
    max-width: 1180px;
}

.vk-task-status-strip {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin: 0 0 16px;
    max-width: 1180px;
}

.vk-task-status-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    color: var(--vk-text);
    display: grid;
    gap: 2px;
    min-height: 58px;
    padding: 8px 10px;
    text-decoration: none;
}

.vk-task-status-card:hover,
.vk-task-status-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 35%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-task-status-card.is-active {
    border-color: var(--vk-accent);
    box-shadow: inset 3px 0 0 var(--vk-accent);
}

.vk-task-status-name {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-task-status-card strong {
    font-size: 1.18rem;
    line-height: 1.05;
}

.vk-task-status-card small {
    color: var(--vk-muted);
    font-size: .72rem;
}

.vk-task-sprint-context {
    align-items: center;
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) auto auto;
    margin: 0 0 14px;
    max-width: 1180px;
    padding: 12px 14px;
}

.vk-task-sprint-main {
    align-items: center;
    display: flex;
    gap: 10px;
    min-width: 0;
}

.vk-task-sprint-main h3 {
    font-size: .98rem;
    line-height: 1.25;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-task-sprint-main p {
    color: var(--vk-muted);
    font-size: .78rem;
    line-height: 1.35;
    margin: 2px 0 0;
}

.vk-task-sprint-stats {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}

.vk-task-sprint-stats span {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .74rem;
    line-height: 1.45;
    padding: 3px 7px;
    white-space: nowrap;
}

.vk-task-sprint-stats strong {
    color: var(--vk-text);
}

.vk-issue-row {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) minmax(220px, 300px);
    min-height: 58px;
    padding: 10px 12px 10px 14px;
    position: relative;
}

.vk-issue-row:last-child {
    border-bottom: 0;
}

.vk-issue-row:hover {
    background: var(--vk-muted-surface);
}

.vk-issue-row:hover::before,
.vk-mini-row:hover::before,
.vk-sprint-issue:hover::before {
    background: var(--vk-accent);
    bottom: 10px;
    content: "";
    left: 0;
    position: absolute;
    top: 10px;
    width: 3px;
}

.vk-issue-row-main {
    min-width: 0;
}

.vk-issue-title-line {
    align-items: center;
    display: flex;
    gap: 8px;
    min-width: 0;
}

.vk-issue-key {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    flex: 0 0 auto;
    font-size: .68rem;
    line-height: 1.6;
    padding: 0 6px;
}

.vk-issue-row-title {
    color: var(--vk-text);
    display: block;
    font-size: .92rem;
    line-height: 1.35;
    overflow: hidden;
    text-decoration: none;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-issue-row-title:hover {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-issue-description {
    color: var(--vk-muted);
    font-size: .74rem;
    line-height: 1.3;
    margin-top: 2px;
    max-width: 760px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-issue-row-meta,
.vk-issue-row-links,
.vk-issue-row-side {
    align-items: center;
    display: flex;
    gap: 7px;
}

.vk-issue-row-meta {
    color: var(--vk-muted);
    flex-wrap: wrap;
    font-size: .7rem;
    margin-top: 4px;
}

.vk-issue-row-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-issue-row-meta .fa {
    color: color-mix(in srgb, var(--vk-muted) 84%, var(--vk-text));
    margin-right: 3px;
}

.vk-inline-page-link {
    color: var(--vk-muted);
    text-decoration: none;
}

.vk-inline-page-link:hover,
.vk-inline-page-link:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-issue-row-meta .is-overdue {
    color: var(--pw-error-inline-text-color);
}

.vk-issue-row-meta .is-muted {
    color: color-mix(in srgb, var(--vk-muted) 74%, var(--vk-surface));
}

.vk-mini-meta .vk-quarter-inline,
.vk-issue-row-meta .vk-quarter-inline {
    background: color-mix(in srgb, var(--vk-accent) 10%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--vk-accent) 24%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--vk-accent);
    line-height: 1.45;
    padding: 0 5px;
}

.vk-mini-meta .vk-quarter-inline::before,
.vk-issue-row-meta .vk-quarter-inline::before {
    content: "" !important;
    margin-right: 0 !important;
}

.vk-issue-row-links {
    margin-top: 4px;
}

.vk-issue-row-side {
    flex-wrap: wrap;
    justify-content: flex-end;
    min-width: 0;
}

.vk-issue-row-side .uk-label,
.vk-issue-row-side .vk-sprint-pill {
    font-size: .68rem;
    line-height: 1.45;
    min-height: 0;
    padding: 2px 6px;
}

.vk-icon-button {
    align-items: center;
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: inline-flex;
    flex: 0 0 30px;
    height: 30px;
    justify-content: center;
    text-decoration: none;
    width: 30px;
}

.vk-icon-button:hover,
.vk-icon-button:focus {
    border-color: var(--vk-accent);
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-icon-button-danger {
    background: transparent;
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 18%, var(--vk-border));
    color: var(--pw-error-inline-text-color);
    cursor: pointer;
    padding: 0;
}

.vk-icon-button-danger:hover,
.vk-icon-button-danger:focus {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 9%, transparent);
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 45%, var(--vk-border));
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-issue-main {
    min-width: 0;
}

.vk-sprint-issue-titleline {
    align-items: center;
    display: flex;
    gap: 8px;
    min-width: 0;
}

.vk-sprint-issue-title {
    color: var(--vk-text);
    display: block;
    font-size: .9rem;
    line-height: 1.35;
    overflow: hidden;
    text-decoration: none;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-issue-title:hover {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-sprint-issue-meta,
.vk-sprint-issue-side {
    align-items: center;
    display: flex;
    gap: 7px;
}

.vk-sprint-issue-meta {
    color: var(--vk-muted);
    font-size: .72rem;
    margin-top: 2px;
    overflow: hidden;
    white-space: nowrap;
}

.vk-sprint-issue-meta span {
    overflow: hidden;
    text-overflow: ellipsis;
}

.vk-sprint-issue-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-sprint-issue-side {
    flex-wrap: nowrap;
    justify-content: flex-end;
    min-width: 0;
}

.vk-sprint-pill {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .72rem;
    line-height: 1.5;
    padding: 0 6px;
    white-space: nowrap;
}

.vk-sprint-pill.is-over {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-remove-task {
    align-items: center;
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    cursor: pointer;
    display: inline-flex;
    height: 28px;
    justify-content: center;
    min-height: 28px;
    padding: 0;
    width: 28px;
}

.vk-sprint-remove-task:hover,
.vk-sprint-remove-task:focus {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 10%, transparent);
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 28%, var(--vk-border));
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-picker-state {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: .84rem;
    gap: 10px;
    justify-content: space-between;
    padding: 13px 12px;
}

.vk-sprint-picker-state.is-error {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-picker-state .uk-button {
    flex: 0 0 auto;
}

.vk-sprint-pick-item {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) auto;
    min-height: 56px;
    padding: 9px 10px 9px 12px;
}

.vk-sprint-pick-item:last-child {
    border-bottom: 0;
}

.vk-sprint-pick-item:hover {
    background: var(--vk-muted-surface);
}

.vk-sprint-pick-details {
    min-width: 0;
}

.vk-sprint-pick-titleline {
    align-items: center;
    display: flex;
    gap: 8px;
    min-width: 0;
}

.vk-sprint-pick-title {
    font-size: .88rem;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-pick-meta {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: .73rem;
    gap: 7px;
    margin-top: 2px;
    text-transform: capitalize;
}

.vk-sprint-pick-meta span + span::before {
    content: "·";
    margin-right: 7px;
}

.vk-sprint-pick-side {
    align-items: center;
    display: flex;
    gap: 7px;
    justify-content: flex-end;
}

.vk-sprint-pick-add {
    min-width: 64px;
}

.vk-task-card .uk-card-header p {
    color: var(--vk-muted);
    margin: .25rem 0 0;
}

.vk-form-compact .uk-card-header {
    min-height: 0;
    padding: .52rem .85rem;
}

.vk-form-compact .uk-card-header p {
    display: none;
}

.vk-form-compact .uk-card-body {
    padding: .65rem .85rem .75rem;
}

.vk-field {
    margin: 0 0 .75rem;
}

.vk-field > .uk-form-label,
.vk-time-form .uk-form-label,
.vk-sprint-picker .uk-form-label {
    color: var(--vk-muted);
    display: block;
    font-size: .76rem;
    line-height: 1.25;
    margin-bottom: 3px;
}

.vk-form-compact .vk-field {
    margin-bottom: .5rem;
    margin-top: 0;
}

.vk-form-compact .uk-form-label {
    font-size: .78rem;
}

.vk-shell .uk-input,
.vk-shell .uk-select,
.vk-shell .uk-textarea {
    border-radius: var(--vk-radius-sm);
    box-shadow: none;
    transition: border-color .12s ease, box-shadow .12s ease, background-color .12s ease;
}

.vk-shell .uk-input:focus,
.vk-shell .uk-select:focus,
.vk-shell .uk-textarea:focus {
    border-color: var(--vk-accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--vk-accent) 16%, transparent);
}

.vk-shell .vk-control-small {
    font-size: .84rem;
    height: 34px;
    line-height: 34px;
    min-height: 34px;
    padding-bottom: 0;
    padding-top: 0;
}

.vk-form-compact .uk-input,
.vk-form-compact .uk-select {
    font-size: .9rem;
    height: 34px;
}

.vk-readonly-input {
    background: var(--vk-muted-surface) !important;
    color: var(--vk-muted) !important;
    cursor: default;
}

.vk-field-hint {
    color: var(--vk-muted);
    font-size: .75rem;
    line-height: 1.35;
    margin-top: 4px;
}

.vk-field-hint.is-active {
    color: var(--vk-accent);
}

.vk-inline-delete {
    display: inline;
    margin: 0 0 0 auto;
}

.vk-delete-link {
    font-size: .7rem !important;
}

.vk-comment-author {
    color: var(--vk-text);
    font-size: .78rem;
}

.vk-comment-date {
    color: var(--vk-muted);
    font-size: .74rem;
}

.vk-button-stack {
    margin-top: 8px !important;
}

.vk-form-inline {
    margin: 0;
}

.vk-hidden-row,
.vk-bulk-panel[hidden] {
    display: none !important;
}

.vk-form-compact textarea.uk-textarea {
    font-size: .9rem;
    min-height: 64px !important;
    padding: .38rem .55rem;
}

.vk-rich-editor .InputfieldTinyMCEEditor {
    width: 100%;
}

.vk-rich-editor textarea.vk-tinymce-editor {
    min-height: 110px !important;
    width: 100%;
}

.vk-rich-editor .tox-tinymce {
    border-color: var(--vk-border);
    border-radius: var(--vk-radius-sm);
}

.vk-sprint-editor .tox-tinymce {
    min-height: 220px;
}

.vk-sprint-editor textarea.vk-tinymce-editor {
    min-height: 130px !important;
}

.vk-rich-editor .tox .tox-toolbar-overlord,
.vk-rich-editor .tox .tox-toolbar__primary {
    background: var(--vk-muted-surface);
}

.vk-note-form textarea.uk-textarea {
    min-height: 260px !important;
}

.vk-note-editor .tox-tinymce {
    min-height: 360px;
}

.vk-note-editor textarea.vk-tinymce-editor {
    min-height: 260px !important;
}

.vk-form-section {
    border-bottom: 1px solid var(--vk-border);
    margin-bottom: 1.1rem;
    padding-bottom: .9rem;
    scroll-margin-top: 92px;
}

.vk-form-section:last-of-type {
    border-bottom: 0;
    margin-bottom: .4rem;
    padding-bottom: 0;
}

.vk-form-compact .vk-form-section {
    margin-bottom: .55rem;
    padding-bottom: .4rem;
}

.vk-form-compact .vk-form-section:last-of-type {
    margin-bottom: 0;
}

.vk-workflow-section {
    display: grid;
    gap: 0 12px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vk-workflow-section .vk-form-section-title {
    grid-column: 1 / -1;
}

.vk-workflow-section .vk-form-grid {
    display: contents;
}

.vk-workflow-section .vk-form-grid > div {
    min-width: 0;
}

.vk-task-card .vk-rich-editor .tox-tinymce {
    height: 190px !important;
    min-height: 118px;
}

.vk-task-card .vk-rich-editor textarea.vk-tinymce-editor {
    min-height: 96px !important;
}

.vk-task-card .vk-form-section {
    margin-bottom: .45rem;
    padding-bottom: .35rem;
}

.vk-task-card .vk-form-actions {
    margin-top: .35rem;
}

.vk-form-section:target,
#vk-sprint-goal:target {
    animation: vk-target-pulse 1.8s ease-out 1;
    background: color-mix(in srgb, var(--vk-accent) 7%, transparent);
    border-radius: var(--vk-radius);
    outline: 2px solid color-mix(in srgb, var(--vk-accent) 22%, transparent);
    outline-offset: 8px;
}

#vk-sprint-goal {
    scroll-margin-top: 110px;
}

.vk-content .vk-form-section:target,
.vk-content #vk-sprint-goal:target {
    background-color: color-mix(in srgb, var(--vk-accent) 7%, transparent) !important;
    outline-color: color-mix(in srgb, var(--vk-accent) 28%, transparent) !important;
    outline-style: solid !important;
    outline-width: 2px !important;
}

@keyframes vk-target-pulse {
    0% {
        background: color-mix(in srgb, var(--vk-accent) 18%, transparent);
        outline-color: color-mix(in srgb, var(--vk-accent) 45%, transparent);
    }
    100% {
        background: color-mix(in srgb, var(--vk-accent) 7%, transparent);
        outline-color: color-mix(in srgb, var(--vk-accent) 22%, transparent);
    }
}

.vk-form-section-title {
    color: var(--vk-muted);
    font-size: .76rem;
    letter-spacing: .06em;
    margin: 0 0 .75rem;
    text-transform: uppercase;
}

.vk-form-grid {
    display: grid;
    gap: 0 12px;
}

.vk-form-grid.is-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.vk-form-grid.is-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vk-quarter-planner,
.vk-quarter-due-picker {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    margin-bottom: .65rem;
    padding: .55rem;
}

.vk-quarter-planner.is-active,
.vk-quarter-due-picker.is-active {
    border-color: color-mix(in srgb, var(--vk-accent) 40%, var(--vk-border));
}

.vk-quarter-planner.is-invalid {
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 55%, var(--vk-border));
}

.vk-field-error {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 9%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--pw-error-inline-text-color) 32%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--pw-error-inline-text-color);
    font-size: .78rem;
    margin: -2px 0 10px;
    padding: 7px 9px;
}

.vk-quarter-planner-head {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: .45rem;
}

.vk-quarter-planner-head span:first-child {
    color: var(--vk-text);
    font-size: .82rem;
}

.vk-quarter-planner-head span:last-child {
    color: var(--vk-muted);
    font-size: .75rem;
}

.vk-quarter-planner.is-active .vk-quarter-planner-head span:last-child,
.vk-quarter-due-picker.is-active .vk-quarter-planner-head span:last-child {
    color: var(--vk-accent);
}

.vk-quarter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.vk-quarter-buttons .uk-button {
    min-width: 46px;
}

.vk-quarter-buttons .uk-button.is-active {
    background: var(--vk-accent);
    border-color: var(--vk-accent);
    color: var(--vk-accent-contrast);
}

.vk-quarter-buttons [data-quarter-clear] {
    margin-left: auto;
}

.vk-form-compact .vk-form-section-title {
    font-size: .68rem;
    margin-bottom: .45rem;
}

.vk-form-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 1.1rem;
}

.vk-form-compact .vk-form-actions {
    margin-top: .45rem;
}

.vk-edit-card {
    scroll-margin-top: 16px;
}

.vk-discussion-card .uk-card-body {
    padding: .8rem;
}

.vk-discussion-card textarea {
    min-height: 64px !important;
}

.vk-comment-editor {
    margin-top: .2rem;
}

.vk-comment-editor .tox-tinymce {
    height: 170px !important;
}

.vk-time-card .uk-card-header {
    padding-bottom: .65rem;
    padding-top: .65rem;
}

.vk-time-subtitle {
    color: var(--vk-muted);
    display: block;
    font-size: .72rem;
    margin-top: 2px;
}

.vk-time-total {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    min-width: 66px;
    padding: 5px 8px;
    text-align: right;
}

.vk-time-total span {
    color: var(--vk-text);
    display: block;
    font-size: 1rem;
    line-height: 1.1;
}

.vk-time-total small {
    color: var(--vk-muted);
    display: block;
    font-size: .66rem;
}

.vk-time-card .uk-card-body {
    padding: .8rem;
}

.vk-time-entries {
    border-bottom: 1px solid var(--vk-border);
    margin: -.2rem 0 .8rem;
    padding-bottom: .55rem;
}

.vk-time-entry {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    padding: .35rem 0;
}

.vk-time-hours {
    color: var(--vk-text);
    font-size: .9rem;
    margin-right: 8px;
}

.vk-time-date,
.vk-time-person {
    color: var(--vk-muted);
    font-size: .76rem;
}

.vk-time-person {
    margin-top: 2px;
}

.vk-time-empty {
    background: var(--vk-muted-surface);
    border-bottom: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: grid;
    gap: 2px;
    font-size: .78rem;
    margin: 0 0 .8rem;
    padding: 10px 11px;
}

.vk-time-empty span {
    color: var(--vk-text);
    font-size: .84rem;
}

.vk-time-empty small {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.35;
}

.vk-time-form {
    display: grid;
    gap: 8px;
    grid-template-columns: 90px minmax(135px, 1fr);
}

.vk-time-form .uk-form-label {
    font-size: .76rem;
}

.vk-time-form .uk-input {
    font-size: .84rem;
    height: 34px;
    max-width: 100%;
}

.vk-time-note,
.vk-time-submit {
    grid-column: 1 / -1;
}

.vk-time-submit {
    padding-top: 2px;
}

.vk-skeleton {
    background: linear-gradient(90deg, var(--vk-skeleton-a), var(--vk-skeleton-b), var(--vk-skeleton-a));
    background-size: 220% 100%;
    animation: vkSkeleton 1.5s ease-in-out infinite;
}

.vk-skeleton-line {
    display: block;
    height: 12px;
    margin: 8px auto;
    max-width: 360px;
}

.vk-skeleton-line.is-short {
    max-width: 180px;
}

.vk-skeleton-card {
    min-height: 132px;
    padding: 20px;
}

.vk-skeleton-card span {
    display: block;
    height: 12px;
    margin-bottom: 12px;
}

.vk-skeleton-card span:nth-child(1) { width: 38%; }
.vk-skeleton-card span:nth-child(2) { width: 74%; }
.vk-skeleton-card span:nth-child(3) { width: 52%; margin-bottom: 0; }

.vk-empty {
    align-items: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 156px;
    padding: 28px;
    text-align: center;
}

.vk-empty-panel {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
}

.vk-empty-title {
    color: var(--vk-text);
    font-size: .98rem;
    margin-bottom: 4px;
}

.vk-empty-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-top: 14px;
}

.vk-sprint-empty-state {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    max-width: 1180px;
}

.vk-filter-tabs {
    background: transparent;
    display: inline-flex;
    gap: 8px;
    margin-bottom: 16px;
    padding: 0;
}

.vk-view-switcher {
    align-items: center;
    background: transparent;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0 0 16px;
}

.vk-task-filters {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: -2px 0 10px;
}

.vk-task-filters.vk-filter-panel {
    align-items: start;
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(280px, 1.2fr) minmax(240px, 1fr) minmax(240px, 1fr);
    margin: 0 0 14px;
    max-width: 1180px;
    padding: 10px;
}

.vk-sprint-filter-panel,
.vk-task-filters.vk-filter-panel.vk-sprint-filter-panel {
    grid-template-columns: minmax(320px, 1.35fr) minmax(260px, 1fr) minmax(130px, .45fr);
    max-width: 1180px;
}

.vk-task-filter-group {
    align-items: start;
    display: grid;
    gap: 6px;
    flex: 0 1 auto;
    min-width: 0;
}

.vk-task-filter-group.is-tabs {
    align-items: center;
    display: grid;
    gap: 8px;
    grid-template-columns: 74px minmax(0, 1fr);
}

.vk-task-filter-group.is-tabs .vk-task-filter-label {
    display: block;
    line-height: 1.25;
}

.vk-task-search-group {
    grid-column: 1 / -1;
    max-width: none;
}

.vk-task-filter-label {
    color: var(--vk-muted);
    font-size: .68rem;
    letter-spacing: .08em;
    line-height: 1;
    min-width: max-content;
    text-transform: uppercase;
}

.vk-task-filter-tabs {
    margin-bottom: 0;
}

.vk-task-filters.vk-filter-panel .vk-task-filter-tabs {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    gap: 0;
    margin: 0;
    min-width: 0;
    padding: 3px;
}

.vk-task-filters.vk-filter-panel .vk-task-filter-group.is-tabs .vk-task-filter-tabs {
    display: inline-flex;
    flex-wrap: wrap;
}

.vk-task-filters.vk-filter-panel .vk-task-filter-tabs > * {
    padding-left: 0;
}

.vk-task-filters.vk-filter-panel .vk-view-switcher > * > :first-child {
    border-radius: var(--vk-radius-sm);
    min-height: 30px;
    padding: 0 9px;
}

.vk-task-search-form {
    margin: 0;
}

.vk-task-search-control {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: grid;
    gap: 8px;
    grid-template-columns: auto minmax(0, 1fr) auto auto;
    min-height: 36px;
    padding: 3px 4px 3px 10px;
}

.vk-task-search-control .fa {
    color: var(--vk-muted);
    font-size: .82rem;
}

.vk-task-search-control .uk-input {
    background: transparent;
    border: 0;
    box-shadow: none;
    height: 30px;
    padding-left: 0;
}

.vk-task-search-control .uk-input:focus {
    background: transparent;
    box-shadow: none;
}

.vk-task-search-control .uk-button {
    min-height: 28px;
    padding: 0 10px;
}

.vk-filter-select-form {
    align-items: stretch;
    display: grid;
    gap: 6px;
}

.vk-filter-select-form .uk-select {
    background-color: var(--vk-muted-surface);
    border-color: var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    font-size: .82rem;
    height: 32px;
    min-width: 0;
    width: 100%;
}

.vk-filter-reset {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.2;
    text-decoration: none;
}

.vk-filter-reset:hover,
.vk-filter-reset:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-active-filters {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin: -2px 0 10px;
    max-width: none;
}

.vk-active-filters-label {
    color: var(--vk-muted);
    font-size: .68rem;
    letter-spacing: .08em;
    margin-right: 3px;
    text-transform: uppercase;
}

.vk-active-filter-chip {
    align-items: center;
    background: color-mix(in srgb, var(--vk-accent) 9%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--vk-accent) 24%, var(--vk-border));
    border-radius: 999px;
    color: var(--vk-text);
    display: inline-flex;
    font-size: .76rem;
    gap: 6px;
    line-height: 1.45;
    padding: 3px 8px 3px 10px;
    text-decoration: none;
}

.vk-active-filter-chip:hover,
.vk-active-filter-chip:focus {
    border-color: var(--vk-accent);
    color: var(--vk-text);
    text-decoration: none;
}

.vk-active-filter-chip .fa {
    color: var(--vk-muted);
    font-size: .68rem;
}

.vk-active-filter-clear {
    color: var(--vk-muted);
    font-size: .76rem;
    text-decoration: none;
}

.vk-active-filter-clear:hover,
.vk-active-filter-clear:focus {
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-pagination-summary {
    color: var(--vk-muted);
    font-size: .78rem;
    line-height: 1.5;
}

.vk-pagination-wrap {
    align-items: center;
    border-top: 1px solid var(--vk-border);
    display: flex;
    justify-content: center;
    min-height: 44px;
    padding: 8px 12px;
}

.vk-pagination-wrap .uk-pagination {
    margin: 0;
}

.vk-task-filter-divider {
    background: var(--vk-border);
    display: inline-block;
    height: 24px;
    width: 1px;
}

.vk-quarter-overview {
    margin: 0 0 8px;
}

.vk-quarter-overview-head {
    align-items: center;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: 4px;
}

.vk-quarter-overview-head > span {
    color: var(--vk-muted);
    font-size: .74rem;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.vk-quarter-overview-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.vk-quarter-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    color: var(--vk-text);
    display: grid;
    gap: 2px;
    min-height: 58px;
    padding: 8px 10px;
    text-decoration: none;
}

.vk-quarter-overview .vk-quarter-card {
    align-items: center;
    gap: 0 8px;
    grid-template-columns: minmax(0, 1fr) auto;
    min-height: 46px;
    padding: 7px 10px;
}

.vk-quarter-overview .vk-quarter-card small {
    grid-column: 1 / -1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-quarter-card:hover,
.vk-quarter-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-quarter-card.is-active {
    border-color: var(--vk-accent);
    box-shadow: inset 3px 0 0 var(--vk-accent);
}

.vk-quarter-card.is-muted {
    background: var(--vk-muted-surface);
}

.vk-quarter-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.2;
}

.vk-quarter-card strong {
    font-size: 1.18rem;
    line-height: 1.05;
}

.vk-quarter-card small {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.25;
}

.vk-quarter-nav {
    align-items: center;
    display: inline-flex;
    gap: 4px;
}

.vk-quarter-nav a {
    border: 1px solid transparent;
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .78rem;
    min-width: 34px;
    padding: 7px 9px;
    text-align: center;
    text-decoration: none;
}

.vk-quarter-nav a:hover,
.vk-quarter-nav a:focus {
    background: var(--vk-muted-surface);
    color: var(--vk-text);
}

.vk-quarter-nav a.is-active {
    background: var(--vk-text);
    border-color: var(--vk-text);
    color: var(--vk-surface);
}

.vk-quarter-year {
    align-items: center;
    display: inline-flex;
    margin: 0;
}

.vk-quarter-year .uk-input {
    height: 34px;
    max-width: 78px;
}

.vk-view-switcher > * {
    padding-left: 0;
}

.vk-view-switcher > * > :first-child {
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .78rem;
    min-height: 32px;
    padding: 0 10px;
    text-transform: uppercase;
}

.vk-view-switcher > * > a:hover,
.vk-view-switcher > * > a:focus {
    background: var(--vk-muted-surface);
    color: var(--vk-text);
}

.vk-view-switcher > .uk-active > a {
    background: var(--vk-accent);
    border-color: var(--vk-accent);
    color: var(--vk-accent-contrast);
}

.vk-shell .uk-card:hover {
    box-shadow: var(--vk-shadow-hover);
}

.vk-shell .uk-card .uk-card {
    box-shadow: none;
}

.vk-shell .uk-table th {
    background: var(--vk-muted-surface);
}

.vk-shell .uk-pagination > * > * {
    border-radius: var(--vk-radius-sm);
    text-decoration: none;
}

.vk-shell .uk-pagination > .uk-active > * {
    background: var(--vk-text);
    color: var(--vk-surface);
}

.vk-section-label {
    color: var(--vk-muted);
    font-size: .74rem;
    letter-spacing: .08em;
    margin: 18px 0 8px;
    text-transform: uppercase;
}

.vk-sprint-group-head {
    align-items: end;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    margin: 18px 0 8px;
}

.vk-sprint-group-head .vk-section-label {
    margin: 0;
}

.vk-sprint-group-meta {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: .74rem;
    gap: 8px;
    justify-content: flex-end;
}

.vk-sprint-group-meta span {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprints-list {
    display: grid;
    gap: 10px;
}

.vk-sprint-summary-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    margin: 0 0 12px;
    max-width: 1180px;
}

.vk-sprint-summary-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    display: grid;
    gap: 2px;
    min-height: 66px;
    min-width: 0;
    padding: 8px 10px;
    text-decoration: none;
}

a.vk-sprint-summary-card:hover,
a.vk-sprint-summary-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 34%, var(--vk-border));
    box-shadow: var(--vk-shadow-strong);
    text-decoration: none;
}

a.vk-sprint-summary-card.is-active {
    outline: 2px solid color-mix(in srgb, var(--vk-accent) 32%, transparent);
    outline-offset: 2px;
}

.vk-sprint-summary-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-sprint-summary-card strong {
    color: var(--vk-text);
    font-size: 1.24rem;
    line-height: 1.05;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-summary-card small {
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1.25;
}

.vk-sprint-summary-card.is-warning {
    border-color: color-mix(in srgb, var(--pw-alert-warning) 32%, var(--vk-border));
}

.vk-sprint-summary-card.is-warning strong {
    color: #7a5b00;
}

.vk-sprint-summary-card.is-success {
    border-color: color-mix(in srgb, var(--pw-alert-success) 28%, var(--vk-border));
}

.vk-sprint-summary-card.is-success strong {
    color: var(--pw-alert-success);
}

.vk-sprint-board-row {
    align-items: stretch;
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(0, 1fr) minmax(190px, .34fr) minmax(210px, .36fr);
    position: relative;
}

.vk-sprint-board-card .uk-card-body {
    padding: 12px;
}

.vk-sprint-board-row > div:first-child {
    min-width: 0;
}

.vk-sprint-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    justify-content: center;
    min-width: 0;
}

.vk-sprint-action-group {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: flex-end;
}

.vk-sprint-action-group.is-plan {
    background: color-mix(in srgb, var(--pw-alert-warning) 7%, var(--vk-muted-surface));
    border: 1px solid color-mix(in srgb, var(--pw-alert-warning) 18%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    padding: 6px;
}

.vk-sprint-action-group.is-main .uk-button[title],
.vk-sprint-action-group.is-main form .uk-button {
    min-width: 34px;
    padding-left: 10px;
    padding-right: 10px;
}

.vk-sprint-actions form {
    margin: 0;
}

.vk-sprint-status-action {
    border-color: color-mix(in srgb, var(--vk-accent) 24%, var(--vk-border));
    color: var(--vk-accent);
}

.vk-sprint-schedule-action {
    border-color: color-mix(in srgb, var(--pw-alert-warning) 32%, var(--vk-border));
    color: #7a5b00;
}

.vk-sprint-dot {
    border-radius: 50%;
    display: inline-block;
    flex: 0 0 9px;
    height: 9px;
    width: 9px;
}

.vk-sprint-dot.is-planned {
    background: var(--vk-muted);
}

.vk-quarter-badge {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .72rem;
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprint-days {
    background: color-mix(in srgb, var(--vk-accent) 8%, var(--vk-surface));
    border: 1px solid color-mix(in srgb, var(--vk-accent) 20%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--vk-accent);
    font-size: .72rem;
    line-height: 1;
    padding: 5px 7px;
    white-space: nowrap;
}

.vk-sprint-days.is-muted {
    background: var(--vk-muted-surface);
    border-color: var(--vk-border);
    color: var(--vk-muted);
}

.vk-sprint-days.is-overdue {
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-days.is-warning {
    color: var(--pw-alert-warning);
}

.vk-sprint-days.is-done {
    color: var(--pw-alert-success);
}

.vk-sprint-dot.is-active {
    background: var(--vk-accent);
}

.vk-sprint-dot.is-completed {
    background: var(--pw-alert-success);
}

.vk-sprint-heading {
    align-items: center;
    display: flex;
    gap: 9px;
    min-width: 0;
}

.vk-sprint-name {
    color: var(--vk-text);
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-name:hover {
    color: var(--vk-accent);
}

.vk-sprint-date {
    color: var(--vk-muted);
    font-size: .76rem;
    margin-top: 3px;
}

.vk-sprint-timeline {
    display: grid;
    gap: 4px;
    margin-top: 6px;
    max-width: 460px;
}

.vk-sprint-timeline-track {
    background: var(--vk-muted-surface);
    border-radius: 999px;
    height: 7px;
    overflow: hidden;
    position: relative;
}

.vk-sprint-timeline-fill {
    background: linear-gradient(90deg, var(--vk-accent), color-mix(in srgb, var(--vk-accent) 55%, var(--pw-alert-success)));
    border-radius: inherit;
    display: block;
    height: 100%;
    min-width: 7px;
    width: var(--vk-progress, 0%);
}

.vk-sprint-timeline-marker {
    background: var(--vk-surface);
    border: 2px solid var(--vk-accent);
    border-radius: 50%;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .18);
    height: 13px;
    left: var(--vk-progress, 0%);
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 13px;
}

.vk-sprint-timeline-labels {
    color: var(--vk-muted);
    display: flex;
    font-size: .68rem;
    justify-content: space-between;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-sprint-task-preview {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 7px;
    max-height: 60px;
    overflow: hidden;
}

.vk-sprint-goal-preview {
    display: -webkit-box;
    font-size: .78rem;
    line-height: 1.35;
    margin-top: 6px;
    max-height: 42px;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.vk-sprint-goal-preview p {
    margin-bottom: 0;
}

.vk-sprint-task-chip,
.vk-sprint-task-more {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    display: inline-flex;
    font-size: .7rem;
    gap: 6px;
    line-height: 1.2;
    max-width: 290px;
    min-height: 24px;
    padding: 4px 7px;
    text-decoration: none;
}

.vk-sprint-task-chip:hover,
.vk-sprint-task-chip:focus,
.vk-sprint-task-more:hover,
.vk-sprint-task-more:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 32%, var(--vk-border));
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-sprint-task-chip::before {
    background: var(--vk-muted);
    border-radius: 50%;
    content: "";
    flex: 0 0 7px;
    height: 7px;
    width: 7px;
}

.vk-sprint-task-chip.is-in_progress::before,
.vk-sprint-task-chip.is-review::before {
    background: var(--pw-alert-warning);
}

.vk-sprint-task-chip.is-done::before {
    background: var(--pw-alert-success);
}

.vk-sprint-task-key,
.vk-sprint-task-date {
    color: var(--vk-muted);
    flex: 0 0 auto;
    text-transform: uppercase;
}

.vk-sprint-task-page {
    align-items: center;
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: inline-flex;
    flex: 0 1 auto;
    gap: 4px;
    min-width: 0;
    overflow: hidden;
    padding: 2px 5px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-task-title {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-sprint-task-more {
    color: var(--vk-muted);
}

.vk-sprint-health {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}

.vk-sprint-health a {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .68rem;
    letter-spacing: .04em;
    line-height: 1;
    padding: 3px 5px;
    text-decoration: none;
    text-transform: uppercase;
}

.vk-content .vk-sprint-health a {
    text-decoration: none;
}

.vk-sprint-health a:hover,
.vk-sprint-health a:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 32%, var(--vk-border));
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-sprint-health .is-ready {
    background: color-mix(in srgb, var(--pw-alert-success) 10%, var(--vk-surface));
    border-color: color-mix(in srgb, var(--pw-alert-success) 30%, var(--vk-border));
    color: var(--pw-alert-success);
}

.vk-sprint-health .is-warning {
    background: color-mix(in srgb, var(--pw-alert-warning) 12%, var(--vk-surface));
    border-color: color-mix(in srgb, var(--pw-alert-warning) 32%, var(--vk-border));
    color: #7a5b00;
}

.vk-sprint-health .is-danger {
    background: color-mix(in srgb, var(--pw-error-inline-text-color) 10%, var(--vk-surface));
    border-color: color-mix(in srgb, var(--pw-error-inline-text-color) 30%, var(--vk-border));
    color: var(--pw-error-inline-text-color);
}

.vk-sprint-metrics {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: grid;
    gap: 6px;
    padding: 8px 9px;
}

.vk-sprint-metric-line {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    font-size: .78rem;
    gap: 10px;
    justify-content: space-between;
}

.vk-sprint-capacity-line {
    align-items: center;
    color: var(--vk-muted);
    display: flex;
    font-size: .72rem;
    gap: 10px;
    justify-content: space-between;
}

.vk-sprint-capacity-track {
    background: var(--vk-muted-surface);
    border-radius: 999px;
    height: 5px;
    overflow: hidden;
}

.vk-sprint-capacity-track span {
    background: color-mix(in srgb, var(--vk-accent) 65%, var(--pw-alert-success));
    border-radius: inherit;
    display: block;
    height: 100%;
    min-width: 5px;
    width: var(--vk-progress, 0%);
}

.vk-sprint-capacity-track.is-over span {
    background: var(--pw-error-inline-text-color);
}

.vk-sprint-kpis {
    align-items: center;
    display: flex;
    gap: 9px;
    justify-content: flex-end;
}

.vk-kpi {
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    font-size: .72rem;
    padding: 4px 7px;
    white-space: nowrap;
}

.vk-kpi.is-over {
    color: var(--pw-error-inline-text-color);
}

.vk-cal-toolbar,
.vk-cal-legend {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.vk-cal-toolbar {
    margin-bottom: 14px;
}

.vk-calendar-planner {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin: 0 0 14px;
    max-width: 1180px;
}

.vk-calendar-plan-card {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius);
    box-shadow: var(--vk-shadow);
    color: var(--vk-text);
    display: grid;
    gap: 2px;
    min-height: 74px;
    padding: 10px 12px;
    text-decoration: none;
}

.vk-calendar-plan-card:hover,
.vk-calendar-plan-card:focus {
    border-color: color-mix(in srgb, var(--vk-accent) 36%, var(--vk-border));
    color: var(--vk-text);
    text-decoration: none;
}

.vk-calendar-plan-card span {
    color: var(--vk-muted);
    font-size: .72rem;
    letter-spacing: .06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vk-calendar-plan-card strong {
    color: var(--vk-text);
    font-size: 1.35rem;
    line-height: 1.05;
}

.vk-calendar-plan-card small {
    color: var(--vk-muted);
    font-size: .72rem;
}

.vk-calendar-plan-card.is-danger strong {
    color: var(--pw-error-inline-text-color);
}

.vk-calendar-plan-card.is-muted {
    background: var(--vk-muted-surface);
}

.vk-cal-summary {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 14px;
    max-width: 1180px;
}

.vk-cal-summary > div {
    background: var(--vk-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    padding: 9px 11px;
}

.vk-cal-summary span {
    color: var(--vk-muted);
    display: block;
    font-size: .68rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.vk-cal-summary strong {
    color: var(--vk-text);
    display: block;
    font-size: 1.12rem;
    line-height: 1.2;
    margin-top: 2px;
}

.vk-calendar-notice {
    align-items: flex-start;
    background: color-mix(in srgb, var(--vk-accent) 7%, var(--vk-surface));
    border: 1px solid var(--vk-border);
    border-left: 3px solid var(--vk-accent);
    border-radius: var(--vk-radius-sm);
    color: var(--vk-muted);
    display: flex;
    gap: 12px;
    margin: 18px 0 14px;
    max-width: 1180px;
    padding: 12px 14px;
}

.vk-sprint-context-notice {
    align-items: center;
    gap: 10px;
    margin: 8px 0 8px;
    padding: 9px 12px;
}

.vk-calendar-notice .fa {
    color: var(--vk-accent);
    margin-top: 2px;
}

.vk-calendar-notice-title {
    color: var(--vk-text);
    font-size: .9rem;
}

.vk-sprint-context-notice .vk-calendar-notice-title {
    display: block;
    margin-right: 0;
}

.vk-calendar-notice p {
    margin: 2px 0 0;
}

.vk-sprint-context-notice p {
    display: none;
}

.vk-sprint-context-notice a {
    color: var(--vk-accent);
    display: inline-flex;
    gap: 5px;
    margin-left: 6px;
    text-decoration: none;
}

.vk-sprint-context-notice a:hover,
.vk-sprint-context-notice a:focus {
    color: var(--vk-accent-strong);
    text-decoration: none;
}

.vk-sprint-notice-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 6px;
}

.vk-sprint-context-notice .vk-sprint-notice-actions a {
    align-items: center;
    background: var(--vk-surface);
    border: 1px solid color-mix(in srgb, var(--vk-accent) 24%, var(--vk-border));
    border-radius: var(--vk-radius-sm);
    color: var(--vk-text);
    display: inline-flex;
    font-size: .78rem;
    gap: 6px;
    line-height: 1;
    margin-left: 0;
    min-height: 30px;
    padding: 7px 9px;
    text-decoration: none;
}

.vk-sprint-context-notice .vk-sprint-notice-actions a:hover,
.vk-sprint-context-notice .vk-sprint-notice-actions a:focus {
    border-color: var(--vk-accent);
    color: var(--vk-accent);
    text-decoration: none;
}

.vk-cal-title {
    color: var(--vk-text);
    font-size: 1rem;
    min-width: 140px;
    text-align: center;
}

.vk-cal-mode {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: inline-flex;
    gap: 2px;
    padding: 3px;
}

.vk-cal-mode a {
    border-radius: calc(var(--vk-radius-sm) - 1px);
    color: var(--vk-muted);
    font-size: .72rem;
    min-height: 26px;
    padding: 5px 9px;
    text-transform: uppercase;
}

.vk-cal-mode a:hover {
    color: var(--vk-text);
}

.vk-cal-mode a.is-active {
    background: var(--vk-text);
    color: var(--vk-surface);
}

.vk-cal-jump {
    align-items: center;
    display: inline-flex;
    gap: 6px;
    margin-left: 8px;
}

.vk-cal-jump .uk-select {
    width: 84px;
}

.vk-cal-jump .uk-input {
    width: 82px;
}

.vk-cal-jump input[type="date"].uk-input {
    width: 145px;
}

.vk-cal-week td {
    height: 420px;
}

.vk-cal-quarter {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.vk-cal-quarter-month {
    min-width: 0;
}

.vk-cal-month-label {
    border-bottom: 1px solid var(--vk-border);
    color: var(--vk-text);
    font-size: .92rem;
    padding: 10px 12px;
}

.vk-cal-quarter .vk-cal th {
    font-size: .62rem;
    padding: .42rem .3rem;
}

.vk-cal-quarter .vk-cal td {
    height: 72px;
    min-width: 0;
    padding: .32rem;
}

.vk-cal-quarter .vk-cal-item {
    font-size: .64rem;
}

.vk-cal-legend {
    margin-top: 10px;
}

.vk-cal-key {
    align-items: center;
    color: var(--vk-muted);
    display: inline-flex;
    font-size: .78rem;
    gap: 6px;
}

.vk-cal-key::before {
    border-radius: 3px;
    content: "";
    height: 10px;
    width: 10px;
}

.vk-cal-key.is-publication::before {
    background: var(--pw-alert-success);
}

.vk-cal-key.is-task::before {
    background: var(--pw-alert-warning);
}

.vk-cal-agenda {
    margin-top: 18px;
    max-width: 1180px;
}

.vk-cal-agenda .uk-card-header {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

.vk-cal-agenda-tools {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}

.vk-agenda-filter {
    align-items: center;
    background: var(--vk-muted-surface);
    border: 1px solid var(--vk-border);
    border-radius: var(--vk-radius-sm);
    display: inline-flex;
    gap: 2px;
    padding: 3px;
}

.vk-agenda-filter button {
    background: transparent;
    border: 0;
    border-radius: calc(var(--vk-radius-sm) - 1px);
    color: var(--vk-muted);
    cursor: pointer;
    font-size: .72rem;
    line-height: 1;
    min-height: 26px;
    padding: 0 8px;
    text-transform: uppercase;
}

.vk-agenda-filter button:hover {
    color: var(--vk-text);
}

.vk-agenda-filter button.is-active {
    background: var(--vk-text);
    color: var(--vk-surface);
}

.vk-cal-agenda-list {
    display: grid;
}

.vk-cal-agenda-row {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    color: var(--vk-text);
    display: grid;
    gap: 12px;
    grid-template-columns: 58px minmax(0, 1fr) auto;
    min-height: 58px;
    padding: 10px 12px;
}

.vk-cal-agenda-row:last-child {
    border-bottom: 0;
}

.vk-cal-agenda-row:hover {
    background: var(--vk-muted-surface);
    color: var(--vk-text);
}

.vk-cal-agenda-date {
    color: var(--vk-muted);
    font-size: .78rem;
    white-space: nowrap;
}

.vk-cal-agenda-main {
    min-width: 0;
}

.vk-cal-agenda-title {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-cal-agenda-meta {
    color: var(--vk-muted);
    display: block;
    font-size: .74rem;
    margin-top: 2px;
}

.vk-agenda-filter-empty {
    min-height: 116px;
}

.vk-empty .fa {
    color: var(--vk-muted);
    font-size: 1.7rem;
    margin-bottom: 10px;
}

/* The decorative-icon sizing above must not bleed onto button icons
   inside the empty-state actions; keep those matching normal buttons. */
.vk-empty-actions .uk-button .fa {
    color: inherit;
    font-size: inherit;
    margin-bottom: 0;
}

.vk-empty-icon {
    color: var(--vk-muted);
    display: block;
    font-size: 1.85rem;
    margin-bottom: .5rem;
}

.vk-empty-icon.is-success {
    color: var(--pw-alert-success);
}

.vk-short-textarea {
    min-height: 70px !important;
}

.vk-scroll-list {
    max-height: 480px;
    overflow-y: auto;
}

.vk-page-pick-row {
    align-items: center;
    border-bottom: 1px solid var(--vk-border);
    display: grid;
    font-size: .85rem;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) minmax(160px, 240px);
    min-height: 42px;
    padding: 8px 16px;
}

.vk-page-pick-title,
.vk-page-pick-url {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vk-page-pick-title {
    color: var(--vk-text);
}

.vk-page-pick-url {
    color: var(--vk-muted);
    font-size: .78rem;
    text-align: right;
}

.vk-page-pick-row:last-child {
    border-bottom: 0;
}

.vk-page-pick-row:hover {
    background: color-mix(in srgb, var(--vk-accent) 5%, var(--vk-muted-surface));
}

@keyframes vkSkeleton {
    0% { background-position: 220% 0; }
    100% { background-position: -220% 0; }
}

@media (max-width: 1180px) {
    .vk-sprint-summary-grid {
        grid-template-columns: repeat(7, minmax(0, 1fr));
        margin-bottom: 8px;
    }

    .vk-sprint-summary-card {
        align-items: center;
        gap: 1px;
        min-height: 42px;
        padding: 5px 8px;
    }

    .vk-sprint-summary-card strong {
        font-size: 1rem;
    }

    .vk-sprint-summary-card span {
        font-size: .66rem;
    }

    .vk-sprint-summary-card small {
        display: none;
    }

    .vk-sprint-toolbar {
        margin-bottom: 8px;
    }

    .vk-sprint-filter-panel,
    .vk-task-filters.vk-filter-panel.vk-sprint-filter-panel {
        gap: 8px;
        grid-template-columns: minmax(420px, 1.15fr) minmax(350px, 1fr) 70px;
        margin-bottom: 8px;
        padding: 8px;
    }

    .vk-sprint-filter-panel .vk-task-filter-label,
    .vk-sprint-filter-panel .vk-task-filter-group.is-tabs .vk-task-filter-label {
        display: none;
    }

    .vk-sprint-filter-panel .vk-task-filter-group.is-tabs {
        gap: 0;
        grid-template-columns: minmax(0, 1fr);
    }

    .vk-quarter-overview {
        display: none;
    }

    .vk-sprint-board-row {
        gap: 9px 12px;
        grid-template-columns: minmax(0, 1fr) minmax(210px, .38fr);
        padding: 14px 16px;
    }

    .vk-sprint-health {
        margin-top: 5px;
    }

    .vk-sprint-task-preview {
        margin-top: 7px;
    }

    .vk-sprint-metrics {
        gap: 5px;
        min-height: 0;
        padding: 8px 10px;
    }

    .vk-sprint-metric-line,
    .vk-sprint-capacity-line {
        font-size: .7rem;
    }

    .vk-sprint-kpis {
        gap: 5px;
    }

    .vk-kpi {
        font-size: .68rem;
        padding: 3px 5px;
    }

    .vk-sprint-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: space-between;
    }

    .vk-sprint-action-group {
        justify-content: flex-start;
    }

    .vk-sprint-action-group.is-plan {
        padding: 5px;
    }
}

@media (max-width: 959px) {
    .vk-issue-body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 780px) {
    .vk-shell {
        padding-left: .25rem;
        padding-right: .25rem;
    }

    .vk-dashboard-head,
    .vk-page-head {
        flex-direction: column;
        gap: 10px;
    }

    .vk-dashboard-head > .vk-actions,
    .vk-page-head > .vk-actions {
        justify-content: flex-start;
        margin-left: 0;
        max-width: none;
        width: 100%;
    }

    .vk-admin-nav {
        align-items: flex-start;
        flex-direction: column;
    }

    .vk-admin-nav .uk-subnav {
        display: flex;
        flex-wrap: wrap;
        max-width: 100%;
        overflow-x: visible;
        padding-bottom: 1px;
        row-gap: 8px;
    }

    .vk-admin-nav .uk-subnav > * {
        flex: 0 1 auto;
    }

    .vk-admin-nav .uk-subnav-pill > * > :first-child {
        padding: 7px 9px;
        white-space: normal;
    }

    .vk-shell .uk-card {
        overflow-x: auto;
    }

    .vk-shell .uk-table {
        min-width: 680px;
    }

    .vk-bulk-workspace {
        grid-template-columns: 1fr;
        max-width: none;
    }

    .vk-page-pick-row {
        align-items: start;
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .vk-page-pick-url {
        text-align: left;
    }

    .vk-stat {
        min-width: 112px;
    }

    .vk-cal {
        min-width: 720px;
    }

    .vk-cal-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-quarter-overview-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-task-filters.vk-filter-panel {
        grid-template-columns: 1fr;
    }

    .vk-task-filters.vk-filter-panel.vk-sprint-filter-panel {
        grid-template-columns: minmax(0, 1fr);
    }

    .vk-task-filters.vk-filter-panel .vk-task-filter-group,
    .vk-task-filters.vk-filter-panel .vk-filter-select-form,
    .vk-task-filters.vk-filter-panel .vk-task-search-form,
    .vk-task-filters.vk-filter-panel .vk-task-search-group,
    .vk-task-filters.vk-filter-panel .vk-task-filter-tabs,
    .vk-task-filters.vk-filter-panel .vk-task-search-control {
        max-width: none;
        width: 100%;
    }

    .vk-task-filters.vk-filter-panel .vk-filter-select-form .uk-select {
        min-width: 0;
        width: 100%;
    }

    .vk-sprint-board-card .uk-card-body {
        padding: 10px;
    }

    .vk-sprint-board-row {
        grid-template-columns: minmax(0, 1fr);
    }

    .vk-sprint-goal-preview {
        -webkit-line-clamp: 1;
        max-height: 22px;
    }

    .vk-sprint-task-preview {
        max-height: 28px;
    }

    .vk-sprint-task-chip,
    .vk-sprint-task-more {
        max-width: 220px;
    }

    .vk-sprint-metrics {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .vk-sprint-metrics .uk-progress,
    .vk-sprint-capacity-track,
    .vk-sprint-kpis {
        grid-column: 1 / -1;
    }

    .vk-task-search-control {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .vk-task-search-control .vk-filter-reset,
    .vk-task-search-control .uk-button {
        grid-column: 2;
        justify-self: start;
    }

    .vk-cal-quarter {
        grid-template-columns: 1fr;
    }

    .vk-dashboard-focus,
    .vk-dashboard-focus-main {
        grid-template-columns: 1fr;
    }

    .vk-dashboard-focus-progress span {
        text-align: left;
    }

    .vk-dashboard-queue {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-dashboard-grid {
        grid-template-columns: 1fr;
    }

    .vk-dashboard-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-sprint-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-task-status-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-task-sprint-context {
        grid-template-columns: 1fr;
    }

    .vk-task-sprint-stats {
        justify-content: flex-start;
    }

    .vk-task-workspace {
        grid-template-columns: 1fr;
    }

    .vk-sprint-workspace {
        grid-template-columns: 1fr;
    }

    .vk-sprint-readiness-list {
        grid-template-columns: 1fr;
    }

    .vk-kb-summary {
        grid-template-columns: 1fr;
    }

    .vk-task-filter-divider {
        display: none;
    }

    .vk-settings-grid {
        grid-template-columns: 1fr;
    }

    .vk-settings-field-grid {
        grid-template-columns: 1fr;
    }

    .vk-settings-options,
    .vk-settings-options.is-compact {
        grid-template-columns: 1fr;
    }

    .vk-widget-config-layout {
        grid-template-columns: 1fr;
    }

    .vk-widget-preview {
        border-left: 0;
        border-top: 1px solid var(--vk-border);
        padding-left: 0;
        padding-top: 12px;
        position: static;
    }

    .vk-form-grid.is-2,
    .vk-form-grid.is-3 {
        grid-template-columns: 1fr;
    }

    .vk-workflow-section {
        display: block;
    }

    .vk-workflow-section .vk-form-grid {
        display: grid;
    }

    .vk-document-header {
        flex-direction: column;
    }

    .vk-note-fields,
    .vk-kb-document {
        grid-template-columns: 1fr;
    }

    .vk-task-layout.has-sidebar > .vk-sprint-sidebar {
        position: static;
    }

    .vk-kb-list-head {
        display: none;
    }

    .vk-note-fields #vk-new-cat-row {
        grid-column: auto;
    }

    .vk-kb-icon {
        display: none;
    }

    .vk-kb-actions {
        justify-content: flex-start;
    }

    .vk-kb-date {
        text-align: left;
    }

    .vk-kb-document {
        align-items: start;
        gap: 6px;
        padding: 12px;
    }

    .vk-issue-header {
        flex-direction: column;
    }

    .vk-issue-meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vk-issue-row,
    .vk-sprint-board-row,
    .vk-sprint-issue,
    .vk-mini-row,
    .vk-audit-page-row {
        grid-template-columns: 1fr;
    }

    .vk-issue-list-head {
        display: none;
    }

    .vk-issue-row-side,
    .vk-sprint-actions,
    .vk-sprint-action-group,
    .vk-sprint-issue-side,
    .vk-mini-side,
    .vk-audit-page-actions {
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .vk-audit-page-check {
        text-align: left;
    }

    .vk-cal {
        min-width: 0;
    }

    .vk-cal th {
        font-size: .58rem;
        padding: .42rem .18rem;
    }

    .vk-cal td {
        height: 82px;
        min-width: 0;
        padding: .3rem .24rem;
    }

    .vk-cal-week td {
        height: 260px;
    }

    .vk-cal-item {
        font-size: .62rem;
        padding: .12rem .22rem;
    }

    .vk-cal-day-n {
        font-size: .66rem;
        gap: 3px;
    }

    .vk-audit-rule-format code,
    .vk-audit-reference code {
        overflow-wrap: anywhere;
        white-space: normal;
        word-break: break-word;
    }
}

@media (max-width: 900px) {
    .vk-task-filters.vk-filter-panel,
    .vk-sprint-filter-panel,
    .vk-task-filters.vk-filter-panel.vk-sprint-filter-panel {
        grid-template-columns: minmax(0, 1fr);
    }

    .vk-task-filters.vk-filter-panel .vk-task-filter-group,
    .vk-task-filters.vk-filter-panel .vk-task-search-group,
    .vk-task-filters.vk-filter-panel .vk-task-search-form,
    .vk-task-filters.vk-filter-panel .vk-task-search-control,
    .vk-task-filters.vk-filter-panel .vk-filter-select-form,
    .vk-task-filters.vk-filter-panel .vk-task-filter-tabs {
        min-width: 0;
        width: 100%;
    }
}
</style>

<div class="vk-shell">
<?= $this->nav() ?>
<div class="vk-content"><?= $content ?></div>
</div>
