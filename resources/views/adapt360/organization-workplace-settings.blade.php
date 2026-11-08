@php
$workplaceProfile = (json_decode($organization->workspace_data ?? '{}', true) ?: [])['profile'] ?? [];
$exposureLabels = ['chemical'=>'Chemicals used','noise'=>'Significant noise / grinding','ergo'=>'Manual handling / ergonomic risk','iaq'=>'Air-conditioned indoor area','machinery'=>'Machinery / equipment','contractor'=>'Contractors','hotwork'=>'Welding / hot work','height'=>'Work at height','confined'=>'Confined space'];
@endphp
<section class="organization-workplace-settings"><h3>Workplace settings</h3>
@if($editing)
<input type="hidden" name="workplace_settings" value="1"><div class="form-grid"><div><label for="organization-lead">OSH lead</label><select name="lead" id="organization-lead">@foreach(['OSH Coordinator','SHO','Management / no dedicated OSH staff'] as $lead)<option @selected(old('lead',$workplaceProfile['lead'] ?? 'OSH Coordinator') === $lead)>{{ $lead }}</option>@endforeach</select></div><div><label for="organization-foreign">Foreign workers</label><select name="foreign" id="organization-foreign"><option value="no" @selected(old('foreign',$workplaceProfile['foreign'] ?? 'no') === 'no')>No</option><option value="yes" @selected(old('foreign',$workplaceProfile['foreign'] ?? 'no') === 'yes')>Yes</option></select></div></div>
<h4>Exposure / workplace triggers</h4><div class="checkbox-grid">@foreach($exposureLabels as $key=>$label)<label class="check"><input type="checkbox" name="exposures[]" value="{{ $key }}" @checked(in_array($key,old('exposures',array_keys(array_filter($workplaceProfile,fn($value,$key)=>array_key_exists($key,$exposureLabels) && $value,ARRAY_FILTER_USE_BOTH)))))>{{ $label }}</label>@endforeach</div><p class="note">These settings update suggested legal requirements, health risk assessment pathways and organization priorities.</p>
@else
<dl class="organization-information-grid"><div><dt>OSH lead:</dt><dd>{{ $workplaceProfile['lead'] ?? '-' }}</dd></div><div><dt>Foreign workers:</dt><dd>{{ isset($workplaceProfile['foreign']) ? ($workplaceProfile['foreign'] === 'yes' ? 'Yes' : 'No') : '-' }}</dd></div><div><dt>Exposure / workplace triggers:</dt><dd>{{ collect($exposureLabels)->filter(fn($label,$key)=>!empty($workplaceProfile[$key]))->implode(', ') ?: '-' }}</dd></div></dl>
@endif
</section>
