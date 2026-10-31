<x-heading title="Your workplace, at a glance." description="Syarikat Contoh Fabrikasi Sdn Bhd / Miri, Sarawak">
    <button class="button" data-dialog="report"><x-icon name="download" size="15" />Export overview</button>
    <a href="/profile" class="button primary"><x-icon name="building-2" size="15" />Workplace profile</a>
</x-heading>
<div class="stats">
    <div class="stat-item"><div class="stat-label"><x-icon name="book-open" size="15" />Legal compliance</div><div class="stat-value">6<small>/ 17</small></div><div class="stat-note"><span class="teal-text">6 compliant</span> requirements</div></div>
    <div class="stat-item"><div class="stat-label"><x-icon name="scan-line" size="15" />Recorded hazards</div><div class="stat-value">7<small>hazards</small></div><div class="stat-note">1 high risk <span class="px-1">/</span> 6 medium risk</div></div>
    <div class="stat-item"><div class="stat-label"><x-icon name="circle-check" size="15" />Controls implemented</div><div class="stat-value">43<small>%</small></div><div class="stat-note"><span class="teal-text">3 of 7 complete</span> / 4 remaining</div></div>
    <div class="stat-item"><div class="stat-label"><x-icon name="clipboard-check" size="15" />OSHWA documentation</div><div class="stat-value">64<small>%</small></div><div class="stat-note">Grade C <span class="px-1">/</span> Working toward B</div></div>
</div>
<div class="two-column">
    <div>
        <section class="pathway">
            <div class="pathway-header"><div><span class="eyebrow">SOLVE 4 SME / YOUR PATHWAY</span><h2>A stronger safety culture, step by step.</h2><p>Basic achieved. Your next milestone is Bronze.</p></div><x-status tone="teal">Basic level</x-status></div>
            <div class="level-list">
                @foreach(['Basic','Bronze','Silver','Gold','Platinum'] as $level)
                <div class="level-item {{ $loop->first ? 'done' : ($loop->index === 1 ? 'current' : '') }}"><span class="level-marker">@if($loop->first)<x-icon name="check" size="14" />@else{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}@endif</span><strong>{{ $level }}</strong><small>{{ $loop->first ? 'Achieved' : ($loop->index === 1 ? 'In progress' : 'Upcoming') }}</small></div>
                @endforeach
            </div>
        </section>
        <section class="section">
            <div class="section-head"><div><h2>Focus on what comes next</h2><p>Complete these steps to reach Bronze.</p></div><span class="mono muted">1 / 2 COMPLETE</span></div>
            <div class="task-row"><span class="task-icon"><x-icon name="file-signature" size="16" /></span><div class="task-body"><strong>Sign and display your OSH policy</strong><small>Get management approval and share it with your team.</small></div><a href="/roles" class="text-link">Review<x-icon name="arrow-up-right" size="14" /></a></div>
            <div class="task-row"><span class="task-icon teal"><x-icon name="users-round" size="16" /></span><div class="task-body"><strong>Form your safety & health committee</strong><small>Chairman, secretary and representatives are appointed.</small></div><x-status tone="teal">Complete</x-status></div>
        </section>
        <section class="section rule">
            <div class="section-head"><div><h2>Risk register</h2><p>Priority hazards across your work activities.</p></div><a href="/hirarc" class="text-link">View register<x-icon name="arrow-up-right" size="14" /></a></div>
            <div class="table-wrap"><table><thead><tr><th>Activity / hazard</th><th>Risk</th><th>Control status</th></tr></thead><tbody>
            <tr><td><strong>MIG welding</strong><small>Welding fumes / respiratory exposure</small></td><td><span class="risk high">16 HIGH</span></td><td><x-status tone="amber">Planned</x-status></td></tr>
            <tr><td><strong>Grinding & cutting steel</strong><small>Excessive noise / hearing loss</small></td><td><span class="risk">12 MEDIUM</span></td><td><x-status tone="amber">Planned</x-status></td></tr>
            <tr><td><strong>Manual lifting of steel plates</strong><small>Heavy loads / back injury</small></td><td><span class="risk">12 MEDIUM</span></td><td><x-status tone="amber">Planned</x-status></td></tr>
            </tbody></table></div>
        </section>
    </div>
    <div>
        <section class="surface"><div class="section-head"><h2>On your radar</h2><x-icon name="calendar-clock" size="19" class="muted" /></div><div class="timeline">
            <div class="timeline-item alert"><a href="/committee"><strong>CHRA quotations overdue</strong><p>Aina Rahman / committee action</p></a><span class="mono">DUE 15 SEP 2026</span></div>
            <div class="timeline-item"><a href="/committee"><strong>Plan the next committee meeting</strong><p>Quarterly review and action follow-up</p></a><span class="mono">BY 10 OCT 2026</span></div>
            <div class="timeline-item"><a href="/hirarc"><strong>Spray painting is ready for HIRARC</strong><p>JMS submitted by the engineer</p></a><span class="mono">ASSESSMENT PENDING</span></div>
            <div class="timeline-item"><a href="/health"><strong>Annual audiometric testing</strong><p>Exposed workers / noise assessment</p></a><span class="mono">DUE 30 NOV 2026</span></div>
        </div></section>
        <section class="section"><div class="section-head"><h3>Health assessments</h3><a href="/health" class="text-link"><x-icon name="arrow-up-right" size="16" /><span class="sr-only">View health assessments</span></a></div>
            @foreach([['Noise risk','Report received','blue'],['Chemical health','Assessor appointed','amber'],['Indoor air quality','Report received','blue'],['Ergonomics','Screening done','amber'],['Psychosocial','Not started','neutral']] as [$label,$status,$tone])
            <div class="flex items-center justify-between gap-2 py-3 border-b border-gray-200/70"><span class="text-xs text-gray-600">{{ $label }}</span><x-status :tone="$tone">{{ $status }}</x-status></div>
            @endforeach
        </section>
    </div>
</div>
