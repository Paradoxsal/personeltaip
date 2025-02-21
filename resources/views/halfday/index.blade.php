@extends('layouts.user_type.auth')

@section('content')
    <div class="container-fluid py-4">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                        <h6 style="font-size: 1.1rem; font-weight: 600; margin:0;">İzin Listesi</h6>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                            <i class="fas fa-plus"></i> Yeni İzin Oluştur
                        </button>
                    </div>

                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Kullanıcı
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Tarih
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Tür
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Reason
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Dosya
                                        </th>
                                        {{-- Yeni: end_date --}}
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Bitiş Tarihi
                                        </th>
                                        <th class="text-secondary opacity-7"></th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $req)
                                        <tr>
                                            <td class="px-3">
                                                <p class="text-xs font-weight-bold mb-0">{{ $req->id }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ optional($req->user)->name }}
                                                </p>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    {{ $req->date }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-gradient-info text-capitalize">
                                                    {{ $req->type }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-xs font-weight-bold text-capitalize">
                                                    {{ $req->reason ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if ($req->rapor_file)
                                                    <a href="{{ asset('images/' . $req->rapor_file) }}" target="_blank"
                                                        class="btn btn-sm btn-info">
                                                        Dosyayı Gör
                                                    </a>
                                                @else
                                                    <small class="text-muted">Yok</small>
                                                @endif
                                            </td>
                                            {{-- end_date --}}
                                            <td class="text-center">
                                                <span class="text-xs font-weight-bold">
                                                    {{ $req->end_date ?? '-' }}
                                                </span>
                                            </td>

                                            {{-- DÜZENLE --}}
                                            <td class="align-middle">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                    data-bs-target="#editRequestModal-{{ $req->id }}">
                                                    Düzenle
                                                </button>
                                            </td>

                                            {{-- SİL --}}
                                            <td class="align-middle">
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteRequestModal-{{ $req->id }}">
                                                    Sil
                                                </button>
                                            </td>
                                        </tr>

                                        {{-- EDIT MODAL --}}
                                        <div class="modal fade" id="editRequestModal-{{ $req->id }}" tabindex="-1"
                                            aria-labelledby="editRequestModalLabel-{{ $req->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('halfday.update', $req->id) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editRequestModalLabel-{{ $req->id }}">
                                                                İzin Düzenle #{{ $req->id }}
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <!-- Tarih -->
                                                            <div class="mb-3">
                                                                <label class="form-label">Tarih</label>
                                                                <input type="date" name="date" class="form-control"
                                                                    value="{{ $req->date }}" required>
                                                            </div>

                                                            <!-- Tür -->
                                                            <div class="mb-3">
                                                                <label class="form-label">Tür</label>
                                                                <select name="type" class="form-select"
                                                                    onchange="editTypeChange({{ $req->id }})"
                                                                    id="editType-{{ $req->id }}">
                                                                    <option value="morning" @if ($req->type == 'morning') selected @endif>Sabah</option>
                                                                    <option value="afternoon" @if ($req->type == 'afternoon') selected @endif>Öğleden Sonra</option>
                                                                    <option value="full_day" @if ($req->type == 'full_day') selected @endif>Tam Gün</option>
                                                                    <option value="rapor" @if ($req->type == 'rapor') selected @endif>Rapor</option>
                                                                </select>
                                                            </div>

                                                            <!-- reason -->
                                                            <div class="mb-3" id="editReasonDiv-{{ $req->id }}">
                                                                <label class="form-label">Reason</label>
                                                                <input type="text" name="reason" class="form-control"
                                                                    value="{{ $req->reason }}">
                                                            </div>

                                                            <!-- Gün Sayısı -->
                                                            <div class="mb-3" id="editDaysCountDiv-{{ $req->id }}">
                                                                <label class="form-label">Gün Sayısı</label>
                                                                <input type="number" name="days_count"
                                                                    class="form-control"
                                                                    value="{{ $req->days_count ?? 0 }}">
                                                            </div>

                                                            <!-- rapor_file -->
                                                            <div class="mb-3" id="editRaporFileDiv-{{ $req->id }}">
                                                                <label class="form-label">Rapor Dosyası (resim)</label>
                                                                <input type="file" name="rapor_file" class="form-control">
                                                                @if ($req->rapor_file)
                                                                    <small>Mevcut:
                                                                        <a href="{{ asset('images/' . $req->rapor_file) }}" target="_blank">
                                                                            Görüntüle
                                                                        </a>
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                                Kapat
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">
                                                                Kaydet
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- DELETE MODAL --}}
                                        <div class="modal fade" id="deleteRequestModal-{{ $req->id }}"
                                            tabindex="-1" aria-labelledby="deleteRequestModalLabel-{{ $req->id }}"
                                            aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('halfday.destroy', $req->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                Silme Onayı (ID:{{ $req->id }})
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>{{ $req->id }} numaralı kaydı silmek istiyor musunuz?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">
                                                                İptal
                                                            </button>
                                                            <button type="submit" class="btn btn-danger">
                                                                Sil
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                Henüz bir izin kaydı bulunmuyor.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div> <!-- card mb-4 -->
            </div> <!-- col-12 -->
        </div> <!-- row -->
    </div> <!-- container-fluid -->

    {{-- CREATE MODAL --}}
    <div class="modal fade" id="createRequestModal" tabindex="-1" aria-labelledby="createRequestModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('halfday.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="createRequestModalLabel">Yeni İzin Oluştur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Kullanıcı Seç -->
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Kullanıcı Seç</label>
                            <select name="user_id" class="form-select" required>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">
                                        {{ $u->name }} (ID: {{ $u->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tarih -->
                        <div class="mb-3">
                            <label class="form-label">İzin Tarihi</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>

                        <!-- Tür -->
                        <div class="mb-3">
                            <label class="form-label">Tür</label>
                            <select name="type" id="createTypeSelect" class="form-select"
                                onchange="createTypeChange()" required>
                                <option value="morning">Sabah</option>
                                <option value="afternoon">Öğleden Sonra</option>
                                <option value="full_day">Tam Gün</option>
                                <option value="rapor">Rapor</option>
                            </select>
                        </div>

                        <!-- reason -->
                        <div class="mb-3" id="createReasonDiv">
                            <label for="reason" class="form-label">Neden</label>
                            <select name="reason" class="form-select">
                                <option value="">Seçiniz</option>
                                <option value="hasta">Hasta</option>
                                <option value="izinli">İzinli</option>
                                <option value="raporlu">Raporlu</option>
                            </select>
                        </div>

                        <!-- Gün Sayısı -->
                        <div class="mb-3" id="daysCountDiv">
                            <label for="days_count" class="form-label">Kaç Gün?</label>
                            <input type="number" name="days_count" class="form-control" value="0">
                        </div>

                        <!-- Rapor Dosyası -->
                        <div class="mb-3" id="raporFileDiv">
                            <label for="rapor_file" class="form-label">Rapor Dosyası (jpg/png vb.)</label>
                            <input type="file" name="rapor_file" class="form-control">
                            <small class="text-muted">Sadece “rapor” seçildiğinde geçerli.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Kaydet
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // CREATE modaldaki sabah/öğleden sonra/tam gün/rapor handle
        function createTypeChange() {
            const izinType = document.getElementById('createTypeSelect').value;
            const reasonDiv = document.getElementById('createReasonDiv');
            const daysCountDiv = document.getElementById('daysCountDiv');
            const raporFileDiv = document.getElementById('raporFileDiv');

            // Hepsini gizle
            reasonDiv.style.display = 'none';
            daysCountDiv.style.display = 'none';
            raporFileDiv.style.display = 'none';

            if (izinType === 'morning' || izinType === 'afternoon') {
                // Yarım gün => reason, days_count, rapor_file kapalı
            } else if (izinType === 'full_day') {
                // Tam gün => reason + days_count
                reasonDiv.style.display = 'block';
                daysCountDiv.style.display = 'block';
            } else if (izinType === 'rapor') {
                // rapor => reason + days_count + rapor_file
                reasonDiv.style.display = 'block';
                daysCountDiv.style.display = 'block';
                raporFileDiv.style.display = 'block';
            }
        }

        // EDIT modaldaki sabah/öğleden sonra/tam gün/rapor handle
        function editTypeChange(id) {
            const select = document.getElementById(`editType-${id}`);
            const val = select.value;

            const reasonDiv = document.getElementById(`editReasonDiv-${id}`);
            const fileDiv   = document.getElementById(`editRaporFileDiv-${id}`);
            const daysDiv   = document.getElementById(`editDaysCountDiv-${id}`);

            // Hepsini kapat
            reasonDiv.style.display = 'none';
            fileDiv.style.display = 'none';
            daysDiv.style.display = 'none';

            if (val === 'full_day') {
                reasonDiv.style.display = 'block';
                daysDiv.style.display = 'block';
            } else if (val === 'rapor') {
                reasonDiv.style.display = 'block';
                daysDiv.style.display = 'block';
                fileDiv.style.display = 'block';
            }
            // morning/afternoon => gizli
        }

        // sayfa ilk yüklenince createTypeChange() tetikle
        document.addEventListener('DOMContentLoaded', function() {
            createTypeChange();
        });
    </script>
@endpush
