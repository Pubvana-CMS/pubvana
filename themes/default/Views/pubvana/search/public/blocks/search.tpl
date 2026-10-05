{# Theme override for the Search block (pubvana.search.form).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/search/public/blocks/search.tpl. #}
<div class="pv-block pv-block-search">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    <form method="get" action="/search" class="d-flex gap-2" role="search">
        <input type="search" name="q" class="form-control" value="{{ query }}" placeholder="Search..." aria-label="Search">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>
