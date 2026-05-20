<?php


namespace App\Http\Controllers;


use App\Http\Requests\StoreNetworkHostRequest;
use App\Http\Requests\UpdateNetworkHostRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Jobs\CheckNetworkHostJob;
use Illuminate\Http\Request;

use App\Models\NetworkHost;
use App\Models\HostCategory;

class NetworkHostController extends Controller
{
    public function index()
    {
        $hosts = NetworkHost::with('addedBy')->orderBy('ip_address')->latest()->paginate(20);
        $categories = HostCategory::orderBy('name')->get(['id','name']);

        return view('network-hosts.index', compact('hosts', 'categories'));
    }

    public function store(StoreNetworkHostRequest $request)
    {
        $host = NetworkHost::create([
            ...$request->validated(),
            'status'     => 'offline',
            'last_check' => null,
            'added_by'   => Auth::user()->id,
        ]);

        $host->load(['hostCategory:id,name', 'addedBy:id,full_name']);

        return response()->json(['success' => true, 'host' => $host, 'category_name'  => $host->hostCategory?->name, 'added_by' => Auth::user()->full_name]);
    }

    public function update(UpdateNetworkHostRequest $request, NetworkHost $networkHost)
    {
        $networkHost->update($request->validated());

        // ActivityLog::create([
        //     'action'       => 'network_host.update',
        //     'description'  => "Updated {$networkHost->ip_address} ({$networkHost->server_name})",
        //     'user_id'      => auth()->id(),
        //     'subject_type' => NetworkHost::class,
        //     'subject_id'   => $networkHost->id,
        //     'meta'         => $request->validated(),
        // ]);

        return response()->json(['success' => true]);
    }

    /** Manual “Check Now” (AJAX safe) */
    public function check(Request $request, NetworkHost $networkHost)
    {

        $strategy = $request->string('strategy', 'auto')->toString();
        dispatch_sync(new CheckNetworkHostJob($networkHost->id, $strategy, Auth::user()->id));

        // Refetch fresh values for the response
        $networkHost->refresh();
        return response()->json([
            'status'     => $networkHost->status,
            'last_check' => optional($networkHost->last_check)->toDateTimeString(),
        ]);
    }

    /** Bulk check now */
    public function checkAll(Request $request)
    {
        $strategy = $request->string('strategy', 'auto')->toString();
        // fire-and-forget or sync for small lists:
        NetworkHost::query()->pluck('id')->each(function ($id) use ($strategy) {
            dispatch(new CheckNetworkHostJob($id, $strategy, auth()->id()));
        });
        return response()->json(['queued' => true]);
    }

    public function destroy(Request $request, NetworkHost $networkHost)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user   = $request->user();
        $plain  = (string) $request->input('password');

        // ✅ SHA-256 check (timing-safe). Assumes users.password stores a SHA-256 hex string.
        $computed = hash('sha256', $plain);
        $stored   = (string) $user->password;

        // normalize case in case you store uppercase hex
        $valid = hash_equals(strtolower($stored), strtolower($computed));

        if (! $valid) {
            return response()->json([
                'message' => 'Credentials Error: Please input your correct password',
            ], 422);
        }

        $shadow = clone $networkHost;
        $networkHost->delete();

        return response()->json([
            'success' => 'Host deleted successfully.',
            'id'      => $shadow->id,
        ]);
    }

    public function listCategories(Request $request)
    {
        $cats = HostCategory::with('addedBy:id,full_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'added_by'   => $c->addedBy?->full_name ?? '—',
                'created_at' => optional($c->created_at)->toDateTimeString(),
            ]);

        return response()->json(['categories' => $cats]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:host_categories,name'],
        ]);

        $category = HostCategory::create([
            'name'     => $data['name'],
            'added_by' => Auth::user()->id,
        ]);

        return response()->json([
            'success'  => 'Host category added.',
            'category' => [
                'id'         => $category->id,
                'name'       => $category->name,
                'added_by'   => auth()->user()?->full_name ?? '—',
                'created_at' => optional($category->created_at)->toDateTimeString(),
            ],
        ]);
    }


    public function updateCategory(Request $request, HostCategory $hostCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:host_categories,name,' . $hostCategory->id],
        ]);

        $old = $hostCategory->name;
        $hostCategory->update(['name' => $data['name']]);

        return response()->json([
            'success'  => 'Category updated.',
            'category' => ['id' => $hostCategory->id, 'name' => $hostCategory->name],
        ]);
    }

    public function destroyCategory(Request $request, HostCategory $hostCategory)
    {
        $id   = $hostCategory->id;
        $name = $hostCategory->name;

        // FK on network_hosts.host_category_id is nullOnDelete() in your migration
        $hostCategory->delete();

        return response()->json([
            'success' => 'Category deleted.',
            'id'      => $id,
        ]);
    }

}
