{{-- resources/views/network-hosts/index.blade.php --}}
@extends('layout.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Network Hosts</h5>
        <div id="bulkCheckProgressWrap" class="my-3 d-none">
            <div class="progress" style="height:22px;">
                <div id="bulkCheckProgress"
                    class="progress-bar progress-bar-striped progress-bar-animated"
                    role="progressbar" style="width:0%;" aria-valuenow="0"
                    aria-valuemin="0" aria-valuemax="100">
                    <span id="bulkCheckProgressText" class="ms-2">0%</span>
                    <p class="d-block mt-1" id="bulkCheckCountText">0 / 0</p>
                </div>
            </div>
            </div>
        <div>
            @if(Auth::user()->isAdmin() || Auth::user()->isMisMember())
            <button class="btn btn-primary btn-sm" id="btnAddHost">Add Host</button>
            <button class="btn btn-outline-primary btn-sm" id="btnCheckAll">Check All</button>
            <button class="btn btn-outline-success btn-sm" id="btnAddHostCategory" data-categories-index-url="{{ route('network-hosts.categories.index') }}" data-categories-store-url="{{ route('network-hosts.categories.store') }}" data-categories-update-url="{{ route('network-hosts.categories.update', ['hostCategory' => ':id']) }}" data-categories-destroy-url="{{ route('network-hosts.categories.destroy', ['hostCategory' => ':id']) }}">Add Host Category</button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered align-middle" id="hostsTable">
            <thead>
                <tr>
                    <th>IP</th>
                    <th>Server Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Last Check</th>
                    <th>Added By</th>
                    @if(Auth::user()->isAdmin() || Auth::user()->isMisMember())
                    <th width="160">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @foreach($hosts as $h)
                <tr data-id="{{ $h->id }}" data-update-url="{{ route('network-hosts.update', $h) }}" data-check-url="{{ route('network-hosts.check', ['networkHost' => $h->id]) }}" data-delete-url="{{ route('network-hosts.destroy', ['network_host' => $h->id]) }}" data-cat-id="{{ $h->host_category_id ?? '' }}">
                    <td>{{ $h->ip_address }}</td>
                    <td>{{ $h->server_name }}</td>
                    <td>{{ $h->description }}</td>
                    <td class="cat-id-{{$h->hostCategory->id ?? ''}}">{{ $h->hostCategory->name ?? '' }}</td>
                    <td class="status">
                        <span class="badge {{ $h->status === 'online' ? 'bg-success' : 'bg-secondary' }}">
                            {{ strtoupper($h->status) }}
                        </span>
                    </td>
                    <td class="last_check">{{ optional($h->last_check)->toDateTimeString() }}</td>
                    <td>{{ optional($h->addedBy)->full_name }}</td>
                    @if(Auth::user()->isAdmin() || Auth::user()->isMisMember())
                    <td>
                        <button class="btn btn-sm btn-outline-primary btnCheck">Check Now</button>
                        <button class="btn btn-sm btn-outline-warning btnEdit">Edit</button>
                        <button class="btn btn-sm btn-outline-danger btnDelete">Delete</button>
                    </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>

        {{ $hosts->links() }}
    </div>
</div>

{{-- Add Host Modal (simple) --}}
<div class="modal fade" id="addHostModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="addHostForm" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title">Add Host</h5></div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">IP Address</label>
          <input type="text" name="ip_address" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Server Name</label>
          <input type="text" name="server_name" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Category</label>
            <select name="host_category_id" class="form-select">
                <option value="">-- none --</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Close</button>
        <button type="submit" class="btn btn-primary">Save Host</button>
      </div>
    </form>
  </div>
</div>

{{-- update Host Modal (simple) --}}
<div class="modal fade" id="updatetHostModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="updateHostForm" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title">Update Host</h5></div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">IP Address</label>
          <input type="text" name="ip_address" class="form-control" disabled="" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Server Name</label>
          <input type="text" name="server_name" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Category</label>
            <select name="host_category_id" class="form-select">
                <option value="">-- none --</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Close</button>
        <button type="submit" class="btn btn-primary">Update Host</button>
      </div>
    </form>
  </div>
</div>

{{-- Check Summay Modal (simple) --}}
<div class="modal fade" id="checkSummaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bulk Check Summary</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <h6>Online <span class="badge bg-success" id="sum_online_count">0</span></h6>
            <ul class="list-group" id="sum_online_list"></ul>
          </div>
          <div class="col-md-4">
            <h6>Offline <span class="badge bg-secondary" id="sum_offline_count">0</span></h6>
            <ul class="list-group" id="sum_offline_list"></ul>
          </div>
          <div class="col-md-4">
            <h6>Errors <span class="badge bg-danger" id="sum_error_count">0</span></h6>
            <ul class="list-group" id="sum_error_list"></ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Delete Host Modal --}}
<div class="modal fade" id="deleteHostModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="deleteHostForm" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Delete Host</h5>
      </div>
      <div class="modal-body">
        <p class="mb-2">
          You are about to delete: <strong id="del_ip_label">—</strong>
        </p>
        <div class="mb-2">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="password" class="form-control" autocomplete="current-password" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

{{-- add new category --}}
<div class="modal fade" id="addHostCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addHostCategoryForm" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Host Categories</h5>
      </div>

      <div class="modal-body">
        {{-- One row: input + buttons --}}
        <div class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label mb-1">Category Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-success flex-fill" id="btnSaveCategory">Save Category</button>
            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Close</button>
          </div>
        </div>

        <hr>

        {{-- Table list (loaded via jQuery on open) --}}
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" id="hostCategoryTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Added By</th>
                <th width="180">Created</th>
                <th width="100"></th>
              </tr>
            </thead>
            <tbody id="categoryTableBody">
              <tr><td colspan="4" class="text-muted">Click “Add Host Category” to load list…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@section('js-custom')
<script>
    const ROUTE_STORE     = @json(route('network-hosts.store'));
    const ROUTE_CHECK_ALL = @json(route('network-hosts.check-all'));
    const ROUTE_CHECK     = (id) => @json(route('network-hosts.check', ['networkHost' => 'ID'])) .replace('ID', id);
</script>
<script src="{{ asset('assets/js/custom/network.js') }}"></script>
<script>
    $(document).ready(function () {
        $('.bx-arrow-back').click();

        setInterval(function () {
            if (document.hidden) return;
            if (window.__checkingAll) return;
            $('#checkSummaryModal').modal('hide');
            $('#btnCheckAll').trigger('click');
        }, 300000);

    });

</script>
@endsection
