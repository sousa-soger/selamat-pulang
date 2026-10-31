@php
    $requirements = [
        ['OSHA 1994 / s.15','General safety, health and welfare','OSH management system','Partial','amber',null],
        ['OSHA 1994 / s.15(2)(f)','Emergency response procedures','Emergency plan and drill records','Compliant','teal',null],
        ['OSHA 1994 / s.16','Written OSH policy','Signed and displayed policy','Not compliant','red','roles'],
        ['OSHA 1994 / s.18A','Safety of contractors','Briefings, permits and monitoring','Partial','amber',null],
        ['OSHA 1994 / s.18B','Risk assessment and controls','JMS, HIRARC and control records','Partial','amber','hirarc'],
        ['OSHA 1994 / s.29A','Appoint an OSH coordinator','Appointment letter / MyKKP record','Compliant','teal','roles'],
        ['OSHA 1994 / s.30','Safety & health committee','Membership and meeting records','Compliant','teal','committee'],
        ['OSHA 1994 / s.31A','Employee OSH training','Training records and certificates','Partial','amber',null],
        ['NADOPOD Regulations 2004','Incident reporting and annual summary','JKKP 6, 7 and 8 submissions','Compliant','teal','oshwa'],
        ['USECHH Regulations 2000','Chemical health risk assessment','Chemical register and CHRA report','Partial','amber','health'],
        ['CLASS Regulations 2013','Chemical labelling and safety data sheets','Labels and SDS records','Partial','amber','health'],
        ['Noise Exposure Regulations 2019','Noise exposure management','NRA, hearing protection and audiograms','Partial','amber','health'],
        ['ERA Guidelines 2017','Ergonomics risk assessment','IERA screening and AERA report','Partial','amber','health'],
        ['ICOP IAQ 2010','Indoor air quality assessment','IAQ report and maintenance records','Partial','amber','health'],
        ['PRiSMA Guidelines 2024','Psychosocial risk management','Assessment results and action plan','Not compliant','red','health'],
        ['OSHA 1994 / certified plant','Valid certificates of fitness','CF register and displayed registration','Compliant','teal',null],
        ['Fire Services Act 1988','Fire safety and equipment','Fire certificate and service records','Compliant','teal',null],
    ];
@endphp
<x-heading title="Legal register" description="The requirements, evidence and compliance picture for your workplace."><button class="button" data-dialog="report"><x-icon name="download" size="15" />Export register</button><button class="button primary" data-dialog="evaluation"><x-icon name="calendar-check" size="15" />Record evaluation</button></x-heading>
<div class="stats">@foreach([['Applicable requirements','17','Based on the sample profile'],['Compliant','6','Evidence in place'],['Partially compliant','9','Follow-up needed'],['Not compliant','2','Action required']] as [$label,$value,$note])<div class="stat-item"><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div><div class="stat-note">{{ $note }}</div></div>@endforeach</div>
<x-guide title="A requirement is only complete when the evidence is in place">Linked requirements show statuses from the related module in the example data. Other requirements are reviewed against their supporting evidence. Source references and thresholds are retained from the original prototype and need verification before operational use.</x-guide>
<div class="section-head"><div class="segmented" role="group" aria-label="Requirements filter"><button data-filter="legal" data-value="all" aria-pressed="true">All requirements <span class="mono">17</span></button><button data-filter="legal" data-value="attention" aria-pressed="false">Needs attention <span class="mono">11</span></button><button data-filter="legal" data-value="Compliant" aria-pressed="false">Compliant <span class="mono">6</span></button></div><span class="text-[10px] text-gray-400">Last evaluation <span class="mono ml-2">30 JUN 2026</span></span></div>
<div class="table-wrap flat"><table><thead><tr><th>Requirement</th><th>Applies</th><th>Supporting evidence</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($requirements as [$source,$title,$evidence,$status,$tone,$target])
<tr data-filter-row="legal" data-state="{{ $status }}"><td class="min-w-[230px]"><span class="mono text-gray-400">{{ $source }}</span><strong class="mt-1">{{ $title }}</strong></td><td><x-status tone="teal">Yes</x-status></td><td class="min-w-[150px] text-gray-500">{{ $evidence }}</td><td><x-status :tone="$tone">{{ $status }}</x-status>@if($target)<small class="flex items-center gap-1"><x-icon name="link-2" size="10" />Linked</small>@endif</td><td>@if($target)<a href="/{{ $target }}" class="icon-button" title="Open {{ $title }}"><x-icon name="arrow-up-right" size="16" /></a>@else<button class="icon-button" data-dialog="evidence" title="Review evidence"><x-icon name="ellipsis" size="16" /></button>@endif</td></tr>
@endforeach
</tbody></table></div>
