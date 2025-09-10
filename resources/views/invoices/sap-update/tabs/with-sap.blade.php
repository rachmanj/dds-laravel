<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoices With SAP Document Number</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-invoice-number-with">Invoice Number</label>
                    <input type="text" class="form-control" id="filter-invoice-number-with"
                        placeholder="Invoice Number">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-faktur-no-with">Faktur No</label>
                    <input type="text" class="form-control" id="filter-faktur-no-with" placeholder="Faktur No">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-po-no-with">PO Number</label>
                    <input type="text" class="form-control" id="filter-po-no-with" placeholder="PO Number">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-sap-doc">SAP Doc</label>
                    <input type="text" class="form-control" id="filter-sap-doc" placeholder="SAP Doc">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-type-with">Type</label>
                    <select class="form-control" id="filter-type-with">
                        <option value="">All Types</option>
                        @foreach ($invoiceTypes as $type)
                            <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-status-with">Status</label>
                    <select class="form-control" id="filter-status-with">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="verify">Verify</option>
                        <option value="return">Return</option>
                        <option value="sap">SAP</option>
                        <option value="close">Close</option>
                        <option value="cancel">Cancel</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filter-supplier-with">Supplier</label>
                    <input type="text" class="form-control" id="filter-supplier-with" placeholder="Supplier">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filter-invoice-project-with">Invoice Project</label>
                    <input type="text" class="form-control" id="filter-invoice-project-with"
                        placeholder="Invoice Project">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mt-4">
                    <button type="button" class="btn btn-primary" id="apply-filters-with">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary ml-2" id="clear-filters-with">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
            <div class="col-md-3 text-right">
                @can('see-all-record-switch')
                    <div class="form-group mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show-all-with">
                            <label class="form-check-label" for="show-all-with">
                                Show All Records
                            </label>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
        <!-- DataTable -->
        <table id="with-sap-table" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Faktur No</th>
                    <th>PO Number</th>
                    <th>SAP Doc</th>
                    <th>Supplier</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<script>
    var withSapTable;

    $(function() {
        // Initialize DataTable
        withSapTable = $('#with-sap-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('invoices.sap-update.with-sap') }}',
                data: function(d) {
                    d.search_invoice_number = $('#filter-invoice-number-with').val();
                    d.search_faktur_no = $('#filter-faktur-no-with').val();
                    d.search_po_no = $('#filter-po-no-with').val();
                    d.search_sap_doc = $('#filter-sap-doc').val();
                    d.search_type = $('#filter-type-with').val();
                    d.search_status = $('#filter-status-with').val();
                    d.search_supplier = $('#filter-supplier-with').val();
                    d.search_invoice_project = $('#filter-invoice-project-with').val();
                    d.show_all = $('#show-all-with').is(':checked');
                }
            },
            columns: [{
                    data: 'invoice_number',
                    name: 'invoice_number'
                },
                {
                    data: 'faktur_no',
                    name: 'faktur_no'
                },
                {
                    data: 'po_no',
                    name: 'po_no'
                },
                {
                    data: 'sap_doc',
                    name: 'sap_doc'
                },
                {
                    data: 'supplier_name',
                    name: 'supplier.name'
                },
                {
                    data: 'type_name',
                    name: 'type.type_name'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'amount',
                    name: 'amount',
                    render: function(data) {
                        return 'IDR ' + parseFloat(data).toLocaleString();
                    }
                },
                {
                    data: 'creator_name',
                    name: 'creator.name'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                search: 'Search:',
                lengthMenu: '_MENU_ records per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ records',
                infoEmpty: 'No records available',
                infoFiltered: '(filtered from _MAX_ total records)',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: '<i class="fas fa-chevron-right"></i>',
                    previous: '<i class="fas fa-chevron-left"></i>'
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            autoWidth: false
        });

        // Filter buttons
        $('#apply-filters-with').click(function() {
            withSapTable.ajax.reload();
        });

        $('#clear-filters-with').click(function() {
            $('#filter-invoice-number-with, #filter-faktur-no-with, #filter-po-no-with, #filter-sap-doc, #filter-type-with, #filter-status-with, #filter-supplier-with, #filter-invoice-project-with')
                .val('');
            $('#show-all-with').prop('checked', false);
            withSapTable.ajax.reload();
        });
    });
</script>
