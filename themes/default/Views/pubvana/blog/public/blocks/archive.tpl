{# Theme override for the Blog Archive block (pubvana.blog.archive).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/blog/public/blocks/archive.tpl. #}
<div class="pv-block pv-block-archive">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {% if items %}
    <ul class="list-unstyled pv-archive-list">
        {% for item in items %}
        <li class="pv-archive-item">
            <a href="{{ item.url }}">{{ item.title }}</a>
            {% if item.date %}<span class="text-muted small ms-1">{{ item.date }}</span>{% endif %}
        </li>
        {% endfor %}
    </ul>
    {% else %}
    <p class="text-muted">Nothing to show yet.</p>
    {% endif %}
</div>
