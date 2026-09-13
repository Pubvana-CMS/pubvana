{# Content block template override: Tags (pubvana/blog/public/blocks/tags). #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The tags variable comes from the Blog plugin's block provider. #}
<div class="card mb-4">
    {# Conditional: the block title comes from the placement options in the admin. #}
    {% if title %}
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ title }}</h3>
    </div>
    {% endif %}
    <div class="card-body">
        {# Conditional + loop: one badge per tag. #}
        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        {# Else branch: shown while the site has no tags. #}
        <p class="text-secondary mb-0">No tags yet.</p>
        {% endif %}
    </div>
</div>