@extends('layouts.user_type.auth')

@section('content')
<div class="container-fluid py-4">

    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger">
        @foreach ($errors->all() as $err)
          <div>{{ $err }}</div>
        @endforeach
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <h6 style="font-size: 1.1rem; font-weight: 600; margin:0;">Tatil Listesi</h6>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createHolidayModal">
          <i class="fas fa-plus"></i> Yeni Tatil Ekle
        </button>
      </div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tatil Adı</th>
                <th>Açıklama</th>
                <th>Başlangıç</th>
                <th>Bitiş</th>
                <th>Kalan / Toplam Gün</th>
                <th>Durum</th>
                <th></th> {{-- Düzenle --}}
                <th></th> {{-- Sil --}}
              </tr>
            </thead>
            <tbody>
              @forelse($holidays as $h)
                <tr>
                  <td>{{ $h->id }}</td>
                  <td>{{ $h->holiday_name }}</td>
                  <td>{{ $h->description ?? '-' }}</td>
                  <td>{{ $h->start_date }}</td>
                  <td>{{ $h->end_date }}</td>

                  {{-- Hesaplanan gün --}}
                  <td>
                    @if($h->calcDays > 0)
                      <span class="badge bg-gradient-info">
                        {{ $h->calcDays }} gün ({{ $h->calcLabel }})
                      </span>
                    @else
                      <span class="badge bg-gradient-secondary">
                        {{ $h->calcLabel }}
                      </span>
                    @endif
                  </td>

                  <td>
                    @if($h->status=='active')
                      <span class="badge bg-gradient-success">Aktif</span>
                    @else
                      <span class="badge bg-gradient-warning">Bekliyor</span>
                    @endif
                  </td>
                  <td>
                    <!-- Düzenle -->
                    <button class="btn btn-sm btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editHolidayModal-{{ $h->id }}">
                      Düzenle
                    </button>
                  </td>
                  <td>
                    <!-- Sil -->
                    <button class="btn btn-sm btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteHolidayModal-{{ $h->id }}">
                      Sil
                    </button>
                  </td>
                </tr>

                <!-- Düzenle Modal -->
                <div class="modal fade" id="editHolidayModal-{{ $h->id }}"
                     tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <form action="{{ route('holidays.update', $h->id) }}"
                          method="POST">
                      @csrf
                      @method('PUT')
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">
                            Tatil Düzenle #{{ $h->id }}
                          </h5>
                          <button type="button" class="btn-close"
                                  data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label">Tatil Adı</label>
                            <input type="text" name="holiday_name"
                                   class="form-control"
                                   value="{{ $h->holiday_name }}" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control"
                                      rows="2">{{ $h->description }}</textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="start_date"
                                   class="form-control"
                                   value="{{ $h->start_date }}" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" name="end_date"
                                   class="form-control"
                                   value="{{ $h->end_date }}" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select" required>
                              <option value="active" 
                                @if($h->status=='active') selected @endif>Aktif</option>
                              <option value="waiting" 
                                @if($h->status=='waiting') selected @endif>Bekliyor</option>
                            </select>
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

                <!-- Sil Modal -->
                <div class="modal fade" id="deleteHolidayModal-{{ $h->id }}"
                     tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <form action="{{ route('holidays.destroy', $h->id) }}"
                          method="POST">
                      @csrf
                      @method('DELETE')
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">
                            Silme Onayı (ID: {{ $h->id }})
                          </h5>
                          <button type="button" class="btn-close"
                                  data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <p>
                            {{ $h->holiday_name }} tatilini silmek istediğinize emin misiniz?
                          </p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary"
                                  data-bs-dismiss="modal">İptal</button>
                          <button type="submit" class="btn btn-danger">Sil</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted">
                    Henüz tatil kaydı yok.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
</div>

<!-- CREATE (YENI TATIL) MODAL -->
<div class="modal fade" id="createHolidayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('holidays.store') }}" method="POST">
      @csrf
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Yeni Tatil Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tatil Adı</label>
            <input type="text" name="holiday_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Başlangıç Tarihi</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bitiş Tarihi</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Durum</label>
            <select name="status" class="form-select" required>
              <option value="active">Aktif</option>
              <option value="waiting">Bekliyor</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
                  data-bs-dismiss="modal">Kapat</button>
          <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>

      </div>
    </form>
  </div>
</div>
@endsection
