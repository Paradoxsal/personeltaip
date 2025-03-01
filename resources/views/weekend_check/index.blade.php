@extends('layouts.user_type.auth')

@push('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<style>
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

  {{-- Haftasonu Kontrol Ayarı Formu --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5>Haftasonu Kontrol Ayarı</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('weekned-settings.store') }}" method="POST">
        @csrf
        <div class="mb-3">
          <label class="form-label">Uygulama Türü</label>
          <select name="apply_for" id="apply_for" class="form-control" required>
            <option value="all">Tüm Kullanıcılar</option>
            <option value="specific">Belirli Kullanıcı</option>
          </select>
        </div>
        <div class="mb-3" id="user_selection" style="display: none;">
          <label class="form-label">Kullanıcı Seç</label>
          <select name="user_id" class="form-control userSelectSearch">
            <option value="">Seçiniz...</option>
            @foreach($users as $userItem)
              <option value="{{ $userItem->id }}">{{ $userItem->name }} (ID: {{ $userItem->id }})</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Haftasonu Durumu</label>
          <select name="weekend_active" class="form-control" required>
            <option value="1">Aktif</option>
            <option value="0">Pasif</option>
          </select>
        </div>
        {{-- Yeni kayıt oluştururken week_start_date alanı verilmemişse controller içinde mevcut haftanın başlangıcı otomatik belirlenecek --}}
        <button type="submit" class="btn btn-primary">Ayarı Kaydet</button>
      </form>
    </div>
  </div>

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

  {{-- Mevcut Haftasonu Kontrol Kayıtları --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5>Mevcut Haftasonu Kontrol Kayıtları</h5>
    </div>
    <div class="card-body">
      @if($weekendControls->isEmpty())
        <p>Henüz kayıt bulunmuyor.</p>
      @else
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Uygulama Türü</th>
              <th>Kullanıcı</th>
              <th>Haftanın Başlangıç Tarihi</th>
              <th>Haftasonu Durumu</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            @foreach($weekendControls as $control)
              <tr>
                <td>{{ $control->id }}</td>
                <td>
                  @if($control->all_users)
                    Tüm Kullanıcılar
                  @else
                    Belirli Kullanıcı
                  @endif
                </td>
                <td>
                  @if(!$control->all_users)
                    {{ optional($control->user)->name }} (ID: {{ $control->user_id }})
                  @else
                    -
                  @endif
                </td>
                <td>{{ $control->week_start_date }}</td>
                <td>
                  @if($control->weekend_active)
                    Aktif
                  @else
                    Pasif
                  @endif
                </td>
                <td>
                  <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editControlModal-{{ $control->id }}">Düzenle</button>
                  <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteControlModal-{{ $control->id }}">Sil</button>
                </td>
              </tr>

              {{-- Edit Modal --}}
              <div class="modal fade" id="editControlModal-{{ $control->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('weekned-settings.update', $control->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Haftasonu Kontrol Düzenle (ID: {{ $control->id }})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Uygulama Türü</label>
                          <select name="apply_for" id="edit_apply_for_{{ $control->id }}" class="form-control" required>
                            <option value="all" {{ $control->all_users ? 'selected' : '' }}>Tüm Kullanıcılar</option>
                            <option value="specific" {{ !$control->all_users ? 'selected' : '' }}>Belirli Kullanıcı</option>
                          </select>
                        </div>
                        <div class="mb-3" id="edit_user_selection_{{ $control->id }}" style="display: {{ $control->all_users ? 'none' : 'block' }};">
                          <label class="form-label">Kullanıcı Seç</label>
                          <select name="user_id" class="form-control userSelectSearch">
                            <option value="">Seçiniz...</option>
                            @foreach($users as $userItem)
                              <option value="{{ $userItem->id }}" {{ (!$control->all_users && $control->user_id == $userItem->id) ? 'selected' : '' }}>
                                {{ $userItem->name }} (ID: {{ $userItem->id }})
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Haftasonu Durumu</label>
                          <select name="weekend_active" class="form-control" required>
                            <option value="1" {{ $control->weekend_active ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !$control->weekend_active ? 'selected' : '' }}>Pasif</option>
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Haftanın Başlangıç Tarihi</label>
                          <input type="date" name="week_start_date" class="form-control" value="{{ $control->week_start_date }}" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              {{-- Delete Modal --}}
              <div class="modal fade" id="deleteControlModal-{{ $control->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('weekned-settings.destroy', $control->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Silme Onayı (ID: {{ $control->id }})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>Bu kaydı silmek istediğinize emin misiniz?</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">Sil</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

            @endforeach
          </tbody>
        </table>
      @endif
    </div>
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
  // Genel select2 uygulaması
  $('.userSelectSearch').select2({
    placeholder: "Kullanıcı arayın...",
    allowClear: true,
    width: '100%'
  });

  // Uygulama Türü seçimine göre (Create form) kullanıcı seçimi alanını göster/gizle
  $('#apply_for').on('change', function(){
    if($(this).val() === 'specific'){
      $('#user_selection').show();
    } else {
      $('#user_selection').hide();
    }
  }).trigger('change');

  // Edit modalleri için: Uygulama Türü seçimine göre kullanıcı seçimi alanını göster/gizle
  @foreach($weekendControls as $control)
    $('#edit_apply_for_{{ $control->id }}').on('change', function(){
      if($(this).val() === 'specific'){
        $('#edit_user_selection_{{ $control->id }}').show();
      } else {
        $('#edit_user_selection_{{ $control->id }}').hide();
      }
    }).trigger('change');
  @endforeach
});
</script>
@endpush
