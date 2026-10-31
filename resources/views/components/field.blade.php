@props(['label', 'value' => '', 'type' => 'text', 'options' => null])
<label class="field"><span>{{ $label }}</span>
@if($options)
<select {{ $attributes }}>@foreach($options as $option)<option @selected($option === $value)>{{ $option }}</option>@endforeach</select>
@else
<input type="{{ $type }}" value="{{ $value }}" {{ $attributes }}>
@endif
</label>
