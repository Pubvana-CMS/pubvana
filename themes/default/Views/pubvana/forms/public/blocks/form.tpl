{# Theme override for the Forms block (pubvana.forms.form).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/forms/public/blocks/form.tpl. #}
<div class="pv-block pv-block-form">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {! content !}
</div>
