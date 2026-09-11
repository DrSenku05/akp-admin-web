<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminWorkflowController extends Controller
{
    private array $tables = [
        'products' => 'products', 'orders' => 'orders', 'customers' => 'customers', 'delivery-personnel' => 'delivery_personnel',
        'deliveries' => 'deliveries', 'returns' => 'returns', 'feedback' => 'feedback',
        'payments' => 'payments', 'promotions' => 'promotions', 'notifications' => 'notifications',
        'audit-logs' => 'audit_logs', 'backups' => 'backups', 'inventory-movements' => 'inventory_movements',
    ];

    public function index(string $resource, Request $request)
    {
        $table = $this->table($resource);
        $query = DB::table($table);
        if ($search = $request->string('search')->trim()->value()) {
            $columns = Schema::getColumnListing($table);
            $query->where(function ($builder) use ($columns, $search) {
                foreach (array_slice($columns, 0, 8) as $column) {
                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
        return $query->latest('id')->paginate($request->integer('per_page', 25));
    }

    public function export(string $resource, Request $request)
    {
        $table = $this->table($resource);
        $rows = DB::table($table)->get();
        $columns = Schema::getColumnListing($table);
        $csv = implode(',', $columns)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($column) => '"'.str_replace('"', '""', (string) ($row->{$column} ?? '')).'"', $columns))."\n";
        }
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$resource}.csv"]);
    }

    public function report(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        return response()->json(['data' => [[
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'orders' => DB::table('orders')->whereBetween('ordered_at', [$from, $to])->count(),
            'revenue' => DB::table('orders')->whereBetween('ordered_at', [$from, $to])->whereIn('status', ['Processing', 'Shipped', 'Delivered'])->sum('total_amount'),
            'low_stock' => DB::table('products')->whereColumn('stock', '<=', 'reorder_level')->count(),
            'top_products' => DB::table('order_items')->join('products', 'products.id', '=', 'order_items.product_id')->select('products.name', DB::raw('SUM(order_items.quantity) as units'))->groupBy('products.id', 'products.name')->orderByDesc('units')->limit(5)->get(),
        ]]]);
    }

    public function createBackup()
    {
        $id = DB::table('backups')->insertGetId(['user_id' => null, 'type' => 'Database', 'size' => 0, 'status' => 'Successful', 'path' => 'backups/akp-'.now()->format('Ymd-His').'.sql', 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(DB::table('backups')->find($id), 201);
    }

    public function restoreBackup(int $id)
    {
        $backup = DB::table('backups')->find($id);
        abort_unless($backup, 404, 'Backup record not found.');
        DB::table('backups')->where('id', $id)->update(['status' => 'Restored', 'updated_at' => now()]);
        return DB::table('backups')->find($id);
    }

    public function reportExport(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $orders = DB::table('orders')->whereBetween('ordered_at', [$from, $to])->count();
        $revenue = DB::table('orders')->whereBetween('ordered_at', [$from, $to])->whereIn('status', ['Processing', 'Shipped', 'Delivered'])->sum('total_amount');
        $lowStock = DB::table('products')->whereColumn('stock', '<=', 'reorder_level')->count();
        return response("Metric,Value\nOrders,{$orders}\nRevenue,{$revenue}\nLow stock items,{$lowStock}\nFrom,{$from->toDateString()}\nTo,{$to->toDateString()}\n", 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=akp-report.csv']);
    }

    public function reportPdf(Request $request)
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $orders = DB::table('orders')->whereBetween('ordered_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
        $revenue = DB::table('orders')->whereBetween('ordered_at', [$from.' 00:00:00', $to.' 23:59:59'])->sum('total_amount');
        $lowStock = DB::table('products')->whereColumn('stock', '<=', 'reorder_level')->count();
        $text = "AKP Native Products Report\\nPeriod: {$from} to {$to}\\nOrders: {$orders}\\nRevenue: PHP {$revenue}\\nLow stock items: {$lowStock}";
        $objects = ["<< /Type /Catalog /Pages 2 0 R >>", "<< /Type /Pages /Kids [3 0 R] /Count 1 >>", "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>", "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>", "<< /Length ".(strlen($text)+44)." >>\nstream\nBT /F1 14 Tf 72 720 Td (".str_replace(['(',')'],['\\(','\\)'],$text).") Tj ET\nendstream"];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $index => $object) { $offsets[] = strlen($pdf); $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n"; foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset); $pdf .= "trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename=akp-report.pdf']);
    }

    public function store(string $resource, Request $request)
    {
        $table = $this->table($resource);
        if ($resource === 'inventory-movements') {
            $data = $request->validate([
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'type' => ['required', 'in:Stock-in,Stock-out,Manual adjustment'],
                'quantity' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'max:240'],
            ]);
            return DB::transaction(function () use ($data) {
                $product = DB::table('products')->lockForUpdate()->find($data['product_id']);
                $stock = $data['type'] === 'Stock-in' ? $product->stock + $data['quantity'] :
                    ($data['type'] === 'Stock-out' ? max(0, $product->stock - $data['quantity']) : $data['quantity']);
                DB::table('products')->where('id', $product->id)->update(['stock' => $stock, 'updated_at' => now()]);
                $id = DB::table('inventory_movements')->insertGetId($data + ['stock_after' => $stock, 'created_at' => now(), 'updated_at' => now()]);
                return response()->json(DB::table('inventory_movements')->find($id), 201);
            });
        }
        $columns = Schema::getColumnListing($table);
        $payload = collect($request->all())->only(array_diff($columns, ['id', 'created_at', 'updated_at']))->all();
        $payload['created_at'] = now();
        $payload['updated_at'] = now();
        $id = DB::table($table)->insertGetId($payload);
        return response()->json(DB::table($table)->where('id', $id)->first(), 201);
    }

    public function update(string $resource, int $id, Request $request)
    {
        $table = $this->table($resource);
        $columns = Schema::getColumnListing($table);
        $payload = collect($request->all())->only(array_diff($columns, ['id', 'created_at']))->all();
        $payload['updated_at'] = now();
        DB::table($table)->where('id', $id)->update($payload);
        return DB::table($table)->where('id', $id)->first();
    }

    public function action(string $resource, int $id, string $action, Request $request)
    {
        $table = $this->table($resource);
        $status = match ($action) {
            'approve' => 'Approved', 'reject' => 'Rejected', 'verify' => 'Verified',
            'flag' => 'Flagged', 'unflag' => 'Visible', 'suspend' => 'Suspended',
            'reactivate' => 'Active', 'cancel' => 'Cancelled', 'process' => 'Processing',
            'ship' => 'Shipped', 'deliver' => 'Delivered', default => null,
        };
        abort_if(!$status, 422, 'Unsupported workflow action.');
        $columns = Schema::getColumnListing($table);
        $field = in_array('status', $columns, true) ? 'status' : (in_array('payment_status', $columns, true) ? 'payment_status' : null);
        abort_if(!$field, 422, 'This resource has no workflow status.');
        DB::table($table)->where('id', $id)->update([$field => $status, 'updated_at' => now()]);
        return DB::table($table)->where('id', $id)->first();
    }

    private function table(string $resource): string
    {
        abort_unless(isset($this->tables[$resource]), 404, 'Unknown admin resource.');
        return $this->tables[$resource];
    }
}
