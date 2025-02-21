@extends('layouts.user_type.auth') 
{{-- Kendi layout’unuzun ismini kullanın --}}

@push('css')
<!-- 1) Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" 
      rel="stylesheet" />
<style>
  /* 2) Hover (fare üzerine gelince) rengi ve z-index ayarları */
  .select2-container .select2-results__option--highlighted {
    background-color: #5897fb !important;
    color: #fff !important;
  }
  .select2-container {
    z-index: 2000 !important; 
  }
</style>
@endpush

@section('content')
<div class="container py-4">

    <!-- Örnek bilgilendirme mesajları -->
    @if (session('status'))
      <div class="alert alert-success">
        {{ session('status') }}
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        @foreach($errors->all() as $err)
          <div>{{ $err }}</div>
        @endforeach
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Mesai Kayıtları</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShiftModal">
          Yeni Mesai Ekle
        </button>
      </div>
      <div class="card-body">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Kullanıcı</th>
              <th>Birim</th>
              <th>Mesai Tarihi</th>
              <th>Durum</th>
              <th>Reason</th>
              <th>Çıkış Saati</th>
              <th>Düzenle</th>
              <th>Sil</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
              <tr>
                <td>{{ $log->id }}</td>
                <td>{{ optional($log->user)->name }}</td>
                <td>{{ optional($log->user)->units_id ?? '-' }}</td>
                <td>{{ $log->shift_date }}</td>
                <td>
                  @if($log->is_on_shift)
                    <span class="badge bg-success">Mesaiye Kalacak</span>
                  @else
                    <span class="badge bg-warning">Kalmadı</span>
                  @endif
                </td>
                <td>{{ $log->no_shift_reason ?? '-' }}</td>
                <td>
                  @if($log->exit_time)
                    {{ $log->exit_time }}
                  @else
                    <small class="text-muted">Henüz çıkış yapılmadı</small>
                  @endif
                </td>
                <td>
                  <!-- Düzenle Butonu -->
                  <button class="btn btn-sm btn-info"
                          data-bs-toggle="modal"
                          data-bs-target="#editShiftModal-{{ $log->id }}">
                    Düzenle
                  </button>
                </td>
                <td>
                  <!-- Sil Butonu -->
                  <button class="btn btn-sm btn-danger"
                          data-bs-toggle="modal"
                          data-bs-target="#deleteShiftModal-{{ $log->id }}">
                    Sil
                  </button>
                </td>
              </tr>

              <!-- EDIT MODAL -->
              <div class="modal fade" id="editShiftModal-{{ $log->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('shift-logs.update',$log->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Mesai Düzenle #{{ $log->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <!-- Kullanıcı Seç (Select2) -->
                        <div class="mb-3">
                          <label class="form-label">Kullanıcı Seç</label>
                          <select name="user_id"
                                  class="userSelectSearch"
                                  required>
                            <option value="">Seçiniz...</option>
                            @foreach($users as $u)
                              <option value="{{ $u->id }}"
                                @if($u->id == $log->user_id) selected @endif>
                                {{ $u->name }} (ID:{{ $u->id }})
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <!-- Mesai Tarihi -->
                        <div class="mb-3">
                          <label class="form-label">Mesai Tarihi</label>
                          <input type="date" name="shift_date" class="form-control"
                                 value="{{ $log->shift_date }}" required>
                        </div>
                        <!-- is_on_shift -->
                        <div class="mb-3">
                          <label class="form-label">Mesai Durumu</label>
                          <select name="is_on_shift" class="form-control" required>
                            <option value="1"
                              @if($log->is_on_shift) selected @endif>
                              Mesaiye Kalacak
                            </option>
                            <option value="0"
                              @if(!$log->is_on_shift) selected @endif>
                              Kalmadı
                            </option>
                          </select>
                        </div>
                        <!-- no_shift_reason -->
                        <div class="mb-3">
                          <label class="form-label">Kalmama Nedeni</label>
                          <input type="text" name="no_shift_reason"
                                 class="form-control"
                                 value="{{ $log->no_shift_reason }}">
                        </div>
                        <!-- exit_time -->
                        <div class="mb-3">
                          <label class="form-label">Çıkış Saati</label>
                          <input type="datetime-local" name="exit_time"
                                 class="form-control"
                                 @if($log->exit_time)
                                   value="{{ \Carbon\Carbon::parse($log->exit_time)->format('Y-m-d\TH:i') }}"
                                 @endif>
                          <small class="text-muted">
                            Eğer kullanıcı henüz çıkmadıysa boş bırakabilirsiniz.
                          </small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                          Kapat
                        </button>
                        <button type="submit"
                                class="btn btn-primary">
                          Kaydet
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- DELETE MODAL -->
              <div class="modal fade" id="deleteShiftModal-{{ $log->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('shift-logs.destroy',$log->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Silme Onayı (ID:{{ $log->id }})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>
                          {{ optional($log->user)->name }} adlı kişinin mesai kaydını silmek istiyor musunuz?
                        </p>
                      </div>
                      <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                          İptal
                        </button>
                        <button type="submit"
                                class="btn btn-danger">
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
                  Henüz mesai kaydı yok.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
</div>

<!-- CREATE SHIFT MODAL -->
<div class="modal fade" id="createShiftModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('shift-logs.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Mesai Kaydı</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Kullanıcı Seç (Select2) -->
          <div class="mb-3">
            <label class="form-label">Kullanıcı Seç</label>
            <select name="user_id"
                    class="userSelectSearch"
                    required>
              <option value="">Seçiniz...</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">
                  {{ $u->name }} (ID:{{ $u->id }})
                </option>
              @endforeach
            </select>
          </div>
          <!-- Mesai Tarihi -->
          <div class="mb-3">
            <label class="form-label">Mesai Tarihi</label>
            <input type="date" name="shift_date"
                   class="form-control"
                   value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                   required>
          </div>
          <!-- is_on_shift -->
          <div class="mb-3">
            <label class="form-label">Mesai Durumu</label>
            <select name="is_on_shift" class="form-control" required>
              <option value="1">Mesaiye Kalacak</option>
              <option value="0">Kalmadı</option>
            </select>
          </div>
          <!-- no_shift_reason -->
          <div class="mb-3">
            <label class="form-label">Kalmama Nedeni</label>
            <input type="text" name="no_shift_reason"
                   class="form-control">
          </div>
          <!-- exit_time -->
          <div class="mb-3">
            <label class="form-label">Çıkış Saati</label>
            <input type="datetime-local" name="exit_time"
                   class="form-control">
            <small class="text-muted">Eğer çıkış yapılmadıysa boş bırakabilirsiniz.</small>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal">
            Kapat
          </button>
          <button type="submit"
                  class="btn btn-primary">
            Kaydet
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('js')
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

  // 1) CREATE modal => ID: #createShiftModal
  //    .userSelectSearch => select2
  $('#createShiftModal .userSelectSearch').select2({
    placeholder: "Kullanıcı arayın veya seçin",
    allowClear: true,
    width: '100%',
    dropdownParent: $('#createShiftModal')
  });

  // 2) EDIT modallar => ID: #editShiftModal-...
  $('.modal[id^="editShiftModal"]').each(function() {
    let modalId = $(this).attr('id'); 
    // modalId örn: "editShiftModal-5"
    $(this).find('.userSelectSearch').select2({
      placeholder: "Kullanıcı arayın veya seçin",
      allowClear: true,
      width: '100%',
      dropdownParent: $('#' + modalId)
    });
  });
});
</script>
@endpush
