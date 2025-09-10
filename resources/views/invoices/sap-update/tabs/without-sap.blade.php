<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoices Without SAP Document Number</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-invoice-number">Invoice Number</label>
                    <input type="text" class="form-control" id="filter-invoice-number" placeholder="Invoice Number">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-faktur-no">Faktur No</label>
                    <input type="text" class="form-control" id="filter-faktur-no" placeholder="Faktur No">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-po-no">PO Number</label>
                    <input type="text" class="form-control" id="filter-po-no" placeholder="PO Number">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-type">Type</label>
                    <select class="form-control" id="filter-type">
                        <option value="">All Types</option>
                        @foreach ($invoiceTypes as $type)
                            <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-status">Status</label>
                    <select class="form-control" id="filter-status">
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
            <div class="col-md-2">
                <div class="form-group">
                    <label for="filter-supplier">Supplier</label>
                    <input type="text" class="form-control" id="filter-supplier" placeholder="Supplier">
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filter-invoice-project">Invoice Project</label>
                    <input type="text" class="form-control" id="filter-invoice-project"
                        placeholder="Invoice Project">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mt-4">
                    <button type="button" class="btn btn-primary" id="apply-filters-without">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary ml-2" id="clear-filters-without">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
            <div class="col-md-6 text-right">
                @can('see-all-record-switch')
                    <div class="form-group mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show-all-without">
                            <label class="form-check-label" for="show-all-without">
                                Show All Records
                            </label>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
        <!-- DataTable -->
        <table id="without-sap-table" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Faktur No</th>
                    <th>PO Number</th>
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
    var withoutSapTable;

    $(function() {
        // Initialize DataTable
        withoutSapTable = $('#without-sap-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('invoices.sap-update.without-sap') }}',
                data: function(d) {
                    d.search_invoice_number = $('#filter-invoice-number').val();
                    d.search_faktur_no = $('#filter-faktur-no').val();
                    d.search_po_no = $('#filter-po-no').val();
                    d.search_type = $('#filter-type').val();
                    d.search_status = $('#filter-status').val();
                    d.search_supplier = $('#filter-supplier').val();
                    d.search_invoice_project = $('#filter-invoice-project').val();
                    d.show_all = $('#show-all-without').is(':checked');
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
        $('#apply-filters-without').click(function() {
            withoutSapTable.ajax.reload();
        });

        $('#clear-filters-without').click(function() {
            $('#filter-invoice-number, #filter-faktur-no, #filter-po-no, #filter-type, #filter-status, #filter-supplier, #filter-invoice-project')
                .val('');
            $('#show-all-without').prop('checked', false);
            withoutSapTable.ajax.reload();
        });
    });
</script>
