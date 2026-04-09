# CSS Class Reference — The `cls_*` Pattern

This is the canonical reference for all `cls_*` CSS class variables in Pubvana CMS. Themes, widgets, and plugins all use this shared vocabulary. See [ThemeBuilder.md](ThemeBuilder.md), [WidgetBuilder.md](WidgetBuilder.md), and [PluginBuilder.md](PluginBuilder.md) for how each uses these variables.

---

Widgets, plugins, and pagination output HTML with semantic CSS class defaults. Themes override these by declaring a `css_class_mapping` object in `theme_info.json`. These values are injected automatically at render time.

### How It Works

Every CSS class in a widget `.tpl` is a variable with a `default()` fallback:

```
<div class="{{ cls_widget | default('widget widget-categories') }}">
```

If the theme provides `cls_widget` via `css_class_mapping`, that value is used. If not, the semantic default applies.

### Declaring `css_class_mapping` in theme_info.json

```json
{
    "name": "My Theme",
    "css_class_mapping": {
        "cls_list": "list-group list-group-flush",
        "cls_list_item": "list-group-item d-flex justify-content-between align-items-center",
        "cls_button": "btn btn-sm btn-primary",
        "cls_tag": "badge bg-light text-dark text-decoration-none"
    }
}
```

Only override the classes you need. Any `cls_` variable not in `css_class_mapping` falls back to its semantic default, which your theme's `theme.css` should style.

### Two Layers

1. **`css_class_mapping` in JSON** — injects your framework's utility classes (Bootstrap, DaisyUI, Tailwind, etc.) directly into widget markup
2. **`theme.css`** — styles the semantic defaults (`.widget-list`, `.widget-title`, etc.) as a base layer

Both work together. `css_class_mapping` values take priority over semantic defaults.

### Naming Convention

All class variables use the `cls_` prefix. There are three tiers:

| Prefix | Scope | Example |
|--------|-------|---------|
| `cls_*` | Standard — available to all widgets, plugins, and theme templates | `cls_card`, `cls_table`, `cls_mb_3` |
| `cls_{plugin}_*` | Plugin-specific — namespaced to a single plugin | `cls_dstore_cart_item` |
| `cls_{widget}_*` | Widget-specific — namespaced to a single widget | `cls_toc_nav` |

Standard `cls_*` names are framework-agnostic. The *values* map to the theme's chosen CSS framework. Variable names use `left`/`right` (not `start`/`end`).

### Complete `cls_*` Standard Reference

#### Widget Shell

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_widget` | Widget outer wrapper | `widget mb-4` | `mb-6` |
| `cls_title` | Widget heading | `h6 fw-bold mb-3` | `text-lg font-bold mb-3` |
| `cls_content` | Free-form content area | `mb-2` | `mb-2` |
| `cls_empty` | Empty state message | `text-muted fst-italic` | `text-gray-400 italic` |
| `cls_meta` | Metadata text | `text-muted small` | `text-gray-500 text-sm` |
| `cls_link` | Standard link | `text-decoration-none` | `no-underline` |

#### Layout

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_container` | Main content wrapper | `container` | `max-w-7xl mx-auto px-4` |
| `cls_row` | Grid row | `row` | `flex flex-wrap` |
| `cls_col` | Generic column | `col` | `flex-1` |
| `cls_col_half` | 50% column | `col-md-6` | `w-1/2` |
| `cls_col_third` | 33% column | `col-md-4` | `w-1/3` |
| `cls_col_quarter` | 25% column | `col-md-3` | `w-1/4` |

#### Typography

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_h1` | Heading 1 | `h1` | `text-4xl font-bold` |
| `cls_h2` | Heading 2 | `h2` | `text-3xl font-bold` |
| `cls_h3` | Heading 3 | `h3` | `text-2xl font-bold` |
| `cls_h4` | Heading 4 | `h4` | `text-xl font-bold` |
| `cls_h5` | Heading 5 | `h5` | `text-lg font-bold` |
| `cls_h6` | Heading 6 | `h6` | `text-base font-bold` |
| `cls_lead` | Intro/lead text | `lead` | `text-xl text-gray-600` |
| `cls_blockquote` | Block quote | `blockquote` | `border-l-4 pl-4 italic` |

#### Text Color (Semantic)

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_text_muted` | De-emphasized | `text-muted` | `text-gray-500` |
| `cls_text_primary` | Brand/primary | `text-primary` | `text-blue-600` |
| `cls_text_secondary` | Secondary | `text-secondary` | `text-gray-600` |
| `cls_text_success` | Success/positive | `text-success` | `text-green-600` |
| `cls_text_danger` | Error/destructive | `text-danger` | `text-red-600` |
| `cls_text_warning` | Caution | `text-warning` | `text-yellow-600` |
| `cls_text_info` | Informational | `text-info` | `text-cyan-600` |
| `cls_text_light` | Light on dark bg | `text-light` | `text-gray-100` |

