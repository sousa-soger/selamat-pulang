@props(['title' => 'Why this matters'])
<details class="guide"><summary><x-icon name="lightbulb" /><span>{{ $title }}</span><x-icon name="chevron-down" /></summary><div>{{ $slot }}</div></details>
