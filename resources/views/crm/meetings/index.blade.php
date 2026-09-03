@extends('layouts.app')

@section('page_title', 'Meetings')

@section('content')
@php
    $isGoogleConnectionEnabled = \App\Helpers\IntegrationHelper::isGoogleEnabled();
@endphp
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="googleCalendarSection">
        <div class="card-header bg-white border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="fw-bold mb-0">Manage Meetings</h4>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($isGoogleConnectionEnabled)
                        @can('meetings.edit')
                        <button type="button" id="connectGoogleBtn" class="btn btn-dark-blue" onclick="GoogleCalendar.connect()">
                            <i class="bi bi-google me-1"></i>Connect Google Calendar
                        </button>
                        <button type="button" id="disconnectGoogleBtn" class="btn btn-outline-danger" onclick="GoogleCalendar.disconnect()" style="display: none;">
                            <i class="bi bi-x-circle me-1"></i>Disconnect
                        </button>
                        @endcan
                    @endif
                    @can('meetings.view')
                    <a href="{{ route('meetings.export') }}" id="meetingExportButton" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('meetings.create')
                    <a href="{{ route('meetings.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Meeting
                    </a>
                    @endcan
                </div>
            </div>
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex gap-2 flex-grow-1"><div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search meetings..." id="meetingsSearch" value="{{ request('search') }}">
                </div><button type="button" id="meetingFilterToggle" class="btn btn-outline-dark-blue btn-sm" aria-expanded="true"><i class="fa-solid fa-filter me-1"></i>Filters <span id="meetingFilterCount" class="badge rounded-pill text-bg-primary d-none">0</span></button></div>
                <div class="d-flex align-items-center gap-2"><label for="meetingPerPage" class="small text-muted text-nowrap mb-0">Show per page:</label><select id="meetingPerPage" class="form-select form-select-sm crm-auto-per-page" style="width:78px;">@foreach([10,25,50,100] as $size)<option>{{ $size }}</option>@endforeach</select></div>
            </div>
            <div id="meetingFilters" class="meeting-filter-panel mt-3"><div class="row g-2 align-items-end flex-lg-nowrap">
                <div class="col-sm-6 col-lg"><label class="form-label small fw-semibold" for="meetingCustomerFilter">Customer</label><select id="meetingCustomerFilter" class="form-select form-select-sm"><option value="">All customers</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg"><label class="form-label small fw-semibold" for="meetingStaffFilter">Staff</label><select id="meetingStaffFilter" class="form-select form-select-sm"><option value="">All staff</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg"><label class="form-label small fw-semibold" for="meetingScheduledRange">Scheduled On</label><div class="input-group input-group-sm"><span class="input-group-text"><i class="fa-regular fa-calendar"></i></span><input id="meetingScheduledRange" class="form-control" type="text" placeholder="From - To" readonly></div></div>
                <div class="col-sm-6 col-lg"><label class="form-label small fw-semibold" for="meetingTypeFilter">Meeting Type</label><select id="meetingTypeFilter" class="form-select form-select-sm"><option value="">All types</option><option value="virtual">Virtual</option><option value="in-person">In-person</option><option value="telephonic">Telephonic</option></select></div>
                <div class="col-sm-6 col-lg"><label class="form-label small fw-semibold" for="meetingStatusFilter">Status</label><select id="meetingStatusFilter" class="form-select form-select-sm"><option value="">All statuses</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                <div class="col-sm-6 col-lg-auto"><button id="meetingClearFilters" type="button" class="btn btn-dark-blue btn-sm px-4 w-100 crm-filter-clear-btn"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button></div>
            </div></div>
        </div>

        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="meetingFilterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                            Created By Me
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                            Assigned To Me
                        </button>
                    </li>
                </ul>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" data-no-auto-filter>
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center">Sr.No</th>
                            <th>Customer</th>
                            <th class="text-center d-none d-md-table-cell">Staff</th>
                            <th class="text-center d-none d-md-table-cell">Scheduled On</th>
                            <th class="text-center d-none d-md-table-cell">Meeting Type</th>
                            <th class="text-center d-none d-md-table-cell">Calender</th>
                            <th class="text-center d-none d-md-table-cell">Status</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 220px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div id="meetingPaginationContainer" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
        .meeting-filter-panel { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        [data-theme="dark"] .meeting-filter-panel { border-color: rgba(255,255,255,.08); background: #172033; }
    </style>
@endpush

@push('scripts')
    <script>
    window.crmUserPermissions = {
        ...(window.crmUserPermissions || {}),
        meetings: {
            view: @json(auth()->user()?->hasMatrixPermission('view_meetings')),
            create: @json(auth()->user()?->hasMatrixPermission('create_meetings')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_meetings')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_meetings')),
        }
    };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/meeting.js') }}?v={{ filemtime(public_path('js/meeting.js')) }}"></script>
@endpush
