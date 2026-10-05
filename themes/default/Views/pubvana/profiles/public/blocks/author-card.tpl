{# Theme override for the Profiles Author Card block (pubvana.profiles.author-card).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/profiles/public/blocks/author-card.tpl. #}
<div class="pv-block pv-block-author-card">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {% if author %}
    <div class="card">
        <div class="card-body d-flex align-items-center gap-3">
            {% if author.avatar %}
            <img src="{{ author.avatar }}" alt="{{ author.name }}" class="rounded-circle" width="64" height="64">
            {% endif %}
            <div>
                <div class="fw-bold">{{ author.name }}</div>
                {% if author.bio %}<div class="text-muted small">{{ author.bio }}</div>{% endif %}
                {% if author.url %}<a class="small" href="{{ author.url }}">View profile</a>{% endif %}
            </div>
        </div>
    </div>
    {% endif %}
</div>
