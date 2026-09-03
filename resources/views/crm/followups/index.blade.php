@extends('layouts.app')

@section('page_title', 'Follow Ups')

@section('content')
<div class="container-fluid p-0">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Follow Ups</h4>
                    <p class="text-muted small mb-0">Track and manage your follow up tasks.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('followups.view')
                    <a href="{{ route('followups.export') }}" id="followUpExportButton" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                    @endcan
                    @can('followups.create')
                    <a href="{{ route('followups.create') }}" class="btn btn-dark-blue">
                        <i class="bi bi-plus-lg me-1"></i>Add Follow Up
                    </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex gap-2 flex-grow-1">
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="followUpSearch" class="form-control crm-search-input border-0" placeholder="Search follow ups..." name="search" value="{{ request('search') }}">
                    </div>
                    <button type="button" id="followUpFilterToggle" class="btn btn-outline-dark-blue btn-sm" aria-expanded="true" aria-controls="followUpFilters"><i class="fa-solid fa-filter me-1"></i>Filters <span id="followUpFilterCount" class="badge rounded-pill text-bg-primary d-none">0</span></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="followUpPerPage" class="small text-muted text-nowrap mb-0">Show per page:</label>
                    <select id="followUpPerPage" class="form-select form-select-sm crm-auto-per-page" style="width: 78px;">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select>
                </div>
            </div>
            <div id="followUpFilters" class="followup-filter-panel mt-3">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-lg"><label for="followUpStaffFilter" class="form-label small fw-semibold">Staff</label><select id="followUpStaffFilter" class="form-select form-select-sm"><option value="">All staff</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div class="col-sm-6 col-lg"><label for="followUpCreatedRange" class="form-label small fw-semibold">Created At</label><div class="input-group input-group-sm"><span class="input-group-text"><i class="fa-regular fa-calendar"></i></span><input type="text" id="followUpCreatedRange" class="form-control" placeholder="From - To" autocomplete="off" readonly></div></div>
                    <div class="col-sm-6 col-lg"><label for="followUpDateRange" class="form-label small fw-semibold">Follow Up Date</label><div class="input-group input-group-sm"><span class="input-group-text"><i class="fa-regular fa-calendar"></i></span><input type="text" id="followUpDateRange" class="form-control" placeholder="From - To" autocomplete="off" readonly></div></div>
                    <div class="col-sm-6 col-lg"><label for="followUpStatusFilter" class="form-label small fw-semibold">Status</label><select id="followUpStatusFilter" class="form-select form-select-sm"><option value="">All statuses</option><option value="pending">Pending</option><option value="resheduled">Rescheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                    <div class="col-sm-6 col-lg"><button type="button" id="followUpClearFilters" class="btn btn-dark-blue btn-sm w-100 crm-filter-clear-btn"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button></div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="followupFilterTabs" role="tablist">
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
                    <thead>
                        <tr>
                            <th class="ps-4 text-center">Sr.No</th>
                            <th class="text-center">Lead Name</th>
                            <th class="text-center d-none d-md-table-cell">Staff Name</th>
                            <th class="text-center d-none d-md-table-cell">Purpose</th>
                            <th class="text-center d-none d-lg-table-cell">Created At</th>
                            <th class="text-center d-none d-md-table-cell">Follow Up Date</th>
                            <th class="text-center d-none d-md-table-cell">Status</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 140px;">Action</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="followUpsTable"></tbody>
                </table>
            </div>
            <div id="followupPaginationContainer" class="px-4 pb-3 pt-0"></div>
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
        .followup-filter-panel { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; box-shadow: 0 2px 6px rgba(15, 23, 42, .05); }
        [data-theme="dark"] .followup-filter-panel { border-color: rgba(255,255,255,.08); background: #172033; }
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
    </style>
@endpush

@push('scripts')
    <script>
    window.crmUserPermissions = {
        followups: {
            view: @json(auth()->user()?->hasMatrixPermission('view_followups')),
            create: @json(auth()->user()?->hasMatrixPermission('create_followups')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_followups')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_followups')),
        }
    };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/followup.js') }}?v={{ filemtime(public_path('js/followup.js')) }}"></script>
@endpush
