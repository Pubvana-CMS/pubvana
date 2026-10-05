{# Theme override for the Social Links block (pubvana.social-links).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/social-links/public/blocks/social-links.tpl. #}
<div class="pv-block pv-block-social-links">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {% if links %}
    <ul class="list-inline pv-social-links mb-0">
        {% for link in links %}
        <li class="list-inline-item">
            <a href="{{ link.url }}" target="_blank" rel="noopener" title="{{ link.label }}" aria-label="{{ link.label }}">
                <i class="{{ link.icon }}"></i>
            </a>
        </li>
        {% endfor %}
    </ul>
    {% endif %}
</div>
