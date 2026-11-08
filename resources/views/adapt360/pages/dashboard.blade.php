@php
    $user = auth()->user();
    $mykkpStatus = in_array($user->certificate_status, ['approved', 'pending', 'rejected'], true) ? $user->certificate_status : 'not_submitted';
    $initials = collect(preg_split('/\s+/', trim($user->name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'U';
    $orgRole = $organization ? ($organization->owner_id === $user->id ? 'Owner' : ($organization->canEdit($user) ? 'Editor' : 'Viewer')) : null;
    $orgAccess = $organization ? ($organization->hasAccess() ? 'Active' : ucfirst($organization->subscription_status)) : null;
    $orgEditable = $organization ? ($organization->canEdit($user) && ($organization->hasAccess() || $user->isSuperAdmin())) : false;
    $orgMembers = $organization ? $organization->users()->count() : 0;
@endphp

<div class="home-welcome">
    <div>
        <div class="home-kicker">Your workspace <span> / </span> Home</div>
        <h1>Welcome back, {{ $user->name }}.</h1>
        <p>A clear view of your workplace and what needs attention.</p>
    </div>
    <a class="btn home-report" href="/workerportal" data-section="workerportal">Report something unsafe <span aria-hidden="true">↗</span></a>
</div>

<div class="home-layout">
    <div class="home-col">
        <section class="card home-profile" aria-labelledby="home-profile-title">
            <div class="home-card-kicker">Profile</div>
            <div class="home-profile-top">
                @if($user->avatar_path)
                <img class="home-avatar" src="{{ route('profile.avatar') }}" alt="">
                @else
                <span class="home-avatar" aria-hidden="true">{{ $initials }}</span>
                @endif
                <div class="home-profile-id">
                    <h2 id="home-profile-title">{{ $user->name }}</h2>
                    <span class="home-profile-email">{{ $user->email }}</span>
                    <div id="homeMykkpStatus" class="home-mykkp-status is-{{ $mykkpStatus }}" role="status" data-review="{{ hash('sha256', ($user->certificate_reviewed_at ?? '').'|'.($user->certificate_submitted_at ?? '').'|'.($user->certificate_feedback ?? '')) }}">
                        <span>@if($mykkpStatus === 'approved')MyKKP ID: {{ $user->mykkp_id }}@elseif($mykkpStatus === 'pending')Your MyKKP ID is being processed@elseif($mykkpStatus === 'rejected')MyKKP ID verification is failed, please verify again@else MyKKP Not verified @endif</span>
                        @if($mykkpStatus === 'rejected')
                        <button type="button" id="dismissMykkpFailure" aria-label="Dismiss MyKKP verification failure">×</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="home-profile-foot">
                <div class="home-org-inline">
                    <div class="home-card-kicker">Organization</div>
                    <div class="home-org-name">{{ $organization?->name ?? 'No organization selected' }}</div>
                    @if($organization)
                    <div class="home-org-meta">
                        <span>{{ $orgRole }}</span>
                        <span>{{ $orgEditable ? 'Editable' : 'View only' }}</span>
                        <span class="home-org-access {{ $organization->hasAccess() ? 'is-ok' : 'is-wait' }}"><i aria-hidden="true"></i>{{ $orgAccess }}</span>
                        <span>{{ $orgMembers }} {{ $orgMembers === 1 ? 'member' : 'members' }}</span>
                    </div>
                    @else
                    <p class="home-org-empty">Choose or create an organization to keep workplace records and priorities in one place.</p>
                    @endif
                </div>
                <div class="home-profile-actions">
                    @if($organization)
                    <a href="/organization" data-section="organization">Organization overview <span aria-hidden="true">↗</span></a>
                    @else
                    <a href="{{ route('account') }}">Choose an organization <span aria-hidden="true">↗</span></a>
                    @endif
                    <a href="{{ route('account') }}">Manage profile <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        </section>

        <div class="home-deck-nav" aria-label="Dashboard cards">
            <div><button type="button" data-home-slide="0" aria-pressed="true">Organization overview</button><button type="button" data-home-slide="1" aria-pressed="false">Compliance calendar</button></div>
            <span>Drag to explore <button type="button" data-home-slide="previous" aria-label="Previous card">←</button><button type="button" data-home-slide="next" aria-label="Next card">→</button></span>
        </div>
        <div class="home-deck" id="homeDeck" tabindex="0" aria-label="Organization overview and compliance calendar">
        <section class="card home-main home-slide" aria-label="Organization overview">
            <div class="home-section-heading"><div><div class="home-kicker">Organization overview</div><h2>{{ $organization?->name ?? 'No organization selected' }}</h2><p>Follow the signals below to review your records and take the next step.</p></div><a href="/next" data-section="next">All priorities ↗</a></div>
            @if($organization)
            <section class="home-priorities" aria-labelledby="home-priorities-title"><div><div class="home-kicker">Focus first</div><h3 id="home-priorities-title">Organization priorities</h3><p>Ordered from the workspace priority queue.</p></div><div id="homePriorityList" class="home-priority-list"></div></section>
            <div class="home-overview" id="homeOverview">
                <section class="home-area"><div class="home-area-head"><span class="home-area-number">01</span><div><h3>OSH system</h3><p>Build the foundations</p></div></div><div class="home-links">
                    <a href="/workplace" data-section="workplace"><span><strong>Workplace profile</strong><small id="homeProfileStatus">Review your details</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/legal" data-section="legal"><span><strong>Legal register</strong><small id="homeLegalStatus">Review requirements</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/committee" data-section="committee"><span><strong>Safety &amp; health committee</strong><small id="homeCommitteeStatus">Review committee</small></span><b aria-hidden="true">↗</b></a>
                </div></section>
                <section class="home-area"><div class="home-area-head"><span class="home-area-number">02</span><div><h3>Risk management</h3><p>Understand exposure</p></div></div><div class="home-links">
                    <a href="/hirarc" data-section="hirarc"><span><strong>JMS &amp; HIRARC</strong><small id="homeHirarcStatus">Review risk assessments</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/hra" data-section="hra"><span><strong>Health risk assessments</strong><small id="homeHraStatus">Review health risks</small></span><b aria-hidden="true">↗</b></a>
                </div></section>
                <section class="home-area"><div class="home-area-head"><span class="home-area-number">03</span><div><h3>Control</h3><p>Follow through on risks</p></div></div><div class="home-links">
                    <a href="/capa" data-section="capa"><span><strong>Corrective actions</strong><small id="homeControlStatus">Review open actions</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/fives" data-section="fives"><span><strong>5S + safety</strong><small id="homeFiveSStatus">Review workplace conditions</small></span><b aria-hidden="true">↗</b></a>
                    <div class="home-fives-summary"><div class="bar" id="homeFiveSProgress" role="progressbar" aria-label="5S checklist completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="homeFiveSBar" style="width:0%"></span></div><div id="homeFiveSGaps" class="home-fives-gaps" hidden><strong>Needs attention</strong><ul id="homeFiveSItems" tabindex="0" aria-label="Incomplete 5S checklist items"></ul></div></div>
                </div><p class="home-area-note">Additional control priorities are to be defined.</p></section>
                <section class="home-area"><div class="home-area-head"><span class="home-area-number">04</span><div><h3>Excellence</h3><p>Keep improving</p></div></div><div class="home-links">
                    <a href="/solve" data-section="solve"><span><strong>SOLVE 4 SME &amp; OSHWA</strong><small id="homeSolveStatus">Review checkpoints</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/excel" data-section="excel"><span><strong>MSOSH &amp; excellence</strong><small>Review evidence and award readiness</small></span><b aria-hidden="true">↗</b></a>
                    <a href="/mentor" data-section="mentor"><span><strong>Fresh graduate mentor</strong><small>Explore learning and guidance</small></span><b aria-hidden="true">↗</b></a>
                </div></section>
                <section class="home-area home-excellence-overview" aria-labelledby="home-excellence-title">
                    <div class="home-area-head"><div><h3 id="home-excellence-title">ISO 45001 &amp; award readiness</h3><p>Overview of your evidence and progress</p></div><a href="/excel" data-section="excel">View details ↗</a></div>
                    <div class="home-excellence-grid">
                        <div><h4>ISO evidence map</h4>
                            @foreach(['Clause 4 Context', 'Clause 5 Leadership', 'Clause 6 Planning', 'Clause 7 Support', 'Clause 8 Operation', 'Clause 9 Evaluation', 'Clause 10 Improvement'] as $clause)
                            <div class="home-iso-row" data-home-iso-index="{{ $loop->index }}"><span>{{ $clause }}</span><div class="bar" role="progressbar" aria-label="{{ $clause }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span style="width:0%"></span></div><strong>0%</strong></div>
                            @endforeach
                        </div>
                        <div><h4>Award evidence pack</h4>
                            @foreach(['Policy & leadership', 'Risk & control evidence', 'Worker participation', 'Performance & review'] as $award)
                            <div class="home-award-row"><span>{{ $award }}</span><span class="badge" data-home-award-index="{{ $loop->index }}"></span></div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
            <div class="home-footnote">Signals reflect the records in this workspace and are not a certification or a complete risk assessment.</div>
            @else
            <div class="home-empty-org"><h3>Start with an organization</h3><p>Select or create an organization to see its safety overview and priorities here.</p><a href="{{ route('account') }}">Your organizations ↗</a></div>
            @endif
        </section>
        <section class="card home-slide home-calendar-card" aria-labelledby="home-calendar-title">
            <div class="home-section-heading"><div><div class="home-kicker">Plan ahead</div><h2 id="home-calendar-title">Compliance calendar</h2><p>Meetings, audits and upcoming safety deadlines.</p></div><a href="/organization#dashCalendar" data-section="calendar">Open calendar ↗</a></div>
            <div class="home-calendar-body"><div class="home-calendar-month"><label for="homeCalendarMonth">Month</label><input type="month" id="homeCalendarMonth">
            <div class="home-calendar-grid" id="homeCalendarGrid"></div>
            </div><div class="home-calendar-summary"><h3>Upcoming &amp; overdue</h3><div id="homeCalendarAgenda" aria-live="polite"></div></div></div>
        </section>
        </div>
    </div>

    <aside class="card home-todo" aria-labelledby="home-todo-title" data-mykkp-status="{{ $mykkpStatus }}" data-user-verified="{{ $user->email_verified_at ? 'true' : 'false' }}" data-org-verified="{{ $organization?->verification_status === 'verified' ? 'true' : 'false' }}">
        <div class="home-todo-top">
            <div>
                <h2 id="home-todo-title">To-do list</h2>
            </div>
        </div>
        <label for="homeTodoSort">Sort alerts</label>
        <select id="homeTodoSort"><option value="priority">Recommended priority</option><option value="due">Due date · earliest first</option><option value="category">Category · A–Z</option><option value="custom">My order</option></select>
        <ol id="homeTodoList" class="home-todo-list" aria-label="Workspace alerts"></ol>
        <p id="homeTodoEmpty" class="note" hidden>No outstanding alerts. Your workspace is up to date.</p>
        <div class="home-todo-foot">
            <span id="homeTodoCount" role="status" aria-live="polite"></span>
            <a href="/next" data-section="next">All priorities ↗</a>
        </div>
    </aside>
</div>
