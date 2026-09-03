@extends('layouts.app')

@section('page_title', 'Leads')

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Manage Leads</h4>
                        <p class="text-muted small mb-0">Track and manage your sales pipeline and enquiries.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('leads.view')
                            <a href="{{ route('leads.export') }}" id="leadsExportButton" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('leads.create')
                            <a href="{{ route('leads.create') }}" class="btn btn-dark-blue">
                                <i class="fa-solid fa-plus me-1"></i>Add Lead
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex gap-2 flex-grow-1">
                        <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                            <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control crm-search-input border-0" placeholder="Search leads..." id="leadsSearch" value="{{ request('search') }}">
                        </div>
                        <button type="button" id="leadsFilterToggle" class="btn btn-outline-dark-blue" aria-expanded="true" aria-controls="leadsFilters"><i class="fa-solid fa-filter me-1"></i>Filters <span id="leadsFilterCount" class="badge rounded-pill text-bg-primary d-none">0</span></button>
                    </div>
                    <div class="d-flex align-items-center gap-2"><label for="leadsPerPage" class="small text-muted text-nowrap mb-0">Show per page:</label><select id="leadsPerPage" class="form-select form-select-sm" style="width:78px"><option>10</option><option>25</option><option>50</option><option>100</option></select></div>
                </div>
                <div id="leadsFilters" class="lead-filter-panel mt-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsDateRange">Created At</label><div class="input-group input-group-sm"><span class="input-group-text"><i class="fa-regular fa-calendar"></i></span><input type="text" id="leadsDateRange" class="form-control" placeholder="From - To" autocomplete="off" readonly></div></div>
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsStatus">Status</label><select id="leadsStatus" class="form-select form-select-sm"><option value="">All statuses</option><option value="new">New</option><option value="qualified">Qualified</option><option value="working">Working</option><option value="ready_to_close">Ready to Close</option><option value="won">Closed Won</option><option value="lost">Closed Lost</option></select></div>
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsSource">Lead Source</label><select id="leadsSource" class="form-select form-select-sm"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source->id }}">{{ $source->name }}</option>@endforeach</select></div>
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsStage">Lead Stage</label><select id="leadsStage" class="form-select form-select-sm"><option value="">All stages</option>@foreach($stages as $stage)<option value="{{ $stage->id }}">{{ $stage->name }}</option>@endforeach</select></div>
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsCreator">Created By</label><select id="leadsCreator" class="form-select form-select-sm"><option value="">All creators</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                        <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold" for="leadsAssignee">Assigned To</label><select id="leadsAssignee" class="form-select form-select-sm"><option value="">All assignees</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                        <div class="col-sm-6 col-lg-3"><button type="button" id="leadsClearFilters" class="btn btn-dark-blue btn-sm w-100"><i class="fa-solid fa-rotate-left me-1"></i>Clear Filters</button></div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(!auth()->user()->isAdmin())
                <div class="px-4 pt-3">
                    <ul class="nav nav-tabs crm-filter-tabs" id="leadFilterTabs" role="tablist">
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
                    <table class="table table-hover align-middle mb-0 responsive-table" id="leadsTable" data-no-auto-filter>
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Sr.No</th>
                                <th>Lead Name</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th class="d-none d-md-table-cell">Created At</th>
                                <th class="d-none d-md-table-cell">Status</th>
                                <th class="text-end pe-4 d-none d-md-table-cell" style="width: 120px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer border-top-0 py-4 px-4" id="leadsPagination"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        #leadsTable .lead-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 132px;
            padding: 0.5rem 0.9rem;
            text-align: center;
            white-space: nowrap;
        }
        .lead-filter-panel { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; box-shadow: 0 2px 6px rgba(15,23,42,.05); }
        [data-theme="dark"] .lead-filter-panel { border-color: rgba(255,255,255,.08); background: #172033; }
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
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            leads: {
                view: @json(auth()->user()?->hasMatrixPermission('view_leads')),
                create: @json(auth()->user()?->hasMatrixPermission('create_leads')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_leads')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_leads')),
            }
        };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/leads.js') }}"></script>
@endpush
