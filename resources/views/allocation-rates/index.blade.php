@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">按分率設定</h1>
        <p class="text-sm text-gray-500">一方を変更すると、他方が自動で100%になるよう調整されます</p>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 text-sm font-bold text-gray-600">勘定科目</th>
                    @foreach($entities as $entity)
                    <th class="text-center px-4 py-3 text-sm font-bold text-gray-600 w-32">
                        {{ $entity->name }}
                    </th>
                    @endforeach
                    <th class="text-center px-4 py-3 text-sm font-bold text-gray-600 w-20">合計</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr class="border-t hover:bg-gray-50" data-category-id="{{ $category->id }}">
                    <td class="px-4 py-2 text-sm">{{ $category->name }}</td>
                    @foreach($entities as $entity)
                    @php
                        $rate = $rates[$category->id][$entity->id]->rate ?? 0;
                    @endphp
                    <td class="px-4 py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <input type="number" 
                                class="rate-input w-16 border rounded px-2 py-1 text-sm text-right"
                                data-category-id="{{ $category->id }}"
                                data-entity-id="{{ $entity->id }}"
                                value="{{ $rate }}"
                                min="0" max="100" step="1">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </td>
                    @endforeach
                    <td class="px-4 py-2 text-center">
                        <span class="total-rate text-sm font-mono" data-category-id="{{ $category->id }}">
                            @php
                                $total = 0;
                                foreach($entities as $e) {
                                    $total += $rates[$category->id][$e->id]->rate ?? 0;
                                }
                            @endphp
                            {{ $total }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded p-4 text-sm text-blue-800">
        <strong>使い方:</strong> 数値を入力してフォーカスを外すと自動保存されます。
        例えば「法人」を89%にすると、「個人事業」は自動的に11%になります。
    </div>
</div>

<script>
document.querySelectorAll('.rate-input').forEach(input => {
    input.addEventListener('change', async function() {
        const categoryId = this.dataset.categoryId;
        const entityId = this.dataset.entityId;
        const rate = parseFloat(this.value) || 0;

        // 0-100の範囲に制限
        if (rate < 0) this.value = 0;
        if (rate > 100) this.value = 100;

        try {
            const res = await fetch('/allocation-rates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    account_category_id: categoryId,
                    entity_id: entityId,
                    rate: this.value,
                }),
            });
            const data = await res.json();
            
            if (data.success) {
                // 他の事業体の入力欄を更新
                const row = document.querySelector(`tr[data-category-id="${categoryId}"]`);
                Object.entries(data.rates).forEach(([entId, r]) => {
                    const inp = row.querySelector(`input[data-entity-id="${entId}"]`);
                    if (inp) inp.value = r;
                });

                // 合計を更新
                let total = 0;
                row.querySelectorAll('.rate-input').forEach(inp => {
                    total += parseFloat(inp.value) || 0;
                });
                row.querySelector('.total-rate').textContent = total + '%';

                // 成功表示
                this.classList.add('bg-green-100');
                setTimeout(() => this.classList.remove('bg-green-100'), 500);
            }
        } catch (e) {
            alert('保存に失敗しました');
        }
    });
});
</script>
@endsection
