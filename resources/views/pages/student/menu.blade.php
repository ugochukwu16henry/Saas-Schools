{{--Marksheet--}}
<li class="nav-item">
    <a href="{{ route('marks.year_select', Qs::hash(Auth::user()->id)) }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.show', 'marks.year_selector', 'pins.enter']) ? 'active' : '' }}"><i class="icon-book"></i> Marksheet</a>
</li>

<li class="nav-item">
    <a href="{{ route('students.transcript.show', Auth::user()->id) }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.transcript.show']) ? 'active' : '' }}"><i class="icon-file-text2"></i> Transcript</a>
</li>