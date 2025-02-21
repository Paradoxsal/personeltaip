@extends('layouts.user_type.auth')

@push('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<style>
  /* Hover rengi ve z-index */
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

    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
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
        <h5>Kullanıcı Saatleri</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createHourModal">
          Yeni Ekle
        </button>
      </div>
      <div class="card-body">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Kullanıcı</th>
              <th>Giriş Saati Başlangıç</th>
              <th>Giriş Saati Bitiş</th>
              <th>Çıkış Saati Başlangıç</th>
              <th>Çıkış Saati Çıkış</th>
              <th>Düzenle</th>
              <th>Sil</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $row)
              <tr>
                <td>{{ $row->id }}</td>
                <td>{{ optional($row->user)->name }}</td>
                <td>{{ $row->morning_start_time ?? '-' }}</td>
                <td>{{ $row->morning_end_time ?? '-' }}</td>
                <td>{{ $row->evening_start_time ?? '-' }}</td>
                <td>{{ $row->evening_end_time ?? '-' }}</td>
                <td>
                  <!-- DÜZENLE BUTON -->
                  <button class="btn btn-sm btn-info"
                          data-bs-toggle="modal"
                          data-bs-target="#editHourModal-{{ $row->id }}">
                    Düzenle
                  </button>
                </td>
                <td>
                  <!-- SİL BUTON -->
                  <button class="btn btn-sm btn-danger"
                          data-bs-toggle="modal"
                          data-bs-target="#deleteHourModal-{{ $row->id }}">
                    Sil
                  </button>
                </td>
              </tr>

              <!-- EDIT MODAL -->
              <div class="modal fade" id="editHourModal-{{ $row->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('user-hours.update',$row->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Kullanıcı Saati Düzenle #{{ $row->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <!-- user_id -->
                        <div class="mb-3">
                          <label class="form-label">Kullanıcı Seç</label>
                          <select name="user_id"
                                  class="userSelectSearch"
                                  required>
                            <option value="">Seçiniz...</option>
                            @foreach($users as $u)
                              <option value="{{ $u->id }}"
                                @if($u->id == $row->user_id) selected @endif>
                                {{ $u->name }} (ID: {{ $u->id }})
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <!-- start_time -->
                        <div class="mb-3">
                          <label class="form-label">Giriş Saati</label>
                          - <small class="text-muted">Örn: 06:30</small>
                          <input type="time"
                                 name="morning_start_time"
                                 class="form-control"
                                 value="{{ $row->morning_start_time }}">                          
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Giriş Saati</label>
                           - <small class="text-muted">Örn: 12:00</small>
                          <input type="time"
                                 name="morning_end_time"
                                 class="form-control"
                                 value="{{ $row->morning_end_time }}">
                        </div>
                        <!-- end_time -->
                        <div class="mb-3">
                         <label class="form-label">Çıkış Saati</label>
                          - <small class="text-muted">Örn: 12:00</small>
                          <input type="time"
                                 name="evening_start_time"
                                 class="form-control"
                                 value="{{ $row->evening_start_time }}">
                         
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Çıkış Saati</label>
                          - <small class="text-muted">Örn: 17:00</small>
                          <input type="time"
                                 name="evening_end_time"
                                 class="form-control"
                                 value="{{ $row->evening_end_time }}">
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
              <div class="modal fade" id="deleteHourModal-{{ $row->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('user-hours.destroy',$row->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Silme Onayı (ID:{{ $row->id }})</h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>
                          {{ optional($row->user)->name }} adlı kullanıcının saat kaydını silmek istiyor musunuz?
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
                <td colspan="6" class="text-center text-muted">
                  Henüz kayıt yok.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
</div>

<!-- CREATE MODAL -->
<div class="modal fade" id="createHourModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('user-hours.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Saat Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- user_id -->
          <div class="mb-3">
            <label class="form-label">Kullanıcı Seç</label>
            <select name="user_id"
                    class="userSelectSearch"
                    required>
              <option value="">Seçiniz...</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">
                  {{ $u->name }} (ID: {{ $u->id }})
                </option>
              @endforeach
            </select>
          </div>
          <!-- start_time -->
          <div class="mb-3">
            <label class="form-label">Giriş Saati Başlangıç</label>
           - <small class="text-muted">Örn: 07:30</small>
            <input type="time" name="morning_start_time" class="form-control">
            <label class="form-label">Giriş Saati Bitiş</label> - 
          - <small class="text-muted">Örn: 12:00</small>
            <input type="time" name="morning_end_time" class="form-control">            
          </div>
          <!-- end_time -->
          <div class="mb-3">
            <label class="form-label">Çıkış Saati Başlangıç</label>
           - <small class="text-muted">Örn: 12:00</small>
            <input type="time" name="evening_start_time" class="form-control">
            <label class="form-label">Çıkış Saati Bitiş</label>
           - <small class="text-muted">Örn: 17:00</small>
            <input type="time" name="evening_end_time" class="form-control">
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

  // CREATE modal => #createHourModal
  $('#createHourModal .userSelectSearch').select2({
    placeholder: "Kullanıcı arayın...",
    allowClear: true,
    width: '100%',
    dropdownParent: $('#createHourModal')
  });

  // EDIT modal => #editHourModal-...
  $('.modal[id^="editHourModal"]').each(function() {
    let modalId = $(this).attr('id');
    $(this).find('.userSelectSearch').select2({
      placeholder: "Kullanıcı arayın...",
      allowClear: true,
      width: '100%',
      dropdownParent: $('#' + modalId)
    });
  });

});
</script>
@endpush
