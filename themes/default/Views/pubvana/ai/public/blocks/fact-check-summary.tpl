{# Theme override for the AI Assistant Fact Check Summary block (pubvana.ai.fact-check-summary).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/ai/public/blocks/fact-check-summary.tpl.
   Renders nothing where no report exists. #}
{% if report %}
<div class="pv-block pv-block-fact-check">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    <div class="card">
        <div class="card-body">
            {% if report.verdict %}<div class="fw-bold">{{ report.verdict }}</div>{% endif %}
            {% if report.summary %}<p class="mb-2">{{ report.summary }}</p>{% endif %}
            {% if report.findings %}
            <ul class="mb-2">
                {% for finding in report.findings %}
                <li>{{ finding }}</li>
                {% endfor %}
            </ul>
            {% endif %}
            {% if report.prompt_version %}
            <div class="text-muted small">Checked under Pubvana fact-check prompt v{{ report.prompt_version }}</div>
            {% endif %}
        </div>
    </div>
</div>
{% endif %}
