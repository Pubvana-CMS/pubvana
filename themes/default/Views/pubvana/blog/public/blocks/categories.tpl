{# Content block template override: Categories (pubvana/blog/public/blocks/categories). #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The categories variable comes from the Blog plugin's block provider. #}
<div class="card mb-4">
    {# Conditional: the block title comes from the placement options in the admin. #}
    {% if title %}
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ title }}</h3>
    </div>
    {% endif %}
    <div class="list-group list-group-flush">
        {# Conditional + loop: one link per category. #}
        {% if categories %}
        {% for category in categories %}
        <a href="{{ category.url }}" class="list-group-item list-group-item-action">{{ category.name }}</a>
        {% endfor %}
        {% else %}
        {# Else branch: shown while the site has no categories. #}
        <div class="list-group-item text-secondary">No categories yet.</div>
        {% endif %}
    </div>
</div>