{% if switcher %}
<div class="{{ cls_widget | default('widget widget-language-picker') }}">
    <h4 class="{{ cls_title | default('widget-title') }}">{% lang 'Blog.language' %}</h4>

    {% if style == 'dropdown' and switcher.dropdown %}
    <form class="{{ cls_form | default('widget-form') }}">
        <select class="{{ cls_input | default('widget-form-input') }}"
                onchange="window.location.href=this.value">
            {% for lang in switcher.dropdown %}
                <option value="{% base_url lang.url %}"
                        {% if lang.active %}selected{% endif %}>
                    {{ lang.native_name }}
                </option>
            {% endfor %}
        </select>
    </form>
    {% endif %}

    {% if style == 'ul' and switcher.ul %}
    <nav>
        <ul class="{{ cls_list | default('widget-list') }}">
            {% for lang in switcher.ul %}
            <li class="{{ cls_list_item | default('widget-list-item') }}">
                <a href="{% base_url lang.url %}"
                   class="{{ cls_link | default('widget-list-link') }}"
                   {% if lang.active %}aria-current="true"{% endif %}>
                    {{ lang.native_name }}
                </a>
            </li>
            {% endfor %}
        </ul>
    </nav>
    {% endif %}

    {% if style == 'buttons' and switcher.buttons %}
    <div class="{{ cls_content | default('widget-content') }}">
        {% for lang in switcher.buttons %}
            <a href="{% base_url lang.url %}"
               class="{{ cls_button | default('widget-form-button') }}"
               {% if lang.active %}aria-current="true"{% endif %}>
                {{ lang.native_name }}
            </a>
        {% endfor %}
    </div>
    {% endif %}
</div>
{% endif %}
