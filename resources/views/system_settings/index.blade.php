@extends('layouts.user_type.auth')

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
        <h5>Giriş-Çıkış Saatleri & Versiyon Ayarları</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSettingModal">
          + Ekle
        </button>
      </div>
      <div class="card-body">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tip</th>
              <th>Giriş-Çıkış Saatleri</th>
              <th>Versiyon Link/Desc/Status</th>
              <th>Düzenle</th>
              <th>Sil</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $row)
              <tr>
                <td>{{ $row->id }}</td>
                <td>
                  @if($row->setting_type=='entry_exit')
                    <span class="badge bg-info">Giriş-Çıkış</span>
                  @else
                    <span class="badge bg-success">Yeni Versiyon</span>
                  @endif
                </td>
                <td>
                  @if($row->setting_type=='entry_exit')
                 <p style="color:blue">Giriş Saati Başlangıç : {{ $row->morning_start_time ?? '-' }}  - Giriş Saati Bitiş:     {{ $row->morning_end_time ?? '-' }}  </p>
                 <p style="color:black">Çıkış Saati Başlangıç : {{ $row->evening_start_time ?? '-' }}    - Çıkış Saati Başlangıç: {{ $row->evening_end_time ?? '-' }} </p>
                  @else
                    <small class="text-muted">Bu kayıt "new_version" tipi</small>
                  @endif
                </td>
                <td>
                  @if($row->setting_type=='new_version')
                    Link: {{ $row->version_link ?? '-' }} <br>
                    Açıklama: {{ $row->version_desc ?? '-' }} <br>
                    Status: {{ $row->version_status ?? '-' }}
                  @else
                    <small class="text-muted">Bu kayıt "entry_exit" tipi</small>
                  @endif
                </td>
                <td>
                  <button class="btn btn-sm btn-info"
                          data-bs-toggle="modal"
                          data-bs-target="#editSettingModal-{{ $row->id }}">
                    Düzenle
                  </button>
                </td>
                <td>
                  <button class="btn btn-sm btn-danger"
                          data-bs-toggle="modal"
                          data-bs-target="#deleteSettingModal-{{ $row->id }}">
                    Sil
                  </button>
                </td>
              </tr>

              <!-- EDIT MODAL -->
              <div class="modal fade" id="editSettingModal-{{ $row->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('system-settings.update',$row->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Kayıt Düzenle #{{ $row->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        
                        <!-- setting_type SELECT -->
                        <div class="mb-3">
                          <label class="form-label">Tip Seç</label>
                          <select name="setting_type"
                                  class="form-select"
                                  id="editSettingType-{{ $row->id }}"
                                  onchange="editSettingToggle('{{ $row->id }}')"
                                  required>
                            <option value="entry_exit"
                              @if($row->setting_type=='entry_exit') selected @endif>Giriş-Çıkış</option>
                            <option value="new_version"
                              @if($row->setting_type=='new_version') selected @endif>Yeni Versiyon</option>
                          </select>
                        </div>

                        <!-- entry_exit alanları -->
                        <div class="mb-3 edit-entry-exit-{{ $row->id }}">
                          <label class="form-label">Giriş Saati</label>
                          <input type="time" name="morning_start_time"
                                 class="form-control"
                                 value="{{ $row->morning_start_time }}">
                        </div>
                        <div class="mb-3 edit-entry-exit-{{ $row->id }}">
                          <label class="form-label">Giriş Saati</label>
                          <input type="time" name="morning_end_time"
                                 class="form-control"
                                 value="{{ $row->morning_end_time }}">
                        </div>
                        <div class="mb-3 edit-entry-exit-{{ $row->id }}">
                          <label class="form-label">Çıkış Saati</label>
                          <input type="time" name="evening_start_time"
                                 class="form-control"
                                 value="{{ $row->evening_start_time }}">
                        </div>
                        <div class="mb-3 edit-entry-exit-{{ $row->id }}">
                          <label class="form-label">Çıkış Saati</label>
                          <input type="time" name="evening_end_time"
                                 class="form-control"
                                 value="{{ $row->evening_end_time }}">
                        </div>
                        <!-- new_version alanları -->
                        <div class="mb-3 edit-new-version-{{ $row->id }}">
                          <label class="form-label">Versiyon Link</label>
                          <input type="text" name="version_link"
                                 class="form-control"
                                 value="{{ $row->version_link }}">
                        </div>
                        <div class="mb-3 edit-new-version-{{ $row->id }}">
                          <label class="form-label">Versiyon Açıklama</label>
                          <textarea name="version_desc"
                                    class="form-control"
                                    rows="2">{{ $row->version_desc }}</textarea>
                        </div>
                        <div class="mb-3 edit-new-version-{{ $row->id }}">
                          <label class="form-label">Durum</label>
                          <select name="version_status" class="form-select">
                            <option value="send"
                              @if($row->version_status=='send') selected @endif>Gönder</option>
                            <option value="wait"
                              @if($row->version_status=='wait') selected @endif>Beklet</option>
                          </select>
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
              <div class="modal fade" id="deleteSettingModal-{{ $row->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <form action="{{ route('system-settings.destroy',$row->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Silme Onayı (ID:{{ $row->id }})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>
                          Bu kaydı silmek istiyor musunuz?
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
<div class="modal fade" id="createSettingModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('system-settings.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Kayıt Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          
          <!-- setting_type -->
          <div class="mb-3">
            <label class="form-label">Tip Seç</label>
            <select name="setting_type"
                    class="form-select"
                    id="createSettingType"
                    onchange="createSettingToggle()"
                    required>
              <option value="entry_exit">Giriş-Çıkış</option>
              <option value="new_version">Yeni Versiyon</option>
            </select>
          </div>

          <!-- entry_exit alanları -->
          <div class="mb-3 create-entry-exit">
            <label class="form-label">Giriş Saati Başlangıç</label>
            <input type="time" name="morning_start_time" class="form-control">
          </div>
          <div class="mb-3 create-entry-exit">
            <label class="form-label">Giriş Saati Bitiş</label>
            <input type="time" name="morning_end_time" class="form-control">
          </div>
          <div class="mb-3 create-entry-exit">
            <label class="form-label">Çıkış Saati Başlangıç</label>
            <input type="time" name="evening_start_time" class="form-control">
          </div>
          <div class="mb-3 create-entry-exit">
            <label class="form-label">Çıkış Saati Bitiş</label>
            <input type="time" name="evening_end_time" class="form-control">
          </div>

          <!-- new_version alanları -->
          <div class="mb-3 create-new-version">
            <label class="form-label">Versiyon Link</label>
            <input type="text" name="version_link" class="form-control">
          </div>
          <div class="mb-3 create-new-version">
            <label class="form-label">Versiyon Açıklama</label>
            <textarea name="version_desc" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3 create-new-version">
            <label class="form-label">Durum</label>
            <select name="version_status" class="form-select">
              <option value="send">Gönder</option>
              <option value="wait">Beklet</option>
            </select>
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
<script>
// CREATE modal'da seçim
function createSettingToggle() {
  const typeVal = document.getElementById('createSettingType').value;
  const entryEls = document.querySelectorAll('.create-entry-exit');
  const verEls   = document.querySelectorAll('.create-new-version');

  if(typeVal === 'entry_exit') {
    entryEls.forEach(el => el.style.display = 'block');
    verEls.forEach(el   => el.style.display = 'none');
  } else {
    // new_version
    entryEls.forEach(el => el.style.display = 'none');
    verEls.forEach(el   => el.style.display = 'block');
  }
}

// EDIT modal'da seçim
function editSettingToggle(id) {
  const selectEl  = document.getElementById('editSettingType-'+id);
  const typeVal   = selectEl.value;
  const entryEls  = document.querySelectorAll('.edit-entry-exit-'+id);
  const verEls    = document.querySelectorAll('.edit-new-version-'+id);

  if(typeVal === 'entry_exit') {
    entryEls.forEach(el => el.style.display = 'block');
    verEls.forEach(el   => el.style.display = 'none');
  } else {
    entryEls.forEach(el => el.style.display = 'none');
    verEls.forEach(el   => el.style.display = 'block');
  }
}

// sayfa yüklenince createSettingToggle() varsayılan ayar
document.addEventListener('DOMContentLoaded', function() {
  createSettingToggle();
  // edit modallar => her modal açıldığında manuel tetiklenebilir 
  // (bootstrap show event) vs. ama basitçe "modal shown" eventinde de çağırabilirsiniz
});
</script>
@endpush
