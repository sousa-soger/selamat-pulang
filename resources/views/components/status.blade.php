@props(['tone' => 'neutral'])
<span {{ $attributes->class(['status', 'status-'.$tone]) }}><span class="status-dot"></span>{{ $slot }}</span>
