<div class="{{ cls_widget | default('widget widget-search') }}">
    <form action="{% site_url 'search' %}" method="GET" class="{{ cls_form | default('widget-form') }}">
        <input type="search" name="q"
               class="{{ cls_input | default('widget-form-input') }}"
               placeholder="{{ placeholder | default('Search…') }}"
               aria-label="{% lang 'Blog.search' %}">
        <button type="submit" class="{{ cls_button | default('widget-form-button') }}">{% lang 'Blog.search' %}</button>
    </form>
</div>
