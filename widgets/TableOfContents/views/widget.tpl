<div id="pubvana-toc" class="{{ cls_widget | default('widget widget-toc') }}" style="display:none;">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <nav id="pubvana-toc-nav" class="{{ cls_toc_nav | default('widget-toc-nav') }}"></nav>
</div>

<script>
(function () {
    var minHeadings = {{ min_headings | default(2) | raw }};
    var maxDepth = "{{ max_depth | default('h3') }}";
    var clsList = "{{ cls_toc_list | default('widget-toc-list') }}";
    var clsItem = "{{ cls_toc_item | default('widget-toc-item') }}";
    var clsLink = "{{ cls_toc_link | default('widget-toc-link') }}";

    var selectorMap = { h2: 'h2', h3: 'h2, h3', h4: 'h2, h3, h4' };
    var selector = '.post-content ' + (selectorMap[maxDepth] || 'h2, h3').split(', ').join(', .post-content ');

    document.addEventListener('DOMContentLoaded', function () {
        var headings = document.querySelectorAll(selector);
        if (headings.length < minHeadings) return;

        headings.forEach(function (h, i) {
            if (!h.id) {
                h.id = 'toc-' + h.tagName.toLowerCase() + '-' + i + '-' +
                       h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            }
        });

        var levels = { H2: 1, H3: 2, H4: 3 };
        var rootUl = document.createElement('ul');
        rootUl.className = clsList;
        var stack = [{ ul: rootUl, level: 0 }];

        headings.forEach(function (h) {
            var level = levels[h.tagName] || 1;
            while (stack.length > 1 && stack[stack.length - 1].level >= level) { stack.pop(); }

            var li = document.createElement('li');
            li.className = clsItem;
            li.style.paddingLeft = ((level - 1) * 12) + 'px';
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent;
            a.className = clsLink;
            li.appendChild(a);

            var parentUl = stack[stack.length - 1].ul;
            parentUl.appendChild(li);

            var subUl = document.createElement('ul');
            subUl.className = clsList;
            li.appendChild(subUl);
            stack.push({ ul: subUl, level: level });
        });

        document.getElementById('pubvana-toc-nav').appendChild(rootUl);
        document.getElementById('pubvana-toc').style.display = '';
    });
}());
</script>
