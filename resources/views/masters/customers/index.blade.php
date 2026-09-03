@extends('layouts.masters')

@section('page_title', 'Masters - Customers')

@section('masters_content')

    <div class="card-header border-bottom-0 py-3 px-3">
        <div class="customer-title-row d-flex justify-content-between align-items-start mb-3 gap-3">
            <div class="customer-title-copy">
                <h4 class="fw-bold mb-0">Manage Customers</h4>
                <p class="text-muted small mb-0">View and manage your customer database and communication history.</p>
            </div>
            <div class="customer-actions d-flex flex-nowrap justify-content-end gap-2">
                @can('customers.create')
                    <button type="button" class="btn btn-outline-dark-blue customer-action-btn" onclick="showImportDialog()">
                        <i class="fa-solid fa-upload me-1"></i>Import CSV
                    </button>
                @endcan
                @can('customers.view')
                    <a href="{{ route('masters.customers.export') }}" id="customerExportButton" class="btn btn-outline-dark-blue customer-action-btn">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                @endcan
                @can('customers.create')
                    <a href="{{ route('masters.customers.create') }}" class="btn btn-dark-blue customer-action-btn">
                        <i class="fa-solid fa-plus me-1"></i>Add Customer
                    </a>
                @endcan
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex gap-2 flex-grow-1">
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="customerSearch" class="form-control crm-search-input border-0"
                        placeholder="Search customers..." name="search" value="{{ request('search') }}">
                </div>
                <button type="button" id="customerFilterToggle" class="btn btn-outline-dark-blue customer-filter-toggle"
                    aria-expanded="true" aria-controls="customerFilters">
                    <i class="fa-solid fa-filter me-1"></i>Filters
                    <span id="customerFilterCount" class="badge rounded-pill text-bg-primary d-none">0</span>
                </button>
            </div>
            <div class="d-flex align-items-center gap-2 customer-per-page">
                <label for="customerPerPage" class="small text-muted text-nowrap mb-0">Show per page:</label>
                <select id="customerPerPage" class="form-select form-select-sm">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="customerFilters" class="customer-filter-panel mt-3">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="customerDateRange" class="form-label small fw-semibold">From - To</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-regular fa-calendar"></i></span>
                        <input type="text" id="customerDateRange" class="form-control" placeholder="Select date range" autocomplete="off" readonly>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3"><label for="customerStatusFilter" class="form-label small fw-semibold">Status</label><select id="customerStatusFilter" class="form-select form-select-sm"><option value="">All statuses</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
                <div class="col-sm-6 col-lg-3">
                    <label for="customerTypeFilter" class="form-label small fw-semibold">Customer Type</label>
                    <select id="customerTypeFilter" class="form-select form-select-sm"><option value="">All types</option>@foreach($customerTypes as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>
                </div>
                <div class="col-sm-6 col-lg-3 d-flex gap-2">
                    <button type="button" id="customerClearFilters" class="btn btn-dark-blue btn-sm flex-grow-1"><i class="fa-solid fa-rotate-left me-1"></i>Clear Filters</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customerTable" class="table table-hover align-middle mb-0 responsive-table" data-sort-mode="server" data-sort-column="created_at" data-sort-direction="desc" data-no-auto-filter>
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 90px;"><button type="button" class="customer-sort-button" data-sort="id">Sr.No <span class="customer-sort-indicator" aria-hidden="true"><i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i></span></button></th>
                        <th><button type="button" class="customer-sort-button" data-sort="name">Customer Name <span class="customer-sort-indicator" aria-hidden="true"><i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i></span></button></th>
                        <th class="d-none d-md-table-cell"><button type="button" class="customer-sort-button" data-sort="email">Email <span class="customer-sort-indicator" aria-hidden="true"><i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i></span></button></th>
                        <th class="d-none d-md-table-cell"><button type="button" class="customer-sort-button" data-sort="phone">Phone <span class="customer-sort-indicator" aria-hidden="true"><i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i></span></button></th>
                        <th class="d-none d-md-table-cell"><button type="button" class="customer-sort-button" data-sort="created_at">Created At <span class="customer-sort-indicator" aria-hidden="true"><i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i></span></button></th>
                        <th class="text-end pe-3 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="customersTable"></tbody>
            </table>
        </div>

        <!-- Pagination Container -->
        <div id="customerPaginationContainer" class="card-footer border-top-0 py-4 px-3"></div>
    </div>
    <form id="customersImportForm" class="d-none" enctype="multipart/form-data">
        @csrf
        <input type="file" name="import_file" id="customersImportFile" accept=".csv,text/csv">
    </form>
@endsection

@push('styles')
    <style>
        .customer-title-copy {
            min-width: 0;
        }

        .customer-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            white-space: nowrap;
        }

        .customer-actions {
            width: auto;
        }

        .customer-action-btn {
            height: 38px;
        }

        .customer-filter-toggle { white-space: nowrap; }

        .customer-filter-panel {
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
        }

        .customer-per-page select { width: 78px; }

        [data-theme="dark"] .customer-filter-panel {
            border-color: rgba(255, 255, 255, .08);
            background: #172033;
        }

        @media (max-width: 575.98px) {
            .customer-title-row {
                flex-direction: column;
            }

            .customer-actions {
                width: 100%;
            }

            .customer-actions {
                gap: 0.4rem !important;
            }

            .customer-action-btn {
                flex: 1 1 0;
                min-width: 0;
                height: 36px;
                padding: 0.35rem 0.35rem;
                font-size: 0.82rem;
                line-height: 1;
            }

            .customer-action-btn i {
                margin-right: 0.2rem !important;
            }
        }
        .customer-action-disabled,
        .customer-action-disabled:hover,
        .customer-action-disabled:focus {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 1;
            box-shadow: none;
        }

        [data-theme="dark"] .customer-action-disabled,
        [data-theme="dark"] .customer-action-disabled:hover,
        [data-theme="dark"] .customer-action-disabled:focus {
            background-color: #1e293b;
            border-color: rgba(255, 255, 255, .08);
            color: #64748b;
        }

        /* Pagination Styles */
        .crm-pagination-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            padding-top: 0.5rem;
        }

        @media (min-width: 768px) {
            .crm-pagination-container {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .crm-pagination .page-item {
            margin: 0 3px;
        }

        .crm-pagination .page-link {
            border-radius: 10px !important;
            padding: 0.6rem 1.1rem;
            color: #475569;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .crm-pagination .page-link:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-color: #0f172a !important;
            color: #fff !important;
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.2);
        }

        .crm-pagination .page-item.disabled .page-link {
            background-color: #f8fafc;
            color: #cbd5e1;
            border-color: #f1f5f9;
            box-shadow: none;
            cursor: not-allowed;
        }

        [data-theme="dark"] .crm-pagination .page-link {
            background-color: #1e293b;
            border-color: rgba(255,255,255,0.06);
            color: #94a3b8;
        }

        [data-theme="dark"] .crm-pagination .page-link:hover {
            background-color: #243146;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        [data-theme="dark"] .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border-color: #3b82f6 !important;
            color: #0f172a !important;
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            customers: {
                view: @json(auth()->user()?->hasMatrixPermission('view_customers')),
                create: @json(auth()->user()?->hasMatrixPermission('create_customers')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_customers')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_customers')),
            }
        };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/customer.js') }}?v={{ filemtime(PUBLIC_PATH('js/customer.js')) }}"></script>
@endpush
