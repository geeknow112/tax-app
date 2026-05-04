<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * 現在の事業体IDを取得
     */
    private function currentEntityId(): ?int
    {
        return session('current_entity_id');
    }

    public function index(Request $request)
    {
        $entityId = $this->currentEntityId();
        $currentYear = $request->input('year', date('Y'));
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');

        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );

        $query = Document::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->with('items')
            ->orderBy('issue_date', 'desc');

        if ($type !== 'all') {
            $query->where('type', $type);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $documents = $query->paginate(30);
        $years = FiscalYear::where('entity_id', $entityId)
            ->orderBy('year', 'desc')->pluck('year');

        return view('documents.index', compact(
            'documents', 'years', 'currentYear', 'type', 'status'
        ));
    }

    public function create(Request $request)
    {
        $type = $request->input('type', 'invoice');
        $year = $request->input('year', date('Y'));
        $documentNumber = Document::generateNumber($type, $year);

        return view('documents.create', compact('type', 'year', 'documentNumber'));
    }

    public function store(Request $request)
    {
        $entityId = $this->currentEntityId();
        
        $request->validate([
            'type' => 'required|in:estimate,order,invoice,delivery',
            'document_number' => 'required|unique:documents',
            'issue_date' => 'required|date',
            'client_name' => 'required|string|max:255',
            'year' => 'required|integer',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $request->year, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );

        $document = Document::create([
            'entity_id' => $entityId,
            'fiscal_year_id' => $fiscalYear->id,
            'type' => $request->type,
            'document_number' => $request->document_number,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'client_name' => $request->client_name,
            'client_address' => $request->client_address,
            'subject' => $request->subject,
            'notes' => $request->notes,
            'status' => 'draft',
        ]);

        return redirect()->route('documents.edit', $document)
            ->with('success', '書類を作成しました。明細を追加してください。');
    }

    public function edit(Document $document)
    {
        $document->load('items');
        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'client_name' => 'required|string|max:255',
        ]);

        $document->update($request->only([
            'issue_date', 'due_date', 'client_name', 'client_address', 'subject', 'notes', 'status'
        ]));

        return redirect()->route('documents.edit', $document)
            ->with('success', '更新しました');
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return redirect()->route('documents.index')
            ->with('success', '削除しました');
    }

    // 明細追加
    public function addItem(Request $request, Document $document)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|integer|min:0',
        ]);

        $maxOrder = $document->items()->max('sort_order') ?? 0;

        $document->items()->create([
            'description' => $request->description,
            'quantity' => $request->quantity,
            'unit' => $request->unit ?? '式',
            'unit_price' => $request->unit_price,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['success' => true, 'document' => $document->fresh(['items'])]);
    }

    // 明細削除
    public function removeItem(Document $document, DocumentItem $item)
    {
        $item->delete();
        return response()->json(['success' => true, 'document' => $document->fresh(['items'])]);
    }

    // ステータス更新
    public function updateStatus(Request $request, Document $document)
    {
        $request->validate(['status' => 'required|in:draft,sent,paid,cancelled']);
        $document->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }
}
