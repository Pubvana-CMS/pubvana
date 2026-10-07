{# Theme override for the Search block (pubvana.search.form).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/search/public/blocks/search.tpl.
   Reads the block's options: action, label, placeholder, button_text.
   The input carries no id, so two placements on one page cannot collide;
   the label doubles as its accessible name. #}
<div class="pv-block pv-block-search">
    {% if label %}<h3 class="pv-block-title">{{ label }}</h3>{% endif %}
    <form method="get" action="{{ action }}" class="d-flex gap-2" role="search">
        <input type="search" name="q" class="form-control" placeholder="{{ placeholder }}" aria-label="{{ label }}">
        <button type="submit" class="btn btn-primary">{{ button_text }}</button>
    </form>
</div>
