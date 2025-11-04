<x-app-layout>
    <x-slot name="title">Daftar Draft Custom Bouquet</x-slot>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-[#f2527d] mb-6">Daftar Draft Custom Bouquet</h1>
        @if($drafts->isEmpty())
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                Belum ada draft custom bouquet.
            </div>
        @else
            <form action="{{ route('custom.bouquet.bulkDelete') }}" method="POST" id="bulkDeleteForm">
                @csrf
                @method('DELETE')
                <div class="mb-3 flex justify-between items-center">
                    <button type="submit" onclick="return confirm('Hapus semua draft yang dipilih?')"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition disabled:opacity-50"
                        id="bulkDeleteBtn" disabled>Hapus Terpilih</button>
                    <span class="text-sm text-gray-500">Pilih draft yang ingin dihapus secara massal.</span>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-2 py-2"><input type="checkbox" id="selectAllDrafts"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Harga</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Terakhir Diubah
                                </th>
                                <th class="px-4 py-2"></th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($drafts as $draft)
                                <tr>
                                    <td class="px-2 py-2"><input type="checkbox" name="draft_ids[]" value="{{ $draft->id }}"
                                            class="draft-checkbox"></td>
                                    <td class="px-4 py-2">#{{ $draft->id }}</td>
                                    <td class="px-4 py-2">{{ $draft->name ?? 'Draft Custom Bouquet' }}</td>
                                    <td class="px-4 py-2">Rp {{ number_format($draft->total_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ $draft->updated_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('custom.bouquet.create', ['draft_id' => $draft->id]) }}"
                                            class="bg-[#f2527d] text-white px-3 py-1 rounded hover:bg-[#d13c6b] transition">Lanjutkan</a>
                                    </td>
                                    <td class="px-4 py-2">
                                        <form action="{{ route('custom.bouquet.deleteDraft', ['draft_id' => $draft->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft ini secara permanen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
            <script>
                // Enable/disable bulk delete button
                const checkboxes = document.querySelectorAll('.draft-checkbox');
                const selectAll = document.getElementById('selectAllDrafts');
                const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
                function updateBulkDeleteBtn() {
                    const anyChecked = Array.from(document.querySelectorAll('.draft-checkbox')).some(cb => cb.checked);
                    bulkDeleteBtn.disabled = !anyChecked;
                }
                document.addEventListener('DOMContentLoaded', function () {
                    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkDeleteBtn));
                    selectAll.addEventListener('change', function () {
                        checkboxes.forEach(cb => cb.checked = selectAll.checked);
                        updateBulkDeleteBtn();
                    });
                });
            </script>
        @endif
    </div>
</x-app-layout>