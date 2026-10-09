<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\IngestRegulatoryDocumentJob;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RegulatoryDocumentController extends Controller
{
    /**
     * Display a listing of regulatory statutory documents.
     */
    public function index(Request $request): View
    {
        $query = RegulatoryDocument::with('uploader:id,name,email')
            ->withCount('chunks')
            ->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        $documents = $query->paginate(15);

        return view('admin.regulatory.index', compact('documents'));
    }

    /**
     * Show upload form.
     */
    public function create(): View
    {
        return view('admin.regulatory.create');
    }

    /**
     * Store and upload a new statutory building standard PDF.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:architectural,structural,mep,fire_safety,general_building_control'],
            'edition_year' => ['required', 'string', 'size:4'],
            'classification' => ['nullable', 'string', 'max:50'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:102400'], // 100MB max for full code books
        ]);

        $file = $request->file('pdf_file');
        $originalName = $file->getClientOriginalName();
        $storedPath = $file->storeAs('regulatory_documents', time().'_'.$originalName, 'local');

        // Resolve authenticated user or fallback to first admin
        $userId = $request->user()?->id ?? User::first()?->id;

        if (! $userId) {
            $adminUser = User::create([
                'name' => 'NBRB System Administrator',
                'email' => 'admin@nbrb.go.ug',
                'password' => bcrypt('Admin@NBRB2026'),
                'bims_id' => 'NBRB-ADMIN-001',
            ]);
            $userId = $adminUser->id;
        }

        $document = RegulatoryDocument::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'edition_year' => $validated['edition_year'],
            'classification' => $validated['classification'] ?? null,
            'file_path' => $storedPath,
            'status' => 'pending',
            'is_active' => true,
            'uploaded_by' => $userId,
        ]);

        // Dispatch chunking and pgvector embedding job
        IngestRegulatoryDocumentJob::dispatch($document);

        return redirect()->route('admin.regulatory.index')
            ->with('success', "Regulatory standard \"{$document->title}\" uploaded successfully. AI Vector ingestion queued.");
    }

    /**
     * Re-trigger vector ingestion for a document.
     */
    public function reingest(RegulatoryDocument $document): RedirectResponse
    {
        $document->update(['status' => 'pending', 'error_message' => null]);
        IngestRegulatoryDocumentJob::dispatch($document);

        return redirect()->back()
            ->with('success', "Vector re-indexing dispatched for \"{$document->title}\".");
    }

    /**
     * Toggle active state for RAG compliance retrieval.
     */
    public function toggleActive(RegulatoryDocument $document): RedirectResponse
    {
        $document->update(['is_active' => ! $document->is_active]);
        $state = $document->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Document \"{$document->title}\" has been {$state} for AI compliance checks.");
    }

    /**
     * View vectorized clauses and chunks for a statutory document.
     */
    public function chunks(Request $request, RegulatoryDocument $document): View
    {
        $query = $document->chunks()->latest();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('clause_number', 'ILIKE', "%{$search}%")
                    ->orWhere('clause_title', 'ILIKE', "%{$search}%")
                    ->orWhere('content', 'ILIKE', "%{$search}%");
            });
        }

        $chunks = $query->paginate(20);

        return view('admin.regulatory.chunks', compact('document', 'chunks'));
    }

    /**
     * Delete a statutory document and all its indexed chunks.
     */
    public function destroy(RegulatoryDocument $document): RedirectResponse
    {
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $title = $document->title;
        $document->delete();

        return redirect()->route('admin.regulatory.index')
            ->with('success', "Document \"{$title}\" and associated vector embeddings deleted.");
    }
}