#### Text Alignment

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_text_left` | Left-aligned | `text-start` | `text-left` |
| `cls_text_center` | Centered | `text-center` | `text-center` |
| `cls_text_right` | Right-aligned | `text-end` | `text-right` |

#### Background (Semantic)

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_bg_primary` | Primary background | `bg-primary text-white` | `bg-blue-600 text-white` |
| `cls_bg_secondary` | Secondary background | `bg-secondary text-white` | `bg-gray-600 text-white` |
| `cls_bg_success` | Success background | `bg-success text-white` | `bg-green-600 text-white` |
| `cls_bg_danger` | Danger background | `bg-danger text-white` | `bg-red-600 text-white` |
| `cls_bg_warning` | Warning background | `bg-warning` | `bg-yellow-500` |
| `cls_bg_light` | Light surface | `bg-light` | `bg-gray-100` |
| `cls_bg_dark` | Dark surface | `bg-dark text-white` | `bg-gray-900 text-white` |

#### Cards

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_card` | Card wrapper | `card` | `rounded-lg shadow p-4` |
| `cls_card_header` | Card header | `card-header` | `px-4 py-3 border-b` |
| `cls_card_body` | Card body | `card-body` | `p-4` |
| `cls_card_footer` | Card footer | `card-footer` | `px-4 py-3 border-t` |
| `cls_card_image` | Card image | `card-img-top` | `rounded-t-lg w-full` |
| `cls_card_title` | Card title | `card-title fw-bold` | `font-bold text-lg` |
| `cls_card_text` | Card body text | `text-muted small` | `text-gray-500 text-sm` |

#### Lists

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_list` | Styled list | `list-group list-group-flush` | `divide-y` |
| `cls_list_item` | List item | `list-group-item` | `py-2` |
| `cls_list_plain` | Unstyled list | `list-unstyled` | `list-none` |
| `cls_list_inline` | Horizontal list | `list-inline` | `flex space-x-4` |

#### Tables

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_table` | Base table | `table` | `min-w-full divide-y` |
| `cls_table_striped` | Alternating rows | `table-striped` | `even:bg-gray-50` |
| `cls_table_hover` | Hover highlight | `table-hover` | `hover:bg-gray-50` |
| `cls_table_head` | Table header row | `table-light` | `bg-gray-50` |
| `cls_table_cell` | `<td>` styling | _(none)_ | `px-4 py-2` |
| `cls_table_header_cell` | `<th>` styling | _(none)_ | `px-4 py-3 font-semibold` |

#### Forms

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_form` | Form wrapper | `d-flex gap-2` | `flex gap-2` |
| `cls_form_group` | Field + label wrapper | `mb-3` | `mb-4` |
| `cls_label` | Form label | `form-label` | `block text-sm font-medium` |
| `cls_input` | Text input | `form-control` | `border rounded px-3 py-2 w-full` |
| `cls_select` | Select dropdown | `form-select` | `border rounded px-3 py-2` |
| `cls_textarea` | Textarea | `form-control` | `border rounded px-3 py-2 w-full` |
| `cls_checkbox` | Checkbox input | `form-check-input` | `rounded border-gray-300` |
| `cls_form_help` | Help/hint text | `form-text text-muted` | `text-sm text-gray-500 mt-1` |

