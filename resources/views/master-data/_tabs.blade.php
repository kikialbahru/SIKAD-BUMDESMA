<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link @if(($activeTab ?? '') === 'positions') active @endif" href="{{ route('positions.index') }}">
            <i class="bi bi-briefcase me-1"></i>Jabatan</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(($activeTab ?? '') === 'schedules') active @endif" href="{{ route('work-schedules.index') }}">
            <i class="bi bi-clock me-1"></i>Jadwal Kerja</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(($activeTab ?? '') === 'salary-components') active @endif" href="{{ route('salary-components.index') }}">
            <i class="bi bi-cash-coin me-1"></i>Komponen Gaji</a>
    </li>
</ul>
