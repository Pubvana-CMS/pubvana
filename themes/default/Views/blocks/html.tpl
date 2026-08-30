{# HTML block template (custom content) #}
<div class="card mb-3">
    {% if title %}
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ title }}</h3>
    </div>
    {% endif %}
    <div class="card-body">
        {! content !}
    </div>
</div>
