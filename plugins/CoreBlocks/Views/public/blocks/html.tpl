<div class="block">
    <div class="block-html">
        {% if title %}
        <h6 class="block-title">{{ title }}</h6>
        {% endif %}
        {# Raw output: the block body is admin-authored HTML. #}
        {! content !}
    </div>
</div>