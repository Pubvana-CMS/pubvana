{# Content block template override: Text (pubvana.core-blocks.text). #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The data comes from the block's placement options entered in the admin. #}
<div class="card mb-3">
    {# Conditional: the header renders only when the block has a title. #}
    {% if title %}
    <div class="card-header">
        {# Escaped output: the block title. #}
        <h3 class="card-title h5 mb-0">{{ title }}</h3>
    </div>
    {% endif %}
    <div class="card-body">
        {# Escaped output: the block body is plain text, not HTML. #}
        {% if content %}
        <div class="block-content">{{ content }}</div>
        {% endif %}
    </div>
</div>