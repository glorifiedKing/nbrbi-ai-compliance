<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessQuickAuditJob;
use App\Models\QuickAudit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuickAuditController extends Controller
{
    /**
     * Display the Quick Compliance Audit console and recent audits.
     */
    public function index(Request $request): View
    {
        $query = QuickAudit::with('user:id,name,email')->latest();

        if ($request->filled('discipline')) {
            $query->where('discipline', $request->query('discipline'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $audits = $query->paginate(15);

        return view('admin.quick_audit.index', compact('audits'));
    }

    /**
     * Process quick compliance analysis form upload.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'drawing_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'discipline' => ['required', 'string', 'in:architectural,structural,mechanical,electrical'],
            'occupancy_class' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $request->file('drawing_file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileHash = hash_file('sha256', $file->getRealPath());

        $userId = $request->user()?->id ?? User::first()?->id;

        $storedPath = $file->storeAs(
            'quick_audits',
            'quick_'.time().'_'.$originalName,
            'local'
        );

        $quickAudit = QuickAudit::create([
            'user_id' => $userId,
            'file_name' => $storedPath,
            'original_name' => $originalName,
            'file_size_bytes' => $fileSize,
            'file_hash' => $fileHash,
            'discipline' => strtolower($validated['discipline']),
            'occupancy_class' => $validated['occupancy_class'] ?? 'Class A (Residential)',
            'status' => 'pending',
        ]);

        // Dispatch background analysis job
        ProcessQuickAuditJob::dispatch($quickAudit);

        return redirect()->route('admin.quick-audit.show', $quickAudit)
            ->with('success', "Quick compliance check initiated for \"{$originalName}\". Analyzing against Uganda Building Regulations...");
    }

    /**
     * View detailed report of a single quick audit.
     */
    public function show(QuickAudit $quickAudit): View
    {
        $quickAudit->load('user:id,name,email');

        return view('admin.quick_audit.show', compact('quickAudit'));
    }

    /**
     * Delete a quick audit record.
     */
    public function destroy(QuickAudit $quickAudit): RedirectResponse
    {
        if (Storage::disk('local')->exists($quickAudit->file_name)) {
            Storage::disk('local')->delete($quickAudit->file_name);
        }

        $quickAudit->delete();

        return redirect()->route('admin.quick-audit.index')
            ->with('success', 'Quick audit record deleted successfully.');
    }
}