#### Buttons

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_button` | Generic button (widget context) | `btn btn-sm btn-primary` | `px-3 py-1 text-sm rounded bg-blue-600 text-white` |
| `cls_btn_primary` | Primary action | `btn btn-primary` | `px-4 py-2 rounded bg-blue-600 text-white` |
| `cls_btn_secondary` | Secondary action | `btn btn-outline-primary` | `px-4 py-2 rounded border border-blue-600 text-blue-600` |
| `cls_btn_success` | Positive/confirm | `btn btn-success` | `px-4 py-2 rounded bg-green-600 text-white` |
| `cls_btn_danger` | Destructive/delete | `btn btn-danger` | `px-4 py-2 rounded bg-red-600 text-white` |
| `cls_btn_warning` | Caution action | `btn btn-warning` | `px-4 py-2 rounded bg-yellow-500` |
| `cls_btn_outline` | Outlined/ghost variant | `btn btn-outline-secondary` | `px-4 py-2 rounded border` |
| `cls_btn_sm` | Small button | `btn btn-sm` | `px-2 py-1 text-sm rounded` |
| `cls_btn_lg` | Large button | `btn btn-lg` | `px-6 py-3 text-lg rounded` |
| `cls_btn_block` | Full-width button | `btn w-100` | `w-full` |

#### Alerts

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_alert` | Base alert wrapper | `alert` | `p-4 rounded` |
| `cls_alert_success` | Success notice | `alert-success` | `bg-green-100 text-green-800` |
| `cls_alert_danger` | Error notice | `alert-danger` | `bg-red-100 text-red-800` |
| `cls_alert_warning` | Warning notice | `alert-warning` | `bg-yellow-100 text-yellow-800` |
| `cls_alert_info` | Info notice | `alert-info` | `bg-cyan-100 text-cyan-800` |

#### Navigation

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_nav` | Nav wrapper | `nav` | `flex space-x-4` |
| `cls_nav_item` | Nav entry | `nav-item` | _(none)_ |
| `cls_nav_link` | Nav anchor | `nav-link` | `px-3 py-2` |
| `cls_nav_active` | Active nav item | `active` | `font-bold border-b-2` |
| `cls_dropdown` | Dropdown menu | `dropdown-menu` | `absolute bg-white shadow rounded` |
| `cls_dropdown_item` | Dropdown entry | `dropdown-item` | `block px-4 py-2` |

#### Breadcrumbs

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_breadcrumb` | Breadcrumb wrapper | `breadcrumb` | `flex space-x-2 text-sm` |
| `cls_breadcrumb_item` | Breadcrumb entry | `breadcrumb-item` | _(none)_ |
| `cls_breadcrumb_active` | Current page | `active` | `text-gray-500` |

#### Badges & Tags

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_badge` | Count/label badge | `badge bg-primary rounded-pill` | `px-2 py-1 text-xs rounded-full bg-blue-600 text-white` |
| `cls_tags` | Tag cloud container | `d-flex flex-wrap gap-1` | `flex flex-wrap gap-1` |
| `cls_tag` | Individual tag link | `badge bg-light text-decoration-none` | `px-2 py-1 text-xs bg-gray-100 rounded no-underline` |

#### Images

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_thumbnail` | Thumbnail image | `rounded float-start me-3` | `rounded float-left mr-3` |
| `cls_img_fluid` | Responsive image | `img-fluid` | `max-w-full h-auto` |
| `cls_img_rounded` | Rounded corners | `rounded` | `rounded-md` |
| `cls_img_circle` | Circular crop | `rounded-circle` | `rounded-full` |

#### Social

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_social_links` | Social links container | `d-flex gap-3` | `flex space-x-4` |
| `cls_social_link` | Individual social link | `text-muted text-decoration-none fs-5` | `text-gray-500 no-underline text-xl` |

#### Table of Contents

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_toc_nav` | TOC nav wrapper | `small` | `text-sm` |
| `cls_toc_list` | TOC list | `list-unstyled` | `list-none` |
| `cls_toc_item` | TOC list item | `py-1` | `py-1` |
| `cls_toc_link` | TOC link | `text-decoration-none` | `no-underline` |

#### Paywall

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_fade` | Fade overlay | `position-relative` | `relative` |
| `cls_cta` | CTA container | `text-center p-4 bg-light rounded` | `text-center p-6 bg-gray-100 rounded-lg` |
| `cls_icon` | Lock icon | `fs-1 text-muted mb-3` | `text-4xl text-gray-400 mb-4` |
| `cls_paywall_title` | Paywall heading | `h5 fw-bold` | `text-xl font-bold` |
| `cls_message` | Paywall message | `text-muted mb-3` | `text-gray-500 mb-4` |

#### Pagination

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_pager_list` | Pagination `<ul>` | `pagination justify-content-center` | `flex justify-center space-x-1` |
| `cls_pager_item` | Each `<li>` | `page-item` | _(none)_ |
| `cls_pager_link` | `<a>`/`<span>` inside | `page-link` | `px-3 py-1 rounded` |
| `cls_pager_active` | Active page | `active` | `bg-blue-600 text-white` |
| `cls_pager_disabled` | Disabled page | `disabled` | `opacity-50 cursor-not-allowed` |

