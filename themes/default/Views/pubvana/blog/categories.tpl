{# Category index: all categories with post counts. Content-only; the layout owns the sidebar. #}
<h1 class="mb-4">Categories</h1>

{# Conditional + loop: one list row per category. #}
{% if categories %}
<div class="list-group">
    {% for cat in categories %}
    <a href="{{ cat.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        {# Escaped output: category name. #}
        {{ cat.name }}
        {# Conditional: post count badge, shown only when the category has posts. #}
        {% if cat.post_count %}
        <span class="badge bg-primary rounded-pill">{{ cat.post_count }}</span>
        {% endif %}
    </a>
    {% endfor %}
</div>
{% else %}
{# Else branch: no categories exist yet. #}
<p>No categories found.</p>
{% endif %}

{# Region: content blocks placed below the category list, inside the content column. #}
{% region 'after-content' %}