Pagination HTML is pre-rendered by `ThemeService::getPaginationClasses()`. Defaults are framework-agnostic `pv-*` classes. Override in `css_class_mapping` to map to your framework.

#### Display & Flex

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_hidden` | Hide element | `d-none` | `hidden` |
| `cls_flex` | Flex container | `d-flex` | `flex` |
| `cls_flex_center` | Centered both axes | `d-flex align-items-center justify-content-center` | `flex items-center justify-center` |
| `cls_flex_between` | Space-between | `d-flex justify-content-between` | `flex justify-between` |
| `cls_flex_col` | Vertical flex | `d-flex flex-column` | `flex flex-col` |
| `cls_flex_wrap` | Wrapping flex | `d-flex flex-wrap` | `flex flex-wrap` |

#### Borders & Shape

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_rounded` | Standard rounding | `rounded` | `rounded-md` |
| `cls_rounded_lg` | Large rounding | `rounded-3` | `rounded-lg` |
| `cls_rounded_circle` | Circle/pill | `rounded-circle` | `rounded-full` |
| `cls_shadow` | Standard shadow | `shadow` | `shadow-md` |
| `cls_shadow_sm` | Subtle shadow | `shadow-sm` | `shadow-sm` |

#### Float & Alignment

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_left` | Float/align left | `float-start` | `float-left` |
| `cls_right` | Float/align right | `float-end` | `float-right` |

#### Miscellaneous

| Variable | Purpose | BS5 Example | Tailwind Example |
|----------|---------|-------------|------------------|
| `cls_divider` | Horizontal separator | `border-bottom my-3` | `border-b my-4` |
| `cls_sr_only` | Screen reader only | `visually-hidden` | `sr-only` |

#### Spacing — Margin

Scale 0–5. Variable names use `left`/`right`, not `start`/`end`. Bootstrap 5 maps `ml`→`ms`, `mr`→`me` internally.

| Pattern | Variables | BS5 Example | Tailwind Example |
|---------|-----------|-------------|------------------|
| `cls_m_{0-5}` | All-side margin | `m-0` … `m-5` | `m-0` … `m-10` |
| `cls_mt_{0-5}` | Margin top | `mt-0` … `mt-5` | `mt-0` … `mt-10` |
| `cls_mb_{0-5}` | Margin bottom | `mb-0` … `mb-5` | `mb-0` … `mb-10` |
| `cls_ml_{0-5}` | Margin left | `ms-0` … `ms-5` | `ml-0` … `ml-10` |
| `cls_mr_{0-5}` | Margin right | `me-0` … `me-5` | `mr-0` … `mr-10` |
| `cls_mx_{0-5}` | Margin horizontal | `mx-0` … `mx-5` | `mx-0` … `mx-10` |
| `cls_mx_auto` | Center horizontally | `mx-auto` | `mx-auto` |
| `cls_my_{0-5}` | Margin vertical | `my-0` … `my-5` | `my-0` … `my-10` |

#### Spacing — Padding

| Pattern | Variables | BS5 Example | Tailwind Example |
|---------|-----------|-------------|------------------|
| `cls_p_{0-5}` | All-side padding | `p-0` … `p-5` | `p-0` … `p-10` |
| `cls_pt_{0-5}` | Padding top | `pt-0` … `pt-5` | `pt-0` … `pt-10` |
| `cls_pb_{0-5}` | Padding bottom | `pb-0` … `pb-5` | `pb-0` … `pb-10` |
| `cls_pl_{0-5}` | Padding left | `ps-0` … `ps-5` | `pl-0` … `pl-10` |
| `cls_pr_{0-5}` | Padding right | `pe-0` … `pe-5` | `pr-0` … `pr-10` |
| `cls_px_{0-5}` | Padding horizontal | `px-0` … `px-5` | `px-0` … `px-10` |
| `cls_py_{0-5}` | Padding vertical | `py-0` … `py-5` | `py-0` … `py-10` |
